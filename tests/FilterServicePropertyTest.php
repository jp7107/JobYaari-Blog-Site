<?php
namespace Tests;

use Eris\Generator;
use Eris\TestTrait;
use App\Services\FilterService;
use App\Models\BlogPost;

class FilterServicePropertyTest extends DatabaseTestCase {
    use TestTrait;

    private FilterService $filterService;

    protected function setUp(): void {
        $this->skipIfNoDatabase();
        parent::setUp();
        $this->filterService = new FilterService();
    }

    /**
     * Feature: blog-management-system, Property 6: SQL Injection Prevention
     * @test
     * @group property-based
     */
    public function testSearchSanitizesSpecialCharacters() {
        $this->forAll(
            Generator\string()
        )
        ->then(function($searchQuery) {
            // Append special characters to ensure they are present for testing
            $searchQuery .= "%_\\\'\"";
            $sanitized = $this->filterService->sanitizeSearch($searchQuery);
            
            // Verify special characters are escaped
            $this->assertStringNotContainsString('%', 
                str_replace('\%', '', $sanitized),
                'Percent should be escaped as \%');
            $this->assertStringNotContainsString('_', 
                str_replace('\_', '', $sanitized),
                'Underscore should be escaped as \_');
            $this->assertStringNotContainsString('\\', 
                str_replace('\\\\', '', str_replace('\%', '', str_replace('\_', '', $sanitized))),
                'Backslash should be escaped as \\\\');
        });
    }

    /**
     * Feature: blog-management-system, Property 7: Search Result Accuracy
     * @test
     * @group property-based
     */
    public function testSearchResultAccuracy() {
        // Create known posts
        $post1 = new BlogPost([
            'title' => 'Searchable Keyword Here',
            'content' => 'Standard blog body text.',
            'short_description' => 'Summary'
        ]);
        $post1->save();

        $post2 = new BlogPost([
            'title' => 'Another Article',
            'content' => 'Searchable Keyword in the body.',
            'short_description' => 'Summary'
        ]);
        $post2->save();

        $post3 = new BlogPost([
            'title' => 'Unrelated Blog',
            'content' => 'Completely normal text without the term.',
            'short_description' => 'Summary'
        ]);
        $post3->save();

        $this->forAll(
            Generator\elements('Searchable', 'keyword', 'SEARCHABLE', 'KEYWORD', 'Here', 'body')
        )
        ->then(function($term) {
            $results = $this->filterService->applyFilters([
                'search' => $term,
                'category' => 'All Categories',
                'date_type' => 'all'
            ]);

            $this->assertNotEmpty($results, "Search should return posts for term: {$term}");
            foreach ($results as $post) {
                $matchesTitle = stripos($post->title, $term) !== false;
                $matchesContent = stripos($post->content, $term) !== false;
                $this->assertTrue($matchesTitle || $matchesContent, "Post must contain search term case-insensitively");
            }
        });
    }

    /**
     * Feature: blog-management-system, Property 8: Category Filter Exact Matching
     * @test
     * @group property-based
     */
    public function testCategoryFilterExactMatching() {
        $categories = ['Admit Card', 'Result', 'Jobs', 'Answer Key'];
        
        // Setup database with posts across different categories
        foreach ($categories as $cat) {
            $post = new BlogPost([
                'title' => "Post for category {$cat}",
                'content' => 'Body content.',
                'short_description' => 'Excerpt',
                'category' => $cat
            ]);
            $post->save();
        }

        $this->forAll(
            Generator\elements('Admit Card', 'Result', 'Jobs', 'Answer Key')
        )
        ->then(function($selectedCategory) {
            $results = $this->filterService->applyFilters([
                'category' => $selectedCategory,
                'date_type' => 'all',
                'search' => ''
            ]);

            $this->assertNotEmpty($results);
            foreach ($results as $post) {
                $this->assertEquals($selectedCategory, $post->category, "Category must match exactly");
            }
        });
    }

    /**
     * Feature: blog-management-system, Property 9, 10, 11: Date Range Calculations
     * @test
     * @group property-based
     */
    public function testDateRangeCalculations() {
        $this->forAll(
            Generator\elements('today', 'this_week', 'this_month')
        )
        ->then(function($rangeType) {
            $range = $this->filterService->calculateDateRange($rangeType);
            
            $this->assertNotNull($range);
            $this->assertArrayHasKey('start', $range);
            $this->assertArrayHasKey('end', $range);
            
            $this->assertStringEndsWith('00:00:00', $range['start']);
            $this->assertStringEndsWith('23:59:59', $range['end']);
            
            $start = strtotime($range['start']);
            $end = strtotime($range['end']);
            
            $this->assertLessThanOrEqual($end, $start);
        });
    }

    /**
     * Feature: blog-management-system, Property 12: Date Range Filter Accuracy
     * @test
     * @group property-based
     */
    public function testDateRangeFilterAccuracy() {
        // Insert a post on a fixed date
        $fixedDate = '2025-06-01 12:00:00';
        $post = new BlogPost([
            'title' => 'Dated Post',
            'content' => 'Body text.',
            'short_description' => 'Excerpt',
            'created_date' => $fixedDate
        ]);
        $post->save();

        $this->forAll(
            Generator\elements('2025-05-01', '2025-05-31', '2025-06-01', '2025-06-02'),
            Generator\elements('2025-05-01', '2025-05-31', '2025-06-01', '2025-06-02')
        )
        ->when(function($start, $end) {
            // Only test valid date ranges
            return strtotime($start) <= strtotime($end);
        })
        ->then(function($start, $end) use ($fixedDate) {
            $results = $this->filterService->applyFilters([
                'category' => 'All Categories',
                'date_type' => 'custom',
                'start_date' => $start,
                'end_date' => $end,
                'search' => ''
            ]);

            $isInRange = (strtotime($fixedDate) >= strtotime($start . ' 00:00:00') &&
                          strtotime($fixedDate) <= strtotime($end . ' 23:59:59'));

            if ($isInRange) {
                $this->assertNotEmpty($results, "Post should be returned inside the custom date range");
            } else {
                $this->assertEmpty($results, "Post should NOT be returned outside the custom date range");
            }
        });
    }

    /**
     * Feature: blog-management-system, Property 15: Combined Filter AND Logic
     * @test
     * @group property-based
     */
    public function testCombinedFilterAndLogic() {
        // Seed some posts
        $post1 = new BlogPost([
            'title' => 'Jobs Exam Notice',
            'content' => 'Admit Card description.',
            'short_description' => 'Excerpt',
            'category' => 'Jobs',
            'created_date' => date('Y-m-d H:i:s') // Today
        ]);
        $post1->save();

        $post2 = new BlogPost([
            'title' => 'Result Notification',
            'content' => 'Result description.',
            'short_description' => 'Excerpt',
            'category' => 'Result',
            'created_date' => date('Y-m-d H:i:s') // Today
        ]);
        $post2->save();

        $post3 = new BlogPost([
            'title' => 'Jobs Old Notice',
            'content' => 'Old body text.',
            'short_description' => 'Excerpt',
            'category' => 'Jobs',
            'created_date' => '2024-01-01 10:00:00' // Past year
        ]);
        $post3->save();

        $this->forAll(
            Generator\elements('Jobs', 'Result', 'All Categories'),
            Generator\elements('today', 'all'),
            Generator\elements('Jobs', 'Notice', '')
        )
        ->then(function($category, $dateType, $search) use ($post1, $post2, $post3) {
            $results = $this->filterService->applyFilters([
                'category' => $category,
                'date_type' => $dateType,
                'search' => $search
            ]);

            foreach ($results as $post) {
                // If category filter is active, it must match
                if ($category !== 'All Categories') {
                    $this->assertEquals($category, $post->category);
                }
                
                // If date filter is active, it must match (today = 24h)
                if ($dateType === 'today') {
                    $this->assertGreaterThanOrEqual(strtotime(date('Y-m-d 00:00:00')), strtotime($post->created_date));
                    $this->assertLessThanOrEqual(strtotime(date('Y-m-d 23:59:59')), strtotime($post->created_date));
                }
                
                // If search is active, it must match
                if ($search !== '') {
                    $this->assertTrue(
                        stripos($post->title, $search) !== false || stripos($post->content, $search) !== false
                    );
                }
            }
        });
    }
}

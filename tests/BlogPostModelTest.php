<?php
namespace Tests;

use App\Models\BlogPost;
use App\Database;
use PDO;

class BlogPostModelTest extends DatabaseTestCase {
    
    protected function setUp(): void {
        $this->skipIfNoDatabase();
        parent::setUp();
    }

    public function testSaveInsert() {
        $post = new BlogPost([
            'title' => 'Test Title',
            'content' => 'Test Content',
            'short_description' => 'Test Short Description',
            'category' => 'Admit Card',
            'image_path' => 'uploads/test.png'
        ]);

        $this->assertTrue($post->save());
        $this->assertNotNull($post->id);
        $this->assertNotNull($post->created_date);

        // Fetch from DB to verify
        $fetched = BlogPost::findById($post->id);
        $this->assertNotNull($fetched);
        $this->assertEquals('Test Title', $fetched->title);
        $this->assertEquals('Test Content', $fetched->content);
        $this->assertEquals('Admit Card', $fetched->category);
        $this->assertEquals('uploads/test.png', $fetched->image_path);
    }

    public function testSaveUpdate() {
        $post = new BlogPost([
            'title' => 'Initial Title',
            'content' => 'Initial Content',
            'short_description' => 'Initial Short Excerpt',
            'category' => 'Result'
        ]);
        $post->save();

        $originalId = $post->id;
        $originalCreatedDate = $post->created_date;

        // Make modifications
        $post->title = 'Updated Title';
        $post->content = 'Updated Content';
        $post->category = 'Jobs';
        
        $this->assertTrue($post->save());

        // Refresh and check
        $fetched = BlogPost::findById($originalId);
        $this->assertEquals('Updated Title', $fetched->title);
        $this->assertEquals('Updated Content', $fetched->content);
        $this->assertEquals('Jobs', $fetched->category);

        // Verify immutability (Property 3)
        $this->assertEquals($originalId, $fetched->id);
        $this->assertEquals($originalCreatedDate, $fetched->created_date);
    }

    public function testFindAllWithPagination() {
        // Insert a few test posts
        for ($i = 1; $i <= 5; $i++) {
            $post = new BlogPost([
                'title' => "Post {$i}",
                'content' => 'Content',
                'short_description' => 'Excerpt',
                'created_date' => date('Y-m-d H:i:s', time() - ($i * 60))
            ]);
            $post->save();
        }

        $all = BlogPost::findAll();
        $this->assertGreaterThanOrEqual(5, count($all));

        // Paginated
        $page1 = BlogPost::findAll(2, 0);
        $this->assertCount(2, $page1);

        $page2 = BlogPost::findAll(2, 2);
        $this->assertCount(2, $page2);
        
        $this->assertNotEquals($page1[0]->id, $page2[0]->id);
    }

    public function testFindByIdWithValidAndInvalidIds() {
        $post = new BlogPost([
            'title' => 'Find Me',
            'content' => 'Content',
            'short_description' => 'Excerpt'
        ]);
        $post->save();

        // Valid
        $found = BlogPost::findById($post->id);
        $this->assertNotNull($found);
        $this->assertEquals('Find Me', $found->title);

        // Non-existent ID
        $notFound = BlogPost::findById(99999);
        $this->assertNull($notFound);

        // Malformed ID (<= 0)
        $notFoundMalformed = BlogPost::findById(-5);
        $this->assertNull($notFoundMalformed);
    }

    public function testDelete() {
        $post = new BlogPost([
            'title' => 'Delete Me',
            'content' => 'Content',
            'short_description' => 'Excerpt'
        ]);
        $post->save();
        $id = $post->id;

        $this->assertNotNull(BlogPost::findById($id));
        $this->assertTrue($post->delete());
        $this->assertNull(BlogPost::findById($id));
    }

    public function testFindByFilters() {
        // Create test posts with different attributes
        $post1 = new BlogPost([
            'title' => 'Result Announcement',
            'content' => 'The exam results are now available',
            'short_description' => 'Results published',
            'category' => 'Result',
            'created_date' => date('Y-m-d H:i:s')
        ]);
        $post1->save();

        $post2 = new BlogPost([
            'title' => 'Admit Card Download',
            'content' => 'Download your admit card now',
            'short_description' => 'Admit card ready',
            'category' => 'Admit Card',
            'created_date' => date('Y-m-d H:i:s')
        ]);
        $post2->save();

        // Test category filter
        $resultPosts = BlogPost::findByFilters(['category' => 'Result']);
        $this->assertNotEmpty($resultPosts);
        foreach ($resultPosts as $post) {
            $this->assertEquals('Result', $post->category);
        }

        // Test search filter
        $searchPosts = BlogPost::findByFilters(['search' => 'exam']);
        $this->assertNotEmpty($searchPosts);
        $found = false;
        foreach ($searchPosts as $post) {
            if (stripos($post->title, 'exam') !== false || stripos($post->content, 'exam') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Search should return posts containing search term');

        // Test all categories (should return all posts)
        $allPosts = BlogPost::findByFilters(['category' => 'All Categories']);
        $this->assertGreaterThanOrEqual(2, count($allPosts));
    }

    public function testToArray() {
        $post = new BlogPost([
            'title' => 'Array Test',
            'content' => 'Testing toArray method',
            'short_description' => 'Test excerpt',
            'category' => 'Test',
            'image_path' => 'uploads/test.jpg'
        ]);
        $post->save();

        $array = $post->toArray();
        
        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('content', $array);
        $this->assertArrayHasKey('short_description', $array);
        $this->assertArrayHasKey('category', $array);
        $this->assertArrayHasKey('image_path', $array);
        $this->assertArrayHasKey('created_date', $array);
        
        $this->assertEquals('Array Test', $array['title']);
        $this->assertEquals('Test', $array['category']);
    }
}

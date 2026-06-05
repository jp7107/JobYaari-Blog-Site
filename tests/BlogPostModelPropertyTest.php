<?php
namespace Tests;

use Eris\Generator;
use Eris\TestTrait;
use App\Models\BlogPost;

class BlogPostModelPropertyTest extends DatabaseTestCase {
    use TestTrait;

    protected function setUp(): void {
        $this->skipIfNoDatabase();
        parent::setUp();
    }

    /**
     * Feature: blog-management-system, Property 3: Update Preserves Immutable Fields
     * @test
     * @group property-based
     */
    public function testUpdatePreservesImmutableFields() {
        // Create an initial post that will be modified in the test iterations
        $post = new BlogPost([
            'title' => 'Initial Title',
            'content' => 'Initial Content',
            'short_description' => 'Initial Short Description',
            'category' => 'Initial Category',
            'image_path' => 'uploads/initial.png'
        ]);
        $post->save();

        $originalId = $post->id;
        $originalCreatedDate = $post->created_date;

        $this->forAll(
            Generator\associative([
                'title' => Generator\string(),
                'content' => Generator\string(),
                'short_description' => Generator\string(),
                'category' => Generator\string(),
                'image_path' => Generator\string()
            ])
        )
        ->when(function($data) {
            // Only test if title, content, short_description are non-empty to pass model-level validation on save
            return !empty($data['title']) && !empty($data['content']) && !empty($data['short_description']);
        })
        ->then(function($data) use ($post, $originalId, $originalCreatedDate) {
            // Apply new data (from generators) to the saved post
            $post->title = substr($data['title'], 0, 200); // respect max bounds
            $post->content = substr($data['content'], 0, 10000);
            $post->short_description = substr($data['short_description'], 0, 500);
            $post->category = substr($data['category'], 0, 100);
            $post->image_path = substr($data['image_path'], 0, 500);

            $this->assertTrue($post->save(), 'Post should save successfully');

            // Refresh from DB
            $refreshed = BlogPost::findById($originalId);
            $this->assertNotNull($refreshed);
            
            // Verify Property 3
            $this->assertEquals($originalId, $refreshed->id, 'The ID must remain unchanged');
            $this->assertEquals($originalCreatedDate, $refreshed->created_date, 'The created date must remain unchanged');
        });
    }
}

<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminCmsTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $uploaded = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.password' => 'test-password-123']);
        $this->withSession(['admin_ok' => true]);
        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        foreach ($this->uploaded as $path) {
            @unlink(public_path(ltrim($path, '/')));
        }
        parent::tearDown();
    }

    private function postData(array $over = []): array
    {
        return array_merge([
            'title' => 'Cách chọn giấy in A4 cho văn phòng',
            'slug' => '',
            'content' => '<h2>Định lượng</h2><p>Nội dung bài viết.</p><script>alert(1)</script>',
            'status' => 'publish',
            'faqs' => [['q' => 'Giấy 70 hay 80gsm?', 'a' => '80gsm cho tài liệu quan trọng.'], ['q' => '', 'a' => '']],
        ], $over);
    }

    public function test_admin_pages_render(): void
    {
        $cat = Category::create(['name' => 'Giấy in', 'slug' => 'giay-in']);
        $product = Product::create(['name' => 'Giấy A4', 'slug' => 'giay-a4', 'sku' => 'A4-1', 'category_id' => $cat->id]);
        $post = Post::create(['title' => 'Bài', 'slug' => 'bai', 'content' => '<p>x</p>', 'published_at' => now()]);

        foreach ([
            route('admin.dashboard'), route('admin.posts.index'), route('admin.posts.create'), route('admin.posts.edit', $post),
            route('admin.products.index'), route('admin.products.create'), route('admin.products.edit', $product),
            route('admin.categories.index'), route('admin.categories.create'), route('admin.categories.edit', $cat),
            route('admin.redirects.index'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_guest_cannot_access_admin(): void
    {
        $this->withSession(['admin_ok' => false]);
        $this->get(route('admin.posts.index'))->assertRedirect(route('admin.login'));
    }

    public function test_create_post_generates_slug_cleans_html_and_publishes(): void
    {
        $this->post(route('admin.posts.store'), $this->postData())->assertRedirect();

        $post = Post::firstOrFail();
        $this->assertSame('cach-chon-giay-in-a4-cho-van-phong', $post->slug);
        $this->assertStringNotContainsString('<script', $post->content);
        $this->assertCount(1, $post->faqs);
        $this->get('/tin-tuc/'.$post->slug)->assertOk()->assertSee('FAQPage', false);
    }

    public function test_draft_post_is_not_public(): void
    {
        $this->post(route('admin.posts.store'), $this->postData(['status' => 'draft']));
        $this->get('/tin-tuc/cach-chon-giay-in-a4-cho-van-phong')->assertNotFound();
    }

    public function test_changing_slug_creates_301_redirect(): void
    {
        $this->post(route('admin.posts.store'), $this->postData(['slug' => 'ten-cu']));
        $post = Post::firstOrFail();

        $this->post(route('admin.posts.update', $post), $this->postData(['slug' => 'ten-moi']));
        $this->get('/tin-tuc/ten-cu')->assertStatus(301)->assertRedirect('/tin-tuc/ten-moi');

        // Đổi lần nữa: link đầu tiên đi thẳng tới link mới nhất (không nối chuỗi 301).
        $this->post(route('admin.posts.update', $post), $this->postData(['slug' => 'ten-moi-nhat']));
        $this->get('/tin-tuc/ten-cu')->assertRedirect('/tin-tuc/ten-moi-nhat');
        $this->get('/tin-tuc/ten-moi')->assertRedirect('/tin-tuc/ten-moi-nhat');
    }

    public function test_product_crud_with_images_specs_and_delete_redirect(): void
    {
        $cat = Category::create(['name' => 'Giấy in', 'slug' => 'giay-in']);

        $this->post(route('admin.products.store'), [
            'name' => 'Giấy in A4 Double A 80gsm',
            'sku' => 'DA-A4-80',
            'category_id' => $cat->id,
            'price' => 75000,
            'is_active' => '1',
            'specs' => [['k' => 'Định lượng', 'v' => '80gsm'], ['k' => '', 'v' => '']],
            'new_images' => [UploadedFile::fake()->image('anh.png', 800, 800)],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $product = Product::firstOrFail();
        $this->uploaded = $product->images;
        $this->assertSame(['Định lượng' => '80gsm'], $product->specs);
        $this->assertMatchesRegularExpression('#^/images/products/uploads/giay-in-a4-double-a-80gsm-[a-z0-9]{5}\.png$#', $product->images[0]);
        $this->assertFileExists(public_path(ltrim($product->images[0], '/')));
        $this->get($product->url())->assertOk();

        $this->post(route('admin.products.destroy', $product))->assertRedirect();
        $this->assertFileDoesNotExist(public_path(ltrim($this->uploaded[0], '/')));
        $this->get('/san-pham/giay-in-a4-double-a-80gsm')->assertRedirect('/danh-muc/giay-in');
    }

    public function test_upload_rejects_php_file_disguised_as_image(): void
    {
        $jpeg = UploadedFile::fake()->image('a.jpg');
        $file = UploadedFile::fake()->createWithContent('shell.php', file_get_contents($jpeg->getPathname()));

        $this->postJson(route('admin.upload'), ['image' => $file])->assertStatus(422);
    }

    public function test_editor_image_upload_returns_url(): void
    {
        $res = $this->postJson(route('admin.upload'), ['image' => UploadedFile::fake()->image('x.jpg'), 'name' => 'Giấy in A4'])->assertOk();
        $this->uploaded[] = $res->json('url');
        $this->assertMatchesRegularExpression('#^/images/editor/uploads/giay-in-a4-[a-z0-9]{5}\.jpg$#', $res->json('url'));
    }

    public function test_category_cannot_be_its_own_descendant_and_delete_is_blocked_when_not_empty(): void
    {
        $parent = Category::create(['name' => 'Cha', 'slug' => 'cha']);
        $child = Category::create(['name' => 'Con', 'slug' => 'con', 'parent_id' => $parent->id]);

        $this->post(route('admin.categories.update', $parent), ['name' => 'Cha', 'parent_id' => $child->id])
            ->assertSessionHasErrors('parent_id');

        $this->post(route('admin.categories.destroy', $parent))->assertSessionHasErrors('category');
        $this->assertModelExists($parent);

        $this->post(route('admin.categories.destroy', $child))->assertRedirect();
        $this->assertModelMissing($child);
        $this->get('/danh-muc/con')->assertRedirect('/danh-muc/cha');
    }

    public function test_recreating_content_at_redirected_path_removes_redirect(): void
    {
        Redirect::record('/tin-tuc/bai-a', '/tin-tuc');
        $this->post(route('admin.posts.store'), $this->postData(['slug' => 'bai-a']));

        $this->assertDatabaseMissing('redirects', ['from_path' => '/tin-tuc/bai-a']);
        $this->get('/tin-tuc/bai-a')->assertOk();
    }
}

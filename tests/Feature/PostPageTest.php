<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostPageTest extends TestCase
{
    use RefreshDatabase;

    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['admin.password' => 'pass-123456789']);
        $this->post = Post::create([
            'title' => 'Giám sát nhiệt độ độ ẩm kho', 'slug' => 'giam-sat-nhiet-do', 'excerpt' => 'Tóm tắt bài.',
            'content' => '<h2>Vì sao cần giám sát</h2><p>'.str_repeat('chữ ', 450).'</p><h3>Chi tiết</h3><h2 id="san-co">Hai hình thức</h2><p>Nội dung.</p>',
            'author' => 'Admin', 'published_at' => now()->subDay(), 'faqs' => [['q' => 'Bao lâu hiệu chuẩn?', 'a' => '12 tháng.']],
        ]);
        foreach (range(1, 4) as $i) {
            Post::create(['title' => "Bài khác $i", 'slug' => "bai-khac-$i", 'content' => '<p>x</p>', 'published_at' => now()->subDays($i + 1)]);
        }
    }

    public function test_layout_has_meta_toc_sidebar_related_share_cta_and_back_link(): void
    {
        $html = $this->get($this->post->url())->assertOk()->getContent();

        $this->assertStringContainsString('3 phút đọc', $html);                       // ~454 từ / 200
        $this->assertStringContainsString('Nội dung bài viết', $html);               // mục lục
        $this->assertStringContainsString('href="#vi-sao-can-giam-sat"', $html);
        $this->assertStringContainsString('<h2 id="vi-sao-can-giam-sat">', $html);   // id tự gắn
        $this->assertStringContainsString('href="#san-co"', $html);                  // giữ id có sẵn
        $this->assertStringContainsString('class="article-content', $html);
        $this->assertStringContainsString('Bài viết mới nhất', $html);
        $this->assertStringContainsString('Bài viết liên quan', $html);
        $this->assertStringContainsString('Chia sẻ bài viết:', $html);
        $this->assertStringContainsString('Cần tư vấn văn phòng phẩm phù hợp?', $html);
        $this->assertStringContainsString('Câu hỏi thường gặp', $html);
        $this->assertStringContainsString('← Quay lại Tin tức', $html);
        $this->assertSame(1, preg_match_all('#<h1[\s>]#', $html));
    }

    public function test_comment_waits_for_approval_then_shows(): void
    {
        $this->post(route('posts.comment', $this->post), ['name' => 'Chị Lan', 'email' => 'lan@x.vn', 'content' => 'Bài viết rất hữu ích, cảm ơn!'])
            ->assertRedirect($this->post->url().'#binh-luan');
        $this->get($this->post->url())->assertDontSee('Bài viết rất hữu ích');

        $c = PostComment::firstOrFail();
        $this->assertFalse($c->is_approved);

        $this->withSession(['admin_ok' => true])->get(route('admin.comments.index'))->assertOk()->assertSee('Chị Lan')->assertSee('Chờ duyệt');
        $this->withSession(['admin_ok' => true])->post(route('admin.comments.approve', $c))->assertRedirect();

        $this->get($this->post->url())->assertSee('Bài viết rất hữu ích')->assertSee('Bình luận (1)')->assertDontSee('lan@x.vn');
    }

    public function test_comment_spam_trap_and_validation(): void
    {
        $this->post(route('posts.comment', $this->post), ['name' => 'Bot', 'email' => 'b@x.vn', 'content' => 'mua hàng giá rẻ', 'website' => 'http://spam'])->assertRedirect();
        $this->assertSame(0, PostComment::count(), 'Ô bẫy spam được điền -> không lưu');

        $this->post(route('posts.comment', $this->post), ['name' => '', 'email' => 'sai', 'content' => 'a'])->assertSessionHasErrors(['name', 'email', 'content']);
    }
}

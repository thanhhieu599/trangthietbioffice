<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'title', 'slug', 'excerpt', 'content', 'image', 'author',
        'meta_title', 'meta_description', 'focus_keyword', 'faqs', 'published_at',
    ];

    protected $casts = [
        'faqs' => 'array',
        'published_at' => 'datetime',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PostComment::class);
    }

    /** Thời gian đọc ước tính (khoảng 200 từ / phút, tối thiểu 1 phút). */
    public function readingMinutes(): int
    {
        return max(1, (int) ceil(\App\Support\Html::wordCount($this->content) / 200));
    }

    /**
     * Nội dung để hiển thị + mục lục: gắn id cho H2/H3 (giữ id có sẵn) để làm link nhảy tới mục.
     * Chỉ xử lý lúc hiển thị, không đổi nội dung đã lưu.
     *
     * @return array{html: string, toc: list<array{id: string, text: string, level: int}>}
     */
    public function contentWithToc(): array
    {
        $html = (string) $this->content;
        if (! preg_match('/<h[23][\s>]/i', $html)) {
            return ['html' => $html, 'toc' => []];
        }

        $toc = [];
        $used = [];
        $html = preg_replace_callback('#<(h[23])([^>]*)>(.*?)</\1>#is', function ($m) use (&$toc, &$used) {
            $text = trim(html_entity_decode(strip_tags($m[3]), ENT_QUOTES, 'UTF-8'));
            if ($text === '') {
                return $m[0];
            }
            if (preg_match('/\bid\s*=\s*"([^"]+)"/i', $m[2], $idm)) {
                $id = $idm[1];
                $attrs = $m[2];
            } else {
                $base = \Illuminate\Support\Str::slug($text) ?: 'muc';
                $id = $base;
                for ($i = 2; isset($used[$id]); $i++) {
                    $id = $base.'-'.$i;
                }
                $attrs = $m[2].' id="'.$id.'"';
            }
            $used[$id] = true;
            $toc[] = ['id' => $id, 'text' => $text, 'level' => (int) substr($m[1], 1)];

            return '<'.$m[1].$attrs.'>'.$m[3].'</'.$m[1].'>';
        }, $html);

        return ['html' => $html, 'toc' => $toc];
    }

    public function authorName(): string
    {
        return $this->author ?: 'Admin';
    }

    /** Đoạn tóm tắt: dùng excerpt, nếu trống thì cắt từ nội dung. */
    public function summary(int $limit = 160): string
    {
        return \Illuminate\Support\Str::limit(
            trim(preg_replace('/\s+/', ' ', strip_tags($this->excerpt ?: $this->content))),
            $limit
        );
    }

    public function url(): string
    {
        return route('posts.show', $this);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

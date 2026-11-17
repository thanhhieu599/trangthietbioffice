<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Category extends Model
{
    protected $fillable = [
        'parent_id', 'name', 'slug', 'intro', 'content', 'image', 'banner', 'banner_link', 'page_banner',
        'meta_title', 'meta_description', 'focus_keyword', 'sort_order', 'is_active', 'show_on_home',
    ];

    protected $casts = ['is_active' => 'boolean', 'show_on_home' => 'boolean'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->where('is_active', true)->orderBy('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id')->orderBy('sort_order');
    }

    /** Danh mục cha -> con, dùng cho breadcrumb. */
    public function ancestors(): Collection
    {
        $chain = collect();
        $node = $this->parent;
        while ($node) {
            $chain->prepend($node);
            $node = $node->parent;
        }

        return $chain;
    }

    /** ID của danh mục này và toàn bộ danh mục con (để lấy sản phẩm). */
    public function descendantIds(): array
    {
        $ids = [$this->id];
        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->descendantIds());
        }

        return $ids;
    }

    /**
     * ID các danh mục đáng cho Google index: đang hiện VÀ (có sản phẩm đang bán trong nhánh, hoặc có nội dung riêng).
     * Danh mục rỗng ("đang cập nhật") là trang mỏng -> noindex, không đưa vào sitemap. Tính bằng 2 truy vấn.
     *
     * @return array<int, true>
     */
    public static function indexableIds(): array
    {
        $cats = static::where('is_active', true)->get(['id', 'parent_id', 'content']);
        $counts = Product::where('is_active', true)->whereNotNull('category_id')
            ->selectRaw('category_id, count(*) as total')->groupBy('category_id')->pluck('total', 'category_id');

        $children = $cats->groupBy('parent_id');
        $memo = [];
        $total = function (int $id) use (&$total, &$memo, $counts, $children): int {
            return $memo[$id] ??= (int) ($counts[$id] ?? 0)
                + collect($children->get($id, []))->sum(fn ($c) => $total($c->id));
        };

        $ids = [];
        foreach ($cats as $c) {
            if ($total($c->id) > 0 || trim(strip_tags((string) $c->content)) !== '') {
                $ids[$c->id] = true;
            }
        }

        return $ids;
    }

    /** Banner dọc (khối sản phẩm trang chủ); danh mục con chưa có thì dùng của danh mục cha gần nhất. */
    public function bannerUrl(): ?string
    {
        return $this->inheritedImage('banner');
    }

    /** Banner ngang đầu trang danh mục; danh mục con chưa có thì dùng của danh mục cha gần nhất. */
    public function pageBannerUrl(): ?string
    {
        return $this->inheritedImage('page_banner');
    }

    private function inheritedImage(string $field): ?string
    {
        for ($c = $this, $depth = 0; $c && $depth < 5; $c = $c->parent, $depth++) {
            if ($c->{$field}) {
                return \App\Support\JsonLd::absolute($c->{$field});
            }
        }

        return null;
    }

    /** Ảnh danh mục: ảnh nhập trong DB (cột image) > file public/images/categories/{slug}.* > null. */
    public function imageUrl(): ?string
    {
        if ($this->image) {
            return \App\Support\JsonLd::absolute($this->image);
        }

        return \App\Support\SiteImage::category($this->slug);
    }

    public function url(): string
    {
        return route('categories.show', $this);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

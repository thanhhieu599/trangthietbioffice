<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Làm sạch HTML nhập từ trình soạn thảo (bài viết, mô tả sản phẩm, nội dung danh mục).
 * - Chỉ giữ thẻ nội dung; bỏ hẳn script/style/form...; thẻ lạ thì bỏ thẻ, giữ chữ.
 * - Thuộc tính style chỉ giữ các thuộc tính trình bày an toàn (màu, cỡ chữ, phông, căn lề, giãn dòng, thụt lề).
 * - Link chỉ nhận http(s), /, #, mailto:, tel:. Iframe chỉ nhận YouTube và Google Maps.
 * - H1 trong nội dung đổi thành H2 (trang đã có H1 là tiêu đề – chuẩn SEO).
 */
class Html
{
    public const MAX_LENGTH = 100000;

    private const DROP = ['script', 'style', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea',
        'noscript', 'template', 'svg', 'math', 'link', 'meta', 'base', 'head', 'title', 'frame', 'frameset', 'applet'];

    private const TAGS = ['p', 'br', 'h2', 'h3', 'h4', 'strong', 'b', 'em', 'i', 'u', 's', 'sub', 'sup', 'span', 'div',
        'ul', 'ol', 'li', 'a', 'img', 'blockquote', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption',
        'figure', 'figcaption', 'hr', 'pre', 'code', 'iframe'];

    private const ATTRS = [
        '*' => ['id', 'style'],
        'a' => ['href', 'target', 'rel', 'title'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan'],
        'ol' => ['start'],
        'iframe' => ['src', 'width', 'height', 'allow', 'allowfullscreen', 'frameborder', 'title', 'loading'],
    ];

    private const STYLES = ['color', 'background-color', 'font-size', 'font-family', 'font-weight', 'font-style',
        'text-decoration', 'text-decoration-line', 'text-align', 'line-height', 'margin-left', 'padding-left', 'width', 'height'];

    private const IFRAME_SRC = '#^https://(www\.)?(youtube\.com/embed/|youtube-nocookie\.com/embed/|google\.com/maps/embed)#i';

    public static function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $doc = new DOMDocument;
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="__root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $doc->getElementById('__root');
        if (! $root) {
            return null;
        }
        self::walk($root, $doc);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }
        // Bỏ đoạn rỗng do trình soạn thảo sinh ra.
        $out = trim(preg_replace('#<p>(\s|&nbsp;|\x{00A0}|<br\s*/?>)*</p>#iu', '', $out));

        return $out === '' ? null : $out;
    }

    private static function walk(DOMNode $node, DOMDocument $doc): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);

                continue;
            }
            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);
            if (in_array($tag, self::DROP, true)) {
                $node->removeChild($child);

                continue;
            }

            // Chuyển thẻ cũ sang thẻ chuẩn
            if ($tag === 'h1' || $tag === 'font') {
                $child = self::convert($child, $doc, $tag === 'h1' ? 'h2' : 'span');
                $tag = strtolower($child->tagName);
            }

            self::walk($child, $doc);

            if (! in_array($tag, self::TAGS, true)) {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            self::filterAttributes($child, $tag);

            if ($tag === 'iframe' && ! preg_match(self::IFRAME_SRC, $child->getAttribute('src'))) {
                $node->removeChild($child);

                continue;
            }
            if ($tag === 'img' && ! $child->getAttribute('src')) {
                $node->removeChild($child);

                continue;
            }
            // span không còn style -> bỏ thẻ, giữ chữ
            if ($tag === 'span' && ! $child->hasAttributes()) {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
            }
        }
    }

    /** Đổi tên thẻ (h1 -> h2, font -> span giữ màu/phông). */
    private static function convert(DOMElement $el, DOMDocument $doc, string $newTag): DOMElement
    {
        $new = $doc->createElement($newTag);
        $style = $el->getAttribute('style');
        if ($el->tagName === 'font') {
            if ($c = $el->getAttribute('color')) {
                $style .= ';color:'.$c;
            }
            if ($f = $el->getAttribute('face')) {
                $style .= ';font-family:'.$f;
            }
        }
        if (trim($style, '; ') !== '') {
            $new->setAttribute('style', $style);
        }
        while ($el->firstChild) {
            $new->appendChild($el->firstChild);
        }
        $el->parentNode->replaceChild($new, $el);

        return $new;
    }

    private static function filterAttributes(DOMElement $el, string $tag): void
    {
        $allowed = array_merge(self::ATTRS['*'], self::ATTRS[$tag] ?? []);

        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->name);
            $value = trim($attr->value);

            $keep = in_array($name, $allowed, true);
            if ($keep && in_array($name, ['href', 'src'], true)) {
                $keep = (bool) preg_match('#^(https?://|/|\#|mailto:|tel:)#i', $value);
            }
            if ($keep && $name === 'id') {
                $keep = (bool) preg_match('/^[A-Za-z][\w-]{0,80}$/', $value);
            }
            if ($keep && $name === 'target') {
                $keep = $value === '_blank';
            }
            if ($keep && $name === 'style') {
                $value = self::filterStyle($value);
                $keep = $value !== '';
            }

            if (! $keep) {
                $el->removeAttribute($attr->name);
            } elseif ($name === 'style') {
                $el->setAttribute('style', $value);
            }
        }

        if ($tag === 'a' && $el->getAttribute('target') === '_blank') {
            $el->setAttribute('rel', 'noopener');
        }
    }

    private static function filterStyle(string $style): string
    {
        $out = [];
        foreach (explode(';', $style) as $decl) {
            [$prop, $val] = array_pad(array_map('trim', explode(':', $decl, 2)), 2, '');
            $prop = strtolower($prop);
            if ($prop === '' || $val === '' || ! in_array($prop, self::STYLES, true)) {
                continue;
            }
            if (strlen($val) > 120 || preg_match('/url\s*\(|expression|javascript|[<>\\\\{}]/i', $val)) {
                continue;
            }
            $out[$prop] = $prop.': '.$val;
        }

        return implode('; ', $out);
    }

    /** Số từ của nội dung (đếm theo khoảng trắng, phù hợp tiếng Việt). */
    public static function wordCount(?string $html): int
    {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $html), ENT_QUOTES, 'UTF-8')));

        return $text === '' ? 0 : count(explode(' ', $text));
    }
}

<?php

namespace Tests\Unit;

use App\Support\Html;
use PHPUnit\Framework\TestCase;

class HtmlCleanTest extends TestCase
{
    public function test_keeps_safe_styles_and_vietnamese_text(): void
    {
        $out = Html::clean('<p style="text-align: center; line-height: 1.8; position: fixed">Giấy in <span style="color: #d71920; font-size: 18px; background: url(x.png)">đỏ</span></p>');

        $this->assertSame('<p style="text-align: center; line-height: 1.8">Giấy in <span style="color: #d71920; font-size: 18px">đỏ</span></p>', $out);
    }

    public function test_removes_scripts_events_and_bad_links(): void
    {
        $out = Html::clean('<p onclick="x()">A<script>alert(1)</script> <a href="javascript:alert(1)">b</a> <a href="/danh-muc/giay" target="_blank">c</a></p><style>p{}</style>');

        $this->assertSame('<p>A <a>b</a> <a href="/danh-muc/giay" target="_blank" rel="noopener">c</a></p>', $out);
    }

    public function test_iframe_only_from_youtube_or_maps(): void
    {
        $yt = Html::clean('<iframe src="https://www.youtube.com/embed/abc123" width="560" height="315" allowfullscreen onload="x()"></iframe>');
        $this->assertStringContainsString('src="https://www.youtube.com/embed/abc123"', $yt);
        $this->assertStringNotContainsString('onload', $yt);

        $this->assertNull(Html::clean('<iframe src="https://evil.example/x"></iframe>'));
    }

    public function test_converts_h1_and_font_tags_and_unwraps_unknown(): void
    {
        $out = Html::clean('<h1>Tiêu đề</h1><font color="#ff0000" face="Arial">chữ</font><section><span>x</span></section>');

        $this->assertSame('<h2>Tiêu đề</h2><span style="color: #ff0000; font-family: Arial">chữ</span>x', $out);
    }

    public function test_anchor_ids_and_empty_paragraphs(): void
    {
        $this->assertSame('<h2 id="muc-1">Mục 1</h2>', Html::clean('<h2 id="muc-1">Mục 1</h2><p><br></p><p>&nbsp;</p>'));
        $this->assertSame('<h2>X</h2>', Html::clean('<h2 id="1 bad">X</h2>'));
    }
}

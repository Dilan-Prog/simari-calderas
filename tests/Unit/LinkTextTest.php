<?php

namespace Tests\Unit;

use App\Support\LinkText;
use PHPUnit\Framework\TestCase;

class LinkTextTest extends TestCase
{
    private function r(?string $t): string
    {
        return (string) LinkText::render($t);
    }

    public function test_converts_internal_link(): void
    {
        $this->assertSame('Ver <a href="/producto/x">bombas</a> hoy', $this->r('Ver [bombas](/producto/x) hoy'));
    }

    public function test_external_link_opens_new_tab(): void
    {
        $this->assertSame(
            '<a href="https://ex.com/a" target="_blank" rel="noopener">ex</a>',
            $this->r('[ex](https://ex.com/a)')
        );
    }

    public function test_multiple_links(): void
    {
        $out = $this->r('[a](/a) y [b](http://b.com)');
        $this->assertSame(2, substr_count($out, '<a '));
    }

    public function test_no_links_equals_escape(): void
    {
        foreach (['Hola mundo', 'a < b & "c" \'d\'', '<b>x</b>', 'texto [sin enlace] (nada)', '', 'a [x]'] as $t) {
            $this->assertSame(e($t), $this->r($t));
        }
        $this->assertSame('', $this->r(null));
    }

    public function test_javascript_and_data_blocked(): void
    {
        foreach (['[x](javascript:alert(1))', '[x](JaVaScRiPt:alert(1))', '[x](data:text/html,hi)', '[x](mailto:a@b.c)'] as $t) {
            $this->assertStringNotContainsString('<a ', $this->r($t));
            $this->assertSame(e($t), $this->r($t));
        }
    }

    public function test_protocol_relative_blocked(): void
    {
        $this->assertStringNotContainsString('<a ', $this->r('[x](//evil.com)'));
        $this->assertStringNotContainsString('<a ', $this->r('[x](/\evil.com)'));
    }

    public function test_nested_or_unclosed_brackets_safe(): void
    {
        $this->assertSame(e('[a [b](/x'), $this->r('[a [b](/x'));
        $this->assertSame(e('[[x]](/y)'), $this->r('[[x]](/y)'));
        $this->assertStringContainsString('<a href="/x">b</a>', $this->r('[a [b](/x)]'));
        $this->assertSame(e('[x](/a b)'), $this->r('[x](/a b)'));
    }

    public function test_xss_in_label_and_url(): void
    {
        $out = $this->r('[<script>alert(1)</script>](/ok)');
        $this->assertStringNotContainsString('<script>', $out);
        $this->assertStringContainsString('&lt;script&gt;', $out);

        $out = $this->r('[x](/a"onmouseover="alert(1))');
        $this->assertStringNotContainsString('"onmouseover', $out);

        $out = $this->r('[x](https://a.com/"><script>alert(1)</script>)');
        $this->assertStringNotContainsString('<script>', $out);
    }

    public function test_plain_strips_markup(): void
    {
        $this->assertSame('Ver bombas hoy', LinkText::plain('Ver [bombas](/producto/x) hoy'));
        $this->assertSame('x', LinkText::plain('[x](https://a.com)'));
        $this->assertSame('[x](javascript:a)', LinkText::plain('[x](javascript:a)'));
        $this->assertSame('', LinkText::plain(null));
    }
}

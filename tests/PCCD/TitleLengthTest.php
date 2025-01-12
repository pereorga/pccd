<?php

/**
 * This file is part of PCCD.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 * (c) Víctor Pàmies i Riudor <vpamies@gmail.com>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

namespace PCCD;

use PHPUnit\Framework\TestCase;

final class TitleLengthTest extends TestCase
{
    public function testTitleCanNotBeTooLong(): void
    {
        require_once __DIR__ . '/../../src/common.php';

        // Test that length is never exceeded.
        $sentences = [
            str_repeat('. ', intdiv(TITLE_MAX_LENGTH, 3)),
            str_repeat(' .', intdiv(TITLE_MAX_LENGTH, 3)),
            str_repeat('. ', intdiv(TITLE_MAX_LENGTH, 2)),
            str_repeat(' .', intdiv(TITLE_MAX_LENGTH, 2)),
            str_repeat('. ', TITLE_MAX_LENGTH),
            str_repeat(' .', TITLE_MAX_LENGTH),
            str_repeat('. ', TITLE_MAX_LENGTH * 2),
            str_repeat(' .', TITLE_MAX_LENGTH * 2),
        ];

        foreach ($sentences as $sentence) {
            $title = format_html_title($sentence);
            $this->assertLessThanOrEqual(TITLE_MAX_LENGTH, mb_strlen($title));

            $title = format_html_title($sentence, 'PCCD');
            $this->assertLessThanOrEqual(TITLE_MAX_LENGTH, mb_strlen($title));
        }
    }

    public function testTitleFormat(): void
    {
        require_once __DIR__ . '/../../src/common.php';

        // Test title shorter than TITLE_MAX_LENGTH.
        $title = 'Short title';
        $this->assertSame('Short title', format_html_title($title));

        // Test title equal to TITLE_MAX_LENGTH.
        $title = str_repeat('a', TITLE_MAX_LENGTH);
        $this->assertSame($title, format_html_title($title));

        // Test long title with spaces.
        $title = str_repeat('a ', (TITLE_MAX_LENGTH + 10) / 2);
        $expected = 'a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a…';
        $this->assertSame($expected, format_html_title($title));

        // Test suffix addition when possible.
        $title = 'Short title';
        $suffix = 'suffix';
        $this->assertSame('Short title - suffix', format_html_title($title, $suffix));

        // Test suffix skipped when title length exceeds TITLE_MAX_LENGTH.
        $title = str_repeat('a', TITLE_MAX_LENGTH);
        $suffix = 'suffix';
        $this->assertSame($title, format_html_title($title, $suffix));
    }
}

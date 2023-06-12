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

declare(strict_types=1);

namespace PCCD;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class TitleLengthTest extends TestCase
{
    public function testTitleCanNotBeTooLong(): void
    {
        require_once __DIR__ . '/../../src/common.php';

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
            self::assertLessThanOrEqual(TITLE_MAX_LENGTH, mb_strlen($title));

            $title = format_html_title($sentence, 'PCCD');
            self::assertLessThanOrEqual(TITLE_MAX_LENGTH, mb_strlen($title));
        }
    }
}

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
final class CopyrightYearTest extends TestCase
{
    public function testCreditsPageHasCorrectCopyrightYear(): void
    {
        $year = date('Y');
        $phpFile = file_get_contents(__DIR__ . '/../../src/pages/credits.php');
        $yearString = sprintf('© Víctor Pàmies i Riudor, 2020-%s.', $year);
        $yearMentions = substr_count($phpFile, $yearString);

        static::assertSame(2, $yearMentions, "File src/pages/credits.php should contain the current year {$year} twice");
    }

    public function testIndexPageHasCorrectCopyrightYear(): void
    {
        $year = date('Y');
        $phpFile = file_get_contents(__DIR__ . '/../../docroot/index.php');
        $yearString = sprintf('© Víctor Pàmies i Riudor, 2020-%s.', $year);
        $yearMentions = substr_count($phpFile, $yearString);

        static::assertSame(1, $yearMentions, "File docroot/index.php should contain the current year {$year}");
    }
}

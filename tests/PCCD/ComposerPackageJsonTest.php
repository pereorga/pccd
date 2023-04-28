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
final class ComposerPackageJsonTest extends TestCase
{
    public function testKeywordsMatch(): void
    {
        $package = $this->getJsonArray(__DIR__ . '/../../package.json');
        $composer = $this->getJsonArray(__DIR__ . '/../../composer.json');

        static::assertSame(
            $package['keywords'],
            $composer['keywords'],
            'The keywords set in package.json and composer.json do not match.'
        );
    }

    public function testDescriptionMatch(): void
    {
        $package = $this->getJsonArray(__DIR__ . '/../../package.json');
        $composer = $this->getJsonArray(__DIR__ . '/../../composer.json');

        static::assertSame(
            $package['description'],
            $composer['description'],
            'The descriptions set in package.json and composer.json do not match.'
        );
    }

    public function testWebsiteMatch(): void
    {
        $package = $this->getJsonArray(__DIR__ . '/../../package.json');
        $composer = $this->getJsonArray(__DIR__ . '/../../composer.json');

        static::assertSame(
            $package['homepage'],
            $composer['homepage'],
            'The websites set in package.json and composer.json do not match.'
        );
    }

    public function testLicenseMatch(): void
    {
        $package = $this->getJsonArray(__DIR__ . '/../../package.json');
        $composer = $this->getJsonArray(__DIR__ . '/../../composer.json');

        static::assertSame(
            $package['license'],
            $composer['license'],
            'The licenses set in package.json and composer.json do not match.'
        );
    }

    private function getJsonArray(string $filename): array
    {
        return json_decode(file_get_contents($filename), true);
    }
}

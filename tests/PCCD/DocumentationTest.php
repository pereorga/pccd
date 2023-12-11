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
final class DocumentationTest extends TestCase
{
    public function testReadmeHasCorrectPhpMinimumVersion(): void
    {
        $composerJsonContent = file_get_contents(__DIR__ . '/../../composer.json');
        $composerJson = json_decode($composerJsonContent, true);
        $composerPhpVersion = trim($composerJson['require']['php'], '>=^');
        $minimumVersionInformation = sprintf('PHP: %s or later is required.', $composerPhpVersion);
        $installationDocPath = realpath(__DIR__ . '/../../README.md');

        self::assertStringContainsString(
            $composerPhpVersion,
            file_get_contents($installationDocPath),
            sprintf('File %s needs to contain information "%s"', $installationDocPath, $minimumVersionInformation)
        );
    }

    public function testReadmeHasCorrectNodeJsMinimumVersion(): void
    {
        $composerJsonContent = file_get_contents(__DIR__ . '/../../package.json');
        $packageJson = json_decode($composerJsonContent, true);
        $minimumVersion = trim($packageJson['engines']['node'], '>=^');
        $minimumVersionInformation = sprintf('Node.js: %s or later is required.', $minimumVersion);
        $installationDocPath = realpath(__DIR__ . '/../../README.md');

        self::assertStringContainsString(
            $minimumVersionInformation,
            file_get_contents($installationDocPath),
            sprintf('File %s needs to contain information "%s"', $installationDocPath, $minimumVersionInformation)
        );
    }
}

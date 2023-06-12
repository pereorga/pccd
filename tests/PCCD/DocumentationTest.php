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
        $phpVersion = $composerJson['require']['php'];
        $minimumVersion = trim($phpVersion, '>=^');
        $minimumVersionInformation = sprintf('PHP: %s or later is required.', $minimumVersion);
        $installationDocPath = realpath(__DIR__ . '/../../README.md');

        self::assertStringContainsString(
            $minimumVersionInformation,
            file_get_contents($installationDocPath),
            sprintf('File %s needs to contain information "%s"', $installationDocPath, $minimumVersionInformation)
        );
    }

    public function testReadmeHasCorrectNodeJsMinimumVersion(): void
    {
        $composerJsonContent = file_get_contents(__DIR__ . '/../../package.json');
        $packageJson = json_decode($composerJsonContent, true);
        $nodeVersion = $packageJson['engines']['node'];
        $minimumVersion = trim($nodeVersion, '>=^');
        $minimumMajorVersion = str_replace('.0.0', '', $minimumVersion);
        $minimumVersionInformation = sprintf('Node.js: %s or later is required.', $minimumMajorVersion);
        $installationDocPath = realpath(__DIR__ . '/../../README.md');

        self::assertStringContainsString(
            $minimumVersionInformation,
            file_get_contents($installationDocPath),
            sprintf('File %s needs to contain information "%s"', $installationDocPath, $minimumVersionInformation)
        );
    }
}

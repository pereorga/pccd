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

final class DocumentationTest extends TestCase
{
    public function testReadmeHasCorrectPhpMinimumVersion(): void
    {
        $composerJsonContent = file_get_contents(__DIR__ . '/../../composer.json');
        $composerJson = json_decode($composerJsonContent, true);
        $composerPhpVersion = trim($composerJson['require']['php'], '>=^');
        $minimumVersionInformation = "PHP: {$composerPhpVersion} or later is required.";
        $installationDocPath = realpath(__DIR__ . '/../../README.md');

        self::assertStringContainsString(
            $composerPhpVersion,
            file_get_contents($installationDocPath),
            "File {$installationDocPath} needs to contain information '{$minimumVersionInformation}'"
        );
    }

    public function testReadmeHasCorrectNodeJsMinimumVersion(): void
    {
        $composerJsonContent = file_get_contents(__DIR__ . '/../../package.json');
        $packageJson = json_decode($composerJsonContent, true);
        $minimumVersion = trim($packageJson['engines']['node'], '>=^');
        $minimumVersionInformation = "Node.js: {$minimumVersion} or later is required.";
        $installationDocPath = realpath(__DIR__ . '/../../README.md');

        self::assertStringContainsString(
            $minimumVersionInformation,
            file_get_contents($installationDocPath),
            "File {$installationDocPath} needs to contain information '{$minimumVersionInformation}'"
        );
    }
}

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

/**
 * @internal
 *
 * @coversNothing
 */
final class PhpVersionTest extends TestCase
{
    public function testShellNixHasAtLeastComposerJsonPhpVersion(): void
    {
        $composerJsonContent = file_get_contents(__DIR__ . '/../../composer.json');
        $composerJson = json_decode($composerJsonContent, true);
        $composerPhpVersion = trim($composerJson['require']['php'], '>=^');

        $shellNixPath = realpath(__DIR__ . '/../../shell.nix');
        $shellNixContent = file_get_contents($shellNixPath);

        // Assuming the PHP version in shell.nix is defined in a line like php82.withExtensions.
        preg_match('/php(\d+)\.withExtensions/', $shellNixContent, $matches);
        $nixPhpVersion = $matches[1][0] . '.' . $matches[1][1];

        self::assertTrue(
            version_compare($nixPhpVersion, $composerPhpVersion, '>='),
            "PHP version in shell.nix ({$nixPhpVersion}) is lower than composer.json minimum version ({$composerPhpVersion})"
        );
    }

    public function testPhpStormSettingsHasAtLeastComposerJsonPhpVersion(): void
    {
        $phpStormConfigPath = realpath(__DIR__ . '/../../.idea/php.xml');
        $phpStormConfigContent = file_get_contents($phpStormConfigPath);

        $matches = [];
        preg_match('/php_language_level="(\d+\.\d+)"/', $phpStormConfigContent, $matches);
        $phpStormPhpVersion = $matches[1];

        $composerJsonContent = file_get_contents(__DIR__ . '/../../composer.json');
        $composerJson = json_decode($composerJsonContent, true);
        $composerPhpVersion = trim($composerJson['require']['php'], '>=^');

        self::assertTrue(
            version_compare($phpStormPhpVersion, $composerPhpVersion, '>='),
            "PHP version in PhpStorm settings {$phpStormPhpVersion}) is lower than composer.json minimum version ({$composerPhpVersion})"
        );
    }

    public function testRectorPhpVersionHasAtLeastComposerJsonPhpVersion(): void
    {
        $rectorConfigContent = file_get_contents(__DIR__ . '/../../rector.php');

        $matches = [];
        preg_match('/UP_TO_PHP_(\d)(\d)/', $rectorConfigContent, $matches);
        $extractedRectorPhpVersion = isset($matches[1]) && isset($matches[2]) ? $matches[1] . '.' . $matches[2] : 'Not Found';

        $composerJsonContent = file_get_contents(__DIR__ . '/../../composer.json');
        $composerJson = json_decode($composerJsonContent, true);
        $composerPhpVersion = trim($composerJson['require']['php'], '>=^');

        self::assertTrue(
            version_compare($extractedRectorPhpVersion, $composerPhpVersion, '>='),
            "PHP version in Rector ({$extractedRectorPhpVersion}) is lower than the minimum required version in composer.json ({$composerPhpVersion})"
        );
    }
}

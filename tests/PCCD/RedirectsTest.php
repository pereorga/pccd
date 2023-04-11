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

const HTTP_STATUS_OK = 200;

/**
 * @internal
 *
 * @coversNothing
 */
final class RedirectsTest extends TestCase
{
    public function testRedirectSourceAndTargetDiffers(): void
    {
        require_once __DIR__ . '/../../src/common.php';

        $redirects = get_redirects();
        foreach ($redirects as $source_url => $target_url) {
            static::assertNotSame(
                $source_url,
                $target_url,
                'Source URL and target URL are the same: ' . $source_url
            );
        }
    }

    public function testRedirectNormalizedCharacters(): void
    {
        require_once __DIR__ . '/../../src/common.php';

        $redirects = get_redirects();
        foreach ($redirects as $source_url => $target_url) {
            static::assertStringNotContainsString(
                '%2B',
                $source_url,
                'Source url contains characters not normalized: ' . $source_url
            );

            static::assertStringNotContainsString(
                '%2B',
                $target_url,
                'Target url contains characters not normalized: ' . $target_url
            );

            static::assertStringNotContainsString(
                '+',
                $target_url,
                'Target url contains spaces rather than underscores: ' . $target_url
            );

            static::assertStringNotContainsString(
                "'",
                $source_url,
                'Source url contains single quotes: ' . $source_url
            );

            static::assertStringNotContainsString(
                "'",
                $target_url,
                'Target url contains single quotes: ' . $target_url
            );
        }
    }

    public function testRedirectPatterns(): void
    {
        require_once __DIR__ . '/../../src/common.php';

        $redirects = get_redirects();
        foreach ($redirects as $source_url => $target_url) {
            if (str_starts_with($source_url, '/?paremiotipus') || str_starts_with($source_url, '/p/')) {
                static::assertStringStartsWith(
                    '/p/',
                    $target_url,
                    'Target paremiotipus URL does not start with /p/: ' . $target_url
                );
            }

            if (str_starts_with($source_url, '/?obra') || str_starts_with($source_url, '/obra/')) {
                static::assertStringStartsWith(
                    '/obra/',
                    $target_url,
                    'Target obra URL does not start with /p/: ' . $target_url
                );
            }
        }
    }

    public function testRedirectTargetReturnsHttp200(): void
    {
        require_once __DIR__ . '/../../src/common.php';

        require_once __DIR__ . '/../../src/reports/tests.php';

        $host = getenv('BASE_URL');
        \assert(\is_string($host));

        $redirects = get_redirects();
        foreach ($redirects as $target_url) {
            static::assertSame(
                HTTP_STATUS_OK,
                curl_get_response_code($host . $target_url),
                'HTTP response code is not 200 for target URL: ' . $host . $target_url
            );
        }
    }

    public function testRedirectSourceReturnsHttp301(): void
    {
        require_once __DIR__ . '/../../src/common.php';

        require_once __DIR__ . '/../../src/reports/tests.php';

        $host = getenv('BASE_URL');
        \assert(\is_string($host));

        $redirects = get_redirects();
        foreach (array_keys($redirects) as $source_url) {
            static::assertSame(
                301,
                curl_get_response_code($host . $source_url),
                'HTTP response code is not 301 for source URL: ' . $host . $source_url
            );
        }
    }
}

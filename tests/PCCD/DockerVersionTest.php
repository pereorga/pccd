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
final class DockerVersionTest extends TestCase
{
    public function testDockerPhpVersionMatch(): void
    {
        // Test that the PHP version matches in all Dockerfiles.
        $dockerFileDev = file_get_contents(__DIR__ . '/../../.docker/Dockerfile');
        $dockerFileProd = file_get_contents(__DIR__ . '/../../.docker/web.prod.Dockerfile');

        $dockerVersionDev = $this->getDockerPhpVersion($dockerFileDev);
        $dockerVersionProd = $this->getDockerPhpVersion($dockerFileProd);

        static::assertSame($dockerVersionDev, $dockerVersionProd, 'Dockerfile and web.prod.Dockerfile should use the same PHP version');
    }

    public function testDockerMysqlVersionMatch(): void
    {
        // Test that the PHP version matches in both sql.prod.Dockerfile and docker-compose.yml.
        $dockerComposeFile = file_get_contents(__DIR__ . '/../../docker-compose.yml');
        $dockerFile = file_get_contents(__DIR__ . '/../../.docker/sql.prod.Dockerfile');

        $dockerVersionDev = $this->getDockerComposeMysqlVersion($dockerComposeFile);
        $dockerVersionProd = $this->getDockerMysqlVersion($dockerFile);

        static::assertSame($dockerVersionDev, $dockerVersionProd, 'docker-compose.yml and sql.prod.Dockerfile should use the same MySQL version');
    }

    public function testDockerApcVersionMatch(): void
    {
        // Test that the APCu version matches in all Dockerfiles.
        $dockerFileDev = file_get_contents(__DIR__ . '/../../.docker/Dockerfile');
        $dockerFileProd = file_get_contents(__DIR__ . '/../../.docker/web.prod.Dockerfile');

        $dockerVersionDev = $this->getDockerApcuVersion($dockerFileDev);
        $dockerVersionProd = $this->getDockerApcuVersion($dockerFileProd);

        static::assertSame($dockerVersionDev, $dockerVersionProd, 'Dockerfile and web.prod.Dockerfile should use the same APCu version');
    }

    protected function getDockerPhpVersion(string $dockerFile): string
    {
        $matches = [];
        preg_match('/^FROM php:([0-9.]+)-apache/', $dockerFile, $matches);

        return $matches[1];
    }

    protected function getDockerMysqlVersion(string $dockerFile): string
    {
        $matches = [];
        preg_match('/^FROM mariadb:([0-9.]+)/', $dockerFile, $matches);

        return $matches[1];
    }

    protected function getDockerComposeMysqlVersion(string $dockerComposeFile): string
    {
        $matches = [];
        preg_match('/image: mariadb:([0-9.]+)/', $dockerComposeFile, $matches);

        return $matches[1];
    }

    protected function getDockerApcuVersion(string $dockerFile): string
    {
        $matches = [];
        preg_match('/pecl install apcu-([0-9.]+)/', $dockerFile, $matches);

        return $matches[1];
    }
}

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

// Settings file.

// if (file_exists(__DIR__ . '/db_settings.local.php')) {
//    require __DIR__ . '/db_settings.local.php';
// }

$pdo = new PDO('mysql:host=' . getenv('MYSQL_HOSTNAME') . ';dbname=' . getenv('MYSQL_DATABASE') . ';charset=utf8mb4', getenv('MYSQL_USER'), getenv('MYSQL_PASSWORD'), [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_PERSISTENT => false,
    PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
]);

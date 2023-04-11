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

/*
 * Prints the sitemap contents to stdout.
 *
 * This file is called by install.sh script.
 */

require __DIR__ . '/../src/common.php';

$pdo = get_db();

$site = 'https://pccd.dites.cat';

$urls = $site . "\n";
$urls .= $site . '/projecte' . "\n";
$urls .= $site . '/instruccions' . "\n";
$urls .= $site . '/credits' . "\n";
$urls .= $site . '/top100' . "\n";
$urls .= $site . '/llibres' . "\n";

$stmt = $pdo->query('SELECT DISTINCT PAREMIOTIPUS FROM 00_PAREMIOTIPUS ORDER BY PAREMIOTIPUS');
$records = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($records as $r) {
    $urls .= get_paremiotipus_url($r, true) . "\n";
}

$stmt = $pdo->query('SELECT DISTINCT Identificador FROM 00_FONTS');
$records = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($records as $r) {
    $urls .= get_obra_url($r, true) . "\n";
}

echo $urls;

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
 * Execute time-consuming tests.
 *
 * This file is called by generate_reports.sh script.
 */

require __DIR__ . '/../src/reports/tests.php';

ini_set('memory_limit', '512M');

if (!isset($argv[1])) {
    echo 'Argument is required.' . \PHP_EOL;

    exit;
}

if ($argv[1] === 'llibres_urls') {
    echo background_test_llibres_urls();
}
if ($argv[1] === 'fonts_urls') {
    echo background_test_fonts_urls();
}
if ($argv[1] === 'imatges_urls') {
    echo background_test_imatges_urls();
}
if ($argv[1] === 'imatges_links') {
    echo background_test_imatges_links();
}
if ($argv[1] === 'paremiotipus_repetits') {
    $start = isset($argv[2]) ? (int) $argv[2] : 0;
    $end = isset($argv[3]) ? (int) $argv[3] : 0;
    echo background_test_paremiotipus_repetits($start, $end);
}

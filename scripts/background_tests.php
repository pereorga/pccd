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

/*
 * Execute time-consuming tests.
 *
 * This file is called by yarn generate:reports script.
 */

require __DIR__ . '/../src/reports/tests.php';

ini_set('memory_limit', '512M');

if (!isset($argv[1])) {
    echo 'Argument is required.' . "\n";

    exit;
}

if ($argv[1] === 'llibres_urls') {
    echo background_test_llibres_urls();

    exit;
}

if ($argv[1] === 'fonts_urls') {
    echo background_test_fonts_urls();

    exit;
}

if ($argv[1] === 'imatges_urls') {
    $start = isset($argv[2]) ? (int) $argv[2] : 0;
    $end = isset($argv[3]) ? (int) $argv[3] : 0;
    echo background_test_imatges_urls($start, $end);

    exit;
}

if ($argv[1] === 'imatges_links') {
    $start = isset($argv[2]) ? (int) $argv[2] : 0;
    $end = isset($argv[3]) ? (int) $argv[3] : 0;
    echo background_test_imatges_links($start, $end);

    exit;
}

if ($argv[1] === 'paremiotipus_repetits') {
    $start = isset($argv[2]) ? (int) $argv[2] : 0;
    $end = isset($argv[3]) ? (int) $argv[3] : 0;
    echo background_test_paremiotipus_repetits($start, $end);

    exit;
}

if ($argv[1] === 'html_escape_and_link_urls') {
    echo background_test_html_escape_and_link_urls();

    exit;
}

if ($argv[1] === 'imatges_no_existents') {
    echo background_test_imatges_no_existents();

    exit;
}

if ($argv[1] === 'imatges_duplicades') {
    echo background_test_imatges_duplicades();

    exit;
}

if ($argv[1] === 'imatges_no_referenciades') {
    echo background_test_imatges_no_referenciades();

    exit;
}

echo 'Unknown test name provided.' . "\n";

exit(1);

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

const CV_MIN_WORDS = 3;
const CV_MAX_WORDS = 14;

/*
 * Export sentences for Common Voice.
 *
 * This script outputs potentially controversial sentences to stderr, and the other ones to stdout.
 */

require __DIR__ . '/../../src/common.php';

require __DIR__ . '/functions.php';

$pdo = get_db();

$cv_res = $pdo->query('SELECT DISTINCT `paremiotipus` FROM `commonvoice`')->fetchAll(PDO::FETCH_COLUMN);
$cv = [];
foreach ($cv_res as $p) {
    $cv[mb_strtolower($p)] = true;
}

$paremiotipus = $pdo->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
foreach ($paremiotipus as $p) {
    // Omit sentences that already exist in Common Voice.
    $p_lowercase = mb_strtolower($p);
    if (isset($cv[$p_lowercase])) {
        continue;
    }

    // Omit sentences that are either too short or too long.
    $number_of_words = mb_substr_count($p, ' ') + 1;
    if ($number_of_words < CV_MIN_WORDS || $number_of_words > CV_MAX_WORDS) {
        continue;
    }

    $p_display = get_paremiotipus_display($p, escape_html: false);
    // End the sentence with a dot, to align with Common Voice corpus.
    if (preg_match('/[.!?,;:]$/', $p_display) === 0) {
        $p_display .= '.';
    }

    // Omit some sentences that contain inappropriate language.
    if (
        preg_match('/\bcago\b/', $p_lowercase) === 1
        || preg_match('/\bcony\b/', $p_lowercase) === 1
        || preg_match('/\bcunyada\b/', $p_lowercase) === 1
        || preg_match('/\bcunyades\b/', $p_lowercase) === 1
        || (preg_match('/\bdona\b/', $p_lowercase) === 1 && !str_contains($p_lowercase, 'dona-'))
        || preg_match('/\bdones\b/', $p_lowercase) === 1
        || preg_match('/\bfilla\b/', $p_lowercase) === 1
        || preg_match('/\bfilles\b/', $p_lowercase) === 1
        || preg_match('/\bgitano\b/', $p_lowercase) === 1
        || preg_match('/\bgitanos\b/', $p_lowercase) === 1
        || preg_match('/\bmamella\b/', $p_lowercase) === 1
        || preg_match('/\bmamelles\b/', $p_lowercase) === 1
        || preg_match('/\bmoro\b/', $p_lowercase) === 1
        || preg_match('/\bmoros\b/', $p_lowercase) === 1
        || preg_match('/\bmuller\b/', $p_lowercase) === 1
        || preg_match('/\bputa\b/', $p_lowercase) === 1
        || preg_match('/\bputes\b/', $p_lowercase) === 1
        || preg_match('/\bsogra\b/', $p_lowercase) === 1
        || preg_match('/\bsogres\b/', $p_lowercase) === 1
    ) {
        fwrite(STDERR, $p_display . "\n");

        continue;
    }

    $p_display = remove_parentheses($p_display);
    if ($p_display === '') {
        continue;
    }

    fwrite(STDOUT, $p_display . "\n");
}

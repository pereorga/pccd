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
 * Top 10000.
 *
 * This page is currently not discoverable.
 */

global $pdo;
global $page_title;

$page_title = 'Les 10.000 parèmies més citades';

/** @var PDO $pdo */
$stmt = $pdo->query('SELECT Paremiotipus FROM common_paremiotipus ORDER BY Compt DESC');

/** @var PDOStatement $stmt */
$records = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo '<ol>';
foreach ($records as $r) {
    /** @var string $r */
    echo '<li><a href="' . get_paremiotipus_url($r) . '">' . get_paremiotipus_display($r) . '</a></li>';
}
echo '</ol>';

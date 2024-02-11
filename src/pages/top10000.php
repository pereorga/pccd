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

// This page is currently not discoverable.
header('X-Robots-Tag: noindex');

set_page_title('Les 10.000 parèmies més citades');

$stmt = get_db()->query('SELECT `Paremiotipus` FROM `common_paremiotipus` ORDER BY `Compt` DESC');
$records = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo '<ol>';
foreach ($records as $record) {
    echo '<li><a href="' . get_paremiotipus_url($record) . '">' . get_paremiotipus_display($record) . '</a></li>';
}
echo '</ol>';

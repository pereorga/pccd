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

set_page_title('Les 100 parèmies més citades');
set_meta_description('Llista de les frases més citades de la Paremiologia catalana comparada digital.');

$stmt = get_db()->query('SELECT `Paremiotipus` FROM `common_paremiotipus` ORDER BY `Compt` DESC LIMIT 100');
$records = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo '<ol>';
foreach ($records as $record) {
    echo '<li><a href="' . get_paremiotipus_url($record) . '">' . get_paremiotipus_display($record) . '</a></li>';
}
echo '</ol>';

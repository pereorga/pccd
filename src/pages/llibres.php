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

set_page_title('Llibres de Víctor Pàmies');
set_meta_description("Llibres de l'autor de la Paremiologia catalana comparada digital (PCCD).");

$stmt = get_db()->query('SELECT `Imatge`, `Títol`, `URL`, `WIDTH`, `HEIGHT` FROM `00_OBRESVPR`');
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo '<div class="books">';
foreach ($records as $record) {
    // TODO: FIXME in the DB.
    if ($record['URL'] === 'https://lafinestralectora.cat/els-100-refranys-mes-populars-2/') {
        $record['URL'] = 'https://lafinestralectora.cat/els-100-refranys-mes-populars/';
    }

    if ($record['URL'] !== null) {
        echo '<a href="' . $record['URL'] . '" title="' . htmlspecialchars($record['Títol']) . '">';
    }
    echo get_image_tags($record['Imatge'], '/img/obres/', $record['Títol'], $record['WIDTH'], $record['HEIGHT'], false);
    if ($record['URL'] !== null) {
        echo '</a>';
    }
}
echo '</div>';

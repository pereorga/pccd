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

// Author's books page.

$pdo = get_db();

set_page_title('Llibres de Víctor Pàmies');
set_meta_description("Llibres de l'autor de la Paremiologia catalana comparada digital (PCCD).");

$stmt = $pdo->query('SELECT Imatge, `Títol`, URL, WIDTH, HEIGHT FROM `00_OBRESVPR`');
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo '<div class="llibres">';
foreach ($records as $o) {
    echo '<a href="' . $o['URL'] . '" title="' . htmlspecialchars($o['Títol']) . '">';
    echo get_image_tags($o['Imatge'], '/img/obres/', $o['Títol'], $o['WIDTH'], $o['HEIGHT'], false);
    echo '</a>';
}
echo '</div>';

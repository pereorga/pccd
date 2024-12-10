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

set_page_title('Fonts bibliogràfiques');
set_meta_description('Llista de les fonts bibliogràfiques disponibles a la Paremiologia catalana comparada digital.');

$stmt = get_db()->query('SELECT `Autor`, `Any`, `Títol`, `Registres`, `Varietat_dialectal`, `Identificador` FROM `00_FONTS`');
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo '<table id="fonts">';
echo '<thead><tr><th scope="col">Autor</th><th scope="col">Any</th><th scope="col">Títol</th><th scope="col" class="registres">Registres</th><th scope="col" class="varietat">Varietat dialectal</th></tr></thead>';
echo '<tbody>';
foreach ($records as $r) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($r['Autor'] ?? '') . '</td>';
    echo '<td>' . $r['Any'] . '</td>';
    echo '<td><a href="' . get_obra_url($r['Identificador'] ?? '') . '">' . htmlspecialchars($r['Títol'] ?? '') . '</a></td>';
    echo '<td>' . $r['Registres'] . '</td>';
    echo '<td>' . htmlspecialchars($r['Varietat_dialectal'] ?? '') . '</td>';
    echo '</tr>';
}
echo '</tbody>';
echo '</table>';

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

function test_dsff(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Frases del DSFF sense modisme a la base de dades</h3>';
    echo "<i>La versió digital i en paper del DSFF, la que es devia buidar a la PCCD en el seu moment, es va publicar el 2004. La segona edició del DSFF no contenia cap canvi respecte a la primera; simplement es va etiquetar així per motius de màrqueting. La pàgina <a href=//dsff.uab.cat>https://dsff.uab.cat</a>, llançada el 2018, ja incorporava prop d'un miler de correccions en comparació amb l'edició de 2004. Aquest informe recull frases d'aquests canvis i inclou també contingut de la futura versió de <a href=//dsff.uab.cat>https://dsff.uab.cat</a> que s'està desenvolupant. La futura versió del DSFF, que encara no és pública, contindrà un buidatge selectiu del DCVB, així com correccions diverses. L'informe és per tant preliminar però pot ajudar a identificar omissions, problemes i errors de picatge tant a la PCCD com al DSFF.</i><br>";

    $modismes = get_db()->query('SELECT DISTINCT `MODISME`, 1 FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_KEY_PAIR);
    $json_content = file_get_contents(__DIR__ . '/dsff_v3beta.txt');
    $json_content = is_string($json_content) ? $json_content : '';

    $titles = [];
    $data = json_decode($json_content, true);
    assert(is_array($data));
    foreach ($data as $item) {
        assert(is_array($item));
        assert(is_string($item['title']));
        $title = mb_ucfirst($item['title']);
        if (!isset($modismes[$title])) {
            $titles[$title] = $item['definition'];
        }
    }
    ksort($titles);

    echo '<p>Modismes únics PCCD: ' . format_nombre(count($modismes)) . '<br>';
    echo 'Frases úniques DSFF v3 beta: ' . format_nombre(count($data)) . '<br>';
    echo 'Frases DSFF que no existeixen a PCCD: ' . format_nombre(count($titles)) . '</p>';

    echo '<table border="1">';
    echo '<tr><th>Modisme</th><th>Font DSFF</th></tr>';
    foreach ($titles as $title => $definition) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($title) . '</td>';
        echo '<td>' . htmlspecialchars($definition) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

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
 * Installation script.
 *
 * This file is called by install.sh script.
 */

ini_set('memory_limit', '1024M');

require __DIR__ . '/../src/common.php';

require __DIR__ . '/../src/install_common.php';

$pdo = get_db();

// Check that latest table has already been created to know if we are ready to proceed with the installation process.
if (!tableExists('paremiotipus_display')) {
    echo "Not ready to install\n";

    exit;
}

// Check that installation has not been executed already.
if (tableExists('pccd_is_installed')) {
    echo "Already installed\n";

    exit;
}

echo date('[H:i:s]') . ' standarizing quotes...' . "\n";
$pdo->exec("UPDATE 00_PAREMIOTIPUS SET MODISME = REPLACE(REPLACE(REPLACE(REPLACE(MODISME, '´', '\\''), '`', '\\''), '’', '\\''), '‘', '\\''), PAREMIOTIPUS = REPLACE(REPLACE(REPLACE(REPLACE(PAREMIOTIPUS, '´', '\\''), '`', '\\''), '’', '\\''), '‘', '\\'')");
$pdo->exec("UPDATE 00_IMATGES SET PAREMIOTIPUS = REPLACE(REPLACE(REPLACE(REPLACE(PAREMIOTIPUS, '´', '\\''), '`', '\\''), '’', '\\''), '‘', '\\'')");

echo date('[H:i:s]') . ' generating paremiotipus display value...' . "\n";
$insert_display_stmt = $pdo->prepare('INSERT IGNORE INTO paremiotipus_display(Paremiotipus, Display) VALUES(?, ?)');
$paremiotipus = $pdo->query('SELECT DISTINCT PAREMIOTIPUS FROM 00_PAREMIOTIPUS')->fetchAll(PDO::FETCH_COLUMN);
foreach ($paremiotipus as $p) {
    $insert_display_stmt->execute([clean_paremiotipus_for_sorting($p), $p]);
}

echo date('[H:i:s]') . ' creating search tables...' . "\n";
$add_accepcio_stmt = $pdo->prepare('UPDATE 00_PAREMIOTIPUS SET MODISME = ?, ACCEPCIO = ? WHERE Id = ?');
$normalize_stmt = $pdo->prepare('UPDATE 00_PAREMIOTIPUS SET PAREMIOTIPUS_LC_WA = ?, MODISME_LC_WA = ?, SINONIM_LC_WA = ?, EQUIVALENT_LC_WA = ? WHERE Id = ?');
$improve_sorting_stmt = $pdo->prepare('UPDATE 00_PAREMIOTIPUS SET PAREMIOTIPUS = ? WHERE Id = ?');

$paremies = $pdo->query('SELECT Id, PAREMIOTIPUS, MODISME, SINONIM, EQUIVALENT FROM 00_PAREMIOTIPUS')->fetchAll(PDO::FETCH_ASSOC);
foreach ($paremies as $p) {
    // Try to clean names ending with numbers and fill ACCEPCIO field.
    if ($p['MODISME'] !== null) {
        $modisme = trim($p['MODISME']);
        if (preg_match_all('/ ([1-4])$/', $modisme, $matches) > 0) {
            $last_number = trim(end($matches[0]));
            $modisme = rtrim($modisme, "{$last_number} \n\r\t\v\x00");
            $add_accepcio_stmt->execute([$modisme, $last_number, $p['Id']]);
        }
    }

    // Normalize strings for searching.
    $args = [
        normalize_search($p['PAREMIOTIPUS']),
        normalize_search($p['MODISME']),
        normalize_search($p['SINONIM']),
        normalize_search($p['EQUIVALENT']),
        $p['Id'],
    ];
    $normalize_stmt->execute($args);

    try {
        // Clean `—` and other characters from the beginning, to improve sorting.
        $improve_sorting_stmt->execute([clean_paremiotipus_for_sorting($p['PAREMIOTIPUS']), $p['Id']]);
    } catch (Exception $e) {
        echo 'Paremiotipus: ' . $p['PAREMIOTIPUS'] . "\n";
        echo 'Paremiotipus processat: ' . clean_paremiotipus_for_sorting($p['PAREMIOTIPUS']) . "\n";
        echo 'Id: ' . $p['Id'] . "\n";
        error_log('Error: ' . $e->getMessage());
    }
}

echo date('[H:i:s]') . ' normalizing paremiotipus in images table...' . "\n";
$normalize_paremiotipus_images_stmt = $pdo->prepare('UPDATE 00_IMATGES SET PAREMIOTIPUS = ? WHERE Comptador = ?');
$images = $pdo->query('SELECT Comptador, PAREMIOTIPUS FROM 00_IMATGES')->fetchAll(PDO::FETCH_ASSOC);
foreach ($images as $image) {
    $normalize_paremiotipus_images_stmt->execute([clean_paremiotipus_for_sorting($image['PAREMIOTIPUS'] ?? ''), $image['Comptador']]);
}

echo date('[H:i:s]') . ' importing top 10000 paremiotipus...' . "\n";
$insert_stmt = $pdo->prepare('INSERT INTO common_paremiotipus(Paremiotipus, Compt) VALUES(?, ?)');
$records = $pdo->query('SELECT
        PAREMIOTIPUS,
        COUNT(*) AS POPULAR
    FROM
        00_PAREMIOTIPUS
    GROUP BY
        PAREMIOTIPUS
    ORDER BY
        POPULAR DESC
    LIMIT ' . MAX_RANDOM_PAREMIOTIPUS)->fetchAll(PDO::FETCH_KEY_PAIR);

foreach ($records as $title => $popular) {
    $insert_stmt->execute([$title, $popular]);
}

echo date('[H:i:s]') . ' storing image dimensions...' . "\n";
store_image_dimensions('00_IMATGES', 'Identificador', 'docroot/img/imatges');
store_image_dimensions('00_FONTS', 'Imatge', 'docroot/img/obres');
store_image_dimensions('00_OBRESVPR', 'Imatge', 'docroot/img/obres');

echo date('[H:i:s]') . ' database installation has finished!' . "\n";
$pdo->exec('CREATE TABLE pccd_is_installed(id int)');

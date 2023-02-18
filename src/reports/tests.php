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

// Report generation.

// TODO:
// Search for weird characters e.g. â

/**
 * @suppress PhanUndeclaredClassMethod
 *
 * @psalm-suppress UndefinedClass, RawObjectIteration
 */
function test_searches(): void
{
    if (function_exists('apcu_enabled') && apcu_enabled()) {
        echo "<h3>Cerques úniques (des de l'últim desplegament)</h3>";
        $records = [];
        foreach (new APCUIterator() as $entry) {
            if (
                is_array($entry)
                && is_string($entry['key'])
                && str_starts_with($entry['key'], ' WHERE')
            ) {
                $key = $entry['key'];
                $last_par = mb_strrpos($key, '|');
                if ($last_par === false) {
                    $last_par = mb_strrpos($key, ')');
                }
                if ($last_par !== false) {
                    $key = mb_substr($key, $last_par + 1);
                }

                $key = str_replace(['+', '.'], ['', '?'], $key);
                $key = trim($key);

                if (!isset($records[$key])) {
                    $records[$key] = $entry['value'];
                }
            }
        }

        echo '<p>Total: ' . count($records) . '</p>';
        echo '<pre>';
        asort($records, \SORT_NUMERIC);
        foreach ($records as $key => $value) {
            echo "{$key} ({$value} resultats)\n";
        }
        echo '</pre>';
    } else {
        echo '<strong>Error: APCu is not enabled</strong>';
    }
}

function test_imatges_paremiotipus(): void
{
    global $pdo;

    echo '<h3>Paremiotipus de la taula 00_IMATGES que no concorda amb cap registre de la taula 00_PAREMIOTIPUS</h3>';
    echo '<pre>';
    $stmt = $pdo->query('SELECT DISTINCT PAREMIOTIPUS FROM 00_IMATGES WHERE PAREMIOTIPUS NOT IN (SELECT PAREMIOTIPUS FROM 00_PAREMIOTIPUS)');
    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($images as $img) {
        echo $img . "\n";
    }
    echo '</pre>';
}

function test_imatges_extensio(): void
{
    echo '<h3>Imatges amb una extensió incorrecta</h3>';
    echo '<pre>';
    echo file_get_contents(__DIR__ . '/../../tmp/test_imatges_extensions.txt');
    echo '</pre>';
}

function test_imatges_format(): void
{
    echo '<h3>Imatges massa petites (menys de 350 píxels d\'amplada)</h3>';
    echo '<i>Si fos possible, haurien de ser de 500 px o més.</i>';
    echo '<pre>';
    echo file_get_contents(__DIR__ . '/../../tmp/test_imatges_petites.txt');
    echo '</pre>';

    echo '<h3>Imatges amb problemes de format (avançat)</h3>';
    echo '<pre>';
    $text = file_get_contents(__DIR__ . '/../../tmp/test_imatges_format.txt');
    if ($text !== false) {
        echo str_replace('../src/images/', '', $text);
    }
    echo '</pre>';
}

function test_imatges_no_reconegudes(): void
{
    global $pdo;

    echo '<h3>Imatges amb extensió no estàndard (no jpg/png/gif) o en majúscules</h3>';
    echo '<pre>';
    $stmt = $pdo->query('SELECT Imatge FROM 00_FONTS');
    $imatges = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($imatges as $i) {
        if ($i !== null && !str_ends_with($i, '.jpg') && !str_ends_with($i, '.png') && !str_ends_with($i, '.gif')) {
            echo 'cobertes/' . $i . "\n";
        }
    }
    $stmt = $pdo->query('SELECT Identificador FROM 00_IMATGES');
    $imatges = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($imatges as $i) {
        if ($i !== null && !str_ends_with($i, '.jpg') && !str_ends_with($i, '.png') && !str_ends_with($i, '.gif')) {
            echo 'paremies/' . $i . "\n";
        }
    }
    echo '</pre>';

    echo '<h3>Imatges que no s\'ha pogut detectar la seva mida</h3>';
    echo '<pre>';
    $stmt = $pdo->query('SELECT Imatge, WIDTH, HEIGHT FROM 00_FONTS');
    $imatges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($imatges as $i) {
        if ($i['Imatge'] !== null && ($i['WIDTH'] === 0 || $i['HEIGHT'] === 0)) {
            echo 'cobertes/' . $i['Imatge'] . "\n";
        }
    }
    $stmt = $pdo->query('SELECT Identificador, WIDTH, HEIGHT FROM 00_IMATGES');
    $imatges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($imatges as $i) {
        if ($i['Identificador'] !== null && ($i['WIDTH'] === 0 || $i['HEIGHT'] === 0)) {
            echo 'paremies/' . $i['Identificador'] . "\n";
        }
    }
    echo '</pre>';
}

function test_imatges_buides(): void
{
    global $pdo;

    echo '<h3>Fonts sense imatge</h3>';
    echo '<pre>';
    $stmt = $pdo->query('SELECT Imatge, Identificador FROM 00_FONTS');
    $imatges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $n = 0;
    $output = '';
    foreach ($imatges as $i) {
        if (!is_string($i['Imatge']) || strlen($i['Imatge']) < 5) {
            $output .= $i['Identificador'] . \PHP_EOL;
            $n++;
        }
    }
    if ($n > 0) {
        echo "{$n} camps 'Imatge' buits a la taula 00_FONTS:" . \PHP_EOL;
        echo $output . \PHP_EOL;
    }

    $stmt = $pdo->query('SELECT Identificador FROM 00_IMATGES');
    $imatges = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $n = 0;
    foreach ($imatges as $i) {
        if ($i === null) {
            $n++;
        }
    }
    if ($n > 0) {
        echo "{$n} camps 'Identificador' buits a la taula 00_IMATGES";
    }
    echo '</pre>';
}

function test_imatges_no_existents(): void
{
    global $pdo;

    echo "<h3>Fitxers d'imatge no existents</h3>";
    echo '<pre>';
    $stmt = $pdo->query('SELECT Imatge FROM 00_FONTS');
    $imatges = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($imatges as $i) {
        if ($i !== null && !is_file(__DIR__ . '/../../docroot/img/obres/' . $i)) {
            echo 'cobertes/' . $i . "\n";
        }
    }
    $stmt = $pdo->query('SELECT Identificador FROM 00_IMATGES');
    $imatges = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($imatges as $i) {
        if ($i !== null && !is_file(__DIR__ . '/../../docroot/img/imatges/' . $i)) {
            echo 'paremies/' . $i . "\n";
        }
    }
    echo '</pre>';
}

function test_imatges_repetides(): void
{
    global $pdo;

    echo '<h3>Identificador repetit a la taula 00_IMATGES</h3>';
    echo '<pre>';
    $stmt = $pdo->query('SELECT Identificador FROM 00_IMATGES');
    $imatges = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // See https://stackoverflow.com/a/5995153/1391963
    $repetides = array_unique(array_diff_assoc($imatges, array_unique($imatges)));
    foreach ($repetides as $r) {
        echo $r . "\n";
    }
    echo '</pre>';

    echo '<h3>Fitxers duplicats (amb diferent nom)</h3>';
    echo '<pre>';
    $files = [];
    $dir = new DirectoryIterator(__DIR__ . '/../../docroot/img/imatges/');
    foreach ($dir as $fileinfo) {
        if (!$fileinfo->isDot()) {
            $filename = $fileinfo->getFilename();
            if (!str_ends_with($filename, '.webp') && !str_ends_with($filename, '.avif')) {
                $files[$filename] = hash_file('xxh3', $fileinfo->getPathname());
            }
        }
    }
    // See https://stackoverflow.com/a/5995153/1391963
    $files_uniq = array_unique($files);
    $repetits = array_diff($files, array_diff($files_uniq, array_diff_assoc($files, $files_uniq)));
    asort($repetits);
    $prev = '';
    foreach ($repetits as $key => $value) {
        if ($prev !== $value) {
            echo "\n";
        }
        echo "{$key}\n";
        $prev = $value;
    }
    echo '</pre>';
}

function test_imatges_no_referenciades(): void
{
    global $pdo;

    $images = $pdo->query('SELECT Identificador, 1 FROM 00_IMATGES')->fetchAll(PDO::FETCH_KEY_PAIR);
    echo "<h3>Fitxers d'imatge no referenciats a la taula 00_IMATGES (o amb una codificació diferent)</h3>";
    echo '<pre>';
    $dir = new DirectoryIterator(__DIR__ . '/../../docroot/img/imatges/');
    foreach ($dir as $fileinfo) {
        $filename = $fileinfo->getFilename();
        if ($filename === '.' || $filename === '..' || str_ends_with($filename, '.webp') || str_ends_with($filename, '.avif')) {
            continue;
        }
        if (!isset($images[$filename])) {
            echo "{$filename}\n";
        }
    }
    echo '</pre>';

    $fonts = $pdo->query('SELECT Imatge, 1 FROM 00_FONTS')->fetchAll(PDO::FETCH_KEY_PAIR);
    echo "<h3>Fitxers d'imatge no referenciats a la taula 00_FONTS (o amb una codificació diferent)</h3>";
    echo '<pre>';
    $dir = new DirectoryIterator(__DIR__ . '/../../docroot/img/obres/');
    foreach ($dir as $fileinfo) {
        $filename = $fileinfo->getFilename();
        if ($filename === '.' || $filename === '..' || str_ends_with($filename, '.webp') || str_ends_with($filename, '.avif')) {
            continue;
        }
        if (!isset($fonts[$filename])) {
            echo "{$filename}\n";
        }
    }
    echo '</pre>';
}

function test_obres_sense_paremia(): void
{
    global $pdo;

    $fonts = get_fonts();
    $fonts_modismes = $pdo->query('SELECT DISTINCT ID_FONT, 1 FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_KEY_PAIR);

    echo '<h3>Obres de la taula 00_FONTS que no estan referenciades per cap parèmia</h3>';
    echo '<div style="font-size: 13px;">';
    foreach ($fonts as $identificador => $title) {
        if (!isset($fonts_modismes[$identificador])) {
            echo '<a href="' . get_obra_url($identificador) . '">' . $title . '</a><br>';
        }
    }
    echo '</div>';
}

function test_paremies_sense_obra_existent(): void
{
    global $pdo;

    $fonts = get_fonts();
    $paremies = $pdo->query('SELECT MODISME, ID_FONT FROM `00_PAREMIOTIPUS` ORDER BY ID_FONT')->fetchAll(PDO::FETCH_ASSOC);

    echo '<h3>Parèmies que tenen obra, però que aquesta no es troba a la taula 00_FONTS</h3>';
    echo '<pre>';
    $prev = '';
    foreach ($paremies as $paremia) {
        if ($paremia['ID_FONT'] !== null && !isset($fonts[$paremia['ID_FONT']])) {
            if ($prev !== $paremia['ID_FONT']) {
                echo "\n\n" . $paremia['ID_FONT'] . ':';
            }
            echo "\n    " . $paremia['MODISME'];
            $prev = $paremia['ID_FONT'];
        }
    }
    echo '</pre>';
}

function test_urls(): void
{
    echo '<h3>Valors URL que retornen diferent de HTTP 200/301/302/307 a la taula 00_OBRESVPR</h3>';
    echo '<pre>';
    echo file_get_contents(__DIR__ . '/../../tmp/test_llibres_URL.txt');
    echo '</pre>';

    echo '<h3>Valors URL que retornen diferent de HTTP 200/301/302/307 a la taula 00_FONTS</h3>';
    echo '<pre>';
    echo file_get_contents(__DIR__ . '/../../tmp/test_fonts_URL.txt');
    echo '</pre>';

    echo '<h3>Valors URL_IMATGE que retornen diferent de HTTP 200/301/302/307 a la taula 00_IMATGES</h3>';
    echo '<pre>';
    echo file_get_contents(__DIR__ . '/../../tmp/test_imatges_URL_IMATGE.txt');
    echo '</pre>';

    echo '<h3>Valors URL_ENLLAÇ que retornen diferent de HTTP 200/301/302/307 a la taula 00_IMATGES</h3>';
    echo '<pre>';
    echo file_get_contents(__DIR__ . '/../../tmp/test_imatges_URL_ENLLAC.txt');
    echo '</pre>';
}

function test_fonts(): void
{
    global $pdo;
    echo '<h3>Registres a la taula 00_FONTS amb el camp Títol buit.</h3>';
    $records = $pdo->query("SELECT Identificador FROM 00_FONTS WHERE `Títol` IS NULL OR `Títol` = ''")->fetchAll(PDO::FETCH_COLUMN);
    echo '<pre>';
    foreach ($records as $r) {
        echo "{$r}\n";
    }
    echo '</pre>';

    echo '<h3>Pàgines de paremiotipus que tenen parèmies amb 0 fonts.</h3>';
    $lines = file(__DIR__ . '/../../tmp/test_zero_fonts.txt');
    if (is_array($lines)) {
        foreach ($lines as $line) {
            if (str_contains($line, 'http')) {
                $url = str_replace('http://localhost:8092/', 'https://pccd.dites.cat/', $line);
                echo "<a href='{$url}'>{$url}</a><br>\n";
            } else {
                echo $line . "<br>\n";
            }
        }
    }
}

function test_espais(): void
{
    global $pdo;

    echo '<h3>Paremiotipus que comencen o acaben amb espai en blanc</h3>';
    echo '<pre>';
    $modismes = $pdo->query('SELECT PAREMIOTIPUS FROM 00_PAREMIOTIPUS WHERE CHAR_LENGTH(PAREMIOTIPUS) != CHAR_LENGTH(TRIM(PAREMIOTIPUS))')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus que contenen 2 espais seguits</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT PAREMIOTIPUS FROM 00_PAREMIOTIPUS WHERE PAREMIOTIPUS LIKE '%  %'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus que contenen salts de línia</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT PAREMIOTIPUS FROM 00_PAREMIOTIPUS WHERE PAREMIOTIPUS LIKE '%\\n%' OR PAREMIOTIPUS LIKE '%\\r%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus que contenen el caràcter tabulador</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT PAREMIOTIPUS FROM 00_PAREMIOTIPUS WHERE PAREMIOTIPUS LIKE '%\\t%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb espais i parèntesis/claudàtors mal posats</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT PAREMIOTIPUS FROM 00_PAREMIOTIPUS WHERE PAREMIOTIPUS LIKE '% )%' OR PAREMIOTIPUS LIKE '%( %' OR PAREMIOTIPUS LIKE '% ]%' OR PAREMIOTIPUS LIKE '%[ %'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes que comencen o acaben amb espai en blanc</h3>';
    echo '<pre>';
    $modismes = $pdo->query('SELECT MODISME FROM 00_PAREMIOTIPUS WHERE CHAR_LENGTH(MODISME) != CHAR_LENGTH(TRIM(MODISME))')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes que contenen 2 espais seguits</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT MODISME FROM 00_PAREMIOTIPUS WHERE MODISME LIKE '%  %'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes que contenen salts de línia</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT MODISME FROM 00_PAREMIOTIPUS WHERE MODISME LIKE '%\\n%' OR MODISME LIKE '%\\r%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes que contenen el caràcter tabulador</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT MODISME FROM 00_PAREMIOTIPUS WHERE MODISME LIKE '%\\t%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb espais i parèntesis/claudàtors mal posats</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT MODISME FROM 00_PAREMIOTIPUS WHERE MODISME LIKE '% )%' OR MODISME LIKE '%( %' OR MODISME LIKE '% ]%' OR MODISME LIKE '%[ %'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes que contenen salts de línia en el camp EXPLICACIO</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT MODISME FROM 00_PAREMIOTIPUS WHERE EXPLICACIO LIKE '%\\n%' OR EXPLICACIO LIKE '%\\r%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';
}

function test_puntuacio(): void
{
    global $pdo;

    echo '<h3>Paremiotipus amb parèntesis o claudàtors no tancats</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT PAREMIOTIPUS FROM 00_PAREMIOTIPUS WHERE LENGTH(REPLACE(PAREMIOTIPUS, '(', '')) != LENGTH(REPLACE(PAREMIOTIPUS, ')', '')) OR LENGTH(REPLACE(PAREMIOTIPUS, '[', '')) != LENGTH(REPLACE(PAREMIOTIPUS, ']', ''))")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb cometes no tancades</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT Display FROM paremiotipus_display WHERE (LENGTH(Display) - LENGTH(REPLACE(Display, '\"', ''))) % 2 != 0 OR LENGTH(REPLACE(Display, '«', '')) != LENGTH(REPLACE(Display, '»', '')) OR LENGTH(REPLACE(Display, '“', '')) != LENGTH(REPLACE(Display, '”', ''))")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb cometa simple seguida del caràcter espai o signe de puntuació inusual.</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT PAREMIOTIPUS FROM 00_PAREMIOTIPUS WHERE (PAREMIOTIPUS LIKE '%\\' %' OR PAREMIOTIPUS LIKE '%\\'.%' OR PAREMIOTIPUS LIKE '%\\',%' OR PAREMIOTIPUS LIKE '%\\';%' OR PAREMIOTIPUS LIKE '%\\':%' OR PAREMIOTIPUS LIKE '%\\'-%') AND (LENGTH(PAREMIOTIPUS) - LENGTH(REPLACE(PAREMIOTIPUS, '\\'', ''))) = 1")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb el caràcter espai seguit de signe de puntuació inusual</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT PAREMIOTIPUS FROM 00_PAREMIOTIPUS WHERE (PAREMIOTIPUS LIKE '% .%' AND PAREMIOTIPUS NOT LIKE '% ...%') OR PAREMIOTIPUS LIKE '% ,%' OR PAREMIOTIPUS LIKE '% ;%' OR PAREMIOTIPUS LIKE '% :%' OR PAREMIOTIPUS LIKE '% !%' OR PAREMIOTIPUS LIKE '% ?%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus acabats amb signe de puntuació inusual</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT PAREMIOTIPUS FROM 00_PAREMIOTIPUS WHERE PAREMIOTIPUS LIKE '%,' OR PAREMIOTIPUS LIKE '%;' OR PAREMIOTIPUS LIKE '%:'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb signe de puntuació seguit de lletres</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT PAREMIOTIPUS FROM 00_PAREMIOTIPUS WHERE (PAREMIOTIPUS REGEXP BINARY ',[a-zA-Z]+') OR (PAREMIOTIPUS REGEXP BINARY '[.][a-zA-Z]+') OR (PAREMIOTIPUS REGEXP BINARY '[)][a-zA-Z]+') OR (PAREMIOTIPUS REGEXP BINARY '[?][a-zA-Z]+') OR (PAREMIOTIPUS REGEXP BINARY ':[a-zA-Z]+') OR (PAREMIOTIPUS REGEXP BINARY ';[a-zA-Z]+')")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb possible confusió del caràcter <code>l</code> amb <code>I</code></h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT MODISME FROM 00_PAREMIOTIPUS WHERE MODISME LIKE BINARY '%I\\'%' OR MODISME REGEXP BINARY '[a-z]+I'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb parèntesis o claudàtors no tancats</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT MODISME FROM 00_PAREMIOTIPUS WHERE LENGTH(REPLACE(MODISME, '(', '')) != LENGTH(REPLACE(MODISME, ')', '')) OR LENGTH(REPLACE(MODISME, '[', '')) != LENGTH(REPLACE(MODISME, ']', ''))")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb cometes no tancades</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT MODISME FROM 00_PAREMIOTIPUS WHERE (LENGTH(MODISME) - LENGTH(REPLACE(MODISME, '\"', ''))) % 2 != 0 OR LENGTH(REPLACE(MODISME, '«', '')) != LENGTH(REPLACE(MODISME, '»', '')) OR LENGTH(REPLACE(MODISME, '“', '')) != LENGTH(REPLACE(MODISME, '”', ''))")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb cometa simple seguida del caràcter espai o signe de puntuació inusual.</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT MODISME FROM 00_PAREMIOTIPUS WHERE (MODISME LIKE '%\\' %' OR MODISME LIKE '%\\'.%' OR MODISME LIKE '%\\',%' OR MODISME LIKE '%\\';%' OR MODISME LIKE '%\\':%' OR MODISME LIKE '%\\'-%') AND (LENGTH(MODISME) - LENGTH(REPLACE(MODISME, '\\'', ''))) = 1")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb el caràcter espai seguit de signe de puntuació inusual</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT MODISME FROM 00_PAREMIOTIPUS WHERE (MODISME LIKE '% .%' AND MODISME NOT LIKE '% ...%') OR MODISME LIKE '% ,%' OR MODISME LIKE '% ;%' OR MODISME LIKE '% :%' OR MODISME LIKE '% !%' OR MODISME LIKE '% ?%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes acabats amb signe de puntuació inusual</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT MODISME FROM 00_PAREMIOTIPUS WHERE MODISME LIKE '%,' OR MODISME LIKE '%;' OR MODISME LIKE '%:'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb signe de puntuació seguit de lletres</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT MODISME FROM 00_PAREMIOTIPUS WHERE (MODISME REGEXP BINARY ',[a-zA-Z]+') OR (MODISME REGEXP BINARY '[.][a-zA-Z]+') OR (MODISME REGEXP BINARY '[)][a-zA-Z]+') OR (MODISME REGEXP BINARY '[?][a-zA-Z]+') OR (MODISME REGEXP BINARY ':[a-zA-Z]+') OR (MODISME REGEXP BINARY ';[a-zA-Z]+')")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb una combinació de signes de puntuació inusual</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT MODISME FROM 00_PAREMIOTIPUS WHERE MODISME LIKE '%?¿%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT MODISME FROM 00_PAREMIOTIPUS WHERE MODISME LIKE '%,?%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT MODISME FROM 00_PAREMIOTIPUS WHERE MODISME LIKE '%,!'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';
}

function test_majuscules(): void
{
    global $pdo;

    echo '<h3>Paremiotipus que comencen amb lletra minúscula</h3>';
    echo '<pre>';
    $modismes = $pdo->query('SELECT PAREMIOTIPUS FROM 00_PAREMIOTIPUS WHERE BINARY UPPER(SUBSTRING(PAREMIOTIPUS, 1, 1)) != SUBSTRING(PAREMIOTIPUS, 1, 1)')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo "<h3>Paremiotipus que tenen una lletra minúscula seguida d'una lletra majúscula</h3>";
    echo '<pre>';
    $modismes = $pdo->query("SELECT PAREMIOTIPUS FROM 00_PAREMIOTIPUS WHERE PAREMIOTIPUS REGEXP BINARY '[a-z]+[A-Z]+'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus que acaben amb lletra majúscula</h3>';
    echo '<pre>';
    $modismes = $pdo->query('SELECT DISTINCT PAREMIOTIPUS FROM 00_PAREMIOTIPUS WHERE BINARY LOWER(SUBSTRING(PAREMIOTIPUS, -1)) != SUBSTRING(PAREMIOTIPUS, -1)')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes que comencen amb lletra minúscula</h3>';
    echo '<pre>';
    $modismes = $pdo->query('SELECT MODISME FROM 00_PAREMIOTIPUS WHERE BINARY UPPER(SUBSTRING(MODISME, 1, 1)) != SUBSTRING(MODISME, 1, 1)')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo "<h3>Modismes que tenen una lletra minúscula seguida d'una lletra majúscula</h3>";
    echo '<pre>';
    $modismes = $pdo->query("SELECT MODISME FROM 00_PAREMIOTIPUS WHERE MODISME REGEXP BINARY '[a-z]+[A-Z]+'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes que acaben amb lletra majúscula</h3>';
    echo '<pre>';
    $modismes = $pdo->query('SELECT MODISME FROM 00_PAREMIOTIPUS WHERE BINARY LOWER(SUBSTRING(MODISME, -1)) != SUBSTRING(MODISME, -1)')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';
}

function test_equivalents(): void
{
    global $pdo;

    echo "<h3>Modismes amb equivalents amb un codi d'idioma no detectat</h3>";
    echo '<pre>';
    $modismes = $pdo->query('SELECT MODISME, EQUIVALENT, IDIOMA FROM 00_PAREMIOTIPUS WHERE EQUIVALENT IS NOT NULL')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($modismes as $m) {
        if ($m['IDIOMA'] !== null && get_idioma($m['IDIOMA']) === '') {
            echo $m['MODISME'] . ' (codi idioma: ' . $m['IDIOMA'] . ', equivalent: ' . $m['EQUIVALENT'] . ")\n";
        }
    }
    echo '</pre>';

    echo '<h3>Modismes amb equivalents amb el camp idioma buit</h3>';
    echo '<pre>';
    $modismes = $pdo->query('SELECT MODISME, EQUIVALENT, IDIOMA FROM 00_PAREMIOTIPUS WHERE EQUIVALENT IS NOT NULL')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($modismes as $m) {
        if ($m['IDIOMA'] === null && $m['MODISME'] !== null && $m['EQUIVALENT'] !== null) {
            echo $m['MODISME'] . ' (equivalent ' . $m['EQUIVALENT'] . ")\n";
        }
    }
    echo '</pre>';
}

function test_explicacio(): void
{
    global $pdo;

    echo '<h3>Modismes amb el camp EXPLICACIO molt llarg però amb el camp EXPLICACIO2 buit i el camp EXEMPLES no buit</h3>';
    echo '<pre>';
    $modismes = $pdo->query('SELECT MODISME, EXPLICACIO, EXPLICACIO2, EXEMPLES FROM 00_PAREMIOTIPUS WHERE LENGTH(EXPLICACIO) > 250 AND (EXPLICACIO2 IS NULL OR LENGTH(EXPLICACIO) < 1) AND LENGTH(EXEMPLES) > 0')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($modismes as $m) {
        echo 'MODISME:' . $m['MODISME'] . "\n";
        echo 'EXPLICACIO:' . $m['EXPLICACIO'] . "\n";
        echo 'EXPLICACIO2:' . $m['EXPLICACIO2'] . "\n";
        echo 'EXEMPLES:' . $m['EXEMPLES'] . "\n";
        echo "\n";
    }
    echo '</pre>';
}

function test_paremiotipus_accents(): void
{
    global $pdo;

    echo '<h3>Paremiotipus amb diferències de majúscules o accents</h3>';
    $paremiotipus = $pdo->query('SELECT
        DISTINCT BINARY a.PAREMIOTIPUS
    FROM
        00_PAREMIOTIPUS a,
        00_PAREMIOTIPUS b
    WHERE
        a.PAREMIOTIPUS = b.PAREMIOTIPUS
    AND
        BINARY a.PAREMIOTIPUS != b.PAREMIOTIPUS
    ')->fetchAll(PDO::FETCH_COLUMN);

    echo '<pre>';
    foreach ($paremiotipus as $p) {
        echo $p . "\n";
    }
    echo '</pre>';
}

function test_paremiotipus_modismes_diferents(): void
{
    global $pdo;

    echo '<h3>Paremiotipus diferents que agrupen un mateix modisme</h3>';
    echo '<pre>';
    $paremiotipus = $pdo->query('SELECT a.PAREMIOTIPUS as PAREMIOTIPUS_A, a.MODISME as MODISME_A, b.PAREMIOTIPUS as PAREMIOTIPUS_B, b.MODISME as MODISME_B FROM 00_PAREMIOTIPUS a, 00_PAREMIOTIPUS b WHERE a.MODISME = b.MODISME AND a.PAREMIOTIPUS != b.PAREMIOTIPUS')->fetchAll(PDO::FETCH_ASSOC);
    $paremiotipus_unics = [];
    foreach ($paremiotipus as $m) {
        if (!isset($paremiotipus_unics[$m['PAREMIOTIPUS_A']]) && !isset($paremiotipus_unics[$m['PAREMIOTIPUS_B']])) {
            echo $m['PAREMIOTIPUS_A'] . ' (modisme: ' . $m['MODISME_A'] . ")\n";
            echo $m['PAREMIOTIPUS_B'] . ' (modisme: ' . $m['MODISME_B'] . ")\n\n";
        }

        $paremiotipus_unics[$m['PAREMIOTIPUS_A']] = true;
        $paremiotipus_unics[$m['PAREMIOTIPUS_B']] = true;
    }
    echo '</pre>';
}

/**
 * @suppress PhanPluginUseReturnValueInternalKnown
 */
function test_paremiotipus_repetits(): void
{
    global $pdo;

    echo '<h3>Paremiotipus molt semblants</h3>';
    echo '<pre>';

    // Threshold.
    $similarity = 85;
    $similarity_long = 75;
    $prev = '';
    $modismes = $pdo->query('SELECT DISTINCT PAREMIOTIPUS FROM 00_PAREMIOTIPUS ORDER BY PAREMIOTIPUS')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        if ($prev !== '') {
            $string1 = strtolower((string) preg_replace('#[[:punct:]]#', '', substr($m, 32)));
            $string2 = strtolower((string) preg_replace('#[[:punct:]]#', '', substr($prev, 32)));

            similar_text($string1, $string2, $percent);
            if ($percent > $similarity || (strlen($string1) > 15 && $percent > $similarity_long)) {
                echo $prev . "\n" . $m . "\n\n";
            }
        }
        $prev = $m;
    }
    echo '</pre>';

    echo '<h3>Paremiotipus molt semblants (implementació alternativa)</h3>';
    echo '<pre>';
    echo file_get_contents(__DIR__ . '/../../tmp/test_repetits.txt');
    echo '</pre>';
}

function test_repeticio_caracters(): void
{
    global $pdo;

    echo '<h3>Paremiotipus amb una repetició de caràcters inusual</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT DISTINCT PAREMIOTIPUS FROM 00_PAREMIOTIPUS WHERE PAREMIOTIPUS LIKE '%lll%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT DISTINCT PAREMIOTIPUS FROM 00_PAREMIOTIPUS WHERE PAREMIOTIPUS LIKE '%sss%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT DISTINCT PAREMIOTIPUS FROM 00_PAREMIOTIPUS WHERE PAREMIOTIPUS LIKE '%vv%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT DISTINCT PAREMIOTIPUS FROM 00_PAREMIOTIPUS WHERE PAREMIOTIPUS LIKE '%ff%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT DISTINCT PAREMIOTIPUS FROM 00_PAREMIOTIPUS WHERE PAREMIOTIPUS LIKE '%hh%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT DISTINCT PAREMIOTIPUS FROM 00_PAREMIOTIPUS WHERE PAREMIOTIPUS LIKE '%tt%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT DISTINCT PAREMIOTIPUS FROM 00_PAREMIOTIPUS WHERE PAREMIOTIPUS LIKE '%\\'\\'%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT DISTINCT PAREMIOTIPUS FROM 00_PAREMIOTIPUS WHERE PAREMIOTIPUS LIKE '%,,%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT DISTINCT PAREMIOTIPUS FROM 00_PAREMIOTIPUS WHERE PAREMIOTIPUS LIKE '%;;%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT DISTINCT PAREMIOTIPUS FROM 00_PAREMIOTIPUS WHERE PAREMIOTIPUS LIKE '%¿¿%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb una repetició de caràcters inusual</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT MODISME FROM 00_PAREMIOTIPUS WHERE MODISME LIKE '%lll%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT MODISME FROM 00_PAREMIOTIPUS WHERE MODISME LIKE '%sss%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT MODISME FROM 00_PAREMIOTIPUS WHERE MODISME LIKE '%vv%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT MODISME FROM 00_PAREMIOTIPUS WHERE MODISME LIKE '%ff%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT MODISME FROM 00_PAREMIOTIPUS WHERE MODISME LIKE '%hh%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT MODISME FROM 00_PAREMIOTIPUS WHERE MODISME LIKE '%tt%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT MODISME FROM 00_PAREMIOTIPUS WHERE MODISME LIKE '%\\'\\'%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT MODISME FROM 00_PAREMIOTIPUS WHERE MODISME LIKE '%,,%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT MODISME FROM 00_PAREMIOTIPUS WHERE MODISME LIKE '%;;%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT MODISME FROM 00_PAREMIOTIPUS WHERE MODISME LIKE '%¿¿%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';
}

function test_paremiotipus_modismes_curts(): void
{
    global $pdo;

    echo '<h3>Paremiotipus de menys de 5 caràcters</h3>';
    echo '<pre>';
    $paremiotipus = $pdo->query('SELECT DISTINCT PAREMIOTIPUS FROM 00_PAREMIOTIPUS  WHERE CHAR_LENGTH(PAREMIOTIPUS) < 5 ORDER BY PAREMIOTIPUS')->fetchAll(PDO::FETCH_COLUMN);
    $existing = [];
    foreach ($paremiotipus as $m) {
        $existing[$m] = true;
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes de menys de 5 caràcters (no repetits a la llista anterior)</h3>';
    echo '<pre>';
    $modismes = $pdo->query('SELECT DISTINCT MODISME as MODISME FROM 00_PAREMIOTIPUS  WHERE CHAR_LENGTH(MODISME) < 5 ORDER BY MODISME')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        if (!isset($existing[$m])) {
            echo $m . "\n";
        }
    }
    echo '</pre>';
}

function test_paremiotipus_llargs(): void
{
    global $pdo;

    echo '<h3>Paremiotipus de més de 250 caràcters</h3>';
    echo '<pre>';
    $modismes = $pdo->query('SELECT DISTINCT PAREMIOTIPUS FROM 00_PAREMIOTIPUS  WHERE CHAR_LENGTH(PAREMIOTIPUS) > 250 ORDER BY PAREMIOTIPUS')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';
}

function test_paremies_separar(): void
{
    global $pdo;

    echo '<h3>Parèmies que probablement es poden separar en dues</h3>';

    $paremies = $pdo->query("SELECT DISTINCT MODISME FROM 00_PAREMIOTIPUS WHERE MODISME LIKE '%(o%' OR MODISME LIKE '%[o%' ORDER BY MODISME")->fetchAll(PDO::FETCH_COLUMN);
    $n = 0;
    $text = '';
    foreach ($paremies as $m) {
        $text .= $m . "\n";
        $n++;
    }
    echo "<p>Total: {$n}</p>";
    echo '<pre>';
    echo $text;
    echo '</pre>';
}

function is_valid_location(string $location): bool
{
    $location = trim($location);
    $location = rtrim($location, '.');
    $location = rtrim($location, ',');
    $location = rtrim($location, ';');
    if (mb_strlen($location) < 4) {
        return $location === 'Alp' || $location === 'Bot' || $location === 'Das'
            || $location === 'Ger' || $location === 'Pau' || $location === 'Vic'
            || $location === 'Cat' || $location === 'Bal' || $location === 'Men'
            || $location === 'Val';
    }
    if (preg_match('/\d/', $location) === 1) {
        return false;
    }
    if (preg_match('/ [a-z]{2}, [a-z]{2},/i', $location) === 1) {
        return false;
    }
    if (preg_match('/ [a-z]{3}, [a-z]{3},/i', $location) === 1) {
        return false;
    }

    return true;
}

function test_llocs(): void
{
    global $pdo;

    echo '<h3>Modismes amb el camp LLOC potser incorrecte</h3>';
    echo '<pre>';
    $modismes = $pdo->query('SELECT MODISME, LLOC FROM 00_PAREMIOTIPUS WHERE LLOC IS NOT NULL')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($modismes as $m) {
        if (!is_valid_location($m['LLOC'])) {
            echo 'MODISME:' . $m['MODISME'] . "\n";
            echo 'LLOC:' . $m['LLOC'] . "\n";
            echo "\n";
        }
    }
    echo '</pre>';
}

function test_obres_sense_editorial(): void
{
    global $pdo;

    $editorials = get_editorials();
    $editorials_modismes = $pdo->query('SELECT DISTINCT EDITORIAL AS EDITORIAL, 1 FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_KEY_PAIR);

    echo '<h3>Editorials de la taula 00_EDITORIA que no estan referenciades per cap obra</h3>';
    echo '<pre>';
    foreach ($editorials as $ed_codi => $ed_title) {
        if (!isset($editorials_modismes[$ed_codi])) {
            echo "{$ed_codi}: {$ed_title}\n";
        }
    }
    echo '</pre>';
}

function test_editorial_sense_obres(): void
{
    global $pdo;

    $editorials = get_editorials();
    $editorials_paremiotipus = $pdo->query('SELECT MODISME, EDITORIAL FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_ASSOC);

    echo '<h3>Editorials referenciades per parèmies que no es troben a la taula 00_EDITORIA</h3>';
    echo '<pre>';
    $missing = [];
    foreach ($editorials_paremiotipus as $ed_p) {
        if ($ed_p['EDITORIAL'] !== null && !isset($editorials[$ed_p['EDITORIAL']]) && !isset($missing[$ed_p['EDITORIAL']])) {
            echo $ed_p['EDITORIAL'] . ' (p. ex. en ' . $ed_p['MODISME'] . ")\n";
            $missing[$ed_p['EDITORIAL']] = true;
        }
    }
    echo '</pre>';
}

function test_buits(): void
{
    global $pdo;

    echo '<h3>Modismes amb el camp PAREMIOTIPUS buit</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT MODISME FROM `00_PAREMIOTIPUS` where PAREMIOTIPUS = '' OR PAREMIOTIPUS IS NULL")->fetchAll(PDO::FETCH_COLUMN);
    if ($modismes === []) {
        echo '(cap resultat)';
    }
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb el camp MODISME buit</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT PAREMIOTIPUS FROM `00_PAREMIOTIPUS` where MODISME = '' OR MODISME IS NULL")->fetchAll(PDO::FETCH_COLUMN);
    if ($modismes === []) {
        echo '(cap resultat)';
    }
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';
}

function test_commonvoice_languagetool(): void
{
    echo '<h3>Paremiotipus exclosos de Common Voice amb LanguageTool</h3>';
    $text = file_get_contents(__DIR__ . '/../../scripts/common-voice-export/excluded.txt');
    if ($text !== false) {
        echo '<i>Pot ser a causa d\'errors tipogràfics, ortogràfics, per incloure paraules malsonants, localismes, o ser falsos positius.</i>';
        echo '<p>Total: ' . substr_count($text, "\n") . '</p>';
        echo "<pre>{$text}</pre>";
    }
}

/**
 * Gets the response code for a URL.
 */
function curl_get_response_code(string $url, bool $nobody = true): int
{
    /** @var non-empty-string $url */
    $response_code = 0;
    static $ch = null;
    if ($ch === null) {
        $ch = curl_init();

        /** @var CurlHandle|false $ch */
        if ($ch !== false) {
            curl_setopt($ch, \CURLOPT_HEADER, true);
            curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, \CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, \CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, \CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36');
        }
    }
    if ($ch !== false) {
        curl_setopt($ch, \CURLOPT_URL, $url);
        curl_setopt($ch, \CURLOPT_NOBODY, $nobody);
        if (curl_exec($ch) === false) {
            return 0;
        }

        $response_code = curl_getinfo($ch, \CURLINFO_HTTP_CODE);
    }

    return $response_code;
}

function background_test_llibres_urls(): string
{
    global $pdo;
    $output = '';
    $urls = [];

    $llibres = $pdo->query('SELECT * FROM `00_OBRESVPR`')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($llibres as $llibre) {
        $url = trim($llibre['URL']);
        if ($url !== '') {
            if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $output .= 'URL no vàlida (Identificador ' . $llibre['Títol'] . '): ' . $url . "\n";
            } elseif (!isset($urls[$url])) {
                $response_code = curl_get_response_code($url, false);
                $urls[$url] = $response_code;
                if ($response_code !== 200 && $response_code !== 301 && $response_code !== 302 && $response_code !== 307) {
                    $output .= 'HTTP ' . $response_code . ' (' . $llibre['Títol'] . '): ' . $url . "\n";
                }
            }
        }
    }

    return $output;
}

function background_test_fonts_urls(): string
{
    global $pdo;
    $output = '';
    $urls = [];

    $fonts = $pdo->query('SELECT * FROM `00_FONTS`')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($fonts as $font) {
        $url = is_string($font['URL']) ? trim($font['URL']) : '';
        if ($url !== '') {
            if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $output .= 'URL no vàlida (Identificador ' . $font['Identificador'] . '): ' . $url . "\n";
            } elseif (!isset($urls[$url])) {
                $response_code = curl_get_response_code($url);
                $urls[$url] = $response_code;
                if ($response_code !== 200 && $response_code !== 301 && $response_code !== 302 && $response_code !== 307) {
                    $output .= 'HTTP ' . $response_code . ' (Identificador ' . $font['Identificador'] . '): ' . $url . "\n";
                }
            }
        }
    }

    return $output;
}

function background_test_imatges_urls(): string
{
    global $pdo;
    $output = '';
    $urls = [];

    $fonts = $pdo->query('SELECT * FROM `00_IMATGES`')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($fonts as $font) {
        $url = is_string($font['URL_IMATGE']) ? trim($font['URL_IMATGE']) : '';
        if ($url !== '') {
            if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $output .= 'URL no vàlida (Identificador ' . $font['Identificador'] . '): ' . $url . "\n";
            } elseif (!isset($urls[$url])) {
                $response_code = curl_get_response_code($url);
                $urls[$url] = $response_code;
                if ($response_code !== 200 && $response_code !== 301 && $response_code !== 302 && $response_code !== 307) {
                    $output .= 'HTTP ' . $response_code . ' (Identificador ' . $font['Identificador'] . '): ' . $url . "\n";
                }
            }
        }
    }

    return $output;
}

function background_test_imatges_links(): string
{
    global $pdo;
    $output = '';
    $urls = [];

    $fonts = $pdo->query('SELECT * FROM `00_IMATGES`')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($fonts as $font) {
        $url = $font['URL_ENLLAÇ'];
        if (is_string($url) && $url !== '') {
            if (
                (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://'))
                || filter_var($url, \FILTER_SANITIZE_URL) !== $url
            ) {
                $output .= 'URL no vàlida o amb caràcters especials (Identificador ' . $font['Identificador'] . '): ' . $url . "\n";
            } elseif (!isset($urls[$url])) {
                $response_code = curl_get_response_code($url);
                $urls[$url] = $response_code;
                if ($response_code !== 200 && $response_code !== 301 && $response_code !== 302 && $response_code !== 307) {
                    $output .= 'HTTP ' . $response_code . ' (Identificador ' . $font['Identificador'] . '): ' . $url . "\n";
                }
            }
        }
    }

    return $output;
}

function background_test_paremiotipus_repetits(int $start = 0, int $end = 0): string
{
    global $pdo;
    $output = '';

    $stmt = $pdo->query('SELECT DISTINCT PAREMIOTIPUS AS PAREMIOTIPUS FROM 00_PAREMIOTIPUS ORDER BY PAREMIOTIPUS');
    $modismes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    /** @var int $total */
    $total = count($modismes);
    if ($end === 0) {
        $end = $total;
    }

    for ($i = $start; $i < $end; $i++) {
        $value1 = $modismes[$i];
        $length1 = strlen($value1);

        for ($u = $i + 1; $u < $total; $u++) {
            $value2 = $modismes[$u];
            $length2 = strlen($value2);

            if (abs($length1 - $length2) < 8) {
                $max_length = max($length1, $length2);
                $lev = levenshtein($value1, $value2);
                if (
                    $lev < 2
                    || ($lev === 2 && $max_length > 8)
                    || ($lev === 3 && $max_length > 12)
                    || ($lev === 4 && $max_length > 15)
                    || ($lev === 5 && $max_length > 25)
                    || ($lev === 6 && $max_length > 40)
                    || ($lev === 7 && $max_length > 60)
                ) {
                    $output .= $value1 . "\n" . $value2 . "\n\n";
                }
            }
        }
    }

    return $output;
}

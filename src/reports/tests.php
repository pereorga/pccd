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

const LEVENSHTEIN_SIMILARITY_THRESHOLD = 0.8;
const LEVENSHTEIN_MAX_DISTANCE = 12;

const SIMILAR_TEXT_THRESHOLD_1 = 85;
const SIMILAR_TEXT_THRESHOLD_2 = 75;
const SIMILAR_TEXT_MIN_LENGTH = 15;
const SIMILAR_TEXT_MAX_LENGTH = 32;

/**
 * Gets an array of all available test functions grouped by category.
 *
 * @return array<string, list<callable>>
 */
function get_test_functions(): array
{
    return [
        'cerques' => ['stats_searches'],
        'commonvoice_languagetool' => ['test_commonvoice_languagetool'],
        'compostos' => ['test_paremies_separar'],
        'dates' => [
            'test_fonts_any_erroni',
            'test_imatges_any_erroni',
            'test_paremies_any_erroni',
        ],
        'editorials' => [
            'test_editorials_no_existents',
            'test_editorials_no_referenciades',
        ],
        'equivalents' => ['test_equivalents'],
        'espais' => ['test_espais'],
        'fonts' => [
            'test_fonts_buides',
            'test_fonts_sense_paremia',
            'test_paremies_sense_font_existent',
            'test_fonts_zero',
        ],
        'imatges' => [
            'test_imatges_buides',
            'test_imatges_extensions',
            'test_imatges_no_existents',
            'test_imatges_no_reconegudes',
            'test_imatges_paremiotipus',
            'test_imatges_no_referenciades',
            'test_imatges_repetides',
            'test_imatges_camps_duplicats',
            'test_imatges_sense_paremiotipus',
            'test_imatges_format',
        ],
        'longitud' => [
            'test_buits',
            'test_paremiotipus_llargs',
            'test_paremiotipus_modismes_curts',
            'test_explicacions_curtes',
            'test_fonts_curtes',
        ],
        'majúscules' => ['test_majuscules'],
        'puntuació' => [
            'test_paremiotipus_caracters_inusuals',
            'test_paremiotipus_final',
            'test_puntuacio',
        ],
        'repeticions_caracters' => ['test_repeticio_caracters'],
        'repeticions_modismes' => ['test_modismes_repetits'],
        'repeticions_paremiotipus' => [
            'test_paremiotipus_accents',
            'test_paremiotipus_modismes_diferents',
            'test_paremiotipus_repetits',
        ],
        'sinonims' => ['test_sinonims'],
        'stats_editorials' => ['stats_editorials'],
        'stats_equivalents' => ['stats_equivalents'],
        'stats_llocs' => ['stats_llocs'],
        'stats_obres' => ['stats_obres'],
        'stats_paremiotipus' => ['stats_paremiotipus'],
        'urls' => ['test_urls'],
    ];
}

/**
 * Print a chart using Chart.js.
 *
 * @param array<int|string, int> $data An associative array where keys are strings (labels) and values are integers (data points).
 */
function getChart(string $chartId, string $type, array $data, string $label = '', string $x_title = '', string $y_title = '', string $style = ''): string
{
    $jsonLabels = json_encode(array_keys($data));
    $jsonValues = json_encode(array_values($data));
    $output = "<div style='position:relative;{$style}'><canvas id='{$chartId}' style='margin-bottom:3rem;'></canvas></div>";

    // Initialize scales options dynamically based on x_title and y_title
    $scalesOptions = '';
    if ($x_title !== '' || $y_title !== '') {
        $scalesOptions = 'scales: {';
        if ($x_title !== '') {
            $scalesOptions .= "x: {title: {display: true, text: '{$x_title}'}},";
        }
        if ($y_title !== '') {
            $scalesOptions .= "y: {title: {display: true, text: '{$y_title}'}},";
        }
        $scalesOptions .= '}';
    }

    return $output . "<script>
        Chart.defaults.color = '#fff';
        new Chart(document.getElementById('{$chartId}'), {
            type: '{$type}',
            data: {
                labels: {$jsonLabels},
                datasets: [{
                    label: '{$label}',
                    data: {$jsonValues},
                }]
            },
            options: {
                {$scalesOptions}
            }
        });
        </script>";
}

/**
 * Outputs cached searches.
 *
 * @psalm-suppress UndefinedClass, RawObjectIteration
 */
function stats_searches(): void
{
    if (function_exists('apcu_enabled') && apcu_enabled()) {
        echo "<h3>Cerques i nombre de resultats (des de l'últim desplegament)</h3>";
        $records = [];
        $records_assoc = [];
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
                    $records_assoc[$key] = [
                        'results' => $entry['value'],
                    ];
                }
            }
        }

        // Sort by number of records.
        asort($records, \SORT_NUMERIC);

        // Sort by key alphabetically, but only if values are equal.
        uksort($records, static function (string $key1, string $key2) use ($records): int {
            if ($records[$key1] === $records[$key2]) {
                return $key1 <=> $key2;
            }

            return 0;
        });

        echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
        $grouped_data = group_data_stats($records_assoc, 'results');
        echo getChart('myChart1', 'bar', $grouped_data, 'Cerques', x_title: 'Nombre de resultats', y_title: 'Nombre de cerques úniques', style: 'width:1000px;');

        echo 'Total de cerques úniques: ' . count($records);
        echo '<details><summary>Mostra la llista</summary>';
        echo "<table style='width:1000px;'>";
        echo '<tr><th>Cerca</th><th>Resultats</th></tr>';
        foreach ($records as $cerca => $resultats) {
            echo "<tr><td>{$cerca}</td><td>" . format_nombre($resultats) . '</td></tr>';
        }
        echo '</table>';
        echo '</details>';
    } else {
        echo '<strong>Error: APCu is not enabled</strong>';
    }
}

function test_imatges_paremiotipus(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus de la taula 00_IMATGES que no concorda amb cap registre de la taula 00_PAREMIOTIPUS</h3>';
    echo '<pre>';
    $stmt = get_db()->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_IMATGES` WHERE `PAREMIOTIPUS` NOT IN (SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS`)');
    $paremiotipus = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        echo $p . "\n";
    }
    echo '</pre>';
}

function test_imatges_extensions(): void
{
    echo "<h3>Fitxers d'imatge amb extensió o format inconsistents</h3>";
    echo '<pre>';
    readfile(__DIR__ . '/../../tmp/test_imatges_extensions.txt');
    echo '</pre>';

    echo "<h3>Fitxers d'imatge amb extensió no suportada, en majúscules o no estàndard (no jpg/png/gif)</h3>";
    echo '<pre>';
    readfile(__DIR__ . '/../../tmp/test_imatges_file_extensions.txt');
    echo '</pre>';
}

function test_imatges_format(): void
{
    echo '<h3>Imatges massa petites (menys de 350 píxels d\'amplada)</h3>';
    echo '<i>Si fos possible, haurien de ser de 500 px o més.</i>';
    echo '<details><pre>';
    readfile(__DIR__ . '/../../tmp/test_imatges_petites.txt');
    echo '</pre></details>';

    echo '<h3>Imatges amb possibles problemes de format</h3>';
    echo '<details><pre>';
    $text = file_get_contents(__DIR__ . '/../../tmp/test_imatges_format.txt');
    if ($text !== false) {
        echo str_replace('../src/images/', '', $text);
    }
    echo '</pre></details>';
}

function test_imatges_no_reconegudes(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Imatges a la BD amb extensió no estàndard (no jpg/png/gif) o en majúscules</h3>';
    echo '<pre>';
    $stmt = get_db()->query('SELECT `Imatge` FROM `00_FONTS`');
    $imatges = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($imatges as $i) {
        if ($i !== null && !str_ends_with($i, '.jpg') && !str_ends_with($i, '.png') && !str_ends_with($i, '.gif')) {
            echo 'cobertes/' . $i . "\n";
        }
    }
    $stmt = get_db()->query('SELECT `Identificador` FROM `00_IMATGES`');
    $imatges = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($imatges as $i) {
        if ($i !== null && !str_ends_with($i, '.jpg') && !str_ends_with($i, '.png') && !str_ends_with($i, '.gif')) {
            echo 'paremies/' . $i . "\n";
        }
    }
    echo '</pre>';

    echo '<h3>Imatges que no s\'ha pogut detectar la seva mida</h3>';
    echo '<pre>';
    $stmt = get_db()->query('SELECT `Imatge`, `WIDTH`, `HEIGHT` FROM `00_FONTS`');
    $imatges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($imatges as $i) {
        if ($i['Imatge'] !== null && ($i['WIDTH'] === 0 || $i['HEIGHT'] === 0)) {
            echo 'cobertes/' . $i['Imatge'] . "\n";
        }
    }
    $stmt = get_db()->query('SELECT `Identificador`, `WIDTH`, `HEIGHT` FROM `00_IMATGES`');
    $imatges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($imatges as $imatge) {
        if ($imatge['Identificador'] !== null && ($imatge['WIDTH'] === 0 || $imatge['HEIGHT'] === 0)) {
            echo 'paremies/' . $imatge['Identificador'] . "\n";
        }
    }
    echo '</pre>';
}

function test_imatges_sense_paremiotipus(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Camp PAREMIOTIPUS buit a la taula 00_IMATGES</h3>';
    echo '<details><pre>';
    $stmt = get_db()->query('SELECT `Identificador`, `PAREMIOTIPUS` FROM `00_IMATGES`');
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $result) {
        if (!is_string($result['PAREMIOTIPUS']) || strlen($result['PAREMIOTIPUS']) < 2) {
            echo $result['Identificador'] . "\n";
        }
    }
    echo '</pre></details>';
}

function test_imatges_buides(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Fonts sense imatge</h3>';
    $stmt = get_db()->query('SELECT `Imatge`, `Identificador` FROM `00_FONTS`');
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $n = 0;
    $output = '';
    foreach ($results as $r) {
        if (!is_string($r['Imatge']) || strlen($r['Imatge']) < 5) {
            $output .= $r['Identificador'] . "\n";
            $n++;
        }
    }
    if ($n > 0) {
        echo "{$n} camps 'Imatge' buits a la taula 00_FONTS:";
        echo '<pre>';
        echo $output . "\n";
        echo '</pre>';
    }

    $stmt = get_db()->query('SELECT `Identificador` FROM `00_IMATGES`');
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $n = 0;
    foreach ($results as $result) {
        if ($result === null) {
            $n++;
        }
    }
    if ($n > 0) {
        echo "{$n} camps 'Identificador' buits a la taula 00_IMATGES";
    }
}

function test_imatges_camps_duplicats(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus de la taula 00_IMATGES amb els mateixos camps URL_ENLLAÇ, AUTOR, DIARI i ARTICLE:</h3>';
    $stmt = get_db()->query('SELECT
        `PAREMIOTIPUS`,
        `URL_ENLLAÇ`,
        `AUTOR`,
        `DIARI`,
        `ARTICLE`,
        GROUP_CONCAT(`Identificador`)
    FROM
        `00_IMATGES`
    WHERE
        `PAREMIOTIPUS` IS NOT NULL AND `PAREMIOTIPUS` <> \'\'
        AND `URL_ENLLAÇ` IS NOT NULL
        AND `AUTOR` IS NOT NULL
        AND `DIARI` IS NOT NULL
        AND `ARTICLE` IS NOT NULL
    GROUP BY
        `PAREMIOTIPUS`,
        `URL_ENLLAÇ`,
        `AUTOR`,
        `DIARI`,
        `ARTICLE`
    HAVING
        COUNT(*) > 1');
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo '<ul>';
    $prev = '';
    foreach ($results as $r) {
        $paremiotipus = $r['PAREMIOTIPUS'];
        if ($prev !== $paremiotipus) {
            echo '<li><a href="' . get_paremiotipus_url($paremiotipus) . '">' . get_paremiotipus_display($paremiotipus) . '</a></li>';
        }
        $prev = $paremiotipus;
    }
    echo '</ul>';
}

function test_imatges_no_existents(): void
{
    echo "<h3>Fitxers d'imatge no existents</h3>";
    echo '<pre>';
    readfile(__DIR__ . '/../../tmp/test_imatges_no_existents.txt');
    echo '</pre>';
}

function test_imatges_duplicades(): void
{
    echo "<h3>Fitxers d'imatge duplicats</h3>";
    echo '<pre>';
    readfile(__DIR__ . '/../../tmp/test_imatges_duplicades.txt');
    echo '</pre>';
}

function test_imatges_no_referenciades(): void
{
    echo "<h3>Fitxers d'imatge no referenciats</h3>";
    echo '<pre>';
    readfile(__DIR__ . '/../../tmp/test_imatges_no_referenciades.txt');
    echo '</pre>';
}

function test_imatges_any_erroni(): void
{
    require_once __DIR__ . '/../common.php';

    echo "<h3>Imatges amb l'any probablement incorrecte</h3>";
    echo '<pre>';
    $stmt = get_db()->query('SELECT `Identificador`, `Any` FROM `00_IMATGES`');
    $imatges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($imatges as $imatge) {
        if (((int) $imatge['Any']) < 0 || ((int) $imatge['Any']) > (int) date('Y')) {
            echo 'paremies/' . $imatge['Identificador'] . ' (' . $imatge['Any'] . ")\n";
        }
    }
    echo '</pre>';
}

function test_imatges_repetides(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Identificador repetit a la taula 00_IMATGES</h3>';
    echo '<pre>';
    $stmt = get_db()->query('SELECT `Identificador` FROM `00_IMATGES`');
    $imatges = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // See https://stackoverflow.com/a/5995153/1391963
    $repetides = array_unique(array_diff_assoc($imatges, array_unique($imatges)));
    foreach ($repetides as $r) {
        echo $r . "\n";
    }
    echo '</pre>';

    echo '<h3>Número repetit a la taula 00_IMATGES</h3>';
    echo '<pre>';
    $stmt = get_db()->query('SELECT `Identificador` FROM `00_IMATGES` ORDER BY `Identificador`');
    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $prev = 0;
    $prev_image = '';
    $numbers = [];
    foreach ($images as $image) {
        // Extract number at the beginning of the string.
        assert(is_string($image));
        $number = (int) preg_replace('/^(\d+).*/', '$1', $image);
        if ($number === $prev) {
            echo $prev_image . "\n";
            echo $image . "\n\n";
        }
        $prev = $number;
        $prev_image = $image;
        $numbers[$number] = true;
    }
    echo '</pre>';

    echo '<h3>Números no fets servir a la taula 00_IMATGES</h3>';
    echo '<details><pre>';
    $keys = array_keys($numbers);
    assert($keys !== []);
    $max = max($keys);
    $string = '';
    for ($i = 1; $i <= $max; $i++) {
        if (!isset($numbers[$i])) {
            $string .= $i . "\n";
        }
    }
    echo $string;
    echo '</pre></details>';
}

/**
 * Group data for stats.
 *
 * @param array<string, array<string, int>> $data
 *
 * @return array<int|string, int>
 */
function group_data_stats(array $data, string $key): array
{
    $groups = [];
    foreach ($data as $item) {
        $count = $item[$key];
        if ($count <= 10) {
            $groupKey = (string) $count;
        } elseif ($count <= 14) {
            $groupKey = '11-14';
        } elseif ($count <= 20) {
            $groupKey = '15-20';
        } elseif ($count <= 30) {
            $groupKey = '21-30';
        } elseif ($count <= 50) {
            $groupKey = '31-50';
        } elseif ($count <= 100) {
            $groupKey = '51-100';
        } else {
            $groupKey = '100+';
        }
        if (!isset($groups[$groupKey])) {
            $groups[$groupKey] = 0;
        }
        $groups[$groupKey]++;
    }
    ksort($groups, \SORT_NATURAL);

    return $groups;
}

function stats_paremiotipus(): void
{
    // phpcs:disable SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys
    require_once __DIR__ . '/../common.php';

    $n_paremiotipus = get_n_paremiotipus();
    $n_modismes = get_n_modismes();
    echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
    echo '<h3>Paremiotipus per nombre de recurrències</h3>';
    $stmt = get_db()->query('SELECT `PAREMIOTIPUS`, COUNT(1) AS `MODISME_COUNT` FROM `00_PAREMIOTIPUS` GROUP BY `PAREMIOTIPUS`');
    $grouped_data = group_data_stats($stmt->fetchAll(PDO::FETCH_ASSOC), 'MODISME_COUNT');
    echo getChart('myChart1', 'bar', $grouped_data, 'Paremiotipus', x_title: 'Nombre de recurrències', y_title: 'Paremiotipus', style: 'width:1000px;');

    echo '<h3>Paremiotipus per nombre de variants</h3>';
    $stmt = get_db()->query('SELECT `PAREMIOTIPUS`, COUNT(DISTINCT `MODISME`) AS `DISTINCT_MODISME_COUNT` FROM `00_PAREMIOTIPUS` GROUP BY `PAREMIOTIPUS`');
    $grouped_data = group_data_stats($stmt->fetchAll(PDO::FETCH_ASSOC), 'DISTINCT_MODISME_COUNT');
    echo getChart('myChart2', 'bar', $grouped_data, 'Paremiotipus', x_title: 'Nombre de variants', y_title: 'Paremiotipus', style: 'width:1000px;');

    echo '<div style="display: flex; flex-wrap: wrap; gap: 3rem;">';
    echo '<article>';
    echo '<h3>Paremiotipus amb equivalents</h3>';
    $stmt = get_db()->query('SELECT COUNT(DISTINCT `PAREMIOTIPUS`) FROM `00_PAREMIOTIPUS` WHERE `EQUIVALENT` IS NOT NULL');
    $total = $stmt->fetchColumn();
    assert(is_int($total));
    $data = [
        'Amb equivalents' => $total,
        'Sense equivalents' => $n_paremiotipus - $total,
    ];
    echo getChart('myChart3', 'pie', $data, 'Paremiotipus', style: 'width:330px;');
    echo '</article>';

    echo '<article>';
    echo '<h3>Paremiotipus amb sinònims</h3>';
    $stmt = get_db()->query('SELECT COUNT(DISTINCT `PAREMIOTIPUS`) FROM `00_PAREMIOTIPUS` WHERE `SINONIM` IS NOT NULL');
    $total = $stmt->fetchColumn();
    assert(is_int($total));
    $data = [
        'Amb sinònims' => $total,
        'Sense sinònims' => $n_paremiotipus - $total,
    ];
    echo getChart('myChart4', 'pie', $data, 'Paremiotipus', style: 'width:330px;');
    echo '</article>';

    echo '<article>';
    echo '<h3>Paremiotipus amb imatges</h3>';
    $stmt = get_db()->query('SELECT COUNT(DISTINCT `PAREMIOTIPUS`) FROM `00_IMATGES`');
    $total = $stmt->fetchColumn();
    assert(is_int($total));
    $data = [
        'Amb imatges' => $total,
        'Sense imatges' => $n_paremiotipus - $total,
    ];
    echo getChart('myChart5', 'pie', $data, 'Paremiotipus', style: 'width:330px;');
    echo '</article>';

    echo '<article>';
    echo '<h3>Paremiotipus / Common Voice</h3>';
    $stmt = get_db()->query('SELECT COUNT(DISTINCT `paremiotipus`) FROM `commonvoice`');
    $total = $stmt->fetchColumn();
    assert(is_int($total));
    $data = [
        'Amb veus' => $total,
        'Sense veus' => $n_paremiotipus - $total,
    ];
    echo getChart('myChart6', 'pie', $data, 'Paremiotipus', style: 'width:330px;');
    echo '</article>';

    echo '<article>';
    echo '<h3>Paremiotipus / LanguageTool</h3>';
    $text = file_get_contents(__DIR__ . '/../../scripts/common-voice-export/excluded.txt');
    if ($text !== false) {
        $total = substr_count($text, "\n");
        $data = [
            'Sense errors' => $n_paremiotipus - $total,
            'Amb errors' => $total,
        ];
        echo getChart('myChart7', 'pie', $data, 'Paremiotipus', style: 'width:330px;');
    }
    echo '</article>';
    echo '</div>';

    echo '<div style="display: flex; flex-wrap: wrap; gap: 3rem;">';
    echo '<article>';
    echo '<h3>Fitxes amb equivalents</h3>';
    $stmt = get_db()->query('SELECT COUNT(1) FROM `00_PAREMIOTIPUS` WHERE `EQUIVALENT` IS NOT NULL');
    $total = $stmt->fetchColumn();
    assert(is_int($total));
    $data = [
        'Amb equivalents' => $total,
        'Sense equivalents' => $n_modismes - $total,
    ];
    echo getChart('myChart8', 'pie', $data, 'Fitxes', style: 'width:330px;');
    echo '</article>';

    echo '<article>';
    echo '<h3>Fitxes amb sinònims</h3>';
    $stmt = get_db()->query('SELECT COUNT(1) FROM `00_PAREMIOTIPUS` WHERE `SINONIM` IS NOT NULL');
    $total = $stmt->fetchColumn();
    assert(is_int($total));
    $data = [
        'Amb sinònims' => $total,
        'Sense sinònims' => $n_modismes - $total,
    ];
    echo getChart('myChart9', 'pie', $data, 'Fitxes', style: 'width:330px;');
    echo '</article>';

    echo '<article>';
    echo '<h3>Fitxes amb explicacions</h3>';
    $stmt = get_db()->query('SELECT COUNT(1) FROM `00_PAREMIOTIPUS` WHERE `EXPLICACIO` IS NOT NULL');
    $total = $stmt->fetchColumn();
    assert(is_int($total));
    $data = [
        'Amb explicacions' => $total,
        'Sense explicacions' => $n_modismes - $total,
    ];
    echo getChart('myChart10', 'pie', $data, 'Fitxes', style: 'width:330px;');
    echo '</article>';

    echo '<article>';
    echo '<h3>Fitxes amb exemples</h3>';
    $stmt = get_db()->query('SELECT COUNT(1) FROM `00_PAREMIOTIPUS` WHERE `EXEMPLES` IS NOT NULL');
    $total = $stmt->fetchColumn();
    assert(is_int($total));
    $data = [
        'Amb exemples' => $total,
        'Sense exemples' => $n_modismes - $total,
    ];
    echo getChart('myChart11', 'pie', $data, 'Fitxes', style: 'width:330px;');
    echo '</article>';

    echo '<article>';
    echo '<h3>Fitxes amb llocs</h3>';
    $stmt = get_db()->query('SELECT COUNT(1) FROM `00_PAREMIOTIPUS` WHERE `LLOC` IS NOT NULL');
    $total = $stmt->fetchColumn();
    assert(is_int($total));
    $data = [
        'Amb llocs' => $total,
        'Sense llocs' => $n_modismes - $total,
    ];
    echo getChart('myChart12', 'pie', $data, 'Fitxes', style: 'width:330px;');
    echo '</article>';
    echo '</div>';

    echo '<article>';
    echo '<h3>Paremiotipus que no coincideixen amb cap dels seus modismes</h3>';
    $records = get_db()->query('
        SELECT
            `Display`
        FROM
            `paremiotipus_display`
        LEFT JOIN
            `00_PAREMIOTIPUS`
        ON
            `paremiotipus_display`.`Paremiotipus` = `00_PAREMIOTIPUS`.`MODISME`
        WHERE
            `MODISME` IS NULL
    ')->fetchAll(PDO::FETCH_COLUMN);

    $list = '';
    $total = 0;
    foreach ($records as $r) {
        $list .= $r . "\n";
        $total++;
    }
    $data = [
        'Amb coincidència' => $n_paremiotipus - $total,
        'Sense coincidència' => $total,
    ];
    echo "<details><summary>Mostra la llista</summary><pre>{$list}</pre></details>";
    echo getChart('myChart13', 'pie', $data, 'Paremiotipus', style: 'width:330px;');
    echo '</article>';
}

function stats_equivalents(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
    echo "<h3>Nombre d'equivalents dels principals idiomes</h3>";
    $modismes = get_db()->query("SELECT
            COUNT(`EQUIVALENT`) AS `EQUIVALENTS`,
            IFNULL(`IDIOMA`, '(buit)') AS `IDIOMA`
        FROM
            `00_PAREMIOTIPUS`
        WHERE
            `EQUIVALENT` IS NOT NULL
        GROUP BY
            `IDIOMA`
    ")->fetchAll(PDO::FETCH_ASSOC);
    $data = [
        '(altres)' => 0,
    ];
    $data_table = [];
    foreach ($modismes as $modisme) {
        $language = $modisme['IDIOMA'] === '(buit)' ? '(buit)' : mb_ucfirst(get_idioma($modisme['IDIOMA']));
        if ($language === '') {
            $data_table['(desconegut)'] = $modisme['EQUIVALENTS'];
        } elseif ($modisme['EQUIVALENTS'] < 1000) {
            $data['(altres)'] += $modisme['EQUIVALENTS'];
            $data_table[$language] = $modisme['EQUIVALENTS'];
        } else {
            $data[$language] = $modisme['EQUIVALENTS'];
            $data_table[$language] = $modisme['EQUIVALENTS'];
        }
    }
    echo getChart('myChart1', 'bar', $data, 'Equivalents', style: 'width:600px;');

    echo "<h3>Idiomes ordenats pel nombre d'equivalents</h3>";
    echo "<table style='width:300px;'>";
    echo '<tr><th>Idioma</th><th>Equivalents</th></tr>';
    arsort($data_table);
    foreach ($data_table as $language => $count) {
        echo "<tr><td>{$language}</td><td>" . format_nombre($count) . '</td></tr>';
    }
    echo '</table>';
}

function stats_editorials(): void
{
    require_once __DIR__ . '/../common.php';

    $editorials = get_editorials();
    echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';

    echo '<h3>Principals editorials pel nombre de paremiotipus</h3>';
    $records = get_db()->query('SELECT
            `EDITORIAL`,
            COUNT(DISTINCT `PAREMIOTIPUS`) AS `Repetitions`
        FROM
            `00_PAREMIOTIPUS`
        WHERE
            `EDITORIAL` IS NOT NULL
        GROUP BY
            `EDITORIAL`
        ORDER BY
            `Repetitions` DESC
    ')->fetchAll(PDO::FETCH_KEY_PAIR);
    $data = [];
    $data_table_paremiotipus = [];
    foreach ($records as $ed_key => $count) {
        if (isset($editorials[$ed_key])) {
            if (count($data) <= 25) {
                $data[$editorials[$ed_key]] = $count;
            }
            $data_table_paremiotipus[$editorials[$ed_key]] = $count;
        }
    }
    echo getChart('myChart1', 'doughnut', $data, 'Paremiotipus', style: 'width:600px;');

    echo '<details><summary>Mostra la llista completa</summary>';
    echo "<table style='width:600px;'>";
    echo '<tr><th>Editorial</th><th>Paremiotipus</th></tr>';
    foreach ($data_table_paremiotipus as $editorial => $count) {
        echo "<tr><td>{$editorial}</td><td>" . format_nombre($count) . '</td></tr>';
    }
    echo '</table>';
    echo '</details>';

    echo '<h3>Principals editorials pel nombre de fitxes</h3>';
    $records = get_db()->query('SELECT
            `EDITORIAL`,
            COUNT(1) AS `Repetitions`
        FROM
            `00_PAREMIOTIPUS`
        WHERE
            `EDITORIAL` IS NOT NULL
        GROUP BY
            `EDITORIAL`
        ORDER BY
            `Repetitions` DESC
    ')->fetchAll(PDO::FETCH_KEY_PAIR);

    $data = [];
    $data_table = [];
    foreach ($records as $ed_key => $count) {
        if (isset($editorials[$ed_key])) {
            if (count($data) <= 25) {
                $data[$editorials[$ed_key]] = $count;
            }
            $data_table[$editorials[$ed_key]] = $count;
        }
    }
    echo getChart('myChart2', 'doughnut', $data, 'Fitxes', style: 'width:600px;');

    echo '<details><summary>Mostra la llista completa</summary>';
    echo "<table style='width:600px;'>";
    echo '<tr><th>Editorial</th><th>Fitxes</th></tr>';
    foreach ($data_table as $editorial => $count) {
        echo "<tr><td>{$editorial}</td><td>" . format_nombre($count) . '</td></tr>';
    }
    echo '</table>';
    echo '</details>';
}

function stats_obres(): void
{
    require_once __DIR__ . '/../common.php';

    echo "<h3>Obres ordenades pel nombre d'entrades a la base de dades</h3>";
    $records = get_db()->query('SELECT
            00_FONTS.`Identificador` as Font,
            00_FONTS.`Registres` as Registres,
            COUNT(00_PAREMIOTIPUS.`ID_FONT`) AS NumberOfReferences
        FROM
            00_FONTS
        LEFT JOIN
            00_PAREMIOTIPUS ON 00_FONTS.`Identificador` = 00_PAREMIOTIPUS.`ID_FONT`
        GROUP BY
            00_FONTS.`Identificador`,
            00_FONTS.`Registres`
        ORDER BY
            NumberOfReferences DESC
    ')->fetchAll(PDO::FETCH_ASSOC);
    echo "<table style='width:1200px;'>";
    echo '<tr><th>Obra</th><th>Total</th><th>Recollides</th><th>Falten</th></tr>';
    foreach ($records as $r) {
        echo '<tr>';
        echo '<td><a href="' . get_obra_url($r['Font']) . '">' . htmlspecialchars($r['Font']) . '</a></td>';
        echo '<td>' . format_nombre($r['Registres']) . '</td>';
        echo '<td>' . format_nombre($r['NumberOfReferences']) . '</td>';
        echo '<td>' . format_nombre($r['Registres'] - $r['NumberOfReferences']) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

function stats_llocs(): void
{
    require_once __DIR__ . '/../common.php';

    $records = get_db()->query('
        SELECT
            `LLOC`,
            COUNT(`LLOC`) AS `Repetitions`
        FROM
            `00_PAREMIOTIPUS`
        GROUP BY
            `LLOC`
        ORDER BY
            `Repetitions` DESC,
            `LLOC` ASC;
    ')->fetchAll(PDO::FETCH_KEY_PAIR);

    echo '<h3>Llocs ordenats per freqüència</h3>';
    echo "<table style='width:600px;'>";
    echo '<tr><th>Lloc</th><th>Entrades</th></tr>';
    foreach ($records as $lloc => $count) {
        if ($count > 0) {
            echo "<tr><td>{$lloc}</td><td>" . format_nombre($count) . '</td></tr>';
        }
    }
    echo '</table>';
}

function test_urls(): void
{
    echo '<h3>Valors de 00_OBRESVPR.URL que responen diferent de HTTP 200/301/302/307</h3>';
    echo '<pre>';
    readfile(__DIR__ . '/../../tmp/test_llibres_URL.txt');
    echo '</pre>';

    echo '<h3>Valors de 00_FONTS.URL que responen diferent de HTTP 200/301/302/307</h3>';
    echo '<pre>';
    readfile(__DIR__ . '/../../tmp/test_fonts_URL.txt');
    echo '</pre>';

    echo '<h3>Valors de 00_IMATGES.URL_IMATGE que responen diferent de HTTP 200/301/302/307</h3>';
    echo '<details><pre>';
    readfile(__DIR__ . '/../../tmp/test_imatges_URL_IMATGE.txt');
    echo '</pre></details>';

    echo '<h3>Valors de 00_IMATGES.URL_ENLLAÇ que responen diferent de HTTP 200/301/302/307</h3>';
    echo '<details><pre>';
    readfile(__DIR__ . '/../../tmp/test_imatges_URL_ENLLAC.txt');
    echo '</pre></details>';
}

function test_fonts_buides(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Registres a la taula 00_FONTS amb el camp Títol buit</h3>';
    $records = get_db()->query('SELECT `Identificador` FROM `00_FONTS` WHERE `Títol` IS NULL OR LENGTH(`Títol`) < 2')->fetchAll(PDO::FETCH_COLUMN);
    echo '<pre>';
    foreach ($records as $r) {
        echo "{$r}\n";
    }
    echo '</pre>';

    echo '<h3>Registres a la taula 00_FONTS amb el camp Autor buit</h3>';
    $records = get_db()->query('SELECT `Identificador` FROM `00_FONTS` WHERE `Autor` IS NULL OR LENGTH(`Autor`) < 2')->fetchAll(PDO::FETCH_COLUMN);
    echo '<pre>';
    foreach ($records as $record) {
        echo "{$record}\n";
    }
    echo '</pre>';
}

function test_fonts_zero(): void
{
    echo '<h3>Paremiotipus amb almenys 1 registre sense detalls a la font</h3>';
    echo '<details><div style="font-size: 13px;">';
    $file = file_get_contents(__DIR__ . '/../../tmp/test_zero_fonts.txt');
    if ($file !== false) {
        $lines = explode("\n", $file);
        foreach ($lines as $line) {
            echo html_escape_and_link_urls($line) . '<br>';
        }
    }
    echo '</div></details>';
}

function test_fonts_sense_paremia(): void
{
    require_once __DIR__ . '/../common.php';

    $fonts = get_fonts();
    $fonts_modismes = get_db()->query('SELECT DISTINCT `ID_FONT`, 1 FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_KEY_PAIR);

    echo '<h3>Obres de la taula 00_FONTS que no estan referenciades per cap parèmia</h3>';
    echo '<div style="font-size: 13px;">';
    foreach ($fonts as $identificador => $title) {
        if (!isset($fonts_modismes[$identificador])) {
            echo '<a href="' . get_obra_url($identificador) . '">' . $title . '</a><br>';
        }
    }
    echo '</div>';
}

function test_fonts_any_erroni(): void
{
    require_once __DIR__ . '/../common.php';

    echo "<h3>Obres amb l'any probablement incorrecte</h3>";
    echo '<pre>';
    $stmt = get_db()->query('SELECT `Identificador`, `Any` FROM `00_FONTS`');
    $imatges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($imatges as $i) {
        if (((int) $i['Any']) < 0 || ((int) $i['Any']) > (int) date('Y')) {
            echo $i['Identificador'] . ' (' . $i['Any'] . ")\n";
        }
    }
    echo '</pre>';

    echo "<h3>Obres amb l'any d'edició probablement incorrecte</h3>";
    echo '<pre>';
    $stmt = get_db()->query('SELECT `Identificador`, `Any_edició` FROM `00_FONTS`');
    $imatges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($imatges as $imatge) {
        if (((int) $imatge['Any_edició']) < 0 || ((int) $imatge['Any_edició']) > (int) date('Y')) {
            echo $imatge['Identificador'] . ' (' . $imatge['Any_edició'] . ")\n";
        }
    }
    echo '</pre>';
}

function test_sinonims(): void
{
    require_once __DIR__ . '/../common.php';

    $pdo = get_db();
    $parem_stmt = $pdo->prepare('SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` = :paremiotipus');
    $mod_stmt = $pdo->prepare('SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` = :modisme');
    $paremiotipus = $pdo->query('SELECT `PAREMIOTIPUS`, `SINONIM` FROM `00_PAREMIOTIPUS` WHERE `SINONIM` IS NOT NULL')->fetchAll(PDO::FETCH_ASSOC);

    $sinonims_array = [];
    foreach ($paremiotipus as $p) {
        $sinonims = get_sinonims($p['SINONIM']);
        foreach ($sinonims as $sin) {
            if (!isset($sinonims_array[$sin])) {
                $sinonims_array[$sin] = 0;
            }
            $sinonims_array[$sin]++;
        }
    }

    $matched = [];
    echo '<h3>Sinònims detectats 2 o més vegades que no existeixen com a modisme</h3>';
    echo "<i>Per a la detecció se separa el camp sinònim amb la barra vertical '|' i se suprimeixen fragments com ' / ', 'v.', 'V.', 'Veg.', 'Similar:'.</i>";
    echo '<details><pre>';
    $sinonims_array_truncated = array_filter($sinonims_array, static fn (int $value): bool => $value >= 2);
    arsort($sinonims_array_truncated);
    foreach ($sinonims_array_truncated as $s => $count) {
        // Try get a modisme for that sinònim.
        $mod_stmt->bindParam(':modisme', $s, PDO::PARAM_STR);
        $mod_stmt->execute();
        $sm = $mod_stmt->fetch(PDO::FETCH_COLUMN);
        if ($sm === false) {
            $matched[$s] = true;
            echo "{$s} ({$count})\n";
        }
    }
    echo '</pre></details>';

    echo '<h3>Altres sinònims detectats 5 o més vegades que no són un paremiotipus</h3>';
    echo '<details><pre>';
    $sinonims_array_truncated = array_filter($sinonims_array, static fn (int $value): bool => $value >= 5);
    arsort($sinonims_array_truncated);
    foreach ($sinonims_array_truncated as $s => $count) {
        // Try get a paremiotipus for that sinònim.
        $parem_stmt->bindParam(':paremiotipus', $s, PDO::PARAM_STR);
        $parem_stmt->execute();
        $sp = $parem_stmt->fetch(PDO::FETCH_COLUMN);
        // Skip results matched in the previous loop.
        if ($sp === false && !isset($matched[$s])) {
            echo "{$s} ({$count})\n";
        }
    }
    echo '</pre></details>';
}

function test_paremies_sense_font_existent(): void
{
    require_once __DIR__ . '/../common.php';

    $fonts = get_fonts();
    $paremies = get_db()->query('SELECT `MODISME`, `ID_FONT` FROM `00_PAREMIOTIPUS` ORDER BY `ID_FONT`')->fetchAll(PDO::FETCH_ASSOC);

    echo '<h3>Parèmies que tenen obra, però que aquesta no es troba a la taula 00_FONTS</h3>';
    echo '<details><pre>';
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
    echo '</pre></details>';
}

function test_paremies_any_erroni(): void
{
    require_once __DIR__ . '/../common.php';

    echo "<h3>Modismes amb l'any probablement incorrecte</h3>";
    echo '<pre>';
    $stmt = get_db()->query('SELECT `MODISME`, `Any` FROM `00_PAREMIOTIPUS`');
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $result) {
        if (((int) $result['Any']) < 0 || ((int) $result['Any']) > (int) date('Y')) {
            echo $result['MODISME'] . ' (' . $result['Any'] . ")\n";
        }
    }
    echo '</pre>';
}

function test_espais(): void
{
    require_once __DIR__ . '/../common.php';

    $pdo = get_db();
    $checker = new Spoofchecker();

    echo '<h3>Paremiotipus que contenen 2 espais seguits</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%  %'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo get_paremiotipus_display($m, escape_html: false) . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus que contenen salts de línia</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%\\n%' OR `PAREMIOTIPUS` LIKE '%\\r%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo trim(get_paremiotipus_display($m, escape_html: false)) . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus que contenen el caràcter tabulador</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%\\t%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo get_paremiotipus_display($m, escape_html: false) . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb espais i parèntesis/claudàtors mal posats</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '% )%' OR `PAREMIOTIPUS` LIKE '%( %' OR `PAREMIOTIPUS` LIKE '% ]%' OR `PAREMIOTIPUS` LIKE '%[ %'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo get_paremiotipus_display($m, escape_html: false) . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb caràcters invisibles no segurs</h3>';
    echo '<pre>';
    $modismes = $pdo->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        if (is_string($m) && (preg_match("/\u{200E}/", $m) === 1 || preg_match("/\u{00AD}/", $m) === 1)) {
            echo get_paremiotipus_display($m, escape_html: false) . "\n";
        }
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb caràcters sospitosos</h3>';
    echo '<pre>';
    $modismes = $pdo->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        if (is_string($m) && $checker->isSuspicious($m)) {
            echo get_paremiotipus_display($m, escape_html: false) . "\n";
        }
    }
    echo '</pre>';

    echo '<h3>Modismes que comencen o acaben amb espai en blanc</h3>';
    echo '<pre>';
    $modismes = $pdo->query('SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE CHAR_LENGTH(`MODISME`) != CHAR_LENGTH(TRIM(`MODISME`))')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes que contenen 2 espais seguits</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%  %'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes que contenen salts de línia</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%\\n%' OR `MODISME` LIKE '%\\r%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes que contenen el caràcter tabulador</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%\\t%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb espais i parèntesis/claudàtors mal posats</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '% )%' OR `MODISME` LIKE '%( %' OR `MODISME` LIKE '% ]%' OR `MODISME` LIKE '%[ %'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb caràcters invisibles no segurs</h3>';
    echo '<pre>';
    $modismes = $pdo->query('SELECT `MODISME` FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        if (is_string($m) && (preg_match("/\u{200E}/", $m) === 1 || preg_match("/\u{00AD}/", $m) === 1)) {
            echo $m . "\n";
        }
    }
    echo '</pre>';

    echo '<h3>Modismes amb caràcters sospitosos</h3>';
    echo '<pre>';
    $modismes = $pdo->query('SELECT `MODISME` FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $modisme) {
        if (is_string($modisme) && $checker->isSuspicious($modisme)) {
            echo $modisme . "\n";
        }
    }
    echo '</pre>';

    echo '<h3>Modismes que contenen salts de línia al camp EXPLICACIO</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `EXPLICACIO` LIKE '%\\n%' OR `EXPLICACIO` LIKE '%\\r%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';
}

function test_puntuacio(): void
{
    require_once __DIR__ . '/../common.php';

    $pdo = get_db();

    echo '<h3>Paremiotipus amb parèntesis o claudàtors no tancats</h3>';
    echo '<pre>';
    $paremiotipus = $pdo->query("SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE LENGTH(REPLACE(`PAREMIOTIPUS`, '(', '')) != LENGTH(REPLACE(`PAREMIOTIPUS`, ')', '')) OR LENGTH(REPLACE(`PAREMIOTIPUS`, '[', '')) != LENGTH(REPLACE(`PAREMIOTIPUS`, ']', ''))")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        echo get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb cometes no tancades</h3>';
    echo '<pre>';
    $paremiotipus = $pdo->query("SELECT `Display` FROM `paremiotipus_display` WHERE (LENGTH(`Display`) - LENGTH(REPLACE(`Display`, '\"', ''))) % 2 != 0 OR LENGTH(REPLACE(`Display`, '«', '')) != LENGTH(REPLACE(`Display`, '»', '')) OR LENGTH(REPLACE(`Display`, '“', '')) != LENGTH(REPLACE(`Display`, '”', ''))")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        echo $p . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb cometa simple seguida del caràcter espai o signe de puntuació inusual</h3>';
    echo '<pre>';
    $paremiotipus = $pdo->query("SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE (`PAREMIOTIPUS` LIKE '%\\' %' OR `PAREMIOTIPUS` LIKE '%\\'.%' OR `PAREMIOTIPUS` LIKE '%\\',%' OR `PAREMIOTIPUS` LIKE '%\\';%' OR `PAREMIOTIPUS` LIKE '%\\':%' OR `PAREMIOTIPUS` LIKE '%\\'-%') AND (LENGTH(`PAREMIOTIPUS`) - LENGTH(REPLACE(`PAREMIOTIPUS`, '\\'', ''))) = 1")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        echo get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb el caràcter espai seguit de signe de puntuació inusual</h3>';
    echo '<pre>';
    $paremiotipus = $pdo->query("SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE (`PAREMIOTIPUS` LIKE '% .%' AND `PAREMIOTIPUS` NOT LIKE '% ...%') OR `PAREMIOTIPUS` LIKE '% ,%' OR `PAREMIOTIPUS` LIKE '% ;%' OR `PAREMIOTIPUS` LIKE '% :%' OR `PAREMIOTIPUS` LIKE '% !%' OR `PAREMIOTIPUS` LIKE '% ?%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        echo get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb 2 punts seguits</h3>';
    echo '<pre>';
    $paremiotipus = $pdo->query("SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%..%' AND `PAREMIOTIPUS` NOT LIKE '%...%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        echo get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb 4 punts seguits</h3>';
    echo '<pre>';
    $paremiotipus = $pdo->query("SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%....%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        echo get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb signes de puntuació repetits</h3>';
    echo '<pre>';
    $paremiotipus = $pdo->query("SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%::%' OR `PAREMIOTIPUS` LIKE '%;;%' OR `PAREMIOTIPUS` LIKE '%,,%' OR `PAREMIOTIPUS` LIKE '%--%' OR `PAREMIOTIPUS` LIKE '%;;%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        echo get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus acabats amb signe de puntuació inusual</h3>';
    echo '<pre>';
    $paremiotipus = $pdo->query("SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%,' OR `PAREMIOTIPUS` LIKE '%;' OR `PAREMIOTIPUS` LIKE '%:'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        echo get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb signe de puntuació seguit de lletres</h3>';
    echo '<pre>';
    $paremiotipus = $pdo->query("SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE (`PAREMIOTIPUS` REGEXP BINARY ',[a-zA-Z]+') OR (`PAREMIOTIPUS` REGEXP BINARY '[.][a-zA-Z]+') OR (`PAREMIOTIPUS` REGEXP BINARY '[)][a-zA-Z]+') OR (`PAREMIOTIPUS` REGEXP BINARY '[?][a-zA-Z]+') OR (`PAREMIOTIPUS` REGEXP BINARY ':[a-zA-Z]+') OR (`PAREMIOTIPUS` REGEXP BINARY ';[a-zA-Z]+')")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        echo get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb possible confusió del caràcter <code>l</code> amb <code>I</code></h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE BINARY '%I\\'%' OR `MODISME` REGEXP BINARY '[a-z]+I'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb parèntesis o claudàtors no tancats</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE LENGTH(REPLACE(`MODISME`, '(', '')) != LENGTH(REPLACE(`MODISME`, ')', '')) OR LENGTH(REPLACE(`MODISME`, '[', '')) != LENGTH(REPLACE(`MODISME`, ']', ''))")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb cometes no tancades</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE (LENGTH(`MODISME`) - LENGTH(REPLACE(`MODISME`, '\"', ''))) % 2 != 0 OR LENGTH(REPLACE(`MODISME`, '«', '')) != LENGTH(REPLACE(`MODISME`, '»', '')) OR LENGTH(REPLACE(`MODISME`, '“', '')) != LENGTH(REPLACE(`MODISME`, '”', ''))")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb el caràcter espai seguit de signe de puntuació inusual</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE (`MODISME` LIKE '% .%' AND `MODISME` NOT LIKE '% ...%') OR `MODISME` LIKE '% ,%' OR `MODISME` LIKE '% ;%' OR `MODISME` LIKE '% :%' OR `MODISME` LIKE '% !%' OR `MODISME` LIKE '% ?%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb 2 punts seguits</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%..%' AND `MODISME` NOT LIKE '%...%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb 4 punts seguits</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%....%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb signes de puntuació repetits</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%::%' OR `MODISME` LIKE '%;;%' OR `MODISME` LIKE '%,,%' OR `MODISME` LIKE '%--%' OR `MODISME` LIKE '%;;%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes acabats amb signe de puntuació inusual</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%,' OR `MODISME` LIKE '%;' OR `MODISME` LIKE '%:'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb una combinació de signes de puntuació inusual</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%?¿%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%,?%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%,!'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb cometa simple seguida del caràcter espai o signe de puntuació inusual</h3>';
    echo '<details><pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE (`MODISME` LIKE '%\\' %' OR `MODISME` LIKE '%\\'.%' OR `MODISME` LIKE '%\\',%' OR `MODISME` LIKE '%\\';%' OR `MODISME` LIKE '%\\':%' OR `MODISME` LIKE '%\\'-%') AND (LENGTH(`MODISME`) - LENGTH(REPLACE(`MODISME`, '\\'', ''))) = 1")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre></details>';

    echo '<h3>Modismes amb signe de puntuació seguit de lletres</h3>';
    echo '<details><pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE (`MODISME` REGEXP BINARY ',[a-zA-Z]+') OR (`MODISME` REGEXP BINARY '[.][a-zA-Z]+') OR (`MODISME` REGEXP BINARY '[)][a-zA-Z]+') OR (`MODISME` REGEXP BINARY '[?][a-zA-Z]+') OR (`MODISME` REGEXP BINARY ':[a-zA-Z]+') OR (`MODISME` REGEXP BINARY ';[a-zA-Z]+')")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre></details>';
}

function test_majuscules(): void
{
    require_once __DIR__ . '/../common.php';
    $pdo = get_db();

    echo '<h3>Paremiotipus que comencen amb lletra minúscula</h3>';
    echo '<pre>';
    $modismes = $pdo->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        if (mb_ucfirst($m) !== $m && mb_ucfirst(get_paremiotipus_display($m, escape_html: false)) !== get_paremiotipus_display($m, escape_html: false)) {
            echo get_paremiotipus_display($m, escape_html: false) . "\n";
        }
    }
    echo '</pre>';

    echo "<h3>Paremiotipus que tenen una lletra minúscula seguida d'una lletra majúscula</h3>";
    echo '<pre>';
    $modismes = $pdo->query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` REGEXP BINARY '[a-z]+[A-Z]+'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo get_paremiotipus_display($m, escape_html: false) . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus que tenen dues lletres majúscules seguides</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` REGEXP BINARY '[A-Z]+[A-Z]+'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo get_paremiotipus_display($m, escape_html: false) . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus que acaben amb lletra majúscula</h3>';
    echo '<pre>';
    $modismes = $pdo->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE BINARY LOWER(SUBSTRING(`PAREMIOTIPUS`, -1)) != SUBSTRING(`PAREMIOTIPUS`, -1)')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo get_paremiotipus_display($m, escape_html: false) . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes que comencen amb lletra minúscula</h3>';
    echo '<pre>';
    $modismes = $pdo->query('SELECT DISTINCT `MODISME` FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        if (is_string($m) && mb_ucfirst($m) !== $m) {
            echo $m . "\n";
        }
    }
    echo '</pre>';

    echo "<h3>Modismes que tenen una lletra minúscula seguida d'una lletra majúscula</h3>";
    echo '<pre>';
    $modismes = $pdo->query("SELECT DISTINCT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` REGEXP BINARY '[a-z]+[A-Z]+'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes que acaben amb lletra majúscula</h3>';
    echo '<pre>';
    $modismes = $pdo->query('SELECT DISTINCT `MODISME` FROM `00_PAREMIOTIPUS` WHERE BINARY LOWER(SUBSTRING(`MODISME`, -1)) != SUBSTRING(`MODISME`, -1)')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $modisme) {
        echo $modisme . "\n";
    }
    echo '</pre>';
}

function test_equivalents(): void
{
    require_once __DIR__ . '/../common.php';

    echo "<h3>Modismes amb equivalents amb un codi d'idioma no detectat</h3>";
    echo '<pre>';
    $modismes = get_db()->query('SELECT `MODISME`, `EQUIVALENT`, `IDIOMA` FROM `00_PAREMIOTIPUS` WHERE `EQUIVALENT` IS NOT NULL')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($modismes as $m) {
        if ($m['IDIOMA'] !== null && get_idioma($m['IDIOMA']) === '') {
            echo $m['MODISME'] . ' (codi idioma: ' . $m['IDIOMA'] . ', equivalent: ' . $m['EQUIVALENT'] . ")\n";
        }
    }
    echo '</pre>';

    echo '<h3>Modismes amb equivalents amb el camp idioma buit</h3>';
    echo '<details><pre>';
    $modismes = get_db()->query('SELECT `MODISME`, `EQUIVALENT`, `IDIOMA` FROM `00_PAREMIOTIPUS` WHERE `EQUIVALENT` IS NOT NULL')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($modismes as $modisme) {
        if ($modisme['IDIOMA'] === null && $modisme['MODISME'] !== null && $modisme['EQUIVALENT'] !== null) {
            echo $modisme['MODISME'] . ' (equivalent ' . $modisme['EQUIVALENT'] . ")\n";
        }
    }
    echo '</pre></details>';
}

function test_paremiotipus_accents(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus amb diferències de majúscules o accents (o espais al principi/final)</h3>';
    $paremiotipus = get_db()->query('SELECT
        DISTINCT BINARY `a`.`PAREMIOTIPUS`
    FROM
        `00_PAREMIOTIPUS` `a`,
        `00_PAREMIOTIPUS` `b`
    WHERE
        `a`.`PAREMIOTIPUS` = `b`.`PAREMIOTIPUS`
    AND
        BINARY `a`.`PAREMIOTIPUS` != `b`.`PAREMIOTIPUS`
    ')->fetchAll(PDO::FETCH_COLUMN);

    echo '<pre>';
    foreach ($paremiotipus as $p) {
        echo get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    echo '</pre>';
}

function test_paremiotipus_modismes_diferents(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus diferents que contenen exactament el mateix modisme</h3>';
    echo '<pre>';
    $accents = '';
    $paremiotipus = get_db()->query('SELECT
            `a`.`PAREMIOTIPUS` as `PAREMIOTIPUS_A`,
            `a`.`MODISME` as `MODISME_A`,
            `b`.`PAREMIOTIPUS` as `PAREMIOTIPUS_B`,
            `b`.`MODISME` as `MODISME_B`
        FROM
            `00_PAREMIOTIPUS` `a`,
            `00_PAREMIOTIPUS` `b`
        WHERE
            `a`.`MODISME` = `b`.`MODISME`
        AND
            `a`.`PAREMIOTIPUS` != `b`.`PAREMIOTIPUS`')->fetchAll(PDO::FETCH_ASSOC);
    $paremiotipus_unics = [];
    foreach ($paremiotipus as $m) {
        if (!isset($paremiotipus_unics[$m['PAREMIOTIPUS_A']]) && !isset($paremiotipus_unics[$m['PAREMIOTIPUS_B']])) {
            if ($m['MODISME_A'] === $m['MODISME_B']) {
                echo get_paremiotipus_display($m['PAREMIOTIPUS_A'], escape_html: false) . ' (modisme: ' . $m['MODISME_A'] . ")\n";
                echo get_paremiotipus_display($m['PAREMIOTIPUS_B'], escape_html: false) . ' (modisme: ' . $m['MODISME_B'] . ")\n";
                echo "\n";
            } else {
                $accents .= get_paremiotipus_display($m['PAREMIOTIPUS_A'], escape_html: false) . ' (modisme: ' . $m['MODISME_A'] . ")\n";
                $accents .= get_paremiotipus_display($m['PAREMIOTIPUS_B'], escape_html: false) . ' (modisme: ' . $m['MODISME_B'] . ")\n";
                $accents .= "\n";
            }
        }
        $paremiotipus_unics[$m['PAREMIOTIPUS_A']] = true;
        $paremiotipus_unics[$m['PAREMIOTIPUS_B']] = true;
    }
    echo '</pre>';

    echo '<h3>Paremiotipus diferents que contenen un mateix modisme amb diferències de majúscules o accents (o espais al principi/final)</h3>';
    echo '<pre>';
    echo $accents;
    echo '</pre>';
}

function test_paremiotipus_repetits(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus molt semblants (consecutius)</h3>';
    echo '<pre>';
    $prev = '';
    $modismes = get_db()->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        $string1 = strtolower((string) preg_replace('#[[:punct:]]#', '', substr($m, SIMILAR_TEXT_MAX_LENGTH)));
        $string2 = strtolower((string) preg_replace('#[[:punct:]]#', '', substr($prev, SIMILAR_TEXT_MAX_LENGTH)));

        similar_text($string1, $string2, $percent);
        if ($percent > SIMILAR_TEXT_THRESHOLD_1 || ($percent > SIMILAR_TEXT_THRESHOLD_2 && strlen($string1) > SIMILAR_TEXT_MIN_LENGTH)) {
            echo get_paremiotipus_display($prev, escape_html: false) . "\n" . get_paremiotipus_display($m, escape_html: false) . "\n\n";
        }
        $prev = $m;
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb diferències de caràcters que es poden confondre visualment (consecutius)</h3>';
    $checker = new Spoofchecker();
    echo '<pre>';
    $prev = '';
    $paremiotipus = get_db()->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        if ($checker->areConfusable($p, $prev)) {
            echo $prev . "\n" . get_paremiotipus_display($p, escape_html: false) . "\n\n";
        }
        $prev = $p;
    }
    echo '</pre>';

    echo "<h3>Nous paremiotipus molt semblants des de l'última actualització (Levenshtein)</h3>";
    echo '<pre>';
    readfile(__DIR__ . '/../../tmp/test_repetits_new.txt');
    echo '</pre>';

    echo '<h3>Paremiotipus molt semblants (Levenshtein)</h3>';
    echo '<details><pre>';
    readfile(__DIR__ . '/../../tmp/test_repetits.txt');
    echo '</pre></details>';
}

function test_modismes_repetits(): void
{
    require_once __DIR__ . '/../common.php';

    $checker = new Spoofchecker();

    echo '<h3>Modismes amb diferències de caràcters que es poden confondre visualment</h3>';
    $results = get_db()->query('SELECT DISTINCT `MODISME`, `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`, `MODISME`')->fetchAll(PDO::FETCH_ASSOC);
    $groupedResults = [];
    foreach ($results as $row) {
        $groupedResults[$row['PAREMIOTIPUS']][] = $row['MODISME'];
    }

    echo '<pre>';
    foreach ($groupedResults as $modisme_array) {
        $prev = '';
        foreach ($modisme_array as $modisme) {
            if ($prev !== '' && $checker->areConfusable($modisme, $prev)) {
                echo $prev . "\n" . $modisme . "\n\n";
            }
            $prev = $modisme;
        }
    }
    echo '</pre>';
}

function string_has_consecutive_repeated_chars(string $str): bool
{
    $common_repetitions = ['...', 'cc', 'ee', 'll', 'mm', 'nn', 'oo', 'rr', 'ss', 'uu'];

    $last_char_pos = mb_strlen($str) - 1;
    for ($i = 0; $i < $last_char_pos; $i++) {
        $count = 1;
        while ($i < $last_char_pos && mb_substr($str, $i, 1) === mb_substr($str, $i + 1, 1)) {
            $count++;
            $i++;
        }

        if ($count > 1) {
            $repeated_string = mb_substr($str, $i + 1 - $count, $count);
            if (!in_array($repeated_string, $common_repetitions, true)) {
                return true;
            }
        }
    }

    return false;
}

function test_repeticio_caracters(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus amb una repetició de caràcters inusual</h3>';
    echo '<details><pre>';
    $modismes = get_db()->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        if (is_string($m) && string_has_consecutive_repeated_chars($m)) {
            echo get_paremiotipus_display($m, escape_html: false) . "\n";
        }
    }
    echo '</pre></details>';

    echo '<h3>Modismes amb una repetició de caràcters inusual</h3>';
    echo '<details><pre>';
    $modismes = get_db()->query('SELECT DISTINCT `MODISME` FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $modisme) {
        if (is_string($modisme) && string_has_consecutive_repeated_chars($modisme)) {
            echo $modisme . "\n";
        }
    }
    echo '</pre></details>';
}

function test_paremiotipus_caracters_inusuals(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus amb caràcters inusuals en català</h3>';
    echo '<pre>';
    $paremiotipus = get_db()->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        $t = str_replace(
            ['à', 'è', 'é', 'í', 'ï', 'ò', 'ó', 'ú', 'ü', 'ç', '«', '»', '·', '–', '‑', '—', '―', '─'],
            '',
            mb_strtolower($p)
        );
        if (
            // If it contains any non-ASCII character
            preg_match('/[^\x00-\x7F]/', $t) > 0
        ) {
            echo get_paremiotipus_display($p, escape_html: false) . "\n";
        }
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb caràcters de guió o guionet no estàndards (ni — ni -)</h3>';
    echo '<pre>';
    $paremiotipus = get_db()->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    $guions = [
        // '-' => [],
        // '—' => [],
        '‑' => [],
        '–' => [],
        '―' => [],
        '─' => [],
    ];
    $guions_keys = array_keys($guions);
    foreach ($paremiotipus as $p) {
        foreach ($guions_keys as $guio) {
            if (str_contains($p, $guio)) {
                $guions[$guio][] = $p;
            }
        }
    }
    foreach ($guions as $guio => $guio_array) {
        $count = count($guio_array);
        if ($count === 0) {
            continue;
        }
        echo "<i>Caràcter {$guio}</i>\n";
        foreach ($guio_array as $p) {
            echo get_paremiotipus_display($p, escape_html: false) . "\n";
        }
        echo "\n\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb caràcters de guió o guionet no estàndards (ni — ni -)</h3>';
    echo '<pre>';
    $paremiotipus = get_db()->query('SELECT `MODISME` FROM `00_PAREMIOTIPUS` ORDER BY `MODISME`')->fetchAll(PDO::FETCH_COLUMN);
    $guions = [
        // '-' => [],
        // '—' => [],
        '‑' => [],
        '–' => [],
        '―' => [],
        '─' => [],
    ];
    $guions_keys = array_keys($guions);
    foreach ($paremiotipus as $p) {
        foreach ($guions_keys as $guio) {
            if (str_contains($p, $guio)) {
                $guions[$guio][] = $p;
            }
        }
    }
    foreach ($guions as $guio => $guio_array) {
        $count = count($guio_array);
        if ($count === 0) {
            continue;
        }
        echo "<i>Caràcter {$guio}</i>\n";
        foreach ($guio_array as $p) {
            echo $p . "\n";
        }
        echo "\n\n";
    }
    echo '</pre>';
}

function test_paremiotipus_final(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus que acaben amb caràcters no alfabètics o amb signe de puntuació inusual</h3>';
    echo '<pre>';
    $paremiotipus = get_db()->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        $t = str_replace(
            ['à', 'è', 'é', 'í', 'ï', 'ò', 'ó', 'ú', 'ü', 'ç'],
            ['a', 'e', 'e', 'i', 'i', 'o', 'o', 'u', 'u', 'c'],
            mb_strtolower($p)
        );
        if (
            preg_match('/[a-z]$/', $t) === 0
            && !str_ends_with($t, '!')
            && !str_ends_with($t, '?')
            && !str_ends_with($t, '»')
            && !str_ends_with($t, '"')
            && !str_ends_with($t, '...')
        ) {
            echo get_paremiotipus_display($p, escape_html: false) . "\n";
        }
    }
    echo '</pre>';
}

function test_paremiotipus_modismes_curts(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus de menys de 5 caràcters</h3>';
    echo '<details><pre>';
    $paremiotipus = get_db()->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE CHAR_LENGTH(`PAREMIOTIPUS`) < 5 ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    $existing = [];
    foreach ($paremiotipus as $m) {
        $existing[$m] = true;
        echo get_paremiotipus_display($m, escape_html: false) . "\n";
    }
    echo '</pre></details>';

    echo '<h3>Modismes de menys de 5 caràcters (no repetits a la llista anterior)</h3>';
    echo '<details><pre>';
    $modismes = get_db()->query('SELECT DISTINCT `MODISME` as `MODISME` FROM `00_PAREMIOTIPUS` WHERE CHAR_LENGTH(`MODISME`) < 5 ORDER BY `MODISME`')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $modisme) {
        if (!isset($existing[$modisme])) {
            echo $modisme . "\n";
        }
    }
    echo '</pre></details>';
}

function test_paremiotipus_llargs(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus de més de 250 caràcters</h3>';
    echo '<details><pre>';
    $modismes = get_db()->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE CHAR_LENGTH(`PAREMIOTIPUS`) > 250 ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo get_paremiotipus_display($m, escape_html: false) . "\n";
    }
    echo '</pre></details>';
}

function test_explicacions_curtes(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Registres de la taula 00_PAREMIOTIPUS amb menys de 4 caràcters al camp EXPLICACIO</h3>';
    echo '<details><pre>';
    $modismes = get_db()->query('SELECT DISTINCT `MODISME`, `EXPLICACIO` FROM `00_PAREMIOTIPUS` WHERE `EXPLICACIO` IS NOT NULL AND CHAR_LENGTH(`EXPLICACIO`) < 4 ORDER BY `MODISME`')->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($modismes as $key => $value) {
        echo $value . " (modisme: {$key})\n";
    }
    echo '</pre></details>';
}

function test_fonts_curtes(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Registres de la taula 00_PAREMIOTIPUS amb menys de 2 caràcters al camp FONT</h3>';
    echo '<details><pre>';
    $modismes = get_db()->query('SELECT DISTINCT `MODISME`, `FONT` FROM `00_PAREMIOTIPUS` WHERE `FONT` IS NOT NULL AND CHAR_LENGTH(`FONT`) < 2 ORDER BY `MODISME`')->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($modismes as $key => $value) {
        echo $value . " (modisme: {$key})\n";
    }
    echo '</pre></details>';
}

function test_paremies_separar(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Parèmies que probablement es poden separar en dues</h3>';

    $paremies = get_db()->query("SELECT DISTINCT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%(o%' OR `MODISME` LIKE '%[o%' ORDER BY `MODISME`")->fetchAll(PDO::FETCH_COLUMN);
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

function test_editorials_no_referenciades(): void
{
    require_once __DIR__ . '/../common.php';

    $editorials = get_editorials();
    $editorials_modismes = get_db()->query('SELECT DISTINCT `EDITORIAL`, 1 FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_KEY_PAIR);

    echo '<h3>Editorials de la taula 00_EDITORIA que no estan referenciades per cap paremiotipus</h3>';
    echo '<details><pre>';
    foreach ($editorials as $ed_codi => $ed_title) {
        if (!isset($editorials_modismes[$ed_codi])) {
            echo "{$ed_codi}: {$ed_title}\n";
        }
    }
    echo '</pre></details>';
}

function test_editorials_no_existents(): void
{
    require_once __DIR__ . '/../common.php';

    $editorials = get_editorials();
    $editorials_paremiotipus = get_db()->query('SELECT `MODISME`, `EDITORIAL` FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_ASSOC);

    echo '<h3>Editorials que estan referenciades per parèmies, però que no existeixen a la taula 00_EDITORIA</h3>';
    echo '<pre>';
    foreach ($editorials_paremiotipus as $ed_p) {
        if ($ed_p['EDITORIAL'] !== null && !isset($editorials[$ed_p['EDITORIAL']])) {
            echo $ed_p['EDITORIAL'] . ' (' . $ed_p['MODISME'] . ")\n";
        }
    }
    echo '</pre>';
}

function test_buits(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Modismes amb el camp PAREMIOTIPUS buit</h3>';
    echo '<pre>';
    $modismes = get_db()->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` where `PAREMIOTIPUS` = '' OR `PAREMIOTIPUS` IS NULL")->fetchAll(PDO::FETCH_COLUMN);
    if ($modismes === []) {
        echo '(cap resultat)';
    }
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb el camp MODISME buit</h3>';
    echo '<pre>';
    $modismes = get_db()->query("SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` where `MODISME` = '' OR `MODISME` IS NULL")->fetchAll(PDO::FETCH_COLUMN);
    if ($modismes === []) {
        echo '(cap resultat)';
    }
    foreach ($modismes as $m) {
        echo get_paremiotipus_display($m, escape_html: false) . "\n";
    }
    echo '</pre>';
}

function test_commonvoice_languagetool(): void
{
    echo "<h3>Nous paremiotipus detectats amb LanguageTool des de l'última actualització</h3>";
    $text = file_get_contents(__DIR__ . '/../../scripts/common-voice-export/excluded_new.txt');
    if ($text !== false) {
        echo 'Total: ' . substr_count($text, "\n");
        echo "<details><pre>{$text}</pre></details>";
    }

    echo '<h3>Paremiotipus detectats amb LanguageTool</h3>';
    $text = file_get_contents(__DIR__ . '/../../scripts/common-voice-export/excluded.txt');
    if ($text !== false) {
        echo '<i>A causa d\'errors tipogràfics, ortogràfics, per incloure paraules malsonants, noms propis, localismes o falsos positius.</i>';
        echo '<br>Total: ' . substr_count($text, "\n");
        echo "<details><pre>{$text}</pre></details>";
    }
}

/**
 * Gets the response code for a URL.
 */
function curl_get_response_code(string $url, bool $nobody = true): int
{
    assert($url !== '');
    static $ch = null;
    if ($ch === null) {
        $ch = curl_init();
        curl_setopt($ch, \CURLOPT_HEADER, true);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, \CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, \CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, \CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36');
    }

    curl_setopt($ch, \CURLOPT_URL, $url);
    curl_setopt($ch, \CURLOPT_NOBODY, $nobody);
    if (curl_exec($ch) === false) {
        return 0;
    }

    return curl_getinfo($ch, \CURLINFO_HTTP_CODE);
}

function background_test_llibres_urls(): string
{
    require_once __DIR__ . '/../common.php';

    $output = '';
    $urls = [];
    $llibres = get_db()->query('SELECT * FROM `00_OBRESVPR`')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($llibres as $llibre) {
        if ($llibre['URL'] === null) {
            $output .= 'URL buida (Identificador ' . $llibre['Títol'] . ")\n";

            continue;
        }

        $url = $llibre['URL'];
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $output .= 'URL no vàlida (Identificador ' . $llibre['Títol'] . '): ' . $url . "\n";

            continue;
        }

        if (!isset($urls[$url])) {
            $response_code = curl_get_response_code(url: $url, nobody: false);
            $urls[$url] = $response_code;
            if ($response_code !== 200 && $response_code !== 301 && $response_code !== 302 && $response_code !== 307) {
                $output .= 'HTTP ' . $response_code . ' (' . $llibre['Títol'] . '): ' . $url . "\n";
            }
        }
    }

    return $output;
}

function background_test_fonts_urls(): string
{
    require_once __DIR__ . '/../common.php';

    $output = '';
    $urls = [];
    $fonts = get_db()->query('SELECT * FROM `00_FONTS`')->fetchAll(PDO::FETCH_ASSOC);
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

function background_test_imatges_urls(int $start = 0, int $end = 0): string
{
    require_once __DIR__ . '/../common.php';

    $output = '';
    $urls = [];
    $fonts = get_db()->query('SELECT * FROM `00_IMATGES`')->fetchAll(PDO::FETCH_ASSOC);
    $total = count($fonts);
    if ($end === 0) {
        $end = $total;
    }

    for ($i = $start; $i < $end; $i++) {
        $font = $fonts[$i];
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

function background_test_imatges_links(int $start = 0, int $end = 0): string
{
    require_once __DIR__ . '/../common.php';

    $output = '';
    $urls = [];
    $fonts = get_db()->query('SELECT * FROM `00_IMATGES`')->fetchAll(PDO::FETCH_ASSOC);
    $total = count($fonts);
    if ($end === 0) {
        $end = $total;
    }

    for ($i = $start; $i < $end; $i++) {
        $font = $fonts[$i];
        $url = $font['URL_ENLLAÇ'];

        if (!is_string($url) || $url === '') {
            continue;
        }

        if (isset($urls[$url])) {
            continue;
        }

        // Process URLs only once.
        $urls[$url] = true;

        // Discard wrong URLs.
        if (
            (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://'))
            || filter_var($url, \FILTER_SANITIZE_URL) !== $url
        ) {
            $output .= 'URL no vàlida o amb caràcters especials (Identificador ' . $font['Identificador'] . '): ' . $url . "\n";

            continue;
        }

        // Request URL.
        $response_code = curl_get_response_code($url);
        if ($response_code !== 200 && $response_code !== 301 && $response_code !== 302 && $response_code !== 307) {
            $output .= 'HTTP ' . $response_code . ' (Identificador ' . $font['Identificador'] . '): ' . $url . "\n";
        }
    }

    return $output;
}

function background_test_paremiotipus_repetits(int $start = 0, int $end = 0): string
{
    require_once __DIR__ . '/../common.php';

    $modismes = get_db()->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    $total = count($modismes);
    if ($end === 0) {
        $end = $total;
    }

    $output = '';
    for ($i = $start; $i < $end; $i++) {
        $value1 = $modismes[$i];
        $length1 = strlen($value1);

        for ($u = $i + 1; $u < $total; $u++) {
            $value2 = $modismes[$u];
            $length2 = strlen($value2);

            if (abs($length1 - $length2) < LEVENSHTEIN_MAX_DISTANCE) {
                $similarity = 1 - (levenshtein($value1, $value2) / max($length1, $length2));

                if ($similarity >= LEVENSHTEIN_SIMILARITY_THRESHOLD) {
                    $output .= get_paremiotipus_display($value1, escape_html: false) . "\n" . get_paremiotipus_display($value2, escape_html: false) . "\n\n";
                }
            }
        }
    }

    return $output;
}

function background_test_html_escape_and_link_urls(): string
{
    require_once __DIR__ . '/../common.php';

    @unlink(__DIR__ . '/../../tmp/test_tmp_debug_html_escape_and_link_urls.txt');

    $records = get_db()->query('SELECT DISTINCT `Observacions` FROM `00_FONTS` WHERE `Observacions` IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($records as $r) {
        html_escape_and_link_urls($r, '', true);
    }

    $records = get_db()->query('SELECT DISTINCT `URL` FROM `00_FONTS` WHERE `URL` IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($records as $r) {
        html_escape_and_link_urls($r, '', true);
    }

    $records = get_db()->query('SELECT DISTINCT `URL_ENLLAÇ` FROM `00_IMATGES` WHERE `URL_ENLLAÇ` IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($records as $r) {
        html_escape_and_link_urls($r, '', true);
    }

    $records = get_db()->query('SELECT DISTINCT `ARTICLE` FROM `00_PAREMIOTIPUS` WHERE `ARTICLE` IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($records as $r) {
        html_escape_and_link_urls($r, '', true);
    }

    $string = file_get_contents(__DIR__ . '/../../tmp/test_tmp_debug_html_escape_and_link_urls.txt');
    assert($string !== false);

    return $string;
}

function background_test_imatges_no_existents(): string
{
    require_once __DIR__ . '/../common.php';

    $output = '';
    $stmt = get_db()->query('SELECT `Imatge` FROM `00_FONTS`');
    $imatges = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($imatges as $i) {
        if ($i !== null && !is_file(__DIR__ . '/../images/cobertes/' . $i)) {
            $output .= 'cobertes/' . $i . "\n";
        }
    }
    $stmt = get_db()->query('SELECT `Identificador` FROM `00_IMATGES`');
    $imatges = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($imatges as $imatge) {
        if ($imatge !== null && !is_file(__DIR__ . '/../images/paremies/' . $imatge)) {
            $output .= 'paremies/' . $imatge . "\n";
        }
    }

    return $output;
}

function background_test_imatges_duplicades(): string
{
    require_once __DIR__ . '/../common.php';

    $output = '';
    $files = [];
    $dir = new DirectoryIterator(__DIR__ . '/../images/paremies/');
    foreach ($dir as $file_info) {
        if (!$file_info->isDot()) {
            $filename = $file_info->getFilename();
            $files[$filename] = hash_file('xxh3', $file_info->getPathname());
        }
    }
    // See https://stackoverflow.com/a/5995153/1391963
    $files_uniq = array_unique($files);
    $repetits = array_diff($files, array_diff($files_uniq, array_diff_assoc($files, $files_uniq)));
    asort($repetits);
    $prev = '';
    foreach ($repetits as $key => $value) {
        if ($prev !== $value) {
            $output .= "\n";
        }
        $output .= "{$key}\n";
        $prev = $value;
    }

    return $output;
}

function background_test_imatges_no_referenciades(): string
{
    require_once __DIR__ . '/../common.php';

    $output = '';
    $images = get_db()->query('SELECT `Identificador`, 1 FROM `00_IMATGES`')->fetchAll(PDO::FETCH_KEY_PAIR);
    $dir = new DirectoryIterator(__DIR__ . '/../images/paremies/');
    foreach ($dir as $file_info) {
        $filename = $file_info->getFilename();
        if ($filename === '.' || $filename === '..' || $filename === '.picasa.ini') {
            continue;
        }
        if (!isset($images[$filename])) {
            $output .= "{$filename}\n";
        }
    }

    $fonts = get_db()->query('SELECT `Imatge`, 1 FROM `00_FONTS`')->fetchAll(PDO::FETCH_KEY_PAIR);
    $llibres = get_db()->query('SELECT `Imatge`, 1 FROM `00_OBRESVPR`')->fetchAll(PDO::FETCH_KEY_PAIR);
    $dir = new DirectoryIterator(__DIR__ . '/../images/cobertes/');
    foreach ($dir as $file_info) {
        $filename = $file_info->getFilename();
        if ($filename === '.' || $filename === '..' || $filename === '.picasa.ini') {
            continue;
        }
        if (!isset($fonts[$filename]) && !isset($llibres[$filename])) {
            $output .= "{$filename}\n";
        }
    }

    return $output;
}

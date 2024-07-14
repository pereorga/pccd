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
            'test_imatges_minuscules',
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
        'majuscules' => ['test_majuscules'],
        'puntuacio' => [
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
        'stats_autors' => ['stats_autors'],
        'stats_editorials' => ['stats_editorials'],
        'stats_equivalents' => ['stats_equivalents'],
        'stats_llocs' => ['stats_llocs'],
        'stats_obres' => ['stats_obres'],
        'stats_paremiotipus' => ['stats_paremiotipus'],
        'urls' => ['test_urls'],
    ];
}

/**
 * Gets Chart.js chart HTML code.
 *
 * @param array<int|string, int> $data An associative array where keys are strings (labels) and values are integers (data points).
 */
function get_chart(string $type, array $data, string $label = '', string $x_title = '', string $y_title = '', string $style = ''): string
{
    $chart_id = 'chart-' . uniqid();
    $json_labels = json_encode(array_keys($data));
    $json_values = json_encode(array_values($data));
    $output = "<div style='position:relative;{$style}'><canvas id='{$chart_id}' style='margin-bottom:3rem;'></canvas></div>";

    // Initialize scales options dynamically based on x_title and y_title
    $scales_options = '';
    if ($x_title !== '' || $y_title !== '') {
        $scales_options = 'scales: {';
        if ($x_title !== '') {
            $scales_options .= "x: {title: {display: true, text: '{$x_title}'}},";
        }
        if ($y_title !== '') {
            $scales_options .= "y: {title: {display: true, text: '{$y_title}'}},";
        }
        $scales_options .= '}';
    }

    return $output . "<script>
        Chart.defaults.color = '#fff';
        new Chart(document.getElementById('{$chart_id}'), {
            type: '{$type}',
            data: {
                labels: {$json_labels},
                datasets: [{
                    label: '{$label}',
                    data: {$json_values},
                }]
            },
            options: {
                {$scales_options}
            }
        });
        </script>";
}

/**
 * Groups data for stats.
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
            $group_key = (string) $count;
        } elseif ($count <= 14) {
            $group_key = '11-14';
        } elseif ($count <= 20) {
            $group_key = '15-20';
        } elseif ($count <= 30) {
            $group_key = '21-30';
        } elseif ($count <= 50) {
            $group_key = '31-50';
        } elseif ($count <= 100) {
            $group_key = '51-100';
        } else {
            $group_key = '100+';
        }
        if (!isset($groups[$group_key])) {
            $groups[$group_key] = 0;
        }
        $groups[$group_key]++;
    }
    ksort($groups, \SORT_NATURAL);

    return $groups;
}

/**
 * Get the JSON from a list of files.
 *
 * @param list<string> $files
 *
 * @return array<string, int>
 */
function get_data_from_files(array $files, string $directoryPath, string $attribute): array
{
    $data = [];
    foreach ($files as $file) {
        if (preg_match('/data-(\d{6})\.json$/', $file, $matches) > 0) {
            // TODO: Remove when https://github.com/phpstan/phpstan/issues/11293 is fixed.
            assert(isset($matches[1]));
            $year = substr($matches[1], 2, 2);
            $month = substr($matches[1], 4, 2);
            $formattedDate = $month . '-' . $year;
            $json_content = file_get_contents($directoryPath . $file);
            assert(is_string($json_content));
            $decoded = json_decode($json_content, true);
            assert(is_array($decoded));
            if (isset($decoded[$attribute])) {
                assert(is_int($decoded[$attribute]));
                $data[$formattedDate] = $decoded[$attribute];
            }
        }
    }

    return $data;
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

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

const YEAR_MAX = 9999;

$request_uri = get_request_uri();
$paremiotipus = is_string($_GET['paremiotipus']) ? path_to_name($_GET['paremiotipus']) : '';
$modismes = get_modismes($paremiotipus);
$variants = group_modismes_by_variant($modismes);

$total_variants = count($variants);
if ($total_variants === 0) {
    // Try to redirect (HTTP 301) to a valid paremiotipus page.
    try_to_redirect_to_valid_paremiotipus_and_exit($paremiotipus);

    // If no match could be found, return an HTTP 404 page.
    error_log("Error: entry not found for URL: {$request_uri}");
    return_404_and_exit();
}

$editorials = get_editorials();
$fonts = get_fonts();

// Loop through the variants.
$paremiotipus_db = '';
$total_min_year = YEAR_MAX;
$rendered_variants_array = [];
foreach ($variants as $modisme => $variant) {
    $is_first_variant = $paremiotipus_db === '';
    if ($is_first_variant) {
        // Set the canonical URL and page title.
        $paremiotipus_db = $variant[0]['PAREMIOTIPUS'];
        $canonical_url = get_paremiotipus_url($paremiotipus_db, true);

        // Redirect old URLs to the new ones.
        if (!str_starts_with($request_uri, '/p/')) {
            header("Location: {$canonical_url}", true, 301);

            exit;
        }

        set_canonical_url($canonical_url);

        set_page_title(get_paremiotipus_display($paremiotipus_db));
    }

    // Loop through the variant's recurrences.
    $min_year = YEAR_MAX;
    $prev_work = '';
    $variant_sources = 0;
    $paremia = '';
    foreach ($variant as $v) {
        $work = '';
        if ($v['AUTOR'] !== null) {
            $work = htmlspecialchars($v['AUTOR']);
        }
        if ($v['ANY'] > 0) {
            if ($work !== '') {
                $work .= ' ';
            }
            $work .= '(' . $v['ANY'] . ')';
            if ($v['ANY'] < $min_year) {
                $min_year = (int) $v['ANY'];
                if ($min_year < $total_min_year) {
                    $total_min_year = $min_year;
                }
            }
        }
        if ($work !== '' && ($v['DIARI'] !== null || $v['ARTICLE'] !== null)) {
            $work .= ':';
        }
        $editorial = '';
        if ($v['EDITORIAL'] !== null) {
            $editorial = $v['EDITORIAL'];
            $editorial = $editorials[$editorial] ?? $editorial;
        }
        // Print DIARI if it is different from EDITORIAL.
        if ($v['DIARI'] !== null && $v['DIARI'] !== $editorial) {
            if ($work !== '') {
                $work .= ' ';
            }
            if ($v['ID_FONT'] !== null && isset($fonts[$v['ID_FONT']])) {
                $work .= '<a href="' . get_obra_url($v['ID_FONT']) . '">';
                $work .= '<i>' . htmlspecialchars($v['DIARI']) . '</i>';
                $work .= '</a>';
            } else {
                $work .= '<i>' . htmlspecialchars($v['DIARI']) . '</i>';
            }
        }
        if ($v['ARTICLE'] !== null) {
            if ($work !== '') {
                $work .= ' ';
            }
            $work .= '«' . htmlEscapeAndLinkUrls($v['ARTICLE']) . '»';
        }
        if ($v['PAGINA'] !== null) {
            $work .= ', p. ' . htmlspecialchars($v['PAGINA']);
        }
        if ($editorial !== '') {
            if ($work !== '') {
                $work .= '. ';
            }
            $work .= htmlspecialchars($editorial);
        }
        if ($work !== '') {
            if ($v['ACCEPCIO'] !== null) {
                $work .= ', accepció ' . htmlspecialchars($v['ACCEPCIO']);
            }
            $work .= '.';

            $explanation = '';
            if ($v['EXPLICACIO'] !== null && $v['EXPLICACIO2'] !== null) {
                $explanation = mb_ucfirst(ct($v['EXPLICACIO'] . $v['EXPLICACIO2']));
            } elseif ($v['EXPLICACIO'] !== null && strlen($v['EXPLICACIO']) > 3) {
                $explanation = mb_ucfirst(ct($v['EXPLICACIO']));
            }
            if ($v['AUTORIA'] !== null) {
                if ($explanation !== '') {
                    $explanation .= ' ';
                }
                $explanation .= 'De: ' . ct($v['AUTORIA']);
            }

            $body = '';
            if ($explanation !== '') {
                set_meta_description_once("Explicació: {$explanation}");
                $body .= "<div>{$explanation}</div>";
            }
            if ($v['EXEMPLES'] !== null) {
                $exemples = mb_ucfirst(ct($v['EXEMPLES']));
                set_meta_description_once("Exemple: {$exemples}");
                $body .= "<div><i>{$exemples}</i></div>";
            }
            if ($v['SINONIM'] !== null) {
                $sinonim = ct($v['SINONIM']);
                set_meta_description_once("Sinònim: {$sinonim}");
                $body .= "<div>Sinònim: {$sinonim}</div>";
            }
            if ($v['EQUIVALENT'] !== null) {
                $equivalent_label = 'Equivalent';
                $idioma = $v['IDIOMA'] !== null ? get_idioma($v['IDIOMA']) : '';
                if ($idioma !== '') {
                    $equivalent_label = "Equivalent en {$idioma}";
                }
                $equivalent = ct($v['EQUIVALENT']);
                set_meta_description_once("{$equivalent_label}: {$equivalent}");

                $iso_code = $v['IDIOMA'] !== null ? get_idioma_iso_code($v['IDIOMA']) : '';
                if ($iso_code !== '') {
                    $body .= "<div>{$equivalent_label}: <span lang=\"{$iso_code}\">{$equivalent}</span></div>";
                } else {
                    $body .= "<div>{$equivalent_label}: {$equivalent}</div>";
                }
            }
            if ($v['LLOC'] !== null) {
                $body .= '<div>Lloc: ' . ct($v['LLOC']) . '</div>';
            }
            if ($v['FONT'] !== null) {
                $body .= '<div>Font: ' . ct($v['FONT']) . '</div>';
            }

            // Do not print the footer if the entry only contains the year.
            if ($body === '' && preg_match('/\(\d{4}\).$/', $work) > 0) {
                $work = '';
            }

            if ($body !== '' || $work !== '') {
                $paremia .= '<div class="entry">';
                if ($body !== '') {
                    $paremia .= $body;
                }
                if ($work !== '') {
                    $paremia .= "<footer>{$work}</footer>";
                }
                $paremia .= '</div>';
            }
            if ($prev_work !== $work) {
                $variant_sources++;
            }
            $prev_work = $work;
        } elseif ($v['LLOC'] !== null) {
            $paremia .= '<div class="entry">';
            $paremia .= '<div>Lloc: ' . ct($v['LLOC']) . '</div>';
            $paremia .= '</div>';
            $variant_sources++;
        }
    }

    $modisme_safe = htmlspecialchars($modisme);
    if ($total_variants > 1 || $modisme_safe !== get_page_title()) {
        $rendered_variant = "<h2>{$modisme_safe}</h2>";
        if ($variant_sources === 0) {
            // Sources with only a year are displayed without details.
            if ($min_year < YEAR_MAX) {
                $rendered_variant .= '<div class="summary">1 font, ' . $min_year . '.</div>';
            }
        } else {
            $rendered_variant .= '<details open>';
            $rendered_variant .= '<summary>';
            $rendered_variant .= $variant_sources === 1 ? '1 font' : "{$variant_sources} fonts";
            if ($min_year < YEAR_MAX) {
                $rendered_variant .= ", {$min_year}";
            }
            $rendered_variant .= '.';
            $rendered_variant .= '</summary>';
            $rendered_variant .= $paremia;
            $rendered_variant .= '</details>';
        }
    } else {
        $rendered_variant = $paremia;
    }

    $rendered_variants_array[] = [
        'html' => $rendered_variant,
        'count' => $variant_sources,
    ];
}

// Build the right column.
// Common Voice.
$mp3_files = get_cv_files($paremiotipus_db);
$cv_output = '';
foreach ($mp3_files as $mp3_file) {
    if (is_file(__DIR__ . "/../../docroot/mp3/{$mp3_file}")) {
        $is_first_audio = $cv_output === '';
        if ($is_first_audio) {
            set_og_audio_url("https://pccd.dites.cat/mp3/{$mp3_file}");
        }

        $cv_output .= '<a class="audio" href="/mp3/' . $mp3_file . '" role="button">';
        $cv_output .= '<audio preload="none" src="/mp3/' . $mp3_file . '"></audio>';
        $cv_output .= '<img width="32" height="27" alt="altaveu" src="/img/speaker.svg">';
        $cv_output .= '</a>';
    } else {
        error_log("Error: asset file is missing: {$mp3_file}");
    }
}

// Images.
$images = get_images($paremiotipus_db);
$images_output = '';
foreach ($images as $image) {
    if (is_file(__DIR__ . '/../../docroot/img/imatges/' . $image['Identificador'])) {
        $is_first_image = $images_output === '';
        if ($is_first_image) {
            // Use it for the meta image.
            set_meta_image('https://pccd.dites.cat/img/imatges/' . rawurlencode($image['Identificador']));
        }

        $images_output .= '<div class="bloc bloc-image text-break"><figure>';

        $image_tag = get_image_tags(
            $image['Identificador'],
            '/img/imatges/',
            $paremiotipus,
            $image['WIDTH'],
            $image['HEIGHT'],
            !$is_first_image
        );

        $image_url = get_clean_url($image['URL']);
        if ($image_url !== '') {
            $images_output .= '<a href="' . $image_url . '">' . $image_tag . '</a>';
        } else {
            $images_output .= $image_tag;
        }

        $image_caption = '';
        if ($image['AUTOR'] !== null) {
            $image_caption = htmlspecialchars($image['AUTOR']);
        }
        if ($image['ANY'] > 0) {
            if ($image_caption !== '') {
                $image_caption .= ' ';
            }
            $image_caption .= '(' . $image['ANY'] . ')';
        }
        if ($image['DIARI'] !== null && $image['DIARI'] !== $image['AUTOR']) {
            if ($image_caption !== '') {
                $image_caption .= ': ';
            }

            // If there is no ARTICLE, link DIARI to the content.
            $diari = htmlspecialchars($image['DIARI']);
            if ($image_url !== '' && $image['ARTICLE'] === null) {
                $diari = '<a href="' . $image_url . '" class="external">' . $diari . '</a>';
            }
            $image_caption .= "<em>{$diari}</em>";
        }
        if ($image['ARTICLE'] !== null) {
            if ($image_caption !== '') {
                $image_caption .= ' ';
            }

            // Link to the content, unless the text has a link already.
            if (str_contains($image['ARTICLE'], 'http')) {
                // In that case, link to the included URL.
                $article = htmlEscapeAndLinkUrls($image['ARTICLE']);
            } else {
                $article = htmlspecialchars($image['ARTICLE']);
                // Reuse the link of the image, if there is one.
                if ($image_url !== '') {
                    $article = '<a href="' . $image_url . '" class="external">' . $article . '</a>';
                }
            }
            $image_caption .= "«{$article}»";
        }

        if ($image_caption !== '') {
            $images_output .= '<figcaption class="small">' . $image_caption . '</figcaption>';
        }

        $images_output .= '</figure></div>';
    }
}

$blocks = '';
if ($cv_output !== '') {
    $blocks = '<div id="commonvoice" class="bloc text-balance text-break" title="Escolteu-ho">';
    $blocks .= $cv_output;
    $blocks .= '<p><a title="Projecte Common Voice" href="https://commonvoice.mozilla.org/ca">';
    $blocks .= '<img alt="logotip de Common Voice" width="100" height="25" src="/img/commonvoice.svg"></a></p>';
    $blocks .= '</div>';
}
if ($images_output !== '') {
    $blocks .= '<div id="imatges">';
    $blocks .= $images_output;
    $blocks .= '</div>';
}
set_paremiotipus_blocks($blocks);

// Main page output.
$output = '';
if ($total_variants > 1) {
    $output = '<div class="article-summary">';
    $output .= count($modismes) . "&nbsp;recurrències en {$total_variants}&nbsp;variants.";
    if ($total_min_year < YEAR_MAX) {
        $output .= " Primera&nbsp;citació:&nbsp;{$total_min_year}.";
    }
    $output .= '<div class="shortcuts">';
    $output .= '<button type="button" id="toggle-all" title="Amaga els detalls de cada font">Contrau-ho tot</button>';
    $output .= '</div></div>';
}

// Sort variants by the number of sources. @phan-suppress-next-line PhanPluginUnknownArrayClosureParamType
usort($rendered_variants_array, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);
foreach ($rendered_variants_array as $rendered_variant) {
    $output .= $rendered_variant['html'];
}

echo $output;

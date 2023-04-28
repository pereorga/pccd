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

/*
 * Paremiotipus pages.
 *
 * This file is probably the ugliest in the codebase.
 */

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
    error_log("Error: no s'ha trobat el paremiotipus per l'URL: " . $request_uri);
    return_404_and_exit();
}

$editorials = get_editorials();
$fonts = get_fonts();

// We'll populate the right column.
$blocks = '';
$meta_desc = '';
$meta_desc_fallback = '';
$paremiotipus_db = '';
$is_first_variant = true;
$total_min_year = YEAR_MAX;
$rendered_array = [];
foreach ($variants as $modisme => $variant) {
    if ($is_first_variant) {
        // Set the canonical URL.
        $paremiotipus_db = $variant[0]['PAREMIOTIPUS'];
        $canonical_url = get_paremiotipus_url($paremiotipus_db, true);
        set_canonical_url($canonical_url);

        // Redirect old URLs to the new ones.
        if (!str_starts_with($request_uri, '/p/')) {
            header("Location: {$canonical_url}", true, 301);

            exit;
        }

        // Get the page title.
        set_page_title(get_paremiotipus_display($paremiotipus_db));
        $is_first_variant = false;
    }

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
        if ($v['DIARI'] !== null) {
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
        if ($v['EDITORIAL'] !== null) {
            $editorial = $v['EDITORIAL'];
            $editorial = $editorials[$editorial] ?? $editorial;
            if ($work !== '') {
                $work .= '. ';
            }
            $work .= htmlspecialchars($editorial);
        }
        if ($work !== '') {
            if ($v['ACCEPCIO'] !== null) {
                $work .= ', accepció ' . htmlspecialchars($v['ACCEPCIO']);
            }

            $explicacio = '';
            if ($v['EXPLICACIO'] !== null && strlen($v['EXPLICACIO']) > 3) {
                $explicacio = htmlspecialchars($v['EXPLICACIO']);
                if ($meta_desc === '') {
                    $meta_desc = 'Explicació: ' . trim($explicacio);
                }
                if ($v['EXPLICACIO2'] !== null) {
                    $explicacio .= htmlspecialchars($v['EXPLICACIO2']);
                }
                $explicacio = trim($explicacio);
            }

            if ($v['AUTORIA'] !== null) {
                if ($explicacio !== '') {
                    if (
                        !str_ends_with($explicacio, '.')
                        && !str_ends_with($explicacio, '?')
                        && !str_ends_with($explicacio, '!')
                    ) {
                        $explicacio .= '.';
                    }
                    $explicacio .= ' ';
                }
                $explicacio .= 'De: ' . htmlspecialchars($v['AUTORIA']);
            }
            if (
                $explicacio !== ''
                && !str_ends_with($explicacio, '.')
                && !str_ends_with($explicacio, '?')
                && !str_ends_with($explicacio, '!')
            ) {
                $explicacio .= '.';
            }

            $body = '';
            if ($explicacio !== '') {
                $body .= '<div>' . ucfirst($explicacio) . '</div>';
            }
            if ($v['EXEMPLES'] !== null) {
                $exemples = ct($v['EXEMPLES']);
                if ($meta_desc === '') {
                    $meta_desc = "Exemple: {$exemples}";
                }
                $body .= '<div><i>' . ucfirst($exemples) . '</i>';
                if (!str_ends_with($exemples, '?') && !str_ends_with($exemples, '!')) {
                    $body .= '.';
                }
                $body .= '</div>';
            }
            if ($v['SINONIM'] !== null) {
                $sinonim = trim(trim(trim($v['SINONIM']), '.'));
                $sinonim = htmlspecialchars($sinonim, ENT_NOQUOTES);
                if ($meta_desc === '') {
                    $meta_desc = 'Sinònim: ' . $sinonim;
                }
                $body .= '<div>Sinònim: ' . $sinonim;
                if (!str_ends_with($v['SINONIM'], '?') && !str_ends_with($v['SINONIM'], '!')) {
                    $body .= '.';
                }
                $body .= '</div>';
            }
            if ($v['EQUIVALENT'] !== null) {
                $equivalent_label = 'Equivalent';
                $idioma = $v['IDIOMA'] !== null ? get_idioma($v['IDIOMA']) : '';
                if ($idioma !== '') {
                    $equivalent_label = "Equivalent en {$idioma}";
                }
                if ($meta_desc === '') {
                    $meta_desc = $equivalent_label . ': ' . ct($v['EQUIVALENT']);
                }
                $iso_code = $v['IDIOMA'] !== null ? get_idioma_iso_code($v['IDIOMA']) : '';
                if ($iso_code !== '') {
                    $body .= "<div>{$equivalent_label}: <span lang=\"{$iso_code}\">" . ct($v['EQUIVALENT']) . '</span>';
                } else {
                    $body .= "<div>{$equivalent_label}: " . ct($v['EQUIVALENT']);
                }
                if (!str_ends_with($v['EQUIVALENT'], '?') && !str_ends_with($v['EQUIVALENT'], '!')) {
                    $body .= '.';
                }
                $body .= '</div>';
            }
            if ($v['LLOC'] !== null) {
                $body .= '<div>Lloc: ' . ct($v['LLOC']) . '.</div>';
                if ($meta_desc_fallback === '') {
                    $meta_desc_fallback = 'Lloc: ' . ct($v['LLOC']);
                }
            }
            if ($v['FONT'] !== null) {
                $font = ct($v['FONT']);
                $body .= '<div>Font: ' . $font;
                // For DSFF.
                if (!str_ends_with($font, '*')) {
                    $body .= '.';
                }
                $body .= '</div>';
            }

            $paremia .= '<div class="entrada">';
            if ($body !== '') {
                $paremia .= $body;
            }
            $paremia .= '<footer>' . $work . '.</footer>';
            $paremia .= '</div>';
            if ($prev_work !== $work) {
                $variant_sources++;
            }
            $prev_work = $work;
        } elseif ($v['LLOC'] !== null) {
            $paremia .= '<div class="entrada">';
            $paremia .= '<div>Lloc: ' . ct($v['LLOC']) . '.</div>';
            $paremia .= '</div>';
            if ($meta_desc_fallback === '') {
                $meta_desc_fallback = 'Lloc: ' . ct($v['LLOC']);
            }
            $variant_sources++;
        }
    }

    $modisme_safe = htmlspecialchars($modisme);
    if ($total_variants > 1 || $modisme_safe !== get_page_title()) {
        $rendered_variant = "<h2>{$modisme_safe}</h2>";
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
    } else {
        $rendered_variant = $paremia;
    }

    $rendered_array[] = [
        'html' => $rendered_variant,
        'count' => $variant_sources,
    ];
}

if ($meta_desc === '') {
    $meta_desc = $meta_desc_fallback;
}
set_meta_description($meta_desc);

// Build the right column.
// Common Voice.
$mp3_files = get_cv_files($paremiotipus_db);
$cv_output = '';
foreach ($mp3_files as $file) {
    if (is_file(__DIR__ . "/../../docroot/mp3/{$file}")) {
        $is_first_audio = $cv_output === '';
        if ($is_first_audio) {
            set_og_audio_url("https://pccd.dites.cat/mp3/{$file}");
        }

        $cv_output .= '<a class="audio" href="/mp3/' . $file . '">';
        $cv_output .= '<audio preload="none" src="/mp3/' . $file . '"></audio>';
        $cv_output .= '<img width="32" height="27" alt="Altaveu" src="/img/speaker.svg">';
        $cv_output .= '</a>';
    } else {
        error_log("Error: asset file is missing: {$file}");
    }
}
if ($cv_output !== '') {
    $blocks .= '<div id="commonvoice" class="bloc text-break" title="Escolteu-ho">';
    $blocks .= $cv_output;
    $blocks .= '<p>';
    $blocks .= '<a title="Projecte Common Voice" href="https://commonvoice.mozilla.org/ca">';
    $blocks .= '<img alt="Logotip de Common Voice" width="100" height="25" src="/img/commonvoice.svg">';
    $blocks .= '</a>';
    $blocks .= '</p>';
    $blocks .= '</div>';
}

// Images.
$images = get_images($paremiotipus_db);
$i = 0;
foreach ($images as $image) {
    if (is_file(__DIR__ . '/../../docroot/img/imatges/' . $image['Identificador'])) {
        $i++;
        $is_first_image = $i === 1;
        if ($is_first_image) {
            // Use it for meta image.
            set_meta_image('https://pccd.dites.cat/img/imatges/' . rawurlencode($image['Identificador']));

            // Add an id for anchor links (once).
            $blocks .= '<div id="imatges" class="bloc bloc-imatge text-break">';
        } else {
            $blocks .= '<div class="bloc bloc-imatge text-break">';
        }

        $blocks .= '<figure>';

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
            $blocks .= '<a href="' . $image_url . '">' . $image_tag . '</a>';
        } else {
            $blocks .= $image_tag;
        }

        $work = '';
        if ($image['AUTOR'] !== null) {
            $work .= htmlspecialchars($image['AUTOR']);
        }
        if ($image['ANY'] > 0) {
            if ($work !== '') {
                $work .= ' ';
            }
            $work .= '(' . $image['ANY'] . ')';
        }
        if ($image['DIARI'] !== null && $image['DIARI'] !== $image['AUTOR']) {
            if ($work !== '') {
                $work .= ': ';
            }

            // If there is no ARTICLE, link DIARI to the content.
            $diari = htmlspecialchars($image['DIARI']);
            if ($image_url !== '' && $image['ARTICLE'] === null) {
                $diari = '<a href="' . $image_url . '">' . $diari . '</a>';
            }
            $work .= "<em>{$diari}</em>";
        }
        if ($image['ARTICLE'] !== null) {
            if ($work !== '') {
                $work .= ' ';
            }

            // Link to the content, unless the text has a link already.
            if (str_contains($image['ARTICLE'], 'http')) {
                // In that case, link to the included URL.
                $article = htmlEscapeAndLinkUrls($image['ARTICLE']);
            } else {
                $article = htmlspecialchars($image['ARTICLE']);
                // Reuse the link of the image, if there is one.
                if ($image_url !== '') {
                    $article = '<a href="' . $image_url . '">' . $article . '</a>';
                }
            }
            $work .= "«{$article}»";
        }

        if ($work !== '') {
            $blocks .= '<figcaption class="small">' . $work . '</figcaption>';
        }

        $blocks .= '</figure>';
        $blocks .= '</div>';
    }
}

// Main page output.
$output = '';
if ($total_variants > 1) {
    $output = '<div class="resum">' . count($modismes) . "&nbsp;recurrències en {$total_variants}&nbsp;variants.";
    if ($total_min_year < YEAR_MAX) {
        $output .= " Primera&nbsp;citació:&nbsp;{$total_min_year}.";
    }
    $output .= '<div class="shortcuts">';
    $output .= '<button type="button" id="toggle-all" title="Amaga els detalls de cada font">contrau-ho tot</button>';

    // Add an anchor link to the multimedia content, only visible on mobile.
    if (get_meta_image() !== '') {
        $output .= '<a class="media-link d-inlineblock d-md-none" href="' . ($cv_output !== '' ? '#commonvoice' : '#imatges') . '">';
        $output .= 'ves als fitxers';
        $output .= '</a>';
    }
    $output .= '</div></div>';
}

set_side_blocks($blocks);

// Print the variants, sorted by the number of sources.
usort($rendered_array, 'variants_comp');
foreach ($rendered_array as $rendered_variant) {
    $output .= $rendered_variant['html'];
}

echo $output;

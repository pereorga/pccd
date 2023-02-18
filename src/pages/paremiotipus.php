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

// We'll populate the page title.
global $page_title;

// We'll try to set meta values based on the available fields.
global $meta_desc;
global $meta_img;

// We'll set a canonical URL, basically to avoid SEO issues with URLs that have different case or accents.
global $canonical_url;

// We'll populate the right column.
global $side_blocks;

/** @var string $request_uri */
/** @psalm-suppress PossiblyUndefinedArrayOffset */
$request_uri = $_SERVER['REQUEST_URI'];

$variants = [];
$paremiotipus = is_string($_GET['paremiotipus']) ? path_to_name($_GET['paremiotipus']) : '';
$modismes = get_modismes($paremiotipus);
// Group them by the variants.
foreach ($modismes as $m) {
    if (!isset($variants[$m['MODISME']])) {
        $variants[$m['MODISME']] = [];
    }
    $variants[$m['MODISME']][] = $m;
}

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

        // Redirect old URLs to the new ones.
        if (!str_starts_with($request_uri, '/p/')) {
            header("Location: {$canonical_url}", true, 301);

            exit;
        }

        // Get the page title.
        $page_title = get_paremiotipus_display($paremiotipus_db);
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
                $body .= '<p>' . ucfirst($explicacio) . '</p>';
            }
            if ($v['EXEMPLES'] !== null) {
                $exemples = ct($v['EXEMPLES']);
                if ($meta_desc === '') {
                    $meta_desc = "Exemple: {$exemples}";
                }
                $body .= '<p><i>' . ucfirst($exemples) . '</i>';
                if (!str_ends_with($exemples, '?') && !str_ends_with($exemples, '!')) {
                    $body .= '.';
                }
                $body .= '</p>';
            }
            if ($v['SINONIM'] !== null) {
                if ($meta_desc === '') {
                    $meta_desc = 'Sinònim: ' . ct($v['SINONIM']);
                }
                $body .= '<p>Sinònim: ' . ct($v['SINONIM']);
                if (!str_ends_with($v['SINONIM'], '?') && !str_ends_with($v['SINONIM'], '!')) {
                    $body .= '.';
                }
                $body .= '</p>';
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
                    $body .= "<p>{$equivalent_label}: <span lang=\"{$iso_code}\">" . ct($v['EQUIVALENT']) . '</span>';
                } else {
                    $body .= "<p>{$equivalent_label}: " . ct($v['EQUIVALENT']);
                }
                if (!str_ends_with($v['EQUIVALENT'], '?') && !str_ends_with($v['EQUIVALENT'], '!')) {
                    $body .= '.';
                }
                $body .= '</p>';
            }
            if ($v['LLOC'] !== null) {
                $body .= '<p>Lloc: ' . ct($v['LLOC']) . '.</p>';
                if ($meta_desc_fallback === '') {
                    $meta_desc_fallback = 'Lloc: ' . ct($v['LLOC'] . '.');
                }
            }
            if ($v['FONT'] !== null) {
                $font = ct($v['FONT']);
                $body .= '<p>Font: ' . $font;
                // For DSFF.
                if (!str_ends_with($font, '*')) {
                    $body .= '.';
                }
                $body .= '</p>';
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
            $paremia .= '<p>Lloc: ' . ct($v['LLOC']) . '.</p>';
            $paremia .= '</div>';
            if ($meta_desc_fallback === '') {
                $meta_desc_fallback = 'Lloc: ' . ct($v['LLOC'] . '.');
            }
            $variant_sources++;
        }
    }

    $modisme = htmlspecialchars($modisme);
    if ($total_variants > 1 || $modisme !== $page_title) {
        $rendered_variant = "<h2>{$modisme}</h2>";
        $rendered_variant .= '<details open>';
        $rendered_variant .= '<summary>';
        $rendered_variant .= ($variant_sources === 1) ? '1 font' : "{$variant_sources} fonts";
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

// Build the right column.
// Common Voice.
$mp3_files = get_cv_files($paremiotipus_db);
$cv_output = '';
foreach ($mp3_files as $file) {
    if (is_file(__DIR__ . "/../../docroot/mp3/{$file}")) {
        $cv_output .= '<a class="audio" href="/mp3/' . $file . '">';
        $cv_output .= '<audio preload="none" src="/mp3/' . $file . '"></audio>';
        $cv_output .= '<img width="32" height="27" alt="Altaveu" src="/img/speaker.svg">';
        $cv_output .= '</a>';
    } else {
        error_log("Error: asset file is missing: {$file}");
    }
}
if ($cv_output !== '') {
    $side_blocks .= '<div id="commonvoice" class="bloc text-break" title="Escolteu-ho">';
    $side_blocks .= $cv_output;
    $side_blocks .= '<p>';
    $side_blocks .= '<a title="Projecte Common Voice" href="https://commonvoice.mozilla.org/ca">';
    $side_blocks .= '<img alt="Logotip de Common Voice" width="100" height="25" src="/img/commonvoice.svg">';
    $side_blocks .= '</a>';
    $side_blocks .= '</p>';
    $side_blocks .= '</div>';
}
// Images.
$images = get_images($paremiotipus_db);
$i = 0;
foreach ($images as $r) {
    if (is_file(__DIR__ . '/../../docroot/img/imatges/' . $r['Identificador'])) {
        $i++;
        $is_first_image = $i === 1;
        if ($is_first_image) {
            // Use it for meta image.
            $meta_img = 'https://pccd.dites.cat/img/imatges/' . rawurlencode($r['Identificador']);

            // Add an id for anchor links (once).
            $side_blocks .= '<div id="imatges" class="bloc bloc-imatge text-break">';
        } else {
            $side_blocks .= '<div class="bloc bloc-imatge text-break">';
        }

        $side_blocks .= '<figure>';
        $link = '';
        if (
            $r['URL'] !== null
            && (str_starts_with($r['URL'], 'http://') || str_starts_with($r['URL'], 'https://'))
            && filter_var($r['URL'], \FILTER_SANITIZE_URL) === $r['URL']
        ) {
            $link = $r['URL'];

            // TODO: properly encode full URLs.
            $link = str_replace(['&', '[', ']'], ['&amp;', '%5B', '%5D'], $link);

            $side_blocks .= '<a href="' . $link . '">';
        }

        // Generate image tag(s). Do not lazy load the first image.
        $side_blocks .= get_image_tags(
            $r['Identificador'],
            '/img/imatges/',
            $paremiotipus,
            $r['WIDTH'],
            $r['HEIGHT'],
            !$is_first_image
        );

        if ($link !== '') {
            $side_blocks .= '</a>';
        }

        $work = '';
        if ($r['AUTOR'] !== null) {
            $work .= htmlspecialchars($r['AUTOR']);
        }
        if ($r['ANY'] > 0) {
            if ($work !== '') {
                $work .= ' ';
            }
            $work .= '(' . $r['ANY'] . ')';
        }
        if ($r['DIARI'] !== null && $r['DIARI'] !== $r['AUTOR']) {
            if ($work !== '') {
                $work .= ': ';
            }

            // If there is no ARTICLE, link DIARI to the content.
            $diari = htmlspecialchars($r['DIARI']);
            if ($link !== '' && $r['ARTICLE'] === null) {
                $diari = '<a href="' . $link . '">' . $diari . '</a>';
            }
            $work .= "<em>{$diari}</em>";
        }
        if ($r['ARTICLE'] !== null) {
            if ($work !== '') {
                $work .= ' ';
            }

            // Link to the content, unless the text has a link already.
            if (str_contains($r['ARTICLE'], 'http')) {
                // In that case, link to the included URL.
                $article = htmlEscapeAndLinkUrls($r['ARTICLE']);
            } else {
                $article = htmlspecialchars($r['ARTICLE']);
                if ($link !== '') {
                    $article = '<a href="' . $link . '">' . $article . '</a>';
                }
            }
            $work .= "«{$article}»";
        }

        if ($work !== '') {
            $side_blocks .= '<figcaption class="small">' . $work . '</figcaption>';
        }

        $side_blocks .= '</figure>';
        $side_blocks .= '</div>';
    }
}

// Main page output.
$output = '';
if ($total_variants > 1) {
    $output = '<div class="resum">' . count($modismes) . ' recurrències en ' . $total_variants . ' variants.';
    if ($total_min_year < YEAR_MAX) {
        $output .= " Primera citació: {$total_min_year}.";
    }
    $output .= '<div class="tools">';
    $output .= '<button type="button" id="toggle-all" class="d-none">contrau-ho tot</button>';

    // Add an anchor link to the multimedia content, only visible on mobile.
    $anchor_link = '';
    if ($cv_output !== '') {
        $anchor_link = '#commonvoice';
    } elseif ($meta_img !== '') {
        $anchor_link = '#imatges';
    }
    if ($anchor_link !== '') {
        $output .= '<a class="media-link d-inlineblock d-md-none" href="' . $anchor_link . '">ves als fitxers</a>';
        // Add a link to main content too.
        $side_blocks .= '<p class="media-link-bottom-wrapper bloc bloc-2 d-block d-md-none">';
        $side_blocks .= '<a class="media-link" href="#contingut">torna a dalt</a>';
        $side_blocks .= '</p>';
    }
    $output .= '</div></div>';
}

// Print the variants, sorted by the number of sources.
usort($rendered_array, 'variants_comp');
foreach ($rendered_array as $rendered_variant) {
    $output .= $rendered_variant['html'];
}

echo $output;

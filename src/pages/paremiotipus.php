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
    return_404_and_exit(get_paremiotipus_best_match($paremiotipus));
}

$editorials = get_editorials();
$fonts = get_fonts();

// Loop through the variants.
$paremiotipus_db = '';
$paremiotipus_title = '';
$canonical_url = '';
$total_min_year = YEAR_MAX;
$rendered_variants_array = [];
foreach ($variants as $modisme => $variant) {
    if ($canonical_url === '') {
        // Set the canonical URL and page title.
        $paremiotipus_db = $variant[0]['PAREMIOTIPUS'];
        $canonical_url = get_paremiotipus_url(paremiotipus: $paremiotipus_db, absolute: true);

        // Redirect old URLs to the new ones.
        if (!str_starts_with($request_uri, '/p/')) {
            header("Location: {$canonical_url}", response_code: 301);

            exit;
        }

        set_canonical_url($canonical_url);
        $paremiotipus_title = get_paremiotipus_display($paremiotipus_db);
        set_page_title($paremiotipus_title);
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
            $diari = '<i>' . htmlspecialchars($v['DIARI']) . '</i>';
            if ($v['ID_FONT'] !== null && isset($fonts[$v['ID_FONT']])) {
                $diari = '<a href="' . get_obra_url($v['ID_FONT']) . '">' . $diari . '</a>';
            }
            $work .= $diari;
        }
        if ($v['ARTICLE'] !== null) {
            if ($work !== '') {
                $work .= ' ';
            }
            $work .= '«' . html_escape_and_link_urls($v['ARTICLE']) . '»';
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
                $equivalent = ct($v['EQUIVALENT']);
                $idioma = $v['IDIOMA'] !== null ? get_idioma($v['IDIOMA']) : '';
                if ($idioma !== '') {
                    $iso_code = get_idioma_iso_code($v['IDIOMA'] ?? '');
                    if ($iso_code !== '') {
                        $equivalent = "<span lang=\"{$iso_code}\">{$equivalent}</span>";
                    }
                    $body .= "<div>Equivalent en {$idioma}: {$equivalent}</div>";
                } else {
                    $body .= "<div>Equivalent: {$equivalent}</div>";
                }
            }
            if ($v['LLOC'] !== null) {
                $body .= '<div>Lloc: ' . ct($v['LLOC']) . '</div>';
            }
            if ($v['FONT'] !== null && strlen($v['FONT']) > 1) {
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
                    $paremia .= '<div class="footer">' . $work . '</div>';
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
        'count' => $variant_sources,
        'html' => $rendered_variant,
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
        $cv_output .= '<img width="32" height="27" alt="▶ Reprodueix" src="/img/speaker.svg">';
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

        $image_tag = get_image_tags(
            file_name: $image['Identificador'],
            path: '/img/imatges/',
            alt_text: $paremiotipus,
            width: $image['WIDTH'],
            height: $image['HEIGHT'],
            preload: $is_first_image,
            preload_media: '(min-width: 768px)'
        );

        $image_link = get_clean_url($image['URL_ENLLAÇ']);
        if ($image_link !== '') {
            $image_tag = '<a href="' . $image_link . '">' . $image_tag . '</a>';
        }

        $images_output .= '<div class="bloc bloc-image text-break"><figure>';
        $images_output .= $image_tag;

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
            if ($image_link !== '' && $image['ARTICLE'] === null) {
                $diari = '<a href="' . $image_link . '" class="external" target="_blank" rel="noopener noreferrer">' . $diari . '</a>';
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
                $article = html_escape_and_link_urls($image['ARTICLE']);
            } else {
                $article = htmlspecialchars($image['ARTICLE']);
                // Reuse the link of the image, if there is one.
                if ($image_link !== '') {
                    $article = '<a href="' . $image_link . '" class="external" target="_blank" rel="noopener noreferrer">' . $article . '</a>';
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

if ($images_output === '') {
    set_meta_image('https://pccd.dites.cat/og/' . name_to_path($paremiotipus_db) . '.png');
}

$blocks = '';
if ($cv_output !== '') {
    $blocks = '<div id="commonvoice" class="bloc text-balance text-break" title="Reprodueix un enregistrament">';
    $blocks .= $cv_output;
    $blocks .= '<p><a href="https://commonvoice.mozilla.org/ca">';
    $blocks .= '<img title="Projecte Common Voice" alt="Logo Common Voice" width="100" height="25" src="/img/commonvoice.svg"></a></p>';
    $blocks .= '</div>';
}
if ($images_output !== '') {
    $blocks .= '<div id="imatges">';
    $blocks .= $images_output;
    $blocks .= '</div>';
}
set_paremiotipus_blocks($blocks);

// Main page output.
if ($total_variants > 1) {
    $output = '<div class="description">';
    $output .= count($modismes) . "&nbsp;recurrències en {$total_variants}&nbsp;variants.";
    if ($total_min_year < YEAR_MAX) {
        $output .= " Primera&nbsp;citació:&nbsp;{$total_min_year}.";
    }
    $output .= '</div>';
    $output .= '<div class="shortcuts">';
    $output .= '<button type="button" id="toggle-all" title="Amaga els detalls de cada font">Contrau-ho tot</button>';
} else {
    $output = '<div class="shortcuts">';
}

$output .= '<div class="share-wrapper">';
$output .= '<button type="button" id="share">Comparteix <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M17 22q-1.3 0-2.1-.9T14 19v-.7l-7-4.1q-.4.4-.9.6T5 15q-1.3 0-2.1-.9T2 12t.9-2.1T5 9q.6 0 1.1.2t1 .6l7-4.1v-.3L14 5q0-1.3.9-2.1T17 2t2.1.9T20 5t-.9 2.1T17 8q-.6 0-1.1-.2t-1-.6l-7 4.1v.3l.1.4q.1.3 0 .4t0 .3l7 4.1q.4-.4.9-.6T17 16q1.3 0 2.1.9T20 19t-.9 2.1-2.1.9"/></svg></button>';
$output .= '<div class="share-icons" hidden>';
$output .= '<span class="close" role="button" tabindex="0" aria-label="Tanca"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M6.4 19L5 17.6l5.6-5.6L5 6.4L6.4 5l5.6 5.6L17.6 5L19 6.4L13.4 12l5.6 5.6l-1.4 1.4l-5.6-5.6z"/></svg></span>';
$output .= '<a class="share-icon facebook" href="https://www.facebook.com/sharer/sharer.php?u=' . $canonical_url . '" target="_blank" rel="noopener noreferrer"><span class="share-image" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95"/></svg></span><span class="share-title">Facebook</span></a>';
$output .= '<a class="share-icon twitter" href="https://x.com/intent/post?url=' . $canonical_url . '" target="_blank" rel="noopener noreferrer"><span class="share-image" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M22.46 6c-.77.35-1.6.58-2.46.69c.88-.53 1.56-1.37 1.88-2.38c-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29c0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15c0 1.49.75 2.81 1.91 3.56c-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.2 4.2 0 0 1-1.93.07a4.28 4.28 0 0 0 4 2.98a8.52 8.52 0 0 1-5.33 1.84q-.51 0-1.02-.06C3.44 20.29 5.7 21 8.12 21C16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56c.84-.6 1.56-1.36 2.14-2.23"/></svg></span><span class="share-title">Twitter</span></a>';
$output .= '<a class="share-icon whatsapp" href="https://api.whatsapp.com/send?text=' . $canonical_url . '" target="_blank" rel="noopener noreferrer"><span class="share-image" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M19.05 4.91A9.82 9.82 0 0 0 12.04 2c-5.46 0-9.91 4.45-9.91 9.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21c5.46 0 9.91-4.45 9.91-9.91c0-2.65-1.03-5.14-2.9-7.01m-7.01 15.24c-1.48 0-2.93-.4-4.2-1.15l-.3-.18l-3.12.82l.83-3.04l-.2-.31a8.26 8.26 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.24-8.24c2.2 0 4.27.86 5.82 2.42a8.18 8.18 0 0 1 2.41 5.83c.02 4.54-3.68 8.23-8.22 8.23m4.52-6.16c-.25-.12-1.47-.72-1.69-.81c-.23-.08-.39-.12-.56.12c-.17.25-.64.81-.78.97c-.14.17-.29.19-.54.06c-.25-.12-1.05-.39-1.99-1.23c-.74-.66-1.23-1.47-1.38-1.72c-.14-.25-.02-.38.11-.51c.11-.11.25-.29.37-.43s.17-.25.25-.41c.08-.17.04-.31-.02-.43s-.56-1.34-.76-1.84c-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.43.06-.66.31c-.22.25-.86.85-.86 2.07s.89 2.4 1.01 2.56c.12.17 1.75 2.67 4.23 3.74c.59.26 1.05.41 1.41.52c.59.19 1.13.16 1.56.1c.48-.07 1.47-.6 1.67-1.18c.21-.58.21-1.07.14-1.18s-.22-.16-.47-.28"/></svg></span><span class="share-title">WhatsApp</span></a>';
$output .= '<a class="share-icon telegram" href="https://t.me/share/?url=' . $canonical_url . '" target="_blank" rel="noopener noreferrer"><span class="share-image" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10s10-4.48 10-10S17.52 2 12 2m4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19c-.14.75-.42 1-.68 1.03c-.58.05-1.02-.38-1.58-.75c-.88-.58-1.38-.94-2.23-1.5c-.99-.65-.35-1.01.22-1.59c.15-.15 2.71-2.48 2.76-2.69a.2.2 0 0 0-.05-.18c-.06-.05-.14-.03-.21-.02c-.09.02-1.49.95-4.22 2.79c-.4.27-.76.41-1.08.4c-.36-.01-1.04-.2-1.55-.37c-.63-.2-1.12-.31-1.08-.66c.02-.18.27-.36.74-.55c2.92-1.27 4.86-2.11 5.83-2.51c2.78-1.16 3.35-1.36 3.73-1.36c.08 0 .27.02.39.12c.1.08.13.19.14.27c-.01.06.01.24 0 .38"/></svg></span><span class="share-title">Telegram</span></a>';
$output .= '<a class="share-icon email" href="mailto:?subject=' . rawurlencode($paremiotipus_title) . '&amp;body=' . $canonical_url . '"><span class="share-image" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M29 9v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9m26 0a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2m26 0l-11.862 8.212a2 2 0 0 1-2.276 0L3 9"/></svg></span><span class="share-title">Correu</span></a>';
$output .= '<a class="share-icon copy" href="#"><span class="share-image" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="M18.327 7.286h-8.044a1.93 1.93 0 0 0-1.925 1.938v10.088c0 1.07.862 1.938 1.925 1.938h8.044a1.93 1.93 0 0 0 1.925-1.938V9.224c0-1.07-.862-1.938-1.925-1.938"/><path d="M15.642 7.286V4.688c0-.514-.203-1.007-.564-1.37a1.92 1.92 0 0 0-1.361-.568H5.673c-.51 0-1 .204-1.36.568a1.95 1.95 0 0 0-.565 1.37v10.088c0 .514.203 1.007.564 1.37s.85.568 1.361.568h2.685"/></g></svg></span><span class="share-title">Enllaç</span></a>';
$output .= '</div></div></div>';

// Sort variants by the number of sources.
usort($rendered_variants_array, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);
foreach ($rendered_variants_array as $rendered_variant) {
    $output .= $rendered_variant['html'];
}

echo $output;

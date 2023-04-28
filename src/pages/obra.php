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

$request_uri = get_request_uri();
$obra_title = is_string($_GET['obra']) ? path_to_name($_GET['obra']) : '';
$obra = get_obra($obra_title);
if ($obra === false) {
    // Try to redirect (HTTP 301) to an existing page.
    try_to_redirect_manual_and_exit();

    // If no match could be found, return an HTTP 404 page.
    error_log("Error: no s'ha trobat l'obra per l'URL: " . $request_uri);
    return_404_and_exit();
}

$canonical_url = get_obra_url($obra['Identificador'], true);
set_canonical_url($canonical_url);

// Redirect old URLs to the new ones.
if (!str_starts_with($request_uri, '/obra/')) {
    header("Location: {$canonical_url}", true, 301);

    exit;
}

set_page_title(htmlspecialchars($obra['Identificador']));
$meta_desc = '';

$output = '<section class="obra text-break">';
$output .= '<div class="row">';

$image_exists = is_file(__DIR__ . '/../../docroot/img/obres/' . $obra['Imatge']);
if ($image_exists) {
    $output .= '<aside class="col-sm-5 order-2 order-sm-1">';
    $output .= get_image_tags($obra['Imatge'], '/img/obres/', $obra['Títol'] ?? '', $obra['WIDTH'], $obra['HEIGHT'], false);
    set_meta_image('https://pccd.dites.cat/img/obres/' . rawurlencode($obra['Imatge']));
    $output .= '</aside>';

    $output .= '<article class="col-sm-7 order-1 order-sm-2">';
} else {
    $output .= '<article class="col-sm">';
}

if ($obra['Títol'] !== null) {
    $output .= '<h1>' . htmlspecialchars($obra['Títol']) . '</h1>';
}
if ($obra['Autor'] !== null) {
    $output .= '<dl><dt>Autor:</dt><dd>' . htmlspecialchars($obra['Autor']) . '</dd></dl>';
}
if ($obra['Any'] !== null) {
    $output .= '<dl><dt>Any de publicació:</dt><dd>' . htmlspecialchars($obra['Any']) . '</dd></dl>';
}
if ($obra['ISBN'] !== null) {
    $isbn = htmlspecialchars($obra['ISBN']);
    if (isbn_is_valid($isbn)) {
        $output .= '<dl><dt>ISBN:</dt><dd><a title="Cerqueu l\'obra a llibreries i biblioteques" href="https://ca.wikipedia.org/wiki/Especial:Fonts_bibliogr%C3%A0fiques?isbn=' . $isbn . '">' . $isbn . '</a></dd></dl>';
    } else {
        $output .= "<dl><dt>ISBN:</dt><dd>{$isbn}</dd></dl>";
    }
}
if ($obra['Editorial'] !== null) {
    $output .= '<dl><dt>Editorial:</dt><dd>' . htmlspecialchars($obra['Editorial']) . '</dd></dl>';
}
if ($obra['Municipi'] !== null) {
    $output .= '<dl><dt>Municipi:</dt><dd>' . htmlspecialchars($obra['Municipi']) . '</dd></dl>';
}
if ($obra['Edició'] !== null) {
    $output .= '<dl><dt>Edició:</dt><dd>' . htmlspecialchars($obra['Edició']) . '</dd></dl>';
}
if ($obra['Any_edició'] > 0) {
    $output .= "<dl><dt>Any de l'edició:</dt><dd>" . $obra['Any_edició'] . '</dd></dl>';
}
if ($obra['Collecció'] !== null) {
    $output .= '<dl><dt>Col·lecció:</dt><dd>' . htmlspecialchars($obra['Collecció']) . '</dd></dl>';
}
if ($obra['Núm_collecció'] !== null) {
    $output .= '<dl><dt>Núm. de la col·lecció:</dt><dd>' . htmlspecialchars($obra['Núm_collecció']) . '</dd></dl>';
}
if ($obra['Pàgines'] > 0) {
    $output .= '<dl><dt>Núm. de pàgines:</dt><dd>' . format_nombre($obra['Pàgines']) . '</dd></dl>';
}
if ($obra['Idioma'] !== null) {
    $output .= '<dl><dt>Idioma:</dt><dd>' . htmlspecialchars($obra['Idioma']) . '</dd></dl>';
}
if ($obra['Preu'] > 0) {
    $output .= '<dl><dt>Preu de compra:</dt><dd>' . round($obra['Preu']) . ' €</dd></dl>';
}
if ($obra['Data_compra'] !== null) {
    $date = DateTime::createFromFormat('Y-m-d', $obra['Data_compra']);
    if ($date !== false) {
        $output .= '<dl><dt>Data de compra:</dt><dd>' . $date->format('d/m/Y') . '</dd></dl>';
    } else {
        $output .= '<dl><dt>Data de compra:</dt><dd>' . htmlspecialchars($obra['Data_compra']) . '</dd></dl>';
    }
}
if ($obra['Lloc_compra'] !== null) {
    $output .= '<dl><dt>Lloc de compra:</dt><dd>' . htmlEscapeAndLinkUrls($obra['Lloc_compra'], '_blank', 'nofollow noopener noreferrer') . '</dd></dl>';
}
if ($obra['URL'] !== null) {
    $output .= '<dl><dt>Enllaç:</dt><dd>' . htmlEscapeAndLinkUrls($obra['URL']) . '</dd></dl>';
}
if ($obra['Observacions'] !== null) {
    $output .= '<dl><dt>Observacions:</dt><dd>' . htmlEscapeAndLinkUrls($obra['Observacions'], '_blank', 'nofollow noopener noreferrer') . '</dd></dl>';
    $meta_desc = htmlspecialchars($obra['Observacions']);
}

if ($obra['Registres'] > 0) {
    $fitxes = format_nombre($obra['Registres']);
    $recollides = format_nombre(get_paremiotipus_count_by_font($obra['Identificador']));
    $registres = "Aquesta obra té {$fitxes} fitxes a la base de dades, de les quals {$recollides} estan recollides en aquest web.";
    if ($meta_desc === '') {
        // If Observacions was empty, use this as the meta description.
        $meta_desc = $registres;
    }

    $output .= '<footer>' . $registres . '</footer>';
}

$output .= '</article></div></section>';

set_meta_description($meta_desc);

echo $output;

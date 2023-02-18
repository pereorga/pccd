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

// Works (source) pages, usually about a book or a website.

// We'll populate the page title.
global $page_title;

// We'll try to set meta values based on the available fields.
global $meta_img;
global $meta_desc;

// We'll set a canonical URL.
global $canonical_url;

/** @var string $request_uri */
/** @psalm-suppress PossiblyUndefinedArrayOffset */
$request_uri = $_SERVER['REQUEST_URI'];

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

// Redirect old URLs to the new ones.
if (!str_starts_with($request_uri, '/obra/')) {
    header("Location: {$canonical_url}", true, 301);

    exit;
}

$page_title = htmlspecialchars($obra['Identificador']);

$output = '<article class="obra text-break">';
$output .= '<div class="row">';

$image_exists = is_file(__DIR__ . '/../../docroot/img/obres/' . $obra['Imatge']);
if ($image_exists) {
    $output .= '<div class="col-sm-5 order-2 order-sm-1">';
    $output .= get_image_tags($obra['Imatge'], '/img/obres/', $obra['Títol'] ?? '', $obra['WIDTH'], $obra['HEIGHT'], false);
    $meta_img = 'https://pccd.dites.cat/img/obres/' . rawurlencode($obra['Imatge']);
    $output .= '</div>';

    $output .= '<div class="col-sm-7 order-1 order-sm-2 mb-3">';
} else {
    $output .= '<div class="col-sm mb-3">';
}

if ($obra['Títol'] !== null) {
    $output .= '<h1>' . htmlspecialchars($obra['Títol']) . '</h1>';
}
if ($obra['Autor'] !== null) {
    $output .= '<p>Autor: ' . htmlspecialchars($obra['Autor']) . '</p>';
}
if ($obra['Any'] !== null) {
    $output .= '<p>Any de publicació: ' . htmlspecialchars($obra['Any']) . '</p>';
}
if ($obra['ISBN'] !== null) {
    $isbn = htmlspecialchars($obra['ISBN']);
    if (isbn_is_valid($isbn)) {
        $output .= '<p>ISBN: <a title="Cerqueu l\'obra a llibreries i biblioteques" href="https://ca.wikipedia.org/wiki/Especial:Fonts_bibliogr%C3%A0fiques?isbn=' . $isbn . '">' . $isbn . '</a></p>';
    } else {
        $output .= "<p>ISBN: {$isbn}</p>";
    }
}
if ($obra['Editorial'] !== null) {
    $output .= '<p>Editorial: ' . htmlspecialchars($obra['Editorial']) . '</p>';
}
if ($obra['Municipi'] !== null) {
    $output .= '<p>Municipi: ' . htmlspecialchars($obra['Municipi']) . '</p>';
}
if ($obra['Edició'] !== null) {
    $output .= '<p>Edició: ' . htmlspecialchars($obra['Edició']) . '</p>';
}
if ($obra['Any_edició'] > 0) {
    $output .= "<p>Any de l'edició: " . htmlspecialchars((string) $obra['Any_edició']) . '</p>';
}
if ($obra['Collecció'] !== null) {
    $output .= '<p>Col·lecció: ' . htmlspecialchars($obra['Collecció']) . '</p>';
}
if ($obra['Núm_collecció'] !== null) {
    $output .= '<p>Núm. de la col·lecció: ' . htmlspecialchars($obra['Núm_collecció']) . '</p>';
}
if ($obra['Pàgines'] > 0) {
    $output .= '<p>Núm. de pàgines: ' . format_nombre($obra['Pàgines']) . '</p>';
}
if ($obra['Idioma'] !== null) {
    $output .= '<p>Idioma: ' . htmlspecialchars($obra['Idioma']) . '</p>';
}
if ($obra['Preu'] > 0) {
    $output .= '<p>Preu de compra: ' . round($obra['Preu']) . ' €</p>';
}
if ($obra['Data_compra'] !== null) {
    $date = DateTime::createFromFormat('Y-m-d', $obra['Data_compra']);
    if ($date !== false) {
        $output .= '<p>Data de compra: ' . $date->format('d/m/Y') . '</p>';
    } else {
        $output .= '<p>Data de compra: ' . htmlspecialchars($obra['Data_compra']) . '</p>';
    }
}
if ($obra['Lloc_compra'] !== null) {
    $output .= '<p>Lloc de compra: ' . htmlEscapeAndLinkUrls($obra['Lloc_compra'], '_blank', 'nofollow noopener noreferrer') . '</p>';
}
if ($obra['URL'] !== null) {
    $output .= '<p>Enllaç: ' . htmlEscapeAndLinkUrls($obra['URL']) . '</p>';
}
if ($obra['Observacions'] !== null) {
    $output .= '<p>Observacions: ' . htmlEscapeAndLinkUrls($obra['Observacions'], '_blank', 'nofollow noopener noreferrer') . '</p>';
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

    $output .= '<p class="peu">' . $registres . '</p>';
}

$output .= '</div></div></article>';

echo $output;

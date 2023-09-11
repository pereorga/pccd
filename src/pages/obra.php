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
    error_log("Error: entry not found for URL: {$request_uri}");
    return_404_and_exit();
}

$canonical_url = get_obra_url($obra['Identificador'], true);

// Redirect old URLs to the new ones.
if (!str_starts_with($request_uri, '/obra/')) {
    header("Location: {$canonical_url}", true, 301);

    exit;
}

set_canonical_url($canonical_url);
set_page_title(htmlspecialchars($obra['Títol']));

$is_book = $obra['ISBN'] !== null;
if ($is_book) {
    set_og_type('book');
    $output = '<div class="row" vocab="http://schema.org/" typeof="Book">';
} else {
    $output = '<div class="row" vocab="http://schema.org/" typeof="Thing">';
}

if (is_file(__DIR__ . '/../../docroot/img/obres/' . $obra['Imatge'])) {
    set_meta_image('https://pccd.dites.cat/img/obres/' . rawurlencode($obra['Imatge']));
    $output .= '<aside class="col-image">';
    $output .= get_image_tags(
        $obra['Imatge'],
        '/img/obres/',
        $is_book ? 'Coberta' : $obra['Títol'],
        $obra['WIDTH'],
        $obra['HEIGHT'],
        false
    );
    $output .= '</aside>';
}

$output .= '<div class="col-work text-break">';
if ($obra['Autor'] !== null) {
    $output .= '<dl>';
    $output .= '<dt>Autor:</dt>';
    $output .= '<dd property="author" typeof="Person">';
    $output .= '<span property="name">' . htmlspecialchars($obra['Autor']) . '</span>';
    $output .= '</dd>';
    $output .= '</dl>';
}
if ($obra['Any'] !== null) {
    $output .= '<dl>';
    $output .= '<dt>Any de publicació:</dt>';
    $output .= '<dd property="datePublished">' . htmlspecialchars($obra['Any']) . '</dd>';
    $output .= '</dl>';
}
if ($obra['ISBN'] !== null) {
    $isbn = htmlspecialchars($obra['ISBN']);
    $output .= '<dl>';
    $output .= '<dt>ISBN:</dt>';
    $output .= '<dd>';
    if (isbn_is_valid($isbn)) {
        $isbn_url = 'https://ca.wikipedia.org/wiki/Especial:Fonts_bibliogr%C3%A0fiques?isbn=' . $isbn;
        $output .= '<a property="isbn" title="Cerqueu l\'obra a llibreries i biblioteques" href="' . $isbn_url . '" class="external">';
        $output .= $isbn;
        $output .= '</a>';
    } else {
        $output .= $isbn;
    }
    $output .= '</dd>';
    $output .= '</dl>';
}
if ($obra['Editorial'] !== null && $obra['Editorial'] !== 'Web') {
    $output .= '<dl>';
    $output .= '<dt>Editorial:</dt>';
    $output .= '<dd property="publisher" typeof="Organization">';
    $output .= '<span property="name">' . htmlspecialchars($obra['Editorial']) . '</span>';
    $output .= '</dd>';
    $output .= '</dl>';
}
if ($obra['Municipi'] !== null) {
    $output .= '<dl>';
    $output .= '<dt>Municipi:</dt>';
    $output .= '<dd property="locationCreated" typeof="Place">';
    $output .= '<span property="name">' . htmlspecialchars($obra['Municipi']) . '</span>';
    $output .= '</dd>';
    $output .= '</dl>';
}
if ($obra['Edició'] !== null) {
    $output .= '<dl>';
    $output .= '<dt>Edició:</dt>';
    $output .= '<dd property="bookEdition">' . htmlspecialchars($obra['Edició']) . '</dd>';
    $output .= '</dl>';
}
if ($obra['Any_edició'] > 0) {
    $output .= '<dl>';
    $output .= "<dt>Any de l'edició:</dt>";
    $output .= '<dd property="copyrightYear">' . $obra['Any_edició'] . '</dd>';
    $output .= '</dl>';
}
if ($obra['Collecció'] !== null) {
    $output .= '<dl>';
    $output .= '<dt>Col·lecció:</dt>';
    $output .= '<dd>' . htmlspecialchars($obra['Collecció']) . '</dd>';
    $output .= '</dl>';
}
if ($obra['Núm_collecció'] !== null) {
    $output .= '<dl>';
    $output .= '<dt>Núm. de la col·lecció:</dt>';
    $output .= '<dd>' . htmlspecialchars($obra['Núm_collecció']) . '</dd>';
    $output .= '</dl>';
}
if ($obra['Pàgines'] > 0) {
    $output .= '<dl>';
    $output .= '<dt>Núm. de pàgines:</dt>';
    $output .= '<dd property="numberOfPages">' . format_nombre($obra['Pàgines']) . '</dd>';
    $output .= '</dl>';
}
if ($obra['Idioma'] !== null) {
    $output .= '<dl>';
    $output .= '<dt>Idioma:</dt>';
    $output .= '<dd property="inLanguage">' . htmlspecialchars($obra['Idioma']) . '</dd>';
    $output .= '</dl>';
}
if ($obra['Preu'] > 0) {
    $output .= '<dl>';
    $output .= '<dt>Preu de compra:</dt>';
    $output .= '<dd>' . round($obra['Preu']) . ' €</dd>';
    $output .= '</dl>';
}
if ($obra['Data_compra'] !== null) {
    $date = DateTime::createFromFormat('Y-m-d', $obra['Data_compra']);
    $output .= '<dl>';
    $output .= '<dt>Data de compra:</dt>';
    $output .= '<dd>';
    $output .= $date !== false ? $date->format('d/m/Y') : htmlspecialchars($obra['Data_compra']);
    $output .= '</dd>';
    $output .= '</dl>';
}
if ($obra['Lloc_compra'] !== null) {
    $output .= '<dl>';
    $output .= '<dt>Lloc de compra:</dt>';
    $output .= '<dd>' . htmlspecialchars($obra['Lloc_compra']) . '</dd>';
    $output .= '</dl>';
}
if ($obra['URL'] !== null) {
    $output .= '<div>' . htmlEscapeAndLinkUrls($obra['URL'], 'url') . '</div>';
}
if ($obra['Observacions'] !== null) {
    $comment = htmlEscapeAndLinkUrls(ct($obra['Observacions'], false));
    $output .= '<dl>';
    $output .= '<dt>Observacions:</dt>';
    $output .= '<dd property="description">' . $comment . '</dd>';
    $output .= '</dl>';
    set_meta_description_once(ct($obra['Observacions']));
}
if ($obra['Registres'] > 0) {
    $fitxes = format_nombre($obra['Registres']);
    $recollides = format_nombre(get_paremiotipus_count_by_font($obra['Identificador']));
    $registres = "Aquesta obra té {$fitxes} fitxes a la base de dades, de les quals {$recollides} estan recollides en aquest web.";
    $output .= "<footer>{$registres}</footer>";
    set_meta_description_once($registres);
}

$output .= '</div></div>';
echo $output;

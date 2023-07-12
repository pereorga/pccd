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

$output = '<div class="row">';

if (is_file(__DIR__ . '/../../docroot/img/obres/' . $obra['Imatge'])) {
    $output .= '<aside class="col-image">';
    $output .= get_image_tags($obra['Imatge'], '/img/obres/', $obra['Títol'], $obra['WIDTH'], $obra['HEIGHT'], false);
    set_meta_image('https://pccd.dites.cat/img/obres/' . rawurlencode($obra['Imatge']));
    $output .= '</aside>';
}

$output .= '<div class="col-work text-break">';
if ($obra['Autor'] !== null) {
    $output .= '<dl><dt>Autor:</dt><dd>' . htmlspecialchars($obra['Autor']) . '</dd></dl>';
}
if ($obra['Any'] !== null) {
    $output .= '<dl><dt>Any de publicació:</dt><dd>' . htmlspecialchars($obra['Any']) . '</dd></dl>';
}
if ($obra['ISBN'] !== null) {
    $isbn = htmlspecialchars($obra['ISBN']);
    $output .= '<dl><dt>ISBN:</dt><dd>';
    if (isbn_is_valid($isbn)) {
        $output .= '<a title="Cerqueu l\'obra a llibreries i biblioteques" href="https://ca.wikipedia.org/wiki/Especial:Fonts_bibliogr%C3%A0fiques?isbn=' . $isbn . '" class="external">' . $isbn . '</a>';
    } else {
        $output .= $isbn;
    }
    $output .= '</dd></dl>';
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
    $output .= '<dl><dt>Data de compra:</dt><dd>';
    $output .= $date !== false ? $date->format('d/m/Y') : htmlspecialchars($obra['Data_compra']);
    $output .= '</dd></dl>';
}
if ($obra['Lloc_compra'] !== null) {
    $output .= '<dl><dt>Lloc de compra:</dt><dd>' . htmlEscapeAndLinkUrls($obra['Lloc_compra'], '_blank', 'nofollow noopener noreferrer') . '</dd></dl>';
}
if ($obra['URL'] !== null) {
    $output .= '<div>' . htmlEscapeAndLinkUrls($obra['URL']) . '</div>';
}
if ($obra['Observacions'] !== null) {
    $output .= '<dl><dt>Observacions:</dt><dd>' . htmlEscapeAndLinkUrls(ct($obra['Observacions'], false), '_blank', 'nofollow noopener noreferrer') . '</dd></dl>';
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

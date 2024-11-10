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

// Search page, including the homepage.
set_meta_description("La PCCD dona accés a la consulta d'un gran ventall de fonts fraseològiques sobre parèmies en general (locucions, frases fetes, refranys, proverbis, citacions, etc.)");

$results_per_page = get_page_limit();
$current_page = get_page_number();
$offset = ($current_page - 1) * $results_per_page;
$search_mode = isset($_GET['mode']) && is_string($_GET['mode']) && $_GET['mode'] !== '' ? $_GET['mode'] : 'conté';

$search = '';
$raw_search_clean = '';
if (isset($_GET['cerca']) && is_string($_GET['cerca']) && $_GET['cerca'] !== '') {
    $trimmed_search = trim($_GET['cerca']);
    $raw_search_clean = htmlspecialchars($trimmed_search);
    $search_length = strlen($trimmed_search);
    if ($search_length > 0 && $search_length < 255) {
        // Switch to internal search modes based on conditions.
        if ($search_mode === 'conté') {
            if (str_starts_with($trimmed_search, '"') && str_ends_with($trimmed_search, '"')) {
                // Simple custom search mode for whole sentence search.
                $search_mode = 'whole_sentence';
            } elseif (
                !str_contains($trimmed_search, ' ')
                && (
                    str_contains($trimmed_search, '*')
                    || str_contains($trimmed_search, '?')
                )
            ) {
                // Simple custom search mode for using wildcards in single-word searches.
                $search_mode = 'wildcard';
            }
        }

        $search = normalize_search($trimmed_search, $search_mode);
    }
}
?>
<form method="get" role="search">
    <div class="filters">
        <div class="row">
            <div class="mode">
                <select name="mode" aria-label="Mode de cerca">
                    <option value="">conté</option>
                    <option<?php echo $search_mode === 'comença' ? ' selected' : ''; ?> value="comença">comença per</option>
                    <option<?php echo $search_mode === 'acaba' ? ' selected' : ''; ?> value="acaba">acaba en</option>
                    <option<?php echo $search_mode === 'coincident' ? ' selected' : ''; ?> value="coincident">coincident</option>
                </select>
            </div>
            <div class="input">
                <input type="search" name="cerca" autocapitalize="off" autocomplete="off" autofocus value="<?php echo $raw_search_clean; ?>" placeholder="Introduïu un o diversos termes" aria-label="Introduïu un o diversos termes" pattern=".*[a-zA-Z]+.*" required>
                <button type="submit" aria-label="Cerca">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14"/></svg>
                </button>
            </div>
        </div>
        <div class="row">
            <div class="label">Inclou en la cerca:</div>
            <div class="options">
                <div title="Variants del paremiotipus">
                    <input type="checkbox" name="variant" id="variant" value=""<?php echo checkbox_checked('variant') ? ' checked' : ''; ?>>
                    <label for="variant">variants</label>
                </div>
                <div title="Expressions sinònimes">
                    <input type="checkbox" name="sinonim" id="sinonim" value=""<?php echo checkbox_checked('sinonim') ? ' checked' : ''; ?>>
                    <label for="sinonim">sinònims</label>
                </div>
                <div title="Equivalents en altres llengües">
                    <input type="checkbox" name="equivalent" id="equivalent" value=""<?php echo checkbox_checked('equivalent') ? ' checked' : ''; ?>>
                    <label for="equivalent">altres idiomes</label>
                </div>
            </div>
        </div>
    </div>
<?php

// Build query.
$where_clause = '';
$arguments = [];
if ($search === '' && !isset($_GET['font'])) {
    // If the search is empty, we are in the main page.
    set_page_title('Paremiologia catalana comparada digital');
    $total = get_n_paremiotipus();
} else {
    set_page_title('Cerca «' . $raw_search_clean . '»');
    $arguments = build_search_query($search, $search_mode, $where_clause);
    $total = get_n_results($where_clause, $arguments);
}

$output = '';
$number_of_pages = 0;
if ($total > 0) {
    $number_of_pages = (int) ceil($total / $results_per_page);
    if ($search !== '') {
        if ($number_of_pages > 1) {
            set_page_title('Cerca «' . $raw_search_clean . "», pàgina {$current_page}");
        }
        $output .= '<p class="text-break">';
        $output .= build_search_summary(
            offset: $offset,
            results_per_page: $results_per_page,
            total: $total,
            search_string: $raw_search_clean
        );
        $output .= '</p>';
    }

    $paremiotipus = get_paremiotipus_search_results(
        where_clause: $where_clause,
        arguments: $arguments,
        limit: $results_per_page,
        offset: $offset
    );
    $output .= '<ol>';
    foreach ($paremiotipus as $p) {
        $output .= '<li><a href="' . get_paremiotipus_url($p) . '">' . get_paremiotipus_display($p) . '</a></li>';
    }
    $output .= '</ol>';
} else {
    $output .= '<p>';
    $output .= "No s'ha trobat cap resultat coincident amb";
    $output .= ' <span class="text-monospace text-break">' . $raw_search_clean . '</span>.';
    $output .= '</p>';

    if (
        $search_mode !== 'wildcard'
        && str_contains($raw_search_clean, ' ')
        && (str_contains($raw_search_clean, '?') || str_contains($raw_search_clean, '*'))
    ) {
        $output .= '<p>';
        $output .= '<strong>Nota:</strong> els caràcters <span class="text-monospace">*</span> i';
        $output .= ' <span class="text-monospace">?</span> funcionen com a comodins només quan es busca un sol terme';
        $output .= ' amb el mode <em>conté</em>.';
        $output .= '</p>';
    }
}

$output .= '<div class="pager">';
// Only show pagination links if there is more than one page.
if ($number_of_pages > 1) {
    $output .= render_pager($current_page, $number_of_pages);
}
$output .= '<select name="mostra" aria-label="Nombre de resultats per pàgina">';
$output .= '<option value="10">10</option>';
$output .= '<option' . ($results_per_page === 15 ? ' selected' : '') . ' value="15">15</option>';
$output .= '<option' . ($results_per_page === 25 ? ' selected' : '') . ' value="25">25</option>';
$output .= '<option' . ($results_per_page === 50 ? ' selected' : '') . ' value="50">50</option>';
$output .= '</select>';
$output .= '</div>';
$output .= '</form>';

echo $output;

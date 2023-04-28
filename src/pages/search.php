<?php

declare(strict_types=1);

/**
 * This file is part of PCCD.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 * (c) Víctor Pàmies i Riudor <vpamies@gmail.com>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

/*
 * Search page and custom code functionality.
 *
 * Note that the homepage is a "search" page too.
 */

set_meta_description("Dona accés a la consulta d'un gran ventall de fonts fraseològiques sobre parèmies en general (locucions, frases fetes, refranys, proverbis, citacions, etc.)");

$results_per_page = get_page_limit();
$current_page = get_page_number();
$offset = ($current_page - 1) * $results_per_page;
$search_mode = isset($_GET['mode']) && is_string($_GET['mode']) && $_GET['mode'] !== '' ? $_GET['mode'] : 'conté';

$search = '';
$raw_search_clean = '';
if (isset($_GET['cerca']) && is_string($_GET['cerca']) && $_GET['cerca'] !== '' && !is_numeric($_GET['cerca'])) {
    $trimmed_search = trim($_GET['cerca']);
    $raw_search_clean = htmlspecialchars($trimmed_search);
    $search_length = strlen($trimmed_search);
    if ($search_length > 0 && $search_length < SEARCH_MAX_LENGTH) {
        // Switch to internal search modes based on conditions.
        if ($search_mode === 'conté') {
            if (str_starts_with($trimmed_search, '"') && str_ends_with($trimmed_search, '"')) {
                // Simple custom search mode for whole sentence search.
                $search_mode = 'whole_sentence';
            } elseif (!str_contains($trimmed_search, ' ') && (str_contains($trimmed_search, '*') || str_contains($trimmed_search, '?'))) {
                // Simple custom search mode for using wildcards in single-word searches.
                $search_mode = 'wildcard';
            }
        }

        $search = normalize_search($trimmed_search, $search_mode);
    }
}
?>
<form method="get" id="search-form">
    <aside>
        <div class="form-row">
            <div class="col-sm-3 order-2 order-sm-1 form-group">
                <select id="mode" name="mode" title="Mode de cerca" aria-label="Seleccioneu el mode de cerca">
                    <option value="">conté</option>
                    <option<?php echo $search_mode === 'comença' ? ' selected' : ''; ?> value="comença">comença per</option>
                    <option<?php echo $search_mode === 'acaba' ? ' selected' : ''; ?> value="acaba">acaba en</option>
                </select>
            </div>
            <div class="col order-1 order-sm-2 form-group">
                <div class="input-group">
                    <input type="search" id="cerca" name="cerca" aria-autocomplete="both" autocapitalize="off" autocomplete="off" value="<?php echo $raw_search_clean; ?>" placeholder="Introduïu un o diversos termes" aria-label="Cerqueu parèmies" maxlength="255" pattern="(.|\s)*\S(.|\s)*" autofocus required>
                    <div class="input-group-append">
                        <button type="submit" class="btn" aria-label="Cerca" title="Cerca">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-row form-group">
            <div class="col-2 col-sm-3 text-nowrap search-options-label">Inclou<span class="d-none d-lg-inline"> en la cerca</span>:</div>
            <div class="col">
                <div class="form-check">
                    <input type="checkbox" name="variant" id="variant" title="Variants del paremiotipus" value=""<?php echo checkbox_checked('variant') ? ' checked' : ''; ?>>
                    <label for="variant" title="Variants del paremiotipus">variants</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="sinonim" id="sinonim" title="Expressions sinònimes" value=""<?php echo checkbox_checked('sinonim') ? ' checked' : ''; ?>>
                    <label for="sinonim" title="Expressions sinònimes">sinònims</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="equivalent" id="equivalent" title="Equivalents en altres llengües" value=""<?php echo checkbox_checked('equivalent') ? ' checked' : ''; ?>>
                    <label for="equivalent" title="Equivalents en altres llengües">altres idiomes</label>
                </div>
            </div>
        </div>
    </aside>
<?php

// Build query.
$where_clause = '';
$arguments = [];
if ($search !== '') {
    set_page_title('Cerca «' . $raw_search_clean . '»');
    $arguments = build_search_query($search, $search_mode, $where_clause);
    $total = get_n_results($where_clause, $arguments);
} else {
    // If the search is empty, get the total number of paremiotipus.
    $total = get_n_paremiotipus();
}

$output = '';
if ($total > 0) {
    $number_of_pages = (int) ceil($total / $results_per_page);
    if ($search !== '') {
        if ($number_of_pages > 1) {
            set_page_title(get_page_title() . ", pàgina {$current_page}");
        }
        $output .= '<p class="text-break">';
        $output .= build_search_summary($offset, $results_per_page, $total, $raw_search_clean);
        $output .= '</p>';
    }

    $paremiotipus = get_paremiotipus_search_results($where_clause, $arguments, $offset, $results_per_page);
    $output .= '<table class="table table-bordered table-striped"><tbody>';
    foreach ($paremiotipus as $p) {
        $output .= '<tr><td>';
        $output .= '<a href="' . get_paremiotipus_url($p) . '">' . get_paremiotipus_display($p) . '</a>';
        $output .= '</td></tr>';

        if ($total === 1) {
            // Tell the browser to prefetch the paremiotipus page if there is only one result.
            set_prefetch_url(get_paremiotipus_url($p), 'document');
        }
    }
    $output .= '</tbody></table>';

    $output .= '<div class="pager">';
    // Only show pagination selector and pager if it can be useful.
    if ($total > PAGER_DEFAULT) {
        // Only show pager if there is more than one page.
        if ($number_of_pages > 1) {
            $output .= render_pager($current_page, $number_of_pages);
        }

        $output .= '<div class="float-right">
                        <select id="mostra" name="mostra" aria-label="Limiteu el nombre de resultats per pàgina" title="Nombre de resultats per pàgina">
                            <option' . ($results_per_page === PAGER_DEFAULT ? ' selected' : '') . ' value="10">10</option>
                            <option' . ($results_per_page === 15 ? ' selected' : '') . ' value="15">15</option>
                            <option' . ($results_per_page === 25 ? ' selected' : '') . ' value="25">25</option>
                            <option' . ($results_per_page === 50 ? ' selected' : '') . ' value="50">50</option>
                        </select>
                    </div>';
    }
    $output .= '</div>';
} else {
    $output .= '<p class="text-break">';
    $output .= "No s'ha trobat cap resultat coincident amb ";
    $output .= '<span class="text-monospace">' . $raw_search_clean . '</span>.';
    $output .= '</p>';

    if (
        $search_mode !== 'wildcard'
        && str_contains($raw_search_clean, ' ')
        && (str_contains($raw_search_clean, '?') || str_contains($raw_search_clean, '*'))
    ) {
        $output .= '<p>';
        $output .= '<strong>Nota:</strong> els caràcters <span class="text-monospace">*</span> i';
        $output .= ' <span class="text-monospace">?</span> funcionen com a comodins només quan es busca un sol terme';
        $output .= ' en el mode <em>conté</em>.';
        $output .= '</p>';
    }
}

$output .= '</form>';

echo $output;

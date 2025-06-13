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

require __DIR__ . '/search_functions.php';

PageRenderer::setMetaDescription("La PCCD dona accés a la consulta d'un gran ventall de fonts fraseològiques sobre parèmies en general (locucions, frases fetes, refranys, proverbis, citacions, etc.)");
PageRenderer::setMetaImage('https://pccd.dites.cat/img/screenshot.png');
PageRenderer::setOgType(OgType::WEBSITE);

$search_mode = get_search_mode();
$search_normalized = get_search_normalized($search_mode);
$search_clean = get_search_clean();

$where_clause = '';
$arguments = [];
if ($search_normalized === '' && !isset($_GET['font'])) {
    // If the search is empty, we are in the home.
    PageRenderer::setTitle('Paremiologia catalana comparada digital');
    PageRenderer::setCanonicalUrl('https://pccd.dites.cat');
    $result_count = get_paremiotipus_count();
} else {
    // Otherwise, we are in a search page.
    PageRenderer::setTitle('Cerca «' . $search_clean . '»');
    [$where_clause, $arguments] = build_search_sql_query($search_normalized, $search_mode);
    $result_count = get_result_count($where_clause, $arguments);
}

$pagination_limit = get_search_pagination_limit();
$page_count = get_search_page_count($result_count, $pagination_limit);
$current_page_number = get_search_page_number();
if ($page_count > 1 && $search_normalized !== '') {
    // Show the page number in the title too.
    PageRenderer::setTitle('Cerca «' . $search_clean . "», pàgina {$current_page_number}");
}
?>
<form method="get" role="search">
    <div class="filters">
        <div class="row">
            <div class="mode">
                <select name="mode" aria-label="Mode de cerca">
                    <option value="">conté</option>
                    <option<?php echo $search_mode === SearchMode::STARTS_WITH ? ' selected' : ''; ?> value="comença">comença per</option>
                    <option<?php echo $search_mode === SearchMode::ENDS_WITH ? ' selected' : ''; ?> value="acaba">acaba en</option>
                    <option<?php echo $search_mode === SearchMode::EXACT ? ' selected' : ''; ?> value="coincident">coincident</option>
                </select>
            </div>
            <div class="input">
                <input type="search" name="cerca" autocapitalize="off" autocomplete="off" autofocus value="<?php echo $search_clean; ?>" placeholder="Introduïu un o diversos termes" aria-label="Introduïu un o diversos termes" pattern=".*[a-zA-Z]+.*" required>
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

if ($result_count > 0) {
    $offset = get_search_page_offset($current_page_number, $pagination_limit);
    if ($search_normalized !== '') {
        echo '<p class="text-break">';
        echo render_search_summary(
            offset: $offset,
            results_per_page: $pagination_limit,
            result_count: $result_count,
            search_string: $search_clean
        );
        echo '</p>';
    }

    $paremiotipus = get_paremiotipus_search_results(
        where_clause: $where_clause,
        arguments: $arguments,
        limit: $pagination_limit,
        offset: $offset
    );
    echo '<ol>';
    foreach ($paremiotipus as $p) {
        echo '<li><a href="' . get_paremiotipus_url($p) . '">' . get_paremiotipus_display($p) . '</a></li>';
    }
    echo '</ol>';
} else {
    echo '<p>';
    echo "No s'ha trobat cap resultat coincident amb";
    echo ' <span class="text-monospace text-break">' . $search_clean . '</span>.';
    echo '</p>';
}

echo '<div class="pager">';
// Only show pagination links if there is more than one page.
if ($page_count > 1) {
    echo render_search_pager($current_page_number, $page_count);
}
echo '<select name="mostra" aria-label="Nombre de resultats per pàgina">';
echo '<option value="10">10</option>';
echo '<option' . ($pagination_limit === 15 ? ' selected' : '') . ' value="15">15</option>';
echo '<option' . ($pagination_limit === 25 ? ' selected' : '') . ' value="25">25</option>';
echo '<option' . ($pagination_limit === 50 ? ' selected' : '') . ' value="50">50</option>';
echo '</select>';
echo '</div>';
echo '</form>';

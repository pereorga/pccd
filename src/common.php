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

// Common code.

const TITLE_MAX_LENGTH = 70;
const ISBN10_LENGTH = 10;
const ISBN13_LENGTH = 13;
const PAGER_DEFAULT = 10;
const SEARCH_MAX_LENGTH = 255;
const EASTER_EGG_PAGER_LIMIT = 999999;
const MAX_RANDOM_PAREMIOTIPUS = 10000;

/**
 * Sort array by a `count` key, desc.
 *
 * @param array{html: string, count: int} $a
 * @param array{html: string, count: int} $b
 */
function variants_comp(array $a, array $b): int
{
    return $b['count'] <=> $a['count'];
}

/**
 * Formats an HTML title, truncated to 70 characters.
 */
function format_html_title(string $title, string $suffix = ''): string
{
    if (mb_strlen($title) > TITLE_MAX_LENGTH) {
        $s = mb_substr($title, 0, TITLE_MAX_LENGTH - 2);
        $space_pos = mb_strrpos($s, ' ');
        if ($space_pos !== false) {
            $title = mb_substr($s, 0, $space_pos) . '...';
        }
    }

    if ($suffix !== '' && mb_strlen($title . ' - ' . $suffix) <= TITLE_MAX_LENGTH) {
        $title .= ' - ' . $suffix;
    }

    return $title;
}

/**
 * ISBN simple (but incorrect) validation.
 */
function isbn_is_valid(string $isbn): bool
{
    $isbn = str_replace('-', '', $isbn);
    $isbn_removed_chars = preg_replace('/[^a-zA-Z0-9]/', '', $isbn);

    return $isbn === $isbn_removed_chars && (strlen($isbn) === ISBN10_LENGTH || strlen($isbn) === ISBN13_LENGTH);
}

/**
 * Returns the pagination limit from query string. Defaults to 10.
 */
function get_page_limit(): int
{
    if (isset($_GET['mostra']) && is_string($_GET['mostra'])) {
        $mostra = $_GET['mostra'];
        if ($mostra === '15' || $mostra === '25' || $mostra === '50') {
            return (int) $mostra;
        }
        if ($mostra === 'infinit') {
            return EASTER_EGG_PAGER_LIMIT;
        }
    }

    return PAGER_DEFAULT;
}

/**
 * Trims and escapes $string, also removing trailing dot character.
 */
function ct(string $string): string
{
    return htmlspecialchars(trim(trim($string), '.'));
}

/**
 * Returns the current page name.
 */
function get_page_name(): string
{
    $allowed_pages = [
        'credits',
        'instruccions',
        'llibres',
        'obra',
        'paremiotipus',
        'projecte',
        'top100',
        'top10000',
    ];

    foreach ($allowed_pages as $page) {
        if (isset($_GET[$page])) {
            return $page;
        }
    }

    // Default to the search page, which is also the homepage.
    return 'search';
}

/**
 * Returns whether a checkbox should be checked in the search page.
 */
function checkbox_checked(string $checkbox): bool
{
    if (isset($_GET[$checkbox])) {
        return true;
    }

    // "variants" checkbox is enabled by default when the search is empty (e.g. in the homepage)
    if ($checkbox === 'variant' && (!isset($_GET['cerca']) || $_GET['cerca'] === '')) {
        return true;
    }

    return false;
}

/**
 * Returns the paremiotipus for display.
 */
function get_paremiotipus_display(string $paremiotipus): string
{
    /** @var false|string $value */
    $value = function_exists('apcu_fetch') ? apcu_fetch($paremiotipus) : false;
    if ($value === false) {
        global $pdo;
        $stmt = $pdo->prepare('SELECT Display FROM paremiotipus_display WHERE Paremiotipus = :paremiotipus');
        $stmt->bindParam(':paremiotipus', $paremiotipus);
        $stmt->execute();

        $value = $stmt->fetchColumn();
        if ($value === false) {
            error_log("Error: {$paremiotipus} is empty in paremiotipus_display table");
            $value = $paremiotipus;
        }
        if (function_exists('apcu_store')) {
            apcu_store($paremiotipus, $value);
        }
    }

    return htmlspecialchars($value);
}

/**
 * Returns the path for a paremiotipus/obra title.
 */
function name_to_path(string $name): string
{
    return rawurlencode(str_replace([' ', '/'], ['_', '\\'], $name));
}

/**
 * Returns the name for a paremiotipus/obra querystring.
 */
function path_to_name(string $path): string
{
    return str_replace(['_', '\\'], [' ', '/'], $path);
}

/**
 * Tries to get a paremiotipus from a modisme.
 */
function get_paremiotipus_by_modisme(string $modisme): string
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT PAREMIOTIPUS FROM 00_PAREMIOTIPUS WHERE MODISME = :modisme LIMIT 1');
    $stmt->bindParam(':modisme', $modisme);
    $stmt->execute();
    $paremiotipus = $stmt->fetchColumn();

    return $paremiotipus !== false ? $paremiotipus : '';
}

/**
 * Get the list of manual redirects.
 *
 * @return array<string, string>
 */
function get_redirects(): array
{
    // $redirects variable will be set in redirects.php file.
    $redirects = ['/old' => '/new'];

    require __DIR__ . '/redirects.php';

    return $redirects;
}

/**
 * Tries to redirect to a URL, using the manual redirects file.
 *
 * @psalm-suppress PossiblyUndefinedArrayOffset
 */
function try_to_redirect_manual_and_exit(): void
{
    $redirects = get_redirects();

    /** @var string $request_uri */
    $request_uri = $_SERVER['REQUEST_URI'];
    // Standardize spaces encoding.
    $request_uri = str_replace('%2B', '+', $request_uri);

    if (isset($redirects[$request_uri])) {
        header('Location: ' . $redirects[$request_uri], true, 301);

        exit;
    }
}

/**
 * Returns an HTTP 404 page and exits.
 *
 * @phan-return never
 */
function return_404_and_exit(): never
{
    header(
        "Content-Security-Policy: default-src 'none'; "
        . "base-uri 'none'; "
        . "connect-src 'self'; "
        . "frame-ancestors 'none'; "
        . "img-src 'self'; "
        . "prefetch-src 'self'; "
        . "style-src 'unsafe-inline'"
    );
    header('HTTP/1.1 404 Not Found', true, 404);

    require __DIR__ . '/../docroot/404.html';

    exit;
}

/**
 * Try to redirect to a valid paremiotipus page and exit.
 */
function try_to_redirect_to_valid_paremiotipus_and_exit(string $paremiotipus): void
{
    $paremiotipus = trim($paremiotipus);

    // Do nothing if the provided paremiotipus was empty.
    if ($paremiotipus === '') {
        return;
    }

    // Try to redirect using the manual redirects file first.
    try_to_redirect_manual_and_exit();

    // Try to get the paremiotipus from the modisme.
    $paremiotipus_match = get_paremiotipus_by_modisme($paremiotipus);
    if ($paremiotipus_match !== '') {
        // Redirect to an existing page.
        header('Location: ' . get_paremiotipus_url($paremiotipus_match), true, 301);

        exit;
    }

    // Try to find the best paremiotipus.
    $paremiotipus_match = get_paremiotipus_best_match($paremiotipus);
    if ($paremiotipus_match !== '') {
        // Redirect to an existing page.
        header('Location: ' . get_paremiotipus_url($paremiotipus_match), true, 301);

        exit;
    }
}

/**
 * Tries to get the best paremiotipus by searching.
 */
function get_paremiotipus_best_match(string $modisme): string
{
    global $pdo;

    // We do not want to avoid words here.
    $modisme = trim($modisme, '-');
    $modisme = str_replace(' -', ' ', $modisme);
    $modisme = trim($modisme);

    $paremiotipus = false;
    $modisme = normalize_search($modisme, 'conté');
    if ($modisme !== '') {
        $stmt = $pdo->prepare('SELECT
            PAREMIOTIPUS
        FROM
            00_PAREMIOTIPUS
        WHERE
            MATCH(PAREMIOTIPUS_LC_WA, MODISME_LC_WA) AGAINST (? IN BOOLEAN MODE)
        ORDER BY
            LENGTH(PAREMIOTIPUS)
        LIMIT
            1');

        try {
            $stmt->execute([$modisme]);
        } catch (Exception $e) {
            error_log('Error buscant el modisme "' . $modisme . '": ' . $e->getMessage());

            return '';
        }

        $paremiotipus = $stmt->fetchColumn();
    }

    return $paremiotipus !== false ? $paremiotipus : '';
}

/**
 * Gets an array of modisme arrays keyed by the modisme title.
 *
 * @phpstan-return list<array{
 *     MODISME: string,
 *     PAREMIOTIPUS: string,
 *     AUTOR: ?string,
 *     AUTORIA: ?string,
 *     DIARI: ?string,
 *     ARTICLE: ?string,
 *     EDITORIAL: ?string,
 *     ANY: ?float,
 *     PAGINA: ?string,
 *     LLOC: ?string,
 *     EXPLICACIO: ?string,
 *     EXPLICACIO2: ?string,
 *     EXEMPLES: ?string,
 *     SINONIM: ?string,
 *     EQUIVALENT: ?string,
 *     IDIOMA: ?string,
 *     FONT: ?string,
 *     ACCEPCIO: ?string,
 *     ID_FONT: ?string,
 * }>
 *
 * @phan-return array<int, array>
 */
function get_modismes(string $paremiotipus): array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT
        DISTINCT MODISME,
        PAREMIOTIPUS,
        AUTOR,
        AUTORIA,
        DIARI,
        ARTICLE,
        EDITORIAL,
        `ANY`,
        PAGINA,
        LLOC,
        EXPLICACIO,
        EXPLICACIO2,
        EXEMPLES,
        SINONIM,
        EQUIVALENT,
        IDIOMA,
        FONT,
        ACCEPCIO,
        ID_FONT
    FROM
        00_PAREMIOTIPUS
    WHERE
        PAREMIOTIPUS = :paremiotipus
    ORDER BY
        MODISME,
        ISNULL(AUTOR),
        AUTOR,
        DIARI,
        ARTICLE,
        `ANY`,
        PAGINA,
        EXPLICACIO,
        EXEMPLES,
        SINONIM,
        EQUIVALENT,
        IDIOMA,
        LLOC');
    $stmt->bindParam(':paremiotipus', $paremiotipus);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Gets a list of image arrays for a specific paremiotipus.
 *
 * @phpstan-return list<array{
 *     Identificador: string,
 *     URL: ?string,
 *     AUTOR: ?string,
 *     ANY: ?float,
 *     DIARI: ?string,
 *     ARTICLE: ?string,
 *     EDITORIAL: ?string,
 *     WIDTH: int,
 *     HEIGHT: int,
 * }>
 *
 * @phan-return array<int, array>
 */
function get_images(string $paremiotipus): array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT
        Identificador,
        `URL_ENLLAÇ` as URL,
        AUTOR,
        `ANY`,
        DIARI,
        ARTICLE,
        WIDTH,
        HEIGHT
    FROM
        00_IMATGES
    WHERE
        PAREMIOTIPUS = :paremiotipus
    ORDER BY
        Comptador DESC');
    $stmt->bindParam(':paremiotipus', $paremiotipus);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Gets a list of Common Voice mp3 files for a specific paremiotipus.
 *
 * @return array<string>
 */
function get_cv_files(string $paremiotipus): array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT `file` FROM `commonvoice` WHERE `paremiotipus` = :paremiotipus');
    $stmt->bindParam(':paremiotipus', $paremiotipus);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Gets an obra array, or false.
 *
 * @phpstan-return false|array{
 *     Identificador: string,
 *     Títol: ?string,
 *     Autor: ?string,
 *     Any: ?string,
 *     ISBN: ?string,
 *     Editorial: ?string,
 *     Municipi: ?string,
 *     Edició: ?string,
 *     Any_edició: ?int,
 *     Collecció: ?string,
 *     Núm_collecció: ?string,
 *     Pàgines: ?int,
 *     Idioma: ?string,
 *     Preu: ?float,
 *     Data_compra: ?string,
 *     Lloc_compra: ?string,
 *     URL: ?string,
 *     Observacions: ?string,
 *     Registres: ?int,
 *     Imatge: string,
 *     WIDTH: int,
 *     HEIGHT: int,
 * }
 *
 * @phan-return array|false
 */
function get_obra(string $obra_title): array|bool
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT
        Identificador,
        `Títol`,
        Autor,
        `Any`,
        ISBN,
        Editorial,
        Municipi,
        `Edició`,
        `Any_edició`,
        `Collecció`,
        `Núm_collecció`,
        `Pàgines`,
        Idioma,
        Preu,
        Data_compra,
        Lloc_compra,
        URL,
        Observacions,
        Registres,
        Imatge,
        WIDTH,
        HEIGHT
    FROM
        00_FONTS
    WHERE
        Identificador = :id');
    $stmt->bindParam(':id', $obra_title);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Returns the number of paremiotipus for a specific font.
 */
function get_paremiotipus_count_by_font(string $font_id): int
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM 00_PAREMIOTIPUS WHERE ID_FONT = :id');
    $stmt->bindParam(':id', $font_id);
    $stmt->execute();

    return $stmt->fetchColumn();
}

/**
 * Returns a canonical URL for the paremiotipus.
 */
function get_paremiotipus_url(string $paremiotipus, bool $absolute = false): string
{
    $url = '';
    if ($absolute) {
        $url = 'https://pccd.dites.cat';
    }

    // TODO: On recent versions of MySQL, different UTF-8 characters are printed (… vs ...). Identify why that happens,
    //       we may potentially want to pass it through normalizer_normalize($paremiotipus, Normalizer::NFKC)
    $url .= '/p/' . name_to_path($paremiotipus);

    return $url;
}

/**
 * Returns a canonical URL for the obra.
 */
function get_obra_url(string $obra, bool $absolute = false): string
{
    $url = '';
    if ($absolute) {
        $url = 'https://pccd.dites.cat';
    }

    $url .= '/obra/' . name_to_path($obra);

    return $url;
}

/**
 * Renders and returns the current page content.
 *
 * @psalm-suppress UnresolvableInclude
 */
function build_main_content(string $page_name): string
{
    ob_start();

    require __DIR__ . "/pages/{$page_name}.php";
    $main_content = ob_get_clean();

    return $main_content !== false ? $main_content : '';
}

/**
 * Returns a search pager URL.
 */
function get_pager_url(int $page_number): string
{
    $mostra = get_page_limit();
    if (!isset($_GET['cerca']) || !is_string($_GET['cerca']) || $_GET['cerca'] === '') {
        // Simplify links to the homepage as much as possible.
        if ($page_number === 1) {
            if ($mostra === PAGER_DEFAULT) {
                return '/';
            }

            return '/?mostra=' . $mostra;
        }

        if ($mostra === PAGER_DEFAULT) {
            return '/?pagina=' . $page_number;
        }

        return '/?mostra=' . $mostra . '&amp;pagina=' . $page_number;
    }

    // Build the URL in the same format as it is when the search form is submitted, so the browser/Varnish cache can be
    // reused.
    $url = '/?mode=';
    if (isset($_GET['mode']) && is_string($_GET['mode']) && $_GET['mode'] !== '') {
        $url .= htmlspecialchars(urlencode($_GET['mode']));
    }

    $url .= '&amp;cerca=' . htmlspecialchars(urlencode($_GET['cerca']));

    $url .= isset($_GET['variant']) ? '&amp;variant=' : '';
    $url .= isset($_GET['sinonim']) ? '&amp;sinonim=' : '';
    $url .= isset($_GET['equivalent']) ? '&amp;equivalent=' : '';

    $url .= '&amp;mostra=' . $mostra;

    if ($page_number > 1) {
        $url .= '&amp;pagina=' . $page_number;
    }

    return $url;
}

/**
 * Renders a search pager element.
 */
function render_pager_element(int $page_number, string $name, string $title = '', bool $is_active = false): string
{
    $rel = '';
    if ($title === 'Primera pàgina') {
        $rel = 'first';
    } elseif ($title === 'Última pàgina') {
        $rel = 'last';
    } elseif ($title === 'Pàgina següent') {
        $rel = 'next';
    } elseif ($title === 'Pàgina anterior') {
        $rel = 'prev';
    }

    $pager_item = '<li>';
    if ($is_active) {
        $pager_item .= '<strong class="page-link" title="' . $title . '">' . $name . '</strong>';
    } else {
        if ($rel !== 'prev' && $rel !== 'next') {
            // On mobile, show only next/prev buttons.
            $pager_item = '<li class="d-none d-sm-block">';
        }
        $pager_item .= '<a class="page-link" href="' . get_pager_url($page_number) . '" title="' . $title . '"';
        if ($rel !== '') {
            $pager_item .= ' rel="' . $rel . '"';
        }
        $pager_item .= '>' . $name . '</a>';
    }
    $pager_item .= '</li>';

    return $pager_item;
}

/**
 * Returns the search pager.
 */
function render_pager(int $page_num, int $num_pages): string
{
    global $prefetch_urls;

    // Previous and first page links.
    $prev_links = '';
    if ($page_num > 1) {
        // Show previous link.
        $prev_links .= render_pager_element(
            $page_num - 1,
            '<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" width="16" height="16" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24"><path fill="currentColor" d="M15.535 3.515L7.05 12l8.485 8.485l1.415-1.414L9.878 12l7.072-7.071l-1.415-1.414Z"/></svg> Anterior',
            'Pàgina anterior'
        );

        // Show first page link.
        $prev_links .= render_pager_element(1, '1', 'Primera pàgina');
    }

    // Current page item.
    $page_links = render_pager_element($page_num, (string) $page_num, 'Sou a la pàgina ' . $page_num, true);

    // `…` previous link.
    if ($page_num > 2) {
        $prev_prev_page = $page_num - 5;
        if ($prev_prev_page < 2) {
            $prev_prev_page = 2;
        }
        $page_links = render_pager_element(
            $prev_prev_page,
            $prev_prev_page === 2 && $page_num === 3 ? '2' : '…',
            'Pàgina ' . $prev_prev_page
        ) . $page_links;
    }

    // `…` next link.
    if ($page_num < $num_pages - 1) {
        $next_next_page = $page_num + 5;
        if ($next_next_page >= $num_pages) {
            $next_next_page = $num_pages - 1;
        }
        $page_links .= render_pager_element(
            $next_next_page,
            $next_next_page === $num_pages - 1 && $page_num === $num_pages - 2 ? (string) ($num_pages - 1) : '…',
            'Pàgina ' . $next_next_page
        );
    }

    // Next and last page links.
    $next_links = '';
    if ($page_num < $num_pages) {
        // Show the last page link.
        $next_links = render_pager_element($num_pages, (string) $num_pages, 'Última pàgina');

        // Show the next link.
        $next_links .= render_pager_element(
            $page_num + 1,
            'Següent <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" width="16" height="16" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24"><path fill="currentColor" d="M8.465 20.485L16.95 12L8.465 3.515L7.05 4.929L14.122 12L7.05 19.071l1.415 1.414Z"/></svg>',
            'Pàgina següent'
        );
        // Make the browser prefetch next page.
        $prefetch_urls[get_pager_url($page_num + 1)] = 'document';
    }

    return '<nav class="float-left" aria-label="Paginació dels resultats"><ul class="pagination flex-wrap">' . $prev_links . $page_links . $next_links . '</ul></nav>';
}

/**
 * Returns the search summary.
 */
function build_search_summary(int $offset, int $results_per_page, int $total, string $raw_search_clean): string
{
    if ($total === 1) {
        return 'S\'ha trobat 1 paremiotipus per a la cerca <span class="text-monospace">' . $raw_search_clean . '</span>.';
    }

    $output = 'S\'han trobat ' . format_nombre($total) . ' paremiotipus per a la cerca <span class="text-monospace">' . $raw_search_clean . '</span>.';

    if ($total > $results_per_page) {
        $first_record_page = $offset + 1;
        $last_record_page = $offset + $results_per_page;
        if ($last_record_page > $total) {
            $last_record_page = $total;
        }

        if ($first_record_page === 1 || $first_record_page === 11) {
            $first_record_page = "de l'{$first_record_page}";
        } else {
            $first_record_page = "del {$first_record_page}";
        }
        if ($last_record_page === 1 || $last_record_page === 11) {
            $last_record_page = "a l'{$last_record_page}";
        } else {
            $last_record_page = "al {$last_record_page}";
        }
        $output .= " Registres {$first_record_page} {$last_record_page}.";
    }

    return $output;
}

/**
 * Formats number in Catalan.
 */
function format_nombre(int|string $num): string
{
    return number_format((float) $num, 0, ',', '.');
}

/**
 * Returns an array of languages from the database.
 *
 * From the 00_EQUIVALENTS table, it returns `IDIOMA` values keyed by `CODI`.
 *
 * @return array<string, string>
 */
function get_idiomes(): array
{
    global $pdo;

    $idiomes = function_exists('apcu_fetch') ? apcu_fetch('idiomes') : false;
    if ($idiomes === false) {
        $stmt = $pdo->query('SELECT CODI, IDIOMA FROM 00_EQUIVALENTS');
        $idiomes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        if (function_exists('apcu_store')) {
            apcu_store('idiomes', $idiomes);
        }
    }

    return $idiomes;
}

/**
 * Gets a language name in lowercase from its language code, or an empty string.
 */
function get_idioma(string $code): string
{
    $code = strtoupper(trim($code));
    if ($code !== '') {
        $languages = get_idiomes();
        if (isset($languages[$code])) {
            return mb_strtolower($languages[$code]);
        }
    }

    return '';
}

/**
 * Tries to return an ISO 639-1 code.
 *
 * ISO 639-2 is returned in some cases, or $code when not found, or an empty string when not valid.
 */
function get_idioma_iso_code(string $code): string
{
    $code = strtolower(trim($code));

    if (strlen($code) === 2 || strlen($code) === 3) {
        $wrong_code_map = [
            // `ar` is the ISO code of Arabic, but in the DB it is used for Aranes and Argentinian (Spanish).
            'ar' => 'oc',
            'as' => 'ast',
            // `bs` is the ISO code of Bosnian, but in the DB it is used for Serbocroata.
            'bs' => 'sh',
            'll' => 'la',
            'po' => 'pl',
            'pr' => 'prv',
            'sa' => 'sc',
            // `si` is the ISO code of Sinhalese, but in the DB it is used for Sicilian.
            'si' => 'scn',
        ];

        return $wrong_code_map[$code] ?? $code;
    }

    return '';
}

/**
 * Gets the current search page number, defaulting to 1.
 */
function get_page_number(): int
{
    if (isset($_GET['pagina']) && is_string($_GET['pagina'])) {
        $pagina = (int) $_GET['pagina'];
        if ($pagina > 0) {
            return $pagina;
        }
    }

    return 1;
}

/**
 * Builds the search query, storing it in $where_clause variable, and returns the search arguments.
 *
 * @return array<int, string>
 */
function build_search_query(string $search, string $search_mode, string &$where_clause): array
{
    global $pdo;

    $checkboxes = [
        'variant' => 'MODISME_LC_WA',
        'sinonim' => 'SINONIM_LC_WA',
        'equivalent' => 'EQUIVALENT_LC_WA',
    ];

    $WORD_BOUNDARY_BEGIN = "'[[:<:]]'";
    $WORD_BOUNDARY_END = "'[[:>:]]'";

    $arguments = [$search];
    if ($search_mode === 'whole_sentence' || $search_mode === 'wildcard') {
        $db_version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        $is_mysql = !str_contains($db_version, 'MariaDB');
        $has_icu = $is_mysql && version_compare($db_version, '8.0.4') >= 0;
        if ($has_icu) {
            // This is needed in MySQL >= v8.0.4. See https://stackoverflow.com/a/59230861/1391963
            $WORD_BOUNDARY_BEGIN = "'\\\\b'";
            $WORD_BOUNDARY_END = "'\\\\b'";
        }

        $where_clause = " WHERE PAREMIOTIPUS_LC_WA REGEXP CONCAT({$WORD_BOUNDARY_BEGIN}, ?, {$WORD_BOUNDARY_END})";
    } elseif ($search_mode === 'comença') {
        $where_clause = " WHERE PAREMIOTIPUS_LC_WA LIKE CONCAT(?, '%')";
    } elseif ($search_mode === 'acaba') {
        $where_clause = " WHERE PAREMIOTIPUS_LC_WA LIKE CONCAT('%', ?)";
    } else {
        // 'conté' (default) search mode uses full-text.
        $columns = 'PAREMIOTIPUS_LC_WA';

        foreach ($checkboxes as $checkbox => $column) {
            if (isset($_GET[$checkbox])) {
                $columns .= ", {$column}";
            }
        }

        $where_clause = " WHERE MATCH({$columns}) AGAINST (? IN BOOLEAN MODE)";
    }

    foreach ($checkboxes as $checkbox => $column) {
        if (isset($_GET[$checkbox])) {
            if ($search_mode === 'whole_sentence' || $search_mode === 'wildcard') {
                $where_clause .= " OR {$column} REGEXP CONCAT({$WORD_BOUNDARY_BEGIN}, ?, {$WORD_BOUNDARY_END})";
                $arguments[] = $search;
            } elseif ($search_mode === 'comença') {
                $where_clause .= " OR {$column} LIKE CONCAT(?, '%')";
                $arguments[] = $search;
            } elseif ($search_mode === 'acaba') {
                $where_clause .= " OR {$column} LIKE CONCAT('%', ?)";
                $arguments[] = $search;
            }
        }
    }

    return $arguments;
}

/**
 * Returns the number of search results.
 *
 * @param array<int, string> $arguments
 */
function get_n_results(string $where_clause, array $arguments): int
{
    global $pdo;

    // Cache the count query if APCu is available.
    $cache_key = $where_clause . ' ' . implode('|', $arguments);
    $total = function_exists('apcu_fetch') ? apcu_fetch($cache_key) : false;
    if ($total === false) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(DISTINCT PAREMIOTIPUS) FROM 00_PAREMIOTIPUS {$where_clause}");
            $stmt->execute($arguments);
            $total = $stmt->fetchColumn();
        } catch (Exception) {
            $total = 0;
        }

        if (function_exists('apcu_store')) {
            apcu_store($cache_key, $total);
        }
    }

    return $total;
}

/**
 * Returns the paremiotipus search results.
 *
 * @param array<int, string> $arguments
 *
 * @return array<int, string>
 */
function get_paremiotipus_search_results(string $where_clause, array $arguments, int $offset, int $limit): array
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT
            DISTINCT PAREMIOTIPUS
        FROM
            00_PAREMIOTIPUS
        {$where_clause}
        ORDER BY
            PAREMIOTIPUS
        LIMIT
            {$offset}, {$limit}");
    $stmt->execute($arguments);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Remove special characters from a string, especially for matching paremiotipus.
 *
 * @param string $search_mode The search mode to normalize for. If provided, the string is processed for search.
 */
function normalize_search(?string $string, string $search_mode = ''): string
{
    if ($string !== null && $string !== '') {
        // Remove useless characters in search that may affect syntax, or that are not useful.
        $string = str_replace(['"', '+', '.', '%', '--', '_', '(', ')', '[', ']', '{', '}', '^', '>', '<', '~', '@', '$', '|', '/', '\\'], '', $string);

        // Normalize to lowercase, standardize simple quotes and remove accents.
        $string = mb_strtolower($string);
        $string = str_replace('’', "'", $string);
        $string = str_replace(['à', 'á'], 'a', $string);
        $string = str_replace(['è', 'é'], 'e', $string);
        $string = str_replace(['í', 'ï'], 'i', $string);
        $string = str_replace(['ò', 'ó'], 'o', $string);
        $string = str_replace(['ú', 'ü'], 'u', $string);

        // Remove double spaces.
        /** @var string $string */
        $string = preg_replace('/\s+/', ' ', $string);
        if ($string !== '') {
            // Fix characters for search.
            if ($search_mode === 'whole_sentence') {
                // Remove wildcards and unnecessary characters.
                $string = str_replace(['*', '?'], '', $string);
            } elseif ($search_mode === 'wildcard') {
                // Replace wildcard characters.
                $string = str_replace(['*', '?'], ['.*', '.'], $string);
            } elseif ($search_mode === 'conté') {
                // Remove characters that may affect FULL-TEXT search syntax.
                $string = str_replace(['*', '?'], '', $string);
                $string = str_replace(' - ', ' ', $string);

                // Nice to have: remove extra useless characters.
                $string = str_replace(['“', '”', '«', '»', '…', ',', ':', ';', '!', '¡', '¿'], '', $string);

                // Build the full-text query.
                $words = preg_split('/\\s+/', $string);

                /** @var string[] $words */
                $string = '';
                foreach ($words as $word) {
                    if (str_starts_with($word, '-')) {
                        // Respect `-` operator.
                        $string .= '-';
                        $word = ltrim($word, '-');
                    } else {
                        // Manually put the `+` operator to ensure the word is searched.
                        $string .= '+';
                    }

                    if (str_contains($word, '-')) {
                        // See https://stackoverflow.com/a/5192800/1391963.
                        $string .= '"' . $word . '" ';
                    } else {
                        $string .= "{$word} ";
                    }
                }
            }

            return trim($string);
        }
    }

    return '';
}

/**
 * Returns array of 00_EDITORIA `NOM` values keyed by `CODI`.
 *
 * @return array<string, string>
 */
function get_editorials(): array
{
    global $pdo;

    $editorials = function_exists('apcu_fetch') ? apcu_fetch('nom_editorials') : false;
    if ($editorials === false) {
        $stmt = $pdo->query('SELECT CODI, NOM FROM 00_EDITORIA');
        $editorials = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        if (function_exists('apcu_store')) {
            apcu_store('nom_editorials', $editorials);
        }
    }

    return $editorials;
}

/**
 * Returns array of 00_FONTS `Títol` values keyed by `Identificador`.
 *
 * @return array<string, string>
 */
function get_fonts(): array
{
    global $pdo;

    $fonts = function_exists('apcu_fetch') ? apcu_fetch('identificador_fonts') : false;
    if ($fonts === false) {
        // We are only using the first column for now (not the title). We could extend this to include the full table
        // and reuse it in the "obra" page, but that may not be worth it.
        $stmt = $pdo->query('SELECT Identificador, Títol FROM 00_FONTS');
        $fonts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        if (function_exists('apcu_store')) {
            apcu_store('identificador_fonts', $fonts);
        }
    }

    return $fonts;
}

/**
 * Gets an image tag.
 *
 * Images will be inside a picture tag that will include an avif or webp file, if they exist.
 */
function get_image_tags(string $file_name, string $path, string $alt_text = '', int $width = 0, int $height = 0, bool $lazy_loading = true): string
{
    $output = '';

    $loading = '';
    if ($lazy_loading) {
        $loading = 'loading="lazy"';
    }

    $width_height = '';
    if ($width > 0 && $height > 0) {
        $width_height = 'width="' . $width . '" height="' . $height . '"';
    }

    // Image files may have been provided in webp/avif format already.
    $optimized_type = '';
    $optimized_file_path = '';
    if (!str_ends_with($file_name, '.webp') && !str_ends_with($file_name, '.avif')) {
        // TODO: Consider providing AVIFs for PNGs and GIFs too.
        $avif_file = str_ireplace('.jpg', '.avif', $file_name);
        $webp_file = str_ireplace('.png', '.webp', $file_name);
        $webp_file = str_ireplace('.gif', '.webp', $webp_file);

        $avif_exists = str_ends_with($avif_file, '.avif') && is_file(__DIR__ . "/../docroot{$path}{$avif_file}");
        if ($avif_exists) {
            $optimized_type = 'avif';
            $optimized_file_path = $path . rawurlencode($avif_file);
        } else {
            $webp_exists = str_ends_with($webp_file, '.webp') && is_file(__DIR__ . "/../docroot{$path}{$webp_file}");
            if ($webp_exists) {
                $optimized_type = 'webp';
                $optimized_file_path = $path . rawurlencode($webp_file);
            }
        }
    }

    if ($optimized_type !== '') {
        $output .= '<picture>';
        $output .= '<source srcset="' . $optimized_file_path . '" type="image/' . $optimized_type . '">';
    }

    $output .= '<img ' . $loading . ' ' . $width_height . ' decoding="async" alt="' . htmlspecialchars($alt_text) . '" src="' . $path . rawurlencode($file_name) . '">';

    if ($optimized_type !== '') {
        $output .= '</picture>';
    }

    return $output;
}

/**
 * Returns the total number of occurrences (modismes).
 */
function get_n_modismes(): int
{
    global $pdo;

    $n_modismes = function_exists('apcu_fetch') ? apcu_fetch('n_modismes') : false;
    if ($n_modismes === false) {
        $stmt = $pdo->query('SELECT COUNT(*) FROM 00_PAREMIOTIPUS');
        $n_modismes = $stmt->fetchColumn();
        if (function_exists('apcu_store')) {
            apcu_store('n_modismes', $n_modismes);
        }
    }

    return $n_modismes;
}

/**
 * Returns the total number of distinct paremiotipus.
 */
function get_n_paremiotipus(): int
{
    global $pdo;

    $n_paremiotipus = function_exists('apcu_fetch') ? apcu_fetch('n_paremiotipus') : false;
    if ($n_paremiotipus === false) {
        $stmt = $pdo->query('SELECT COUNT(DISTINCT PAREMIOTIPUS) FROM 00_PAREMIOTIPUS');
        $n_paremiotipus = $stmt->fetchColumn();
        if (function_exists('apcu_store')) {
            apcu_store('n_paremiotipus', $n_paremiotipus);
        }
    }

    return $n_paremiotipus;
}

/**
 * Returns the total number of sources (fonts).
 */
function get_n_fonts(): int
{
    global $pdo;

    $n_fonts = function_exists('apcu_fetch') ? apcu_fetch('n_fonts') : false;
    if ($n_fonts === false) {
        $stmt = $pdo->query('SELECT COUNT(DISTINCT AUTOR, ANY, EDITORIAL) FROM 00_PAREMIOTIPUS');
        $n_fonts = $stmt->fetchColumn();
        if (function_exists('apcu_store')) {
            apcu_store('n_fonts', $n_fonts);
        }
    }

    return $n_fonts;
}

/**
 * Returns a list of top 100 paremiotipus.
 *
 * @return array<string>
 */
function get_top100_paremiotipus(): array
{
    global $pdo;

    $top_paremiotipus = function_exists('apcu_fetch') ? apcu_fetch('top_paremiotipus') : false;
    if ($top_paremiotipus === false) {
        $stmt = $pdo->query('SELECT
                PAREMIOTIPUS, COUNT(*) AS POPULAR
            FROM
                00_PAREMIOTIPUS
            GROUP BY
                PAREMIOTIPUS
            ORDER BY
                POPULAR DESC
            LIMIT
                100');
        $top_paremiotipus = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (function_exists('apcu_store')) {
            apcu_store('top_paremiotipus', $top_paremiotipus);
        }
    }

    return $top_paremiotipus;
}

/**
 * Returns a random paremiotipus from top 100.
 */
function get_random_top100_paremiotipus(): string
{
    $top_paremiotipus = get_top100_paremiotipus();
    $random_key = array_rand($top_paremiotipus);

    return $top_paremiotipus[$random_key];
}

/**
 * Returns a random paremiotipus from top 10000.
 */
function get_random_top10000_paremiotipus(): string
{
    global $pdo;

    $random_offset = mt_rand(0, MAX_RANDOM_PAREMIOTIPUS - 1);
    $stmt = $pdo->query("SELECT Paremiotipus FROM common_paremiotipus LIMIT {$random_offset}, 1");

    $random_paremiotipus = $stmt->fetchColumn();

    return is_string($random_paremiotipus) ? $random_paremiotipus : '';
}

/**
 * Returns a random book by Víctor Pàmies.
 *
 * @return array{Imatge: string, Títol: string, URL: string, WIDTH: int, HEIGHT: int}
 */
function get_random_book(): array
{
    global $pdo;

    // As this query has a limited number of results but runs many times, cache it in memory.
    $books = function_exists('apcu_fetch') ? apcu_fetch('books') : false;
    if ($books === false) {
        $stmt = $pdo->query('SELECT Imatge, `Títol`, URL, WIDTH, HEIGHT FROM `00_OBRESVPR`');
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (function_exists('apcu_store')) {
            apcu_store('books', $books);
        }
    }

    $random_key = array_rand($books);

    return $books[$random_key];
}

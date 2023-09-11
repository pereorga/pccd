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

const EASTER_EGG_PAGER_LIMIT = 999999;
const ISBN10_LENGTH = 10;
const ISBN13_LENGTH = 13;
const MAX_RANDOM_PAREMIOTIPUS = 10000;
const MIN_SINONIM_WORD_LENGTH = 3;
const NONCE_LENGTH = 18;
const PAGER_DEFAULT = 10;
const SEARCH_MAX_LENGTH = 255;
const TITLE_MAX_LENGTH = 70;

const REX_SCHEME = 'https?://';
const REX_DOMAIN = '(?:[-a-zA-Z0-9\x7f-\xff]{1,63}\.)+[a-zA-Z\x7f-\xff][-a-zA-Z0-9\x7f-\xff]{1,62}';
const REX_PORT = '(:[0-9]{1,5})?';
const REX_PATH = '(/[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]*?)?';
const REX_QUERY = '(\?[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?';
const REX_FRAGMENT = '(#[!$-/0-9?:;=@_\':;!a-zA-Z\x7f-\xff]+?)?';
const REX_USERNAME = '[^]\\\\\x00-\x20\"(),:-<>[\x7f-\xff]{1,64}';
const REX_PASSWORD = '[^]\\\\\x00-\x20\"(),:-<>[\x7f-\xff]{1,64}';
const REX_TRAIL_PUNCT = "[)'?.!,;:]";
const REX_NON_URL = "[^-_#$+.!*%'(),;/?:@=&a-zA-Z0-9\x7f-\xff]";

/**
 * Transforms plain text into valid HTML turning URLs into links.
 *
 * Originally based on urlLinker by Søren Løvborg.
 * TODO: make it work with multibyte strings
 * TODO: convert host (whole domain?) to lowercase characters
 */
function htmlEscapeAndLinkUrls(string $text, string $property = ''): string
{
    $rexUrl = '(' . REX_SCHEME . ')?(?:( ' . REX_USERNAME . ' )(:' . REX_PASSWORD . ')?@)?(' . REX_DOMAIN . ')(' . REX_PORT . REX_PATH . REX_QUERY . REX_FRAGMENT . ')';
    $rexUrlLinker = "{\\b{$rexUrl}(?=" . REX_TRAIL_PUNCT . '*(' . REX_NON_URL . '|$))}';

    // List of valid TLDs
    $tlds = '.ac .ad .ae .af .ag .ai .al .am .an .ao .app .aq .ar .arpa .as .asia .at .au .aw .ax .az
        .ba .barcelona .bb .bd .be .bf .bg .bh .bi .bio .biz .blog .bj .bm .bn .bo .br .bs .bt .bv
        .bw .by .bz .ca .cat .cc .cd .cf .cg .ch .ci .ck .cl .cm .cn .co .com .coop .cr .cu .cv .cw
        .cx .cy .cz .de .dev .dj .dk .dm .do .dz .ec .edu .ee .eg .er .es .et .eu .eus .fi .fj .fk
        .fm .fo .fr .ga .gal .gb .gd .ge .gf .gg .gh .gi .gl .gm .gn .gov .gp .gq .gr .gs .gt .gu .gw
        .gy .hk .hm .hn .hr .ht .hu .id .ie .il .im .in .info .int .io .iq .ir .is .it .je .jm .jo .jp
        .ke .kg .kh .ki .km .kn .kp .kr .kw .ky .kz .la .lb .lc .li .link .lk .lr .ls .lt .lu .lv .ly
        .ma .mc .md .me .mg .mh .mk .ml .mm .mn .mo .mp .mq .mr .ms .mt .mu .mv .mw .mx .my .mz .na
        .nc .ne .net .nf .ng .ni .nl .no .np .nr .nu .nz .om .online .org .pa .pe .pf .pg .ph .pk .pl
        .pm .pn .pr .ps .pt .pub .pw .py .qa .quebec .re .rio .ro .rs .ru .rw .sa .sb .sc .sd .se .sg
        .sh .si .site .sj .sk .sl .sm .sn .so .sr .st .su .sv .sx .sy .sz .tc .td .tech .tf .tg .th
        .tj .tk .tl .tm .tn .to .tp .tr .tt .tv .tw .tz .ua .ug .uk .us .uy .uz .va .vc .ve .vet .vg
        .vi .vn .vu .website .wf .wiki .ws .xyz .ye .yt .za .zm .zw';

    // Convert the space-separated list into an associative array
    $validTlds = array_fill_keys(explode(' ', $tlds), true);

    $html = '';
    $position = 0;
    while (preg_match($rexUrlLinker, $text, $match, PREG_OFFSET_CAPTURE, $position)) {
        [$url, $urlPosition] = $match[0];

        // Add the text leading up to the URL.
        $html .= htmlspecialchars(substr($text, $position, $urlPosition - $position));

        $scheme = $match[1][0];
        $username = $match[2][0];
        $password = $match[3][0];
        $domain = $match[4][0];
        // Everything following the domain.
        $afterDomain = $match[5][0];

        $tld = mb_strrchr($domain, '.');
        assert(is_string($tld));
        $tld = mb_strtolower($tld);
        if (isset($validTlds[$tld])) {
            if ($username !== '' && $password === '' && $afterDomain === '') {
                // Looks like an email address.
                $completeUrl = "mailto:{$url}";
                $linkText = $url;
            } else {
                // Prepend https:// if no scheme is specified.
                $completeUrl = $scheme !== '' && $scheme !== '0' ? $url : "https://{$url}";
                $linkText = $completeUrl;
            }

            $linkHtml = '<a class="external" target="_blank" rel="nofollow noopener noreferrer"';
            $linkHtml .= ' href="' . htmlspecialchars($completeUrl) . '"';
            if ($property !== '') {
                $linkHtml .= ' property="' . $property . '"';
            }
            $linkHtml .= '>' . htmlspecialchars($linkText) . '</a>';

            // Add the hyperlink.
            $html .= $linkHtml;
        } else {
            // This is not a valid URL.
            $html .= htmlspecialchars($url);
        }

        // Continue text parsing from after the URL.
        $position = $urlPosition + mb_strlen($url);
    }

    // Add the remainder of the text.
    $html .= htmlspecialchars(substr($text, $position));

    return $html;
}

/**
 * ucfirst() function for multibyte character encodings.
 *
 * Borrowed from https://stackoverflow.com/a/58915632/1391963.
 *
 * @suppress PhanTypeMismatchArgumentNullableInternal
 */
function mb_ucfirst(string $str, ?string $encoding = null): string
{
    return mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding) . mb_substr($str, 1, null, $encoding);
}

/**
 * Returns the database connection.
 */
function get_db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    // Potentially, set environment variables in a local file.
    // if (file_exists(__DIR__ . '/db_settings.local.php')) {
    //    require __DIR__ . '/db_settings.local.php';
    // }

    $host = getenv('MYSQL_HOSTNAME');
    $db_name = getenv('MYSQL_DATABASE');
    $user = getenv('MYSQL_USER');
    $password = getenv('MYSQL_PASSWORD');

    assert(is_string($host));
    assert(is_string($db_name));
    assert(is_string($user));
    assert(is_string($password));

    $pdo = new PDO("mysql:host={$host};dbname={$db_name};charset=utf8mb4", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_PERSISTENT => false,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
    ]);

    return $pdo;
}

/**
 * Sets the default HTTP headers, and returns the CSP nonce.
 */
function set_default_headers(): string
{
    header('Cache-Control: public, s-maxage=31536000, max-age=300');

    try {
        $nonce = base64_encode(random_bytes(NONCE_LENGTH));
    } catch (Exception) {
        $nonce = uniqid();
    }

    header(
        "Content-Security-Policy: default-src 'self'; "
        . "base-uri 'none'; "
        . "connect-src 'self' https://*.google-analytics.com https://*.analytics.google.com https://*.googletagmanager.com; "
        . "frame-ancestors 'none'; "
        . "img-src 'self' https://*.google-analytics.com https://*.googletagmanager.com; "
        . "object-src 'none'; "
        . "script-src 'self' https://*.googletagmanager.com; "
        . "style-src 'nonce-{$nonce}'"
    );

    return $nonce;
}

/**
 * Formats an HTML title, truncated to 70 characters.
 */
function format_html_title(string $title, string $suffix = ''): string
{
    if (mb_strlen($title) > TITLE_MAX_LENGTH) {
        $truncated_title = mb_substr($title, 0, TITLE_MAX_LENGTH - 2);
        $last_space_pos = mb_strrpos($truncated_title, ' ');
        if ($last_space_pos !== false) {
            $title = mb_substr($truncated_title, 0, $last_space_pos) . '…';
        }
    }

    if ($suffix !== '') {
        $full_title = $title . ' - ' . $suffix;
        if (mb_strlen($full_title) <= TITLE_MAX_LENGTH) {
            $title = $full_title;
        }
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
    if (isset($_GET['mostra'])) {
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
 * Removes newlines, extra spaces and unsafe characters.
 *
 * Optionally and by default, escapes HTML and ensures the string ends with a dot or exclamation mark.
 */
function ct(string $string, bool $escape_html = true, bool $end_with_dot = true): string
{
    // Remove unsafe characters (https://htmlhint.com/docs/user-guide/rules/attr-unsafe-chars).
    $string = preg_replace("/\u{00AD}/", '', $string);
    assert(is_string($string));
    $string = preg_replace("/\u{200E}/", '', $string);
    assert(is_string($string));

    // Remove newlines and extra spaces.
    // https://html-validate.org/rules/attr-delimiter.html.
    // https://html-validate.org/rules/no-trailing-whitespace.html.
    // https://htmlhint.com/docs/user-guide/rules/attr-whitespace.
    $string = preg_replace('/\n/', ' ', $string);
    assert(is_string($string));
    $string = preg_replace('/\s+/', ' ', $string);
    assert(is_string($string));

    // Escape HTML.
    if ($escape_html) {
        $string = htmlspecialchars($string);
    }

    if ($end_with_dot) {
        // Remove trailing dot character.
        $string = trim($string, ". \n\r\t\v\x00");

        // Add trailing dot character.
        if (
            !str_ends_with($string, '?')
            && !str_ends_with($string, '!')
            && !str_ends_with($string, '…')
            && !str_ends_with($string, ';')
            && !str_ends_with($string, '*')
        ) {
            $string .= '.';
        }
    }

    return $string;
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

    foreach ($allowed_pages as $allowed_page) {
        if (isset($_GET[$allowed_page])) {
            return $allowed_page;
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
 * Returns the paremiotipus side blocks HTML.
 */
function get_paremiotipus_blocks(): string
{
    global $paremiotipus_blocks;

    return $paremiotipus_blocks ?? '';
}

/**
 * Sets the paremiotipus side blocks HTML.
 */
function set_paremiotipus_blocks(string $blocks): void
{
    global $paremiotipus_blocks;

    $paremiotipus_blocks = $blocks;
}

/**
 * Returns the side blocks HTML.
 */
function get_side_blocks(string $page_name): string
{
    $side_blocks = '';
    if ($page_name === 'search') {
        // Homepage and search pages show Top 100 and Books blocks.
        $random_paremiotipus = get_random_top100_paremiotipus();
        $side_blocks = '<div class="bloc" data-nosnippet>';
        $side_blocks .= '<p class="text-balance">';
        $side_blocks .= '«<a href="' . get_paremiotipus_url($random_paremiotipus) . '">';
        $side_blocks .= get_paremiotipus_display($random_paremiotipus);
        $side_blocks .= '</a>»';
        $side_blocks .= '</p>';
        $side_blocks .= '<footer><a href="/top100">Les 100 parèmies més citades</a></footer>';
        $side_blocks .= '</div>';

        $side_blocks .= '<div class="bloc bloc-books">';
        $side_blocks .= '<p><a href="/llibres">Llibres de l\'autor</a></p>';
        $random_book = get_random_book();
        // TODO: FIXME in the DB.
        if ($random_book['URL'] === 'https://lafinestralectora.cat/els-100-refranys-mes-populars-2/') {
            $random_book['URL'] = 'https://lafinestralectora.cat/els-100-refranys-mes-populars/';
        }
        if ($random_book['URL'] !== null) {
            $side_blocks .= '<a href="' . $random_book['URL'] . '" title="' . htmlspecialchars($random_book['Títol']) . '">';
        }
        $side_blocks .= get_image_tags(
            $random_book['Imatge'],
            '/img/obres/',
            $random_book['Títol'],
            $random_book['WIDTH'],
            $random_book['HEIGHT'],
            false
        );
        if ($random_book['URL'] !== null) {
            $side_blocks .= '</a>';
        }
        $side_blocks .= '</div>';
    } elseif ($page_name === 'paremiotipus') {
        $side_blocks = get_paremiotipus_blocks();
    }

    // All pages show the credits block.
    $side_blocks .= '<div class="bloc bloc-credits bloc-white">';
    $side_blocks .= '<p>Un projecte de:</p>';
    $side_blocks .= '<p><a class="credits" href="http://www.dites.cat" title="www.dites.cat">dites.cat</a></p>
        <p><a href="https://www.softcatala.org" title="Softcatalà"><img loading="lazy" alt="logotip de Softcatalà" width="120" height="80" src="/img/logo-softcatala.svg"></a></p>';
    $side_blocks .= '</div>';

    if ($page_name !== 'search') {
        // All non-search pages show the Top 10000 block, after credits.
        $random_paremiotipus = get_random_top10000_paremiotipus();
        $side_blocks .= '<div class="bloc" data-nosnippet>';
        $side_blocks .= '<p class="text-balance">';
        $side_blocks .= '«<a href="' . get_paremiotipus_url($random_paremiotipus) . '">';
        $side_blocks .= get_paremiotipus_display($random_paremiotipus);
        $side_blocks .= '</a>»';
        $side_blocks .= '</p>';
        $side_blocks .= '<footer>Les 10.000 parèmies més citades</footer>';
        $side_blocks .= '</div>';
    }

    return $side_blocks;
}

/**
 * Returns the page title.
 */
function get_page_title(): string
{
    global $page_title;

    return $page_title ?? '';
}

/**
 * Sets the page title.
 */
function set_page_title(string $title): void
{
    global $page_title;

    // Remove some unsafe and unwanted characters.
    $page_title = ct($title, false, false);
}

/**
 * Returns the canonical URL.
 */
function get_canonical_url(): string
{
    global $canonical_url;

    return $canonical_url ?? '';
}

/**
 * Sets the canonical URL.
 */
function set_canonical_url(string $url): void
{
    global $canonical_url;

    $canonical_url = $url;
}

/**
 * Returns the meta description.
 */
function get_meta_description(): string
{
    global $meta_description;

    return $meta_description ?? '';
}

/**
 * Sets the meta description, but only once.
 */
function set_meta_description(string $description): void
{
    global $meta_description;

    $meta_description = $description;
}

/**
 * Sets the meta description, but only once.
 */
function set_meta_description_once(string $description): void
{
    global $meta_description;

    if ($meta_description === null || $meta_description === '') {
        $meta_description = $description;
    }
}

/**
 * Returns the meta image URL.
 */
function get_meta_image(): string
{
    global $meta_image;

    return $meta_image ?? '';
}

/**
 * Sets the meta image URL.
 */
function set_meta_image(string $image_url): void
{
    global $meta_image;

    $meta_image = $image_url;
}

/**
 * Returns the og:audio URL.
 */
function get_og_audio_url(): string
{
    global $og_audio_url;

    return $og_audio_url ?? '';
}

/**
 * Sets the og:audio URL.
 */
function set_og_audio_url(string $audio_url): void
{
    global $og_audio_url;

    $og_audio_url = $audio_url;
}

/**
 * Returns the og:type.
 */
function get_og_type(): string
{
    global $og_type;

    return $og_type ?? '';
}

/**
 * Sets the og:type.
 */
function set_og_type(string $type): void
{
    global $og_type;

    $og_type = $type;
}

/**
 * Returns page-specific meta tags.
 */
function get_page_meta_tags(string $page_name): string
{
    $meta_tags = '';
    if ($page_name === 'search') {
        $is_homepage = (!isset($_GET['cerca']) || $_GET['cerca'] === '') && get_page_number() === 1;
        if ($is_homepage) {
            // Set canonical URL.
            set_canonical_url('https://pccd.dites.cat');
        } else {
            // Do not index the rest of result pages.
            $meta_tags .= '<meta name="robots" content="noindex">';
        }

        // Provide nice-to-have social metadata for the homepage and search pages.
        $meta_tags .= '<meta name="twitter:card" content="summary_large_image">';
        $meta_tags .= '<meta property="og:type" content="website">';
        // See https://stackoverflow.com/q/71087872/1391963.
        $meta_tags .= '<meta name="twitter:image" property="og:image" content="https://pccd.dites.cat/img/screenshot.png">';
    } elseif (get_og_type() !== '') {
        // Set specific type if set. This is only used for books in obra pages for now.
        $meta_tags .= '<meta property="og:type" content="' . get_og_type() . '">';
    } else {
        // Set og:type article for all other pages.
        $meta_tags .= '<meta property="og:type" content="article">';
    }

    // Meta description may be set when building main content.
    if (get_meta_description() !== '') {
        $meta_tags .= '<meta name="description" property="og:description" content="' . get_meta_description() . '">';
    }

    // Meta image may be set in paremiotipus and obra pages.
    if (get_meta_image() !== '') {
        $meta_tags .= '<meta name="twitter:card" content="summary">';
        // See https://stackoverflow.com/q/71087872/1391963.
        $meta_tags .= '<meta name="twitter:image" property="og:image" content="' . get_meta_image() . '">';
    }

    // og:audio URL may be set in paremiotipus pages.
    if (get_og_audio_url() !== '') {
        $meta_tags .= '<meta property="og:audio" content="' . get_og_audio_url() . '">';
    }

    // Canonical may be set above or in paremiotipus and obra pages.
    if (get_canonical_url() !== '') {
        $meta_tags .= '<link rel="canonical" href="' . get_canonical_url() . '">';
    }

    return $meta_tags;
}

/**
 * Returns the paremiotipus name for display.
 */
function get_paremiotipus_display(string $paremiotipus, bool $return_empty_if_does_not_exist = false, bool $htmlspecialchars = true): string
{
    $value = function_exists('apcu_fetch') ? apcu_fetch($paremiotipus) : false;
    if ($value === false) {
        $stmt = get_db()->prepare('SELECT Display FROM paremiotipus_display WHERE Paremiotipus = :paremiotipus');
        $stmt->bindParam(':paremiotipus', $paremiotipus);
        $stmt->execute();
        $value = $stmt->fetchColumn();
        if ($value === false) {
            if ($return_empty_if_does_not_exist) {
                return '';
            }
            error_log("Error: {$paremiotipus} is empty in paremiotipus_display table");
            $value = $paremiotipus;
        }
        if (function_exists('apcu_store')) {
            apcu_store($paremiotipus, $value);
        }
    }

    assert(is_string($value));

    if ($htmlspecialchars) {
        return htmlspecialchars($value);
    }

    return $value;
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
    $stmt = get_db()->prepare('SELECT PAREMIOTIPUS FROM 00_PAREMIOTIPUS WHERE MODISME = :modisme LIMIT 1');
    $stmt->bindParam(':modisme', $modisme);
    $stmt->execute();

    $paremiotipus = $stmt->fetchColumn();
    $paremiotipus = $paremiotipus !== false ? $paremiotipus : '';
    assert(is_string($paremiotipus));

    return $paremiotipus;
}

/**
 * Get the list of manual redirects.
 *
 * @return non-empty-array<string, string>
 */
function get_redirects(): array
{
    // These redirects are mapped manually based on a Google Search console report.
    return [
        '/p/A_cabsssos' => '/p/A_cabassos',
        '/?paremiotipus=A+la+Tallada+totes+les+dones+s%C3%83%C2%B3n+garrelles' => '/p/A_la_Tallada_totes_les_dones_són_garrelles',
        '/p/Fer-li_fer_la_figuereta' => '/p/Fer_la_figuereta',
        '/p/dintreAdeu-siau%2C_gent_de_Palau%3B_%C2%ABadi%C3%B3s%C2%BB%2C_gent_de_Palam%C3%B3s' => '/p/Adeu-siau%2C_gent_de_Palau%3B_«adiós»%2C_gent_de_Palamós',
        '/?obra=Badia+i+Pujol%2C+Jordi+%282021%29%3A+Ras+i+curt+-+Fer+un+%E2%80%98polvo%E2%80%99+o+fotre+un+clau%3F%3A+aquesta+%C3%A9s+la+q%C3%BCesti%C3%B3' => '/obra/Badia_i_Pujol%2C_Jordi_%282022%29%3A_Ras_i_curt_-_Deu_refranys_catalans_intradu%C3%AFbles',
        '/?obra=Badia+i+Pujol%2C+Jordi+%282022%29%3A+Vilaweb+-+%E2%80%9CFotre%E2%80%9D%2C+el+Messi+dels+verbs+catalans' => '/obra/Badia_i_Pujol%2C_Jordi_%282022%29%3A_Vilaweb_-_«Fotre»%2C_el_Messi_dels_verbs_catalans',
        '/?obra=Badia+i+Pujol%2C+Jordi+(2021):+Ras+i+curt+-+Fer+un+%E2%80%98polvo%E2%80%99+o+fotre+un+clau?:+aquesta+%C3%A9s+la+q%C3%BCesti%C3%B3' => '/obra/Badia_i_Pujol%2C_Jordi_%282022%29%3A_Ras_i_curt_-_Deu_refranys_catalans_intradu%C3%AFbles',
        '/?obra=Badia+i+Pujol,+Jordi+(2021):+Ras+i+curt+-+Fer+un+%E2%80%98polvo%E2%80%99+o+fotre+un+clau?:+aquesta+%C3%A9s+la+q%C3%BCesti%C3%B3' => '/obra/Badia_i_Pujol%2C_Jordi_%282022%29%3A_Ras_i_curt_-_Deu_refranys_catalans_intradu%C3%AFbles',
        '/?obra=Bonsenyor%2C+Jahuda+%281889%29%3A+Libre+de+paraules+e+dits+de+savis+e+filosofs' => '/obra/Bonsenyor%2C_Jahuda_%281298%29%3A_Libre_de_paraules_e_dits_de_savis_e_filosofs%2C_ed_1889',
        '/?obra=Marrugat+Cuy%C3%A0s%2C+Ramon+%282018%29%3A+Alguna+cosa+m%C3%A9s+que+l%27anar+a+tocar+ferro' => '/obra/Marrugat_Cuyàs%2C_Ramon_%282018%29%3A_«Alguna_cosa_més_que_l%27anar_a_tocar_ferro»._La_fraseologia_tarragonina',
        '/?obra=Marrugat+Cuy%C3%A0s%2C+Ramon+(2018):+Alguna+cosa+m%C3%A9s+que+l%27anar+a+tocar+ferro' => '/obra/Marrugat_Cuyàs%2C_Ramon_%282018%29%3A_«Alguna_cosa_més_que_l%27anar_a_tocar_ferro»._La_fraseologia_tarragonina',
        '/?obra=Marrugat+Cuy%C3%A0s,+Ramon+(2018):+Alguna+cosa+m%C3%A9s+que+l%27anar+a+tocar+ferro' => '/obra/Marrugat_Cuyàs%2C_Ramon_%282018%29%3A_«Alguna_cosa_més_que_l%27anar_a_tocar_ferro»._La_fraseologia_tarragonina',
        '/?obra=Mettmann%2C+Walter+%281989%29%3A+%C2%ABProverbia+arabum%C2%BB+eine+altkatalanische+sprichw%C3%B6rter-Uns+sentenzensammlung' => '/obra/Mettmann%2C_Walter_%281298%29%3A_«Proverbia_arabum»_eine_altkatalanische_sprichwörter-Uns_sentenzensammlung%2C_ed._1989',
        '/?obra=Mettmann%2C+Walter+(1989):+%C2%ABProverbia+arabum%C2%BB+eine+altkatalanische+sprichw%C3%B6rter-Uns+sentenzensammlung' => '/obra/Mettmann%2C_Walter_%281298%29%3A_«Proverbia_arabum»_eine_altkatalanische_sprichwörter-Uns_sentenzensammlung%2C_ed._1989',
        '/?obra=Mettmann,+Walter+(1989):+%C2%ABProverbia+arabum%C2%BB+eine+altkatalanische+sprichw%C3%B6rter-Uns+sentenzensammlung' => '/obra/Mettmann%2C_Walter_%281298%29%3A_«Proverbia_arabum»_eine_altkatalanische_sprichwörter-Uns_sentenzensammlung%2C_ed._1989',
        '/?obra=Mettmann.%2C+Walter+%281989%29%3A+%C2%ABProverbia+arabum%C2%BB+eine+altkatalanische+sprichw%C3%B6rter-Uns+sentenzensammlung' => '/obra/Mettmann%2C_Walter_%281298%29%3A_«Proverbia_arabum»_eine_altkatalanische_sprichwörter-Uns_sentenzensammlung%2C_ed._1989',
        '/?obra=Mettmann.%2C+Walter+(1989):+%C2%ABProverbia+arabum%C2%BB+eine+altkatalanische+sprichw%C3%B6rter-Uns+sentenzensammlung' => '/obra/Mettmann%2C_Walter_%281298%29%3A_«Proverbia_arabum»_eine_altkatalanische_sprichwörter-Uns_sentenzensammlung%2C_ed._1989',
        '/?obra=Mettmann.,+Walter+(1989):+%C2%ABProverbia+arabum%C2%BB+eine+altkatalanische+sprichw%C3%B6rter-Uns+sentenzensammlung' => '/obra/Mettmann%2C_Walter_%281298%29%3A_«Proverbia_arabum»_eine_altkatalanische_sprichwörter-Uns_sentenzensammlung%2C_ed._1989',
        '/?obra=Revista+S%27Uni%C3%B3+de+S%27Arenal+%281988-1996%29' => '/obra/S%27Unió_de_S%27Arenal_%281988-1996%29',
        '/?obra=Revista+S%27Uni%C3%B3+de+S%27Arenal+(1988-1996)' => '/obra/S%27Unió_de_S%27Arenal_%281988-1996%29',
        '/?paremiotipus=%C3%A9s+dolent+que+la+carn+de+gos' => '/p/Dolent_com_la_carn_de_gos',
        '/?paremiotipus=A+Cabanes%2C+hi+ha+qui+en+t%C3%A9+ganes' => '/p/A_Cabanes%2C_hi_va_qui_en_té_ganes',
        '/?paremiotipus=A+judici+i+pagar%C2%ABlo%C2%BB+judicat' => '/p/A_judici_i_pagar_«lo»_judicat',
        '/?paremiotipus=A+l%27Ascensi%C3%B3+cireretes+abundo+A+Val%C3%A8ncia%E2%80%A6+que+aqu%C3%AD+no' => '/p/Per_l%27Ascensió%2C_cireretes_en_abundor',
        '/?paremiotipus=A+l%27Ascensió+cireretes+abundo+A+València…+que+aquí+no' => '/p/Per_l%27Ascensió%2C_cireretes_en_abundor',
        '/?paremiotipus=A+l%27aire+llure' => '/p/A_l%27aire_lliure',
        '/?paremiotipus=C%C3%A0cilment' => '/p/D%C3%B2cilment',
        '/?paremiotipus=Cadasc%C3%BA%2C+en+sa+casa%2C+sal+el+que+hi+passa' => '/p/Cadascú%2C_en_sa_casa%2C_sap_el_que_hi_passa',
        '/?paremiotipus=Camina+que+caminara%CC%80s' => '/p/Camina_que_caminar%C3%A0s',
        '/?paremiotipus=Coll+avalll' => '/p/Coll_avall',
        '/?paremiotipus=Com+e+p%C3%A8l+de+la+pasta' => '/p/Com_el_p%C3%A8l_de_la_pasta',
        '/?paremiotipus=Com+un+eix+a+l%27aigua' => '/p/Com_un_peix_a_l%27aigua',
        '/?paremiotipus=Cre%CC%81ixer+com+una+mala+herba' => '/p/Créixer_com_una_mala_herba',
        '/?paremiotipus=D%C3%A9u+te+guard+de+paret+fesa+e+de+hom+de+Manresa%3B+de+passera+rodona+e+de+dona+de+Girona%3B+de+sucarrat+d%27Urgel+e+de+buf%C3%B3+de+Leyda%3B+de+Manda+de+Ual%C3%A0s+%28Pallars%29+e+de+sard+de+Cerdenya%3B+de+g%C3%BCelfo+e+gibellis+fals+a+mestre+e+bon+gussanyador+de+Rossell%C3%B3%3B+%E2%80%A6' => '/p/Déu_te_guard_de_paret_fesa_e_de_hom_de_Manresa%3B_de_passera_rodona_e_de_dona_de_Girona%3B_de_sucarrat_d%27Urgel_e_de_bufó_de_Leyda%3B_de_Manda_de_Ualàs_Pallars_e_de_sard_de_Cerdenya%3B_de_güelfo_e_gibellis_fals_a_mestre_e_bon_gussanyador_de_Rosselló%3B_...',
        '/?paremiotipus=Decloure+les+mans' => '/p/Descloure_les_mans',
        '/?paremiotipus=Deixar+a+manlleu' => '/p/Anar_a_manlleu',
        '/?paremiotipus=Diu+el+bou+pel+febrer:+-Prou+aigua%2C+que+se%27m+podreix+la+banya' => '/p/Diu_el_bou_pel_febrer%3A_-_Pluja%2C_pluja%2C_fins_que_se%27m_podreixi_la_cua',
        '/?paremiotipus=Dona+d%27altre+marit%2C+olla+de+caldo+afegitDona+d%27altre+marit%2C+olla+de+caldo+afegit' => '/p/Dona_d%27altre_marit%2C_olla_de_caldo_afegit',
        '/?paremiotipus=El+perol+diu+a+la+paella%3A+si+m%27embrutes%2C+t%27emmascaroSer+el+rei+del+mambo' => '/p/El_perol_diu_a_la_paella%3A_si_m%27embrutes%2C_t%27emmascaro',
        '/?paremiotipus=El+perol+diu+a+la+paella:+si+m%27embrutes%2C+t%27emmascaroSer+el+rei+del+mambo' => '/p/El_perol_diu_a_la_paella%3A_si_m%27embrutes%2C_t%27emmascaro',
        '/?paremiotipus=El+perol+diu+a+la+paella:+si+m%27embrutes,+t%27emmascaroSer+el+rei+del+mambo' => '/p/El_perol_diu_a_la_paella%3A_si_m%27embrutes%2C_t%27emmascaro',
        '/?paremiotipus=En+X%C3%A0bia%2C+desculats%3B+en+Ondara%2C+fanfarrons%3B+en+Benissa%2C+senyorets%2C+i+en+Teulada%2C+boquimollls' => '/p/En_X%C3%A0bia%2C_desculats%3B_en_Ondara%2C_fanfarrons%3B_en_Benissa%2C_senyorets%2C_i_en_Teulada%2C_boquimolls',
        '/?paremiotipus=En+Xàbia,+desculats;+en+Ondara,+fanfarrons;+en+Benissa,+senyorets,+i+en+Teulada,+boquimollls' => '/p/En_X%C3%A0bia%2C_desculats%3B_en_Ondara%2C_fanfarrons%3B_en_Benissa%2C_senyorets%2C_i_en_Teulada%2C_boquimolls',
        '/?paremiotipus=Estira-i-arronsav' => '/p/Estira-i-arronsa',
        '/?paremiotipus=Fer+enbuts' => '/p/Fer_embuts',
        '/?paremiotipus=Fer+me%CC%81s+badalls+que+rots' => '/p/Fer_m%C3%A9s_badalls_que_rots',
        '/?paremiotipus=Fer+uin+merder' => '/p/Fer_merder',
        '/?paremiotipus=Fer-li+una+mala+jugada' => '/p/Mala_jugada',
        '/?paremiotipus=Ja+ports+xiular+si+l%27ase+no+vol+beure' => '/p/Ja_pots_xiular_si_l%27ase_no_vol_beure',
        '/?paremiotipus=Jugar-li+una+mala+passada' => '/p/Mala_passada',
        '/?paremiotipus=Les+xiques+de+Vilella+%28o+del+Poretal+d%27Horta%2C+o+de+Guardamar%2C+o+de+Torrevella+o+del+Vilar%29%2C+a+la+llum+diuen+%C2%ABcandil%C2%BB%2C+a+la+finestra%2C+%C2%ABventana%C2%BB+i+al+julivert%2C+%C2%ABperegil%C2%BB' => '/p/Les_xiques_de_Vilella_o_del_Portal_d%27Horta%2C_o_de_Guardamar%2C_o_de_Torrevella%2C_o_del_Vilar%2C_a_la_llum_diuen_«candil»%2C_a_la_finestra%2C_«ventana»_i_al_julivert%2C_«peregil»',
        '/?paremiotipus=Les+xiques+de+Vilella+(o+del+Poretal+d%27Horta%2C+o+de+Guardamar%2C+o+de+Torrevella+o+del+Vilar)%2C+a+la+llum+diuen+%C2%ABcandil%C2%BB%2C+a+la+finestra%2C+%C2%ABventana%C2%BB+i+al+julivert%2C+%C2%ABperegil%C2%BB' => '/p/Sas%2C_Malp%C3%A0s%2C_Peranera_i_Castell%C3%A0s%2C_Sent%C3%ADs%2C_C%C3%A9rvoles_i_Naens%3A_els_set_pobles_m%C3%A9s_dolents._Si_n%27hi_voleu_m%C3%A9s%2C_poseu-hi_Ben%C3%A9s%2C_si_no_n%27hi_ha_prou%2C_poseu-hi_Castellnou._Si_n%27hi_voleu_una_bona_renglera%2C_poseu-hi_tota_la_vall_de_Cabdella',
        '/?paremiotipus=Les+xiques+de+Vilella+o+del+Poretal+d%27Horta%2C+o+de+Guardamar%2C+o+de+Torrevella+o+del+Vilar%2C+a+la+llum+diuen+%C2%ABcandil%C2%BB%2C+a+la+finestra%2C+%C2%ABventana%C2%BB+i+al+julivert%2C+%C2%ABperegil%C2%BB' => '/p/Sas%2C_Malp%C3%A0s%2C_Peranera_i_Castell%C3%A0s%2C_Sent%C3%ADs%2C_C%C3%A9rvoles_i_Naens%3A_els_set_pobles_m%C3%A9s_dolents._Si_n%27hi_voleu_m%C3%A9s%2C_poseu-hi_Ben%C3%A9s%2C_si_no_n%27hi_ha_prou%2C_poseu-hi_Castellnou._Si_n%27hi_voleu_una_bona_renglera%2C_poseu-hi_tota_la_vall_de_Cabdella',
        '/?paremiotipus=Ni+fe+d%27enc%C3%A0rrec' => '/p/Ni_fet_d%27enc%C3%A0rrec',
        '/?paremiotipus=Ofegar-se+en+nu+got+d%27aigua' => '/p/Ofegar-se_en_un_got_d%27aigua',
        '/?paremiotipus=Ognominiosament' => '/p/Ignominiosament',
        '/?paremiotipus=Parar+taula' => '/p/Parar_taula',
        '/?paremiotipus=Passarse-li+l%27arr%C3%B2s' => '/p/Passar-se-li_l%27arr%C3%B2s',
        '/?paremiotipus=Passr+el+rosari' => '/p/Passar_el_rosari',
        '/?paremiotipus=Pel+setembre+o+desembre%2C+qui+tingui+blat%2C+que+en+sembri' => '/p/Pel_setembre%2C_qui_tingui_blat%2C_que_en_sembri',
        '/?paremiotipus=Posar-li+el+dogall+al+coll' => '/p/Estar_amb_el_dogal_al_coll',
        '/?paremiotipus=Posr+en+gu%C3%A0rdia' => '/p/Posar_en_guàrdia',
        '/?paremiotipus=Posra+barba' => '/p/Posar_barba',
        '/?paremiotipus=Prendre+a+manlleu' => '/p/Anar_a_manlleu',
        '/?paremiotipus=Quan+la+Murta+s%27emborrasca+i+la+Casella+o+Matamon+fa+capell%2C+llaurador%2C+ves-te%27n+a+casa%2C+pica+espart+i+fes+cordell' => '/p/Pica_espart_i_fes_cordell',
        '/?paremiotipus=Rompre-li+la+crisma' => '/p/Trencar_o_rompre_la_crisma',
        '/?paremiotipus=Romprer-li+el+cap' => '/p/Trencar-li_el_cap',
        '/?paremiotipus=Ser+jun+desvirgagallines' => '/p/Ser_un_desvirgagallines',
        '/?paremiotipus=Ser+un+figa+blana' => '/p/Figa_blana',
        '/?paremiotipus=Ser+un+figa+tova' => '/p/Figa_tova',
        '/?paremiotipus=Ser+un+malandando' => '/p/Malandando',
        '/?paremiotipus=Ser+un+trapsser' => '/p/Ser_un_trapasser',
        '/?paremiotipus=Si+el+Vall%C3%83%C2%A8s+fos+un+ou,+el+rovell+fora+Palou' => '/p/Si_el_Vallès_fos_un_ou%2C_el_rovell_fora_Palou',
        '/?paremiotipus=Si+tens+una+filla+que+no+l%27estimis+gaire%2C+casa-la+a+Albons%2C+o+a+Bellcaire%2C+o+sin%C3%83%C2%B3+a+Vilademat%2C+que+ser%C3%83%C2%A0+morta+m%C3%83%C2%A9s+aviat' => '/p/Si_tens_una_filla_que_no_l%27estimis_gaire%2C_casa-la_a_Albons%2C_o_a_Bellcaire%2C_o_sin%C3%B3_a_Vilademat%2C_que_ser%C3%A0_morta_m%C3%A9s_aviat',
        '/?paremiotipus=Si+tens+una+filla+que+no+l%27estimis+gaire,+casa-la+a+Albons,+o+a+Bellcaire,+o+sin%C3%83%C2%B3+a+Vilademat,+que+ser%C3%83%C2%A0+morta+m%C3%83%C2%A9s+aviat' => '/p/Si_tens_una_filla_que_no_l%27estimis_gaire%2C_casa-la_a_Albons%2C_o_a_Bellcaire%2C_o_sinó_a_Vilademat%2C_que_serà_morta_més_aviat',
        '/?paremiotipus=Tenir+pinyo%CC%81' => '/p/Tenir_pinyó',
        '/?paremiotipus=Terra+on+vas%2C+costum+hi+trobes' => '/p/A_terra_que_vas%2C_usan%C3%A7a_o_costums_que_trobes',
        '/?paremiotipus=Tothom+vol+just%C3%ADcia%2C+per%C3%B2+no+per+casa+sevaTothom+vol+justicia%2C+per%C3%B2+no+per+casa+seva' => '/p/Tothom_vol_justícia%2C_però_no_per_casa_seva',
        '/?paremiotipus=Tots+els+mosquits+volen+prendre+tabaco' => '/p/Totes_les_mosques_tenen_tos_i_els_mosquits_prenen_tabac',
        '/?paremiotipus=Treure-hi+la+pols' => '/p/Treure_la_pols',
        '/?paremiotipus=Treureli+la+son' => '/p/Treure-li_la_son',
        '/?paremiotipus=Vaig+anar+a+Constantinoble+i+al+punt+em+varen+constantinoblitzar+tan+b%C3%A9%2C+que+ara+cap+desconstantinoblitzador+no+f%C3%B3ra+poru+per+desconstantinoblitzar-me%2C+encara+que+fos+el+primer+desconstantinoblitzador+de+tots+els+desconstantinoblitzadors+de+Constantinoble' => '/p/Vaig_anar_a_Constantinoble_i_al_punt_em_varen_constantinoblitzar_tan_b%C3%A9%2C_que_ara_cap_desconstantinoblitzador_no_f%C3%B3ra_prou_per_desconstantinoblitzar-me%2C_encara_que_fos_el_primer_desconstantinoblitzador_de_tots_els_desconstantinoblitzadors_de_Constantinoble',
        '/?paremiotipus=anera+i+Castell%C3%A0s%2C+Sent%C3%ADs%2C+C%C3%A9rvoles+i+Naens%3A+els+set+pobles+m%C3%A9s+dolents.+Si+n%27hi+voleu+m%C3%A9s%2C+poseu-hi+Ben%C3%A9s%2C+si+no+n%27hi+ha+prou%2C+poseu-hi+Castellnou.+Si+n%27hi+voleu+una+bona+renglera%2C+poseu-hi+tota+la+vall+de+Cabdella' => '/p/Sas%2C_Malpàs%2C_Peranera_i_Castellàs%2C_Sentís%2C_Cérvoles_i_Naens%3A_els_set_pobles_més_dolents._Si_n%27hi_voleu_més%2C_poseu-hi_Benés%2C_si_no_n%27hi_ha_prou%2C_poseu-hi_Castellnou._Si_n%27hi_voleu_una_bona_renglera%2C_poseu-hi_tota_la_vall_de_Cabdella',
        '/?paremiotipus=anera+i+Castell%C3%A0s%2C+Sent%C3%ADs%2C+C%C3%A9rvoles+i+Naens:+els+set+pobles+m%C3%A9s+dolents.+Si+n%27hi+voleu+m%C3%A9s%2C+poseu-hi+Ben%C3%A9s%2C+si+no+n%27hi+ha+prou%2C+poseu-hi+Castellnou.+Si+n%27hi+voleu+una+bona+renglera%2C+poseu-hi+tota+la+vall+de+Cabdella' => '/p/Sas%2C_Malp%C3%A0s%2C_Peranera_i_Castell%C3%A0s%2C_Sent%C3%ADs%2C_C%C3%A9rvoles_i_Naens%3A_els_set_pobles_m%C3%A9s_dolents._Si_n%27hi_voleu_m%C3%A9s%2C_poseu-hi_Ben%C3%A9s%2C_si_no_n%27hi_ha_prou%2C_poseu-hi_Castellnou._Si_n%27hi_voleu_una_bona_renglera%2C_poseu-hi_tota_la_vall_de_Cabdella',
        '/?paremiotipus=ap+pelat%2C+de+Viladamat' => '/p/Cap_pelat%2C_de_Viladamat',
        '/?paremiotipus=ap+pelat,+de+Viladamat' => '/p/Cap_pelat%2C_de_Viladamat',
        '/?paremiotipus=ell%C3%B3%2C+venen+oli%3B+a+Alpicat%2C+venen+els+alls%2C+i+a+Almenar%2C+fan+l%27allioli' => '/p/A_Peralta%2C_venen_sal%3B_a_Rosselló%2C_venen_oli%3B_a_Alpicat%2C_venen_els_alls%2C_i_a_Almenar%2C_fan_l%27allioli',
        '/?paremiotipus=ell%C3%B3,%20venen%20oli;%20a%20Alpicat,%20venen%20els%20alls,%20i%20a%20Almenar,%20fan%20l%27allioli' => '/p/A_Peralta%2C_venen_sal%3B_a_Rosselló%2C_venen_oli%3B_a_Alpicat%2C_venen_els_alls%2C_i_a_Almenar%2C_fan_l%27allioli',
        '/?paremiotipus=ell%C3%B3,+venen+oli;+a+Alpicat,+venen+els+alls,+i+a+Almenar,+fan+l%27allioli' => '/p/A_Peralta%2C_venen_sal%3B_a_Rosselló%2C_venen_oli%3B_a_Alpicat%2C_venen_els_alls%2C_i_a_Almenar%2C_fan_l%27allioli',
        '/?paremiotipus=elló,+venen+oli;+a+Alpicat,+venen+els+alls,+i+a+Almenar,+fan+l%27allioli' => '/p/A_Peralta%2C_venen_sal%3B_a_Rosselló%2C_venen_oli%3B_a_Alpicat%2C_venen_els_alls%2C_i_a_Almenar%2C_fan_l%27allioli',
        '/?paremiotipus=escansar+despr%C3%A9s+de+dinar+%C3%A9s+salut+que+es+pot+donar' => '/p/Descansar_despr%C3%A9s_de_dinar_%C3%A9s_salut_que_es_pot_donar',
        '/?paremiotipus=n+canyissos%2C+a+la+Torre+fan+sab%C3%B3+i+a+Garcia+fan+cabestres+per+a+tots+els+rucots+d%27Asc%C3%B3' => '/p/A_Vinebre_fan_canyissos%2C_a_la_Torre_fan_sab%C3%B3_i_a_Garcia_fan_cabestres_per_tots_els_rucots_d%27Asc%C3%B3',
        '/?paremiotipus=oA+pas+de+bou' => '/p/A_pas_de_bou',
        '/?paremiotipus=ure-se-li+el+llaut%C3%B3' => '/p/Veure-se-li_el_llautó',
        '/obra/Badia_i_Pujol%2C_Jordi_%282022%29%3A_Vilaweb_-_%E2%80%9CFotre%E2%80%9D%2C_el_Messi_dels_verbs_catalans' => '/obra/Badia_i_Pujol%2C_Jordi_%282022%29%3A_Vilaweb_-_«Fotre»%2C_el_Messi_dels_verbs_catalans',
        '/obra/Casta%C3%B1eda%2C_Vicente_%281919-20%29%3A_Refranes_valencianos_recopilados_por_el_P._Luis_Galiana%2C_Dominico' => '/obra/Castañeda%2C_Vicente_%281770%29%3A_Refranes_valencianos_recopilados_por_el_P._Luis_Galiana%2C_Dominico%2C_ed._1919-20',
        '/obra/Casta%C3%B1eda%2C_Vicente_(1919-20)%3A_Refranes_valencianos_recopilados_por_el_P._Luis_Galiana%2C_Dominico' => '/obra/Castañeda%2C_Vicente_%281770%29%3A_Refranes_valencianos_recopilados_por_el_P._Luis_Galiana%2C_Dominico%2C_ed._1919-20',
        '/obra/Casta%c3%b1eda%2C_Vicente_(1919-20):_Refranes_valencianos_recopilados_por_el_P._Luis_Galiana%2C_Dominico' => '/obra/Castañeda%2C_Vicente_%281770%29%3A_Refranes_valencianos_recopilados_por_el_P._Luis_Galiana%2C_Dominico%2C_ed._1919-20',
        '/obra/Costafreda_i_Castillo%2C_Alfred_%282021%29%3A_Folklore_d%27Artesa_de_Lleida._Del_poble_i_del_terme' => '/obra/Costafreda_i_Castillo%2C_Adolf_%282021%29%3A_Folklore_d%27Artesa_de_Lleida._Del_poble_i_del_terme',
        '/obra/Gonz%C3%A1lez%2C_Juan_Antonio_%282018%29%3A_Els_refranys_a_l%E2%80%99obra_Los_col%C2%B7loquis_de_la_insigne_ciutat_de_Tortosa%2C_de_Crist%C3%B2fol_Despuig' => '/obra/González%2C_Juan_Antonio_%282018%29%3A_Els_refranys_a_l%27obra_Los_col·loquis_de_la_insigne_ciutat_de_Tortosa%2C_de_Cristòfol_Despuig',
        '/obra/Marrugat_Cuy%C3%A0s%2C_Ramon_%282018%29%3A_Alguna_cosa_m%C3%A9s_que_l%27anar_a_tocar_ferro' => '/obra/Marrugat_Cuyàs%2C_Ramon_%282018%29%3A_«Alguna_cosa_més_que_l%27anar_a_tocar_ferro»._La_fraseologia_tarragonina',
        '/obra/Marrugat_Cuy%c3%a0s%2C_Ramon_(2018):_Alguna_cosa_m%c3%a9s_que_l%27anar_a_tocar_ferro' => '/obra/Marrugat_Cuyàs%2C_Ramon_%282018%29%3A_«Alguna_cosa_més_que_l%27anar_a_tocar_ferro»._La_fraseologia_tarragonina',
        '/obra/Matas_Dalmases%2C_Jordi_%282021%29%3A_El_viacrucis_de_la_negociaci%C3%B3_d%E2%80%99un_govern_de_coalici%C3%B3' => '/obra/Matas_Dalmases%2C_Jordi_%282021%29%3A_El_viacrucis_de_la_negociació_d%27un_govern_de_coalició',
        '/obra/Mettmann%2C_Walter_%281989%29%3A_%C2%ABProverbia_arabum%C2%BB_eine_altkatalanische_sprichw%C3%B6rter-Uns_sentenzensammlung' => '/obra/Mettmann%2C_Walter_%281298%29%3A_«Proverbia_arabum»_eine_altkatalanische_sprichwörter-Uns_sentenzensammlung%2C_ed._1989',
        '/obra/Mettmann%2C_Walter_(1989)%3A_%C2%ABProverbia_arabum%C2%BB_eine_altkatalanische_sprichw%C3%B6rter-Uns_sentenzensammlung' => '/obra/Mettmann%2C_Walter_%281298%29%3A_«Proverbia_arabum»_eine_altkatalanische_sprichwörter-Uns_sentenzensammlung%2C_ed._1989',
        '/obra/Mettmann%2C_Walter_(1989):_%c2%abProverbia_arabum%c2%bb_eine_altkatalanische_sprichw%c3%b6rter-Uns_sentenzensammlung' => '/obra/Mettmann%2C_Walter_%281298%29%3A_«Proverbia_arabum»_eine_altkatalanische_sprichwörter-Uns_sentenzensammlung%2C_ed._1989',
        '/obra/Mettmann,_Walter_(1989):_%C2%ABProverbia_arabum%C2%BB_eine_altkatalanische_sprichw%C3%B6rter-Uns_sentenzensammlung' => '/obra/Mettmann%2C_Walter_%281298%29%3A_«Proverbia_arabum»_eine_altkatalanische_sprichwörter-Uns_sentenzensammlung%2C_ed._1989',
        '/p/A_Cabanes%2C_hi_ha_qui_en_t%C3%A9_ganes' => '/p/A_Cabanes%2C_hi_va_qui_en_té_ganes',
        '/p/A_judici_i_pagar%C2%ABlo%C2%BB_judicat' => '/p/A_judici_i_pagar_«lo»_judicat',
        '/p/Arribar_a_l' => '/p/Arribar_a_l%27ermita_i_no_veure_el_sant',
        '/p/Borratxos_s%C3%B3n_a_les_Coves%2C_%5C_borratxos_son_a_Alcal%C3%A0%2C_%5C_boratxos_a_orreblanca%2C_%5C_no_sabem_qui_guanyar%C3%A0' => '/p/Borratxos_són_a_les_Coves%2C_borratxos_són_a_Alcalà%2C_borratxos_a_Torreblanca_i_a_Orpesa_també_n%27hi_ha',
        '/p/Cadasc%C3%BA%2C_en_sa_casa%2C_sal_el_que_hi_passa' => '/p/Cadascú%2C_en_sa_casa%2C_sap_el_que_hi_passa',
        '/p/Com_un_eix_a_l%27aigua' => '/p/Com_un_peix_a_l%27aigua',
        '/p/Fer_uin_merder' => '/p/Fer_merder',
        '/p/Fr_els_ous_en_terra' => '/p/Fer_els_ous_en_terra',
        '/p/M%C3%A9s_dolent_que_la_tinyaEn' => '/p/Més_dolent_que_la_tinya',
        '/p/Ni_fe_d%27enc%C3%A0rrec' => '/p/Ni_fet_d%27enc%C3%A0rrec',
        '/p/Ni_fe_d%27enc%c3%a0rrec' => '/p/Ni_fet_d%27enc%C3%A0rrec',
        '/p/Pel_setembre_o_desembre%2C_qui_tingui_blat%2C_que_en_sembri' => '/p/Pel_setembre%2C_qui_tingui_blat%2C_que_en_sembri',
        '/p/Posar-li_els_dits_a_la_boca' => '/p/Ficar-li_els_dits_a_la_boca',
        '/p/Posr_en_gu%C3%A0rdia' => '/p/Posar_en_guàrdia',
        '/p/Posra_barba' => '/p/Posar_barba',
        '/p/Qui_dolent_fou_a_Tortosa%2C_dolent_ser%C3%A0_a_TolosaQui_dolent_fou_a_Tortosa%2C_dolent_ser%C3%A0_a_Tolosa' => '/p/Qui_dolent_fou_a_Tortosa%2C_dolent_serà_a_Tolosa',
        '/p/Rompre-li_la_crisma' => '/p/Trencar_o_rompre_la_crisma',
        '/p/Ser_bo_per_a_la_forca_i_per_als_rampills' => '/p/Ser_bo_per_a_la_forca_i_per_al_rampill',
        '/p/Ser_jun_desvirgagallines' => '/p/Ser_un_desvirgagallines',
        '/p/Ser_un_trapsser' => '/p/Ser_un_trapasser',
        '/p/na_dreta_%C3%A9s_m%C3%A9s_un_homenot_que_una_doneta' => '/p/La_dona_que_fuma%2C_jura_i_orina_dreta_és_més_un_homenot_que_una_doneta',
        '/p/A_prendre_ple_sac' => '/p/A_prendre_pel_sac',
        '/?paremiotipus=A+prendre+ple+sac' => '/p/A_prendre_pel_sac',
        '/p/ata_ton_porc%2C_posa_les_olives_al_top%C3%AD%2C_destapa_la_b%C3%B3ta%2C_beu_ton_vi_i_convida_el_teu_ve%C3%AD' => '/p/Per_Sant_Martí_mata_ton_porc%2C_posa_les_olives_al_topí%2C_destapa_la_bóta%2C_beu_ton_vi_i_convida_el_teu_veí',
    ];
}

/**
 * Returns the REQUEST_URI.
 *
 * @psalm-suppress PossiblyUndefinedArrayOffset, RedundantCondition
 */
function get_request_uri(): string
{
    $request_uri = $_SERVER['REQUEST_URI'];
    assert(is_string($request_uri));

    return $request_uri;
}

/**
 * Tries to redirect to a URL, using the manual redirects file.
 */
function try_to_redirect_manual_and_exit(): void
{
    $redirects = get_redirects();

    // Standardize spaces encoding.
    $request_uri = str_replace('%2B', '+', get_request_uri());

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
 * Returns an HTTP 500 page and exits if the database connection fails.
 */
function check_db_or_exit(): void
{
    try {
        get_db();
    } catch (Exception) {
        header(
            "Content-Security-Policy: default-src 'none'; "
            . "base-uri 'none'; "
            . "frame-ancestors 'none'; "
            . "img-src 'self'; "
            . "style-src 'unsafe-inline'"
        );
        header('HTTP/1.1 500 Internal Server Error', true, 500);

        require __DIR__ . '/../docroot/500.html';

        exit;
    }
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

    // Try to find a possible paremiotipus for this URL.
    // TODO: probably disable in the long term.
    $paremiotipus_match = get_paremiotipus_best_match($paremiotipus);
    if ($paremiotipus_match !== '') {
        // Redirect to an existing page.
        header('Location: ' . get_paremiotipus_url($paremiotipus_match), true, 302);

        exit;
    }
}

/**
 * Tries to get the best paremiotipus by searching.
 */
function get_paremiotipus_best_match(string $modisme): string
{
    // We do not want to avoid words here.
    $modisme = trim($modisme, '-');
    $modisme = str_replace(' -', ' ', $modisme);
    $modisme = trim($modisme);

    $paremiotipus = false;
    $modisme = normalize_search($modisme, 'conté');
    if ($modisme !== '') {
        $stmt = get_db()->prepare('SELECT
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
            error_log("Error: {$modisme} not found: " . $e->getMessage());

            return '';
        }

        $paremiotipus = $stmt->fetchColumn();
    }

    return is_string($paremiotipus) ? $paremiotipus : '';
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
 * @phan-return list<array>
 */
function get_modismes(string $paremiotipus): array
{
    $stmt = get_db()->prepare('SELECT
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

    /**
     * @phpstan-var list<array{
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
     */
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Groups modismes by variant.
 *
 * @phpstan-param list<array{
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
 * }> $modismes
 *
 * @phan-param list<array> $modismes
 *
 * @phpstan-return array<string, non-empty-list<array{
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
 * }>>
 *
 * @phan-return non-empty-associative-array<list>
 */
function group_modismes_by_variant(array $modismes): array
{
    $variants = [];
    foreach ($modismes as $modisme) {
        if (!isset($variants[$modisme['MODISME']])) {
            $variants[$modisme['MODISME']] = [];
        }
        $variants[$modisme['MODISME']][] = $modisme;
    }

    return $variants;
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
 * @phan-return list<array>
 */
function get_images(string $paremiotipus): array
{
    $stmt = get_db()->prepare('SELECT
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

    /**
     * @phpstan-var list<array{
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
     */
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Gets a list of Common Voice mp3 files for a specific paremiotipus.
 *
 * @return list<string>
 */
function get_cv_files(string $paremiotipus): array
{
    $stmt = get_db()->prepare('SELECT `file` FROM `commonvoice` WHERE `paremiotipus` = :paremiotipus');
    $stmt->bindParam(':paremiotipus', $paremiotipus);
    $stmt->execute();

    /** @var list<string> */
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Gets an obra array, or false.
 *
 * @phpstan-return false|array{
 *     Identificador: string,
 *     Títol: string,
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
 */
function get_obra(string $obra_title): false|array
{
    $stmt = get_db()->prepare('SELECT
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

    /**
     * @phpstan-var false|array{
     *     Identificador: string,
     *     Títol: string,
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
     */
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Returns the number of paremiotipus for a specific font.
 */
function get_paremiotipus_count_by_font(string $font_id): int
{
    $stmt = get_db()->prepare('SELECT COUNT(*) FROM 00_PAREMIOTIPUS WHERE ID_FONT = :id');
    $stmt->bindParam(':id', $font_id);
    $stmt->execute();

    $total = $stmt->fetchColumn();
    assert(is_int($total));

    return $total;
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
 * Returns a valid URL.
 */
function get_clean_url(?string $url): string
{
    $clean_url = '';
    if ($url !== null) {
        $url = trim($url);

        /** @noinspection NotOptimalIfConditionsInspection */
        if ((str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) && filter_var($url, \FILTER_SANITIZE_URL) === $url) {
            $clean_url = str_replace(['&', '[', ']'], ['&amp;', '%5B', '%5D'], $url);
        }
    }

    return $clean_url;
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
 * Renders a search pagination element.
 */
function render_pager_element(int $page_number, int|string $name, int|string $title = '', bool $is_active = false): string
{
    $rel = '';
    if ($title === 'Primera pàgina') {
        $rel = 'first';
    } elseif ($title === 'Última pàgina') {
        $rel = 'last';
    } elseif ($title === 'Pàgina següent (Ctrl →)') {
        $rel = 'next';
    } elseif ($title === 'Pàgina anterior (Ctrl ←)') {
        $rel = 'prev';
    }

    $pager_item = '<li>';
    if ($is_active) {
        $pager_item .= '<strong class="page-link" title="' . $title . '">' . $name . '</strong>';
    } else {
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
 * Returns the search pagination links.
 */
function render_pager(int $page_num, int $num_pages): string
{
    // Previous and first page links.
    $prev_links = '';
    if ($page_num > 1) {
        // Show previous link.
        $prev_links .= render_pager_element(
            $page_num - 1,
            '<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" width="16" height="16" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24"><path fill="currentColor" d="M15.535 3.515L7.05 12l8.485 8.485l1.415-1.414L9.878 12l7.072-7.071l-1.415-1.414Z"/></svg> Anterior',
            'Pàgina anterior (Ctrl ←)'
        );

        // Show first page link.
        $prev_links .= render_pager_element(1, '1', 'Primera pàgina');
    }

    // Current page item.
    $page_links = render_pager_element($page_num, $page_num, 'Sou a la pàgina ' . $page_num, true);

    // `…` previous link.
    if ($page_num > 2) {
        $prev_prev_page = max(2, $page_num - 5);
        $page_links = render_pager_element(
            $prev_prev_page,
            $prev_prev_page === 2 && $page_num === 3 ? '2' : '…',
            'Pàgina ' . $prev_prev_page
        ) . $page_links;
    }

    // `…` next link.
    if ($page_num < $num_pages - 1) {
        $next_next_page = min($page_num + 5, $num_pages - 1);
        $page_links .= render_pager_element(
            $next_next_page,
            $next_next_page === $num_pages - 1 && $page_num === $num_pages - 2 ? $next_next_page : '…',
            'Pàgina ' . $next_next_page
        );
    }

    // Next and last page links.
    $next_links = '';
    if ($page_num < $num_pages) {
        // Show the last page link.
        $next_links = render_pager_element($num_pages, $num_pages, 'Última pàgina');

        // Show the next link.
        $next_links .= render_pager_element(
            $page_num + 1,
            'Següent <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" width="16" height="16" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24"><path fill="currentColor" d="M8.465 20.485L16.95 12L8.465 3.515L7.05 4.929L14.122 12L7.05 19.071l1.415 1.414Z"/></svg>',
            'Pàgina següent (Ctrl →)'
        );
    }

    return '<nav aria-label="Paginació dels resultats"><ul class="pagination">' . $prev_links . $page_links . $next_links . '</ul></nav>';
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
        if ($first_record_page === 1 || $first_record_page === 11) {
            $output .= " Registres de l'{$first_record_page}";
        } else {
            $output .= " Registres del {$first_record_page}";
        }

        $last_record_page = min($offset + $results_per_page, $total);
        if ($last_record_page === 1 || $last_record_page === 11) {
            $output .= " a l'{$last_record_page}.";
        } else {
            $output .= " al {$last_record_page}.";
        }
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
 * @return non-empty-array<string, string>
 */
function get_idiomes(): array
{
    $idiomes = function_exists('apcu_fetch') ? apcu_fetch('idiomes') : false;
    if ($idiomes === false) {
        $stmt = get_db()->query('SELECT CODI, IDIOMA FROM 00_EQUIVALENTS');
        $idiomes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        if (function_exists('apcu_store')) {
            apcu_store('idiomes', $idiomes);
        }
    }

    /** @var non-empty-array<string, string> $idiomes */
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
 * Tries to return a valid ISO 639-1/639-2 code when given a potentially wrong code coming from the database.
 *
 * Input parameter $code is returned when value is not found in mapping but the format is valid. An empty string is
 * returned if the format of $code is not valid. Value is trimmed and converted to lowercase before performing checks.
 */
function get_idioma_iso_code(string $code): string
{
    $code = strtolower(trim($code));
    if (!ctype_lower($code) || (strlen($code) !== 2 && strlen($code) !== 3)) {
        return '';
    }

    $wrong_code_map = [
        // `ar` is the ISO code for Arabic, but in the database it is used for Aranès and Argentinian (Spanish).
        'ar' => 'oc',
        'as' => 'ast',
        // `bs` is the ISO code for Bosnian, but in the database it is used for Serbocroata.
        'bs' => 'sh',
        'll' => 'la',
        'po' => 'pl',
        // ISO code for Provençal is missing. "pro" is for Old Provençal, and "prv" is no longer recognised. In the
        // database we have "pr", which is not assigned by ISO.
        'pr' => 'prv',
        'sa' => 'sc',
        // `si` is the ISO code of Sinhalese, but in the database it is used for Sicilian.
        'si' => 'scn',
    ];

    return $wrong_code_map[$code] ?? $code;
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
 * @return list<string>
 */
function build_search_query(string $search, string $search_mode, string &$where_clause): array
{
    $checkboxes = [
        'variant' => 'MODISME_LC_WA',
        'sinonim' => 'SINONIM_LC_WA',
        'equivalent' => 'EQUIVALENT_LC_WA',
    ];

    $WORD_BOUNDARY_BEGIN = "'[[:<:]]'";
    $WORD_BOUNDARY_END = "'[[:>:]]'";

    $arguments = [$search];
    if ($search_mode === 'whole_sentence' || $search_mode === 'wildcard') {
        $db_version = get_db()->getAttribute(PDO::ATTR_SERVER_VERSION);
        assert(is_string($db_version));
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
 * @param list<string> $arguments
 */
function get_n_results(string $where_clause, array $arguments): int
{
    // Cache the count query if APCu is available.
    $cache_key = $where_clause . ' ' . implode('|', $arguments);
    $total = function_exists('apcu_fetch') ? apcu_fetch($cache_key) : false;
    if ($total === false) {
        try {
            $stmt = get_db()->prepare("SELECT COUNT(DISTINCT PAREMIOTIPUS) FROM 00_PAREMIOTIPUS {$where_clause}");
            $stmt->execute($arguments);
            $total = $stmt->fetchColumn();
            if (function_exists('apcu_store')) {
                apcu_store($cache_key, $total);
            }
        } catch (Exception) {
            $total = 0;
        }
    }

    assert(is_int($total));

    return $total;
}

/**
 * Returns the paremiotipus search results.
 *
 * @param list<string> $arguments
 *
 * @return list<string>
 */
function get_paremiotipus_search_results(string $where_clause, array $arguments, int $offset, int $limit): array
{
    $stmt = get_db()->prepare("SELECT
            DISTINCT PAREMIOTIPUS
        FROM
            00_PAREMIOTIPUS
        {$where_clause}
        ORDER BY
            PAREMIOTIPUS
        LIMIT
            {$offset}, {$limit}");
    $stmt->execute($arguments);

    /** @var list<string> */
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
        $string = str_replace(
            ['’', 'à', 'á', 'è', 'é', 'í', 'ï', 'ò', 'ó', 'ú', 'ü'],
            ["'", 'a', 'a', 'e', 'e', 'i', 'i', 'o', 'o', 'u', 'u'],
            mb_strtolower($string)
        );

        // Remove double spaces.
        $string = preg_replace('/\s+/', ' ', $string);
        assert(is_string($string));
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

                // Remove loose `-` operator.
                /** @noinspection CascadeStringReplacementInspection */
                $string = str_replace(' - ', ' ', $string);

                // Nice to have: remove extra useless characters (not `-`).
                /** @noinspection CascadeStringReplacementInspection */
                $string = str_replace(['“', '”', '«', '»', '…', ',', ':', ';', '!', '¡', '¿', '–', '—', '―', '─'], '', $string);

                // Build the full-text query.
                $words = preg_split('/\\s+/', $string);
                $string = '';

                /** @var non-empty-list<string> $words */
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
 * @return non-empty-array<string, string>
 */
function get_editorials(): array
{
    $editorials = function_exists('apcu_fetch') ? apcu_fetch('nom_editorials') : false;
    if ($editorials === false) {
        $stmt = get_db()->query('SELECT CODI, NOM FROM 00_EDITORIA');
        $editorials = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        if (function_exists('apcu_store')) {
            apcu_store('nom_editorials', $editorials);
        }
    }

    /** @var non-empty-array<string, string> $editorials */
    return $editorials;
}

/**
 * Returns array of 00_FONTS `Títol` values keyed by `Identificador`.
 *
 * @return non-empty-array<string, string>
 */
function get_fonts(): array
{
    $fonts = function_exists('apcu_fetch') ? apcu_fetch('identificador_fonts') : false;
    if ($fonts === false) {
        // We are only using the first column for now (not the title). We could extend this to include the full table
        // and reuse it in the "obra" page, but that may not be worth it.
        $stmt = get_db()->query('SELECT Identificador, Títol FROM 00_FONTS');
        $fonts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        if (function_exists('apcu_store')) {
            apcu_store('identificador_fonts', $fonts);
        }
    }

    /** @var non-empty-array<string, string> $fonts */
    return $fonts;
}

/**
 * Gets an image tag.
 *
 * Images will be inside a picture tag that will include an avif or webp file, if they exist.
 * Note: image file existence is not checked, as it is expected to be checked before calling this function.
 */
function get_image_tags(
    string $file_name,
    string $path,
    string $alt_text = '',
    int $width = 0,
    int $height = 0,
    bool $lazy_loading = true
): string {
    $optimized_type = '';
    $optimized_file_path = '';
    // Image files may have been provided in WEBP/AVIF format already.
    if (!str_ends_with($file_name, '.webp') && !str_ends_with($file_name, '.avif')) {
        // We currently provide AVIF as an alternative for JPEG images, and WEBP for GIF/PNG images.
        $avif_file = str_ireplace('.jpg', '.avif', $file_name);
        $avif_exists = str_ends_with($avif_file, '.avif') && is_file(__DIR__ . "/../docroot{$path}{$avif_file}");
        if ($avif_exists) {
            $optimized_type = 'avif';
            $optimized_file_path = $path . rawurlencode($avif_file);
        } else {
            $webp_file = str_ireplace(['.png', '.gif'], '.webp', $file_name);
            $webp_exists = str_ends_with($webp_file, '.webp') && is_file(__DIR__ . "/../docroot{$path}{$webp_file}");
            if ($webp_exists) {
                $optimized_type = 'webp';
                $optimized_file_path = $path . rawurlencode($webp_file);
            }
        }
    }

    $output = '';
    if ($optimized_type !== '') {
        $output .= '<picture>';
        $output .= '<source srcset="' . $optimized_file_path . '" type="image/' . $optimized_type . '">';
    }

    $output .= '<img';
    if ($lazy_loading) {
        $output .= ' loading="lazy"';
    }

    if ($width > 0 && $height > 0) {
        $output .= ' width="' . $width . '" height="' . $height . '"';
    }

    $output .= ' decoding="async" alt="' . htmlspecialchars($alt_text) . '" src="' . $path . rawurlencode($file_name) . '">';

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
    $n_modismes = function_exists('apcu_fetch') ? apcu_fetch('n_modismes') : false;
    if ($n_modismes === false) {
        $stmt = get_db()->query('SELECT COUNT(*) FROM 00_PAREMIOTIPUS');
        $n_modismes = $stmt->fetchColumn();
        if (function_exists('apcu_store')) {
            apcu_store('n_modismes', $n_modismes);
        }
    }

    assert(is_int($n_modismes));

    return $n_modismes;
}

/**
 * Returns the total number of distinct paremiotipus.
 */
function get_n_paremiotipus(): int
{
    $n_paremiotipus = function_exists('apcu_fetch') ? apcu_fetch('n_paremiotipus') : false;
    if ($n_paremiotipus === false) {
        $stmt = get_db()->query('SELECT COUNT(DISTINCT PAREMIOTIPUS) FROM 00_PAREMIOTIPUS');
        $n_paremiotipus = $stmt->fetchColumn();
        if (function_exists('apcu_store')) {
            apcu_store('n_paremiotipus', $n_paremiotipus);
        }
    }

    assert(is_int($n_paremiotipus));

    return $n_paremiotipus;
}

/**
 * Returns the total number of sources (fonts).
 */
function get_n_fonts(): int
{
    $n_fonts = function_exists('apcu_fetch') ? apcu_fetch('n_fonts') : false;
    if ($n_fonts === false) {
        $stmt = get_db()->query('SELECT COUNT(DISTINCT AUTOR, ANY, EDITORIAL) FROM 00_PAREMIOTIPUS');
        $n_fonts = $stmt->fetchColumn();
        if (function_exists('apcu_store')) {
            apcu_store('n_fonts', $n_fonts);
        }
    }

    assert(is_int($n_fonts));

    return $n_fonts;
}

/**
 * Returns a list of top 100 paremiotipus.
 *
 * @return non-empty-list<string>
 */
function get_top100_paremiotipus(): array
{
    $top_paremiotipus = function_exists('apcu_fetch') ? apcu_fetch('top_paremiotipus') : false;
    if ($top_paremiotipus === false) {
        $stmt = get_db()->query('SELECT
                PAREMIOTIPUS, COUNT(*) AS POPULAR
            FROM
                00_PAREMIOTIPUS
            GROUP BY
                PAREMIOTIPUS
            ORDER BY
                POPULAR DESC
            LIMIT
                100');
        $top_paremiotipus = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        if (function_exists('apcu_store')) {
            apcu_store('top_paremiotipus', $top_paremiotipus);
        }
    }

    /** @var non-empty-list<string> $top_paremiotipus */
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
    $random_offset = mt_rand(0, MAX_RANDOM_PAREMIOTIPUS - 1);
    $stmt = get_db()->query("SELECT Paremiotipus FROM common_paremiotipus LIMIT {$random_offset}, 1");

    $random_paremiotipus = $stmt->fetchColumn();

    return is_string($random_paremiotipus) ? $random_paremiotipus : '';
}

/**
 * Returns a random book by Víctor Pàmies.
 *
 * @return array{Imatge: string, Títol: string, URL: ?string, WIDTH: int, HEIGHT: int}
 */
function get_random_book(): array
{
    // As this query has a limited number of results but runs many times, cache it in memory.
    /** @phpstan-var false|list<array{Imatge: string, Títol: string, URL: ?string, WIDTH: int, HEIGHT: int}> $books */
    $books = function_exists('apcu_fetch') ? apcu_fetch('books') : false;
    if ($books === false) {
        $stmt = get_db()->query('SELECT Imatge, `Títol`, URL, WIDTH, HEIGHT FROM `00_OBRESVPR`');

        /** @phpstan-var list<array{Imatge: string, Títol: string, URL: ?string, WIDTH: int, HEIGHT: int}> $books */
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (function_exists('apcu_store')) {
            apcu_store('books', $books);
        }
    }

    return $books[array_rand($books)];
}

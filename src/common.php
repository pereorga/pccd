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

// TODO: Remove when PHP 8.4 is required.
if (!function_exists('mb_ucfirst')) {
    /**
     * ucfirst() function for multibyte character encodings.
     *
     * Borrowed from https://stackoverflow.com/a/58915632/1391963.
     */
    function mb_ucfirst(string $str, ?string $encoding = null): string
    {
        return mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding) . mb_substr($str, 1, null, $encoding);
    }
}

final class PageRenderer
{
    public readonly string $name;
    public readonly string $mainContent;
    public readonly string $sideBlocks;

    private static string $title;
    private static string $paremiotipusBlocks;
    private static string $canonicalUrl = '';
    private static string $metaDescription = '';
    private static string $metaImage = '';
    private static string $ogAudioUrl = '';
    private static string $ogType = 'article';

    public function __construct()
    {
        $this->name = $this->detectPageName();
        $this->mainContent = $this->renderMainContent();
        $this->sideBlocks = $this->renderSideBlocks();
    }

    public function getTitle(): string
    {
        return self::$title;
    }

    public static function setTitle(string $title): void
    {
        self::$title = ct(text: $title, escape_html: false, end_with_dot: false);
    }

    public static function setParemiotipusBlocks(string $blocks): void
    {
        self::$paremiotipusBlocks = $blocks;
    }

    public static function setCanonicalUrl(string $url): void
    {
        self::$canonicalUrl = $url;
    }

    public static function setMetaDescription(string $description): void
    {
        self::$metaDescription = $description;
    }

    public static function setMetaImage(string $image_url): void
    {
        self::$metaImage = $image_url;
    }

    public static function setOgAudioUrl(string $audio_url): void
    {
        self::$ogAudioUrl = $audio_url;
    }

    public static function setOgType(string $type): void
    {
        self::$ogType = $type;
    }

    public function renderPageMetaTags(): string
    {
        $meta_tags = [];
        $is_homepage = $this->name === 'search' && (!isset($_GET['cerca']) || $_GET['cerca'] === '') && get_search_page_number() === 1;

        if ($this->name === 'search' && !$is_homepage) {
            // Do not index search pages that are not the homepage.
            $meta_tags[] = '<meta name="robots" content="noindex">';
        }

        if (self::$ogType !== '') {
            $meta_tags[] = '<meta property="og:type" content="' . self::$ogType . '">';
        }

        if (self::$metaDescription !== '') {
            $meta_tags[] = '<meta name="description" property="og:description" content="' . self::$metaDescription . '">';
        }

        if (self::$metaImage !== '') {
            $meta_tags[] = '<meta name="twitter:image" property="og:image" content="' . self::$metaImage . '">';
            $meta_tags[] = '<meta name="twitter:card" content="' . ($is_homepage || str_contains(self::$metaImage, '/og/') ? 'summary_large_image' : 'summary') . '">';
        }

        if (self::$ogAudioUrl !== '') {
            $meta_tags[] = '<meta property="og:audio" content="' . self::$ogAudioUrl . '">';
        }

        if (self::$canonicalUrl !== '') {
            $meta_tags[] = '<link rel="canonical" href="' . self::$canonicalUrl . '">';
        }

        return implode("\n", $meta_tags);
    }

    public static function render(): void
    {
        // Redirect to the homepage if 'index.php' is in the URL.
        if (str_contains(get_request_uri(), 'index.php')) {
            header('Location: /');

            exit;
        }

        // Cache pages for 15 minutes in the browser.
        header('Cache-Control: public, max-age=900');

        // Include the page template.
        require __DIR__ . '/templates/main.php';
    }

    private function detectPageName(): string
    {
        $allowed_pages = ['credits', 'instruccions', 'fonts', 'llibres', 'obra', 'paremiotipus', 'projecte', 'top100', 'top10000'];

        foreach ($allowed_pages as $allowed_page) {
            if (isset($_GET[$allowed_page])) {
                return $allowed_page;
            }
        }

        // Default to the search page, which is also the homepage.
        return 'search';
    }

    private function renderMainContent(): string
    {
        ob_start();

        require __DIR__ . "/pages/{$this->name}.php";
        $main_content = ob_get_clean();

        assert($main_content !== false);

        return $main_content;
    }

    private function renderBooksBlock(): string
    {
        $block = '<div class="bloc bloc-books">';
        $block .= '<p><a href="/llibres">Llibres de l\'autor</a></p>';
        $block .= get_random_book()->render(['preload' => true, 'preload_media' => '(min-width: 768px)']);
        $block .= '</div>';

        return $block;
    }

    private function renderCreditsBlock(): string
    {
        $block = '<div class="bloc bloc-credits bloc-white">';
        $block .= '<p>Un projecte de:</p>';
        $block .= '<p><a href="http://www.dites.cat">dites.cat</a></p>';
        $block .= '<p><a href="https://www.softcatala.org"><img alt="Softcatalà" width="120" height="80" src="/img/logo-softcatala.svg"></a></p>';
        $block .= '</div>';

        return $block;
    }

    private function renderTop100Block(): string
    {
        $random_paremiotipus = get_random_top_paremiotipus(100);

        $block = '<div class="bloc" data-nosnippet>';
        $block .= '<p class="text-balance">«<a href="' . get_paremiotipus_url($random_paremiotipus) . '">';
        $block .= get_paremiotipus_display($random_paremiotipus);
        $block .= '</a>»</p>';
        $block .= '<div class="footer"><a href="/top100">Les 100 parèmies més citades</a></div>';
        $block .= '</div>';

        return $block;
    }

    private function renderTop10000Block(): string
    {
        $random_paremiotipus = get_random_top_paremiotipus(10000);

        $block = '<div class="bloc" data-nosnippet>';
        $block .= '<p class="text-balance">«<a href="' . get_paremiotipus_url($random_paremiotipus) . '">';
        $block .= get_paremiotipus_display($random_paremiotipus);
        $block .= '</a>»</p>';
        $block .= '<div class="footer">Les 10.000 parèmies més citades</div>';
        $block .= '</div>';

        return $block;
    }

    private function renderSideBlocks(): string
    {
        $blocks = '';
        if ($this->name === 'search') {
            $blocks .= $this->renderTop100Block();
            $blocks .= $this->renderBooksBlock();
        } elseif ($this->name === 'paremiotipus') {
            $blocks .= self::$paremiotipusBlocks;
        }

        $blocks .= $this->renderCreditsBlock();

        if ($this->name !== 'search') {
            $blocks .= $this->renderTop10000Block();
        }

        return $blocks;
    }
}

final readonly class Variant
{
    public string $PAREMIOTIPUS;
    public string $AUTOR;
    public string $AUTORIA;
    public string $DIARI;
    public string $ARTICLE;
    public string $EDITORIAL;
    public string $ANY;
    public string $PAGINA;
    public string $LLOC;
    public string $EXPLICACIO;
    public string $EXPLICACIO2;
    public string $EXEMPLES;
    public string $SINONIM;
    public string $EQUIVALENT;
    public string $IDIOMA;
    public string $FONT;
    public string $ACCEPCIO;
    public string $ID_FONT;
}

final readonly class Obra
{
    public string $Identificador;
    public string $Títol;
    public string $Imatge;
    public string $Preu;
    public string $Any_edició;
    public string $Pàgines;
    public string $Registres;
    public string $Any;
    public string $Autor;
    public string $Collecció;
    public string $Data_compra;
    public string $Edició;
    public string $Editorial;
    public string $Idioma;
    public string $ISBN;
    public string $Lloc_compra;
    public string $Municipi;
    public string $Núm_collecció;
    public string $Observacions;
    public string $URL;
    public string $Varietat_dialectal;
    public string $WIDTH;
    public string $HEIGHT;
}

final readonly class Image
{
    public string $Identificador;
    public string $URL_ENLLAÇ;
    public string $AUTOR;
    public string $ANY;
    public string $DIARI;
    public string $ARTICLE;
    public string $WIDTH;
    public string $HEIGHT;
}

final readonly class Book
{
    private const array URL_FIXES = [
        'https://lafinestralectora.cat/els-100-refranys-mes-populars-2/' => 'https://lafinestralectora.cat/els-100-refranys-mes-populars/',
    ];
    private string $Imatge;
    private string $Títol;
    private string $URL;
    private string $WIDTH;
    private string $HEIGHT;

    /**
     * @param array{
     *     alt_text?: string,
     *     file_name?: string,
     *     height?: int,
     *     lazy_loading?: bool,
     *     path?: string,
     *     width?: int,
     *     preload?: bool,
     *     preload_media?: string
     * } $imageOptions
     */
    public function render(array $imageOptions = []): string
    {
        $url = self::URL_FIXES[$this->URL] ?? $this->URL;
        $html = '';
        if ($url !== '') {
            $html .= '<a href="' . $url . '" title="' . htmlspecialchars($this->Títol) . '">';
        }

        // Default image options.
        $defaultOptions = [
            'alt_text' => $this->Títol,
            'file_name' => $this->Imatge,
            'height' => (int) $this->HEIGHT,
            'path' => '/img/obres/',
            'width' => (int) $this->WIDTH,
        ];

        // Generate image tags, merging provided options.
        $html .= get_image_tags(...$imageOptions + $defaultOptions);

        if ($url !== '') {
            $html .= '</a>';
        }

        return $html;
    }
}

/**
 * Generic cache wrapper to get data from cache (APCu).
 *
 * @template T
 *
 * @param callable(): T $callback
 *
 * @return T
 */
function cache_get(string $key, callable $callback)
{
    if (!extension_loaded('apcu')) {
        return $callback();
    }

    $cached = apcu_fetch($key);
    if ($cached !== false) {
        return $cached;
    }

    $value = $callback();
    apcu_store($key, $value);

    return $value;
}

/**
 * Transforms plain text into valid HTML turning URLs into links.
 */
function html_escape_and_link_urls(string $text, string $property = '', bool $debug = false): string
{
    $escaped = htmlspecialchars($text, ENT_COMPAT | ENT_SUBSTITUTE | ENT_HTML5);
    $pattern = '/(https?:\/\/[^\s]+?)(?=[.,;:!?)"\']*(?:\s|&gt;|$))/';

    $output = preg_replace_callback($pattern, static function (array $matches) use ($debug, $property): string {
        $url = $matches[1];
        if ($debug) {
            file_put_contents(
                __DIR__ . '/../tmp/test_tmp_debug_html_escape_and_link_urls.txt',
                $url . "\n",
                FILE_APPEND
            );
        }

        $link = '<a class="external" target="_blank" rel="noopener" href="' . $url . '"';
        if ($property !== '') {
            $link .= ' property="' . $property . '"';
        }
        $link .= ">{$url}</a>";

        return $link;
    }, $escaped);

    assert(is_string($output));

    return $output;
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

    try {
        $pdo = new PDO("mysql:host={$host};dbname={$db_name};charset=utf8mb4", $user, $password, [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ORACLE_NULLS => PDO::NULL_TO_STRING,
            PDO::ATTR_STRINGIFY_FETCHES => true,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
        ]);

        return $pdo;
    } catch (Exception) {
        ob_end_clean();

        header('HTTP/1.1 500 Internal Server Error', response_code: 500);
        header('Cache-Control: no-cache, no-store, must-revalidate');

        require __DIR__ . '/../docroot/500.html';

        exit;
    }
}

/**
 * Trims and removes newlines, extra spaces and unsafe characters from the provided string.
 *
 * Optionally and by default, escapes HTML and ensures the string ends with a dot or punctuation.
 */
function ct(string $text, bool $escape_html = true, bool $end_with_dot = true): string
{
    // Remove unsafe characters (https://htmlhint.com/docs/user-guide/rules/attr-unsafe-chars).
    $text = preg_replace("/\u{00AD}/", '', $text);
    assert(is_string($text));
    $text = preg_replace("/\u{200E}/", '', $text);
    assert(is_string($text));

    // Remove newlines and extra spaces.
    // https://html-validate.org/rules/attr-delimiter.html.
    // https://html-validate.org/rules/no-trailing-whitespace.html.
    // https://htmlhint.com/docs/user-guide/rules/attr-whitespace.
    $text = preg_replace('/\n/', ' ', $text);
    assert(is_string($text));
    $text = preg_replace('/\s+/', ' ', $text);
    assert(is_string($text));

    // Escape HTML.
    if ($escape_html) {
        $text = htmlspecialchars($text);
    }

    if ($end_with_dot) {
        // Remove trailing dot character.
        $text = trim($text, ". \n\r\t\v\x00");

        // Add trailing dot character.
        if (
            !str_ends_with($text, '?')
            && !str_ends_with($text, '!')
            && !str_ends_with($text, '…')
            && !str_ends_with($text, ';')
            && !str_ends_with($text, '*')
        ) {
            $text .= '.';
        }
    }

    return trim($text);
}

/**
 * Gets the current search page number, defaulting to 1.
 */
function get_search_page_number(): int
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
 * Returns the paremiotipus name for display.
 */
function get_paremiotipus_display(string $paremiotipus, bool $escape_html = true, bool $use_fallback_string = true): string
{
    static $stmt = null;
    if ($stmt === null) {
        $stmt = get_db()->prepare('SELECT `Display` FROM `paremiotipus_display` WHERE `Paremiotipus` = :paremiotipus');
    }

    $display = cache_get($paremiotipus, static function () use ($paremiotipus, $stmt): string {
        $stmt->execute([':paremiotipus' => $paremiotipus]);
        $value = $stmt->fetchColumn();
        if ($value === false) {
            error_log("Error: '{$paremiotipus}' not found in paremiotipus_display table");

            return '';
        }

        assert(is_string($value));

        return $value;
    });

    if ($display === '' && $use_fallback_string) {
        $display = $paremiotipus;
    }

    return $escape_html ? htmlspecialchars($display) : $display;
}

/**
 * Returns the path for a paremiotipus/obra title.
 */
function name_to_path(string $name, bool $encode = true): string
{
    $path = str_replace([' ', '/'], ['_', '\\'], $name);

    if ($encode) {
        return rawurlencode($path);
    }

    return $path;
}

/**
 * Returns the name for a paremiotipus/obra querystring.
 */
function path_to_name(string $path): string
{
    return str_replace(['_', '\\'], [' ', '/'], $path);
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
 * Returns a canonical URL for the paremiotipus.
 */
function get_paremiotipus_url(string $paremiotipus, bool $absolute = false, bool $encode_full_url = false): string
{
    $base_url = '';
    if ($absolute) {
        $base_url = 'https://pccd.dites.cat';
    }

    if ($encode_full_url) {
        return rawurlencode($base_url . '/p/' . name_to_path($paremiotipus, encode: false));
    }

    return $base_url . '/p/' . name_to_path($paremiotipus);
}

/**
 * Returns a canonical URL for the obra.
 */
function get_obra_url(string $obra, bool $absolute = false): string
{
    $base_url = '';
    if ($absolute) {
        $base_url = 'https://pccd.dites.cat';
    }

    return $base_url . '/obra/' . name_to_path($obra);
}

/**
 * Returns a URL with some encoded characters if $url is a valid HTTP/HTTPS url, or an empty string otherwise.
 */
function get_clean_url(string $url): string
{
    $url = trim($url);
    if (
        (str_starts_with($url, 'http://') || str_starts_with($url, 'https://'))
        && filter_var($url, FILTER_VALIDATE_URL) !== false
    ) {
        // Encode a few characters.
        return str_replace(['&', '[', ']'], ['&amp;', '%5B', '%5D'], $url);
    }

    return '';
}

/**
 * Formats an integer in Catalan.
 */
function format_nombre(int|string $num): string
{
    return number_format(num: (float) $num, thousands_separator: '.');
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
    return cache_get('equivalents', static function (): array {
        $stmt = get_db()->query('SELECT `CODI`, `IDIOMA` FROM `00_EQUIVALENTS`');

        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    });
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
    if (preg_match('/^[a-z]{2,3}$/', $code) !== 1) {
        return '';
    }

    $wrong_code_map = [
        // `ar` is the ISO code for Arabic, but in the database it is used for Aranès and Argentinian (Spanish).
        'ar' => 'oc',
        'as' => 'ast',
        // `bs` is the ISO code for Bosnian, but in the database it is used for Serbocroata.
        'bs' => 'sh',
        'll' => 'la',
        // `ne` is the ISO code Official Nepali Native, but in the database may be used for Dutch.
        // 'ne' => 'nl',
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
 * Remove special characters from a string, especially for matching paremiotipus.
 *
 * @param string $search_mode The search mode to normalize for. If provided, the string is processed for search.
 */
function normalize_search(string $string, string $search_mode = ''): string
{
    if ($string !== '') {
        // Remove useless characters in search that may affect syntax, or that are not useful.
        $string = str_replace(
            ['"', '+', '.', '%', '--', '_', '(', ')', '[', ']', '{', '}', '^', '>', '<', '~', '@', '$', '|', '/', '\\'],
            '',
            $string
        );

        // Standardize simple quotes.
        $string = str_replace('’', "'", $string);

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
                $string = str_replace(
                    ['“', '”', '«', '»', '…', ',', ':', ';', '!', '¡', '¿', '–', '—', '―', '─'],
                    '',
                    $string
                );

                // Build the full-text query.
                $words = preg_split('/\s+/', $string);
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
 * @return array<string, string>
 */
function get_editorials(): array
{
    return cache_get('editorials', static function (): array {
        $stmt = get_db()->query('SELECT `CODI`, `NOM` FROM `00_EDITORIA`');

        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    });
}

/**
 * Returns array of 00_FONTS `Títol` values keyed by `Identificador`.
 *
 * Used in the paremiotipus page and in some reports.
 *
 * @return array<string, string>
 */
function get_fonts_paremiotipus(): array
{
    return cache_get('fonts', static function (): array {
        $stmt = get_db()->query('SELECT `Identificador`, `Títol` FROM `00_FONTS`');

        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    });
}

/**
 * Generates HTML markup for an image, optionally within a <picture> tag for optimized formats.
 *
 * @param string $file_name The file name of the image file.
 * @param string $path The path to the image file, starting with a slash.
 * @param string $alt_text (optional) The alternative text for the image. Defaults to an empty string.
 * @param bool $escape_html (optional) Whether to escape the alternative text. Defaults to true.
 * @param int $width (optional) The width attribute for the <img> tag. Defaults to 0 (not set).
 * @param int $height (optional) The height attribute for the <img> tag. Defaults to 0 (not set).
 * @param bool $lazy_loading (optional) If true, adds 'loading="lazy"' to the <img> tag. Defaults to true.
 * @param bool $preload (optional) If true, adds a preload HTTP header for the image. Defaults to false.
 * @param string $preload_media (optional) Adds the media rule to the preloaded image. Defaults to empty string (no media rule).
 *
 * @return string The generated HTML markup for the image.
 */
function get_image_tags(
    string $file_name,
    string $path,
    string $alt_text = '',
    bool $escape_html = true,
    int $width = 0,
    int $height = 0,
    bool $lazy_loading = true,
    bool $preload = false,
    string $preload_media = ''
): string {
    $optimized_file_url = '';
    $file_url = $path . rawurlencode($file_name);
    // Image files may have been provided in WEBP/AVIF format already.
    if (!str_ends_with($file_name, '.webp') && !str_ends_with($file_name, '.avif')) {
        // We currently provide AVIF as an alternative for JPEG/PNG images, and WEBP for GIF.
        $avif_file = str_ireplace(['.jpg', '.png'], '.avif', $file_name);
        $avif_exists = str_ends_with($avif_file, '.avif') && is_file(__DIR__ . "/../docroot{$path}{$avif_file}");
        if ($avif_exists) {
            $optimized_file_url = $path . rawurlencode($avif_file);
        } else {
            $webp_file = str_ireplace('.gif', '.webp', $file_name);
            $webp_exists = str_ends_with($webp_file, '.webp') && is_file(__DIR__ . "/../docroot{$path}{$webp_file}");
            if ($webp_exists) {
                $optimized_file_url = $path . rawurlencode($webp_file);
            }
        }
    }
    $preload_url = $optimized_file_url !== '' ? $optimized_file_url : $file_url;
    $extension = strtolower(pathinfo($preload_url, PATHINFO_EXTENSION));
    $mime_types = [
        'avif' => 'image/avif',
        'gif' => 'image/gif',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];
    $mime_type = $mime_types[$extension] ?? '';

    // Generate the HTML markup for the image.
    $image_tags = '';
    if ($optimized_file_url !== '') {
        $image_tags .= '<picture>';
        $image_tags .= '<source srcset="' . $optimized_file_url . '"';
        if ($mime_type !== '') {
            $image_tags .= ' type="' . $mime_type . '"';
        }
        $image_tags .= '>';
    }
    $image_tags .= '<img alt="' . ($escape_html ? htmlspecialchars($alt_text) : $alt_text) . '"';
    if ($lazy_loading) {
        $image_tags .= ' loading="lazy"';
    }
    if ($width > 0 && $height > 0) {
        $image_tags .= ' width="' . $width . '" height="' . $height . '"';
    }
    $image_tags .= ' src="' . $file_url . '">';
    if ($optimized_file_url !== '') {
        $image_tags .= '</picture>';
    }

    if ($preload) {
        preload_image_header(url: $preload_url, media: $preload_media, type: $mime_type);
    }

    return $image_tags;
}

/**
 * Adds an HTTP header to preload an image.
 */
function preload_image_header(string $url, string $media = '', string $type = ''): void
{
    $header = "Link: <{$url}>; rel=preload; as=image";
    if ($type !== '') {
        $header .= "; type={$type}";
    }
    if ($media !== '') {
        $header .= "; media=\"{$media}\"";
    }
    header($header);

    // The goal here is to flush the headers early, allowing the browser to start
    // preloading the specified image before the full HTML content is generated.
    // However, the practical impact of this optimization has proven to be minimal
    // for 2 reasons:
    // 1. The page is already generated very quickly.
    // 2. The header is added late in the script execution.
    //
    // Benchmarking with Chrome DevTools and curl showed marginal improvements of
    // ~2-5ms in the time for headers to be received. Moving the preload logic
    // earlier in the script (e.g., at the top of a long "paremiotipus" page)
    // reduced this time further to ~12-17ms, still well below the threshold of
    // human perception.
    //
    // ob_flush();
    // flush();
}

/**
 * Returns an HTTP 404 page and exits.
 *
 * @param string $paremiotipus if not empty, suggest to visit that paremiotipus page.
 */
function return_404_and_exit(string $paremiotipus = ''): never
{
    header('HTTP/1.1 404 Not Found', response_code: 404);

    require __DIR__ . '/../docroot/404.html';
    if ($paremiotipus !== '') {
        $url = get_paremiotipus_url($paremiotipus);
        $paremiotipus = get_paremiotipus_display($paremiotipus);
        echo "<p>També us pot ser útil la pàgina del paremiotipus <a href='{$url}'>{$paremiotipus}</a>.";
    }

    exit;
}

/**
 * Returns the total number of occurrences (modismes).
 */
function get_n_modismes(): int
{
    return cache_get('n_modismes', static function (): int {
        $stmt = get_db()->query('SELECT COUNT(1) FROM `00_PAREMIOTIPUS`');

        return (int) $stmt->fetchColumn();
    });
}

/**
 * Returns the total number of distinct paremiotipus.
 */
function get_n_paremiotipus(): int
{
    return cache_get('n_paremiotipus', static function (): int {
        $stmt = get_db()->query('SELECT COUNT(1) FROM `paremiotipus_display`');

        return (int) $stmt->fetchColumn();
    });
}

/**
 * Returns the total number of individual authors (informants).
 */
function get_n_informants(): int
{
    return cache_get('n_informants', static function (): int {
        $stmt = get_db()->query('SELECT COUNT(DISTINCT `AUTOR`) FROM `00_PAREMIOTIPUS`');

        return (int) $stmt->fetchColumn();
    });
}

/**
 * Returns the total number of sources (fonts).
 */
function get_n_fonts(): int
{
    return cache_get('n_fonts', static function (): int {
        $stmt = get_db()->query('SELECT COUNT(1) FROM `00_FONTS`');

        return (int) $stmt->fetchColumn();
    });
}

/**
 * Returns a random paremiotipus from top 10000.
 */
function get_random_top_paremiotipus(int $max = 10000): string
{
    $random_index = mt_rand(0, $max - 1);

    return cache_get("paremiotipus_{$random_index}", static function () use ($random_index): string {
        $stmt = get_db()->query("SELECT `Paremiotipus` FROM `common_paremiotipus` LIMIT 1 OFFSET {$random_index}");

        $random = $stmt->fetchColumn();
        assert(is_string($random));

        return $random;
    });
}

/**
 * Returns a random book by Víctor Pàmies.
 */
function get_random_book(): Book
{
    $books = cache_get('obresvpr', static function (): array {
        $stmt = get_db()->query('SELECT `Imatge`, `Títol`, `URL`, `WIDTH`, `HEIGHT` FROM `00_OBRESVPR`');

        return $stmt->fetchAll(PDO::FETCH_CLASS, Book::class);
    });

    return $books[array_rand($books)];
}

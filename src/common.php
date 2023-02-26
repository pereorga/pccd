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
 * Returns the database connection.
 *
 * @psalm-suppress PossiblyFalseOperand,PossiblyFalseArgument
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

    /** @phpstan-ignore-next-line */
    $pdo = new PDO('mysql:host=' . getenv('MYSQL_HOSTNAME') . ';dbname=' . getenv('MYSQL_DATABASE') . ';charset=utf8mb4', getenv('MYSQL_USER'), getenv('MYSQL_PASSWORD'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_PERSISTENT => false,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
    ]);

    return $pdo;
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
 * Returns side blocks HTML.
 */
function get_side_blocks(): string
{
    global $side_blocks;

    return $side_blocks ?? '';
}

/**
 * Sets the side blocks HTML.
 */
function set_side_blocks(string $blocks): void
{
    global $side_blocks;

    $side_blocks = $blocks;
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

    $page_title = $title;
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
 * Sets the meta description.
 */
function set_meta_description(string $description): void
{
    global $meta_description;

    $meta_description = $description;
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
 * Returns the list of URLs to prefetch.
 *
 * @return array<string, string>
 */
function get_prefetch_urls(): array
{
    global $prefetch_urls;

    return $prefetch_urls ?? [];
}

/**
 * Sets a URL to be prefetched.
 */
function set_prefetch_url(string $url, string $type): void
{
    global $prefetch_urls;

    $prefetch_urls[$url] = $type;
}

/**
 * Returns the paremiotipus name for display.
 */
function get_paremiotipus_display(string $paremiotipus): string
{
    $value = function_exists('apcu_fetch') ? apcu_fetch($paremiotipus) : false;
    if ($value === false) {
        $pdo = get_db();
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

    assert(is_string($value));

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
    $pdo = get_db();

    $stmt = $pdo->prepare('SELECT PAREMIOTIPUS FROM 00_PAREMIOTIPUS WHERE MODISME = :modisme LIMIT 1');
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
 * @return array<string, string>
 */
function get_redirects(): array
{
    // These redirects are mapped manually based on a Google Search console report.
    return [
        "/obra/Marrugat_Cuy%C3%A0s%2C_Ramon_(2018)%3A_Alguna_cosa_m%C3%A9s_que_l'anar_a_tocar_ferro" => '/obra/Marrugat_Cuyàs%2C_Ramon_%282018%29%3A_«Alguna_cosa_més_que_l%27anar_a_tocar_ferro»._La_fraseologia_tarragonina',
        "/p/Ni_fe_d'enc%C3%A0rrec" => '/p/Ni_fet_d%27enc%C3%A0rrec',
        '/?obra=Badia+i+Pujol%2C+Jordi+%282021%29%3A+Ras+i+curt+-+Fer+un+%E2%80%98polvo%E2%80%99+o+fotre+un+clau%3F%3A+aquesta+%C3%A9s+la+q%C3%BCesti%C3%B3' => '/obra/Badia_i_Pujol%2C_Jordi_%282022%29%3A_Ras_i_curt_-_Deu_refranys_catalans_intradu%C3%AFbles',
        '/?obra=Badia+i+Pujol%2C+Jordi+(2021):+Ras+i+curt+-+Fer+un+%E2%80%98polvo%E2%80%99+o+fotre+un+clau?:+aquesta+%C3%A9s+la+q%C3%BCesti%C3%B3' => '/obra/Badia_i_Pujol%2C_Jordi_%282022%29%3A_Ras_i_curt_-_Deu_refranys_catalans_intradu%C3%AFbles',
        '/?obra=Badia+i+Pujol,+Jordi+(2021):+Ras+i+curt+-+Fer+un+%E2%80%98polvo%E2%80%99+o+fotre+un+clau?:+aquesta+%C3%A9s+la+q%C3%BCesti%C3%B3' => '/obra/Badia_i_Pujol%2C_Jordi_%282022%29%3A_Ras_i_curt_-_Deu_refranys_catalans_intradu%C3%AFbles',
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
        '/?paremiotipus=A+judici+i+pagar%C2%ABlo%C2%BB+judicat' => '/p/A_judici_i_pagar_«lo»_judicat',
        '/?paremiotipus=A+l%27Ascensi%C3%B3+cireretes+abundo+A+Val%C3%A8ncia%E2%80%A6+que+aqu%C3%AD+no' => '/p/Per_l%27Ascensió%2C_cireretes_en_abundor',
        '/?paremiotipus=A+l%27Ascensió+cireretes+abundo+A+València…+que+aquí+no' => '/p/Per_l%27Ascensió%2C_cireretes_en_abundor',
        '/?paremiotipus=A+l%27aire+llure' => '/p/A_l%27aire_lliure',
        '/?paremiotipus=C%C3%A0cilment' => '/p/D%C3%B2cilment',
        '/?paremiotipus=Camina+que+caminara%CC%80s' => '/p/Camina_que_caminar%C3%A0s',
        '/?paremiotipus=Coll+avalll' => '/p/Coll_avall',
        '/?paremiotipus=Com+e+p%C3%A8l+de+la+pasta' => '/p/Com_el_p%C3%A8l_de_la_pasta',
        '/?paremiotipus=Com+un+eix+a+l%27aigua' => '/p/Com_un_peix_a_l%27aigua',
        '/?paremiotipus=Cre%CC%81ixer+com+una+mala+herba' => '/p/Créixer_com_una_mala_herba',
        '/?paremiotipus=Decloure+les+mans' => '/p/Descloure_les_mans',
        '/?paremiotipus=Deixar+a+manlleu' => '/p/Anar_a_manlleu',
        '/?paremiotipus=Diu+el+bou+pel+febrer:+-Prou+aigua%2C+que+se%27m+podreix+la+banya' => '/p/Diu_el_bou_pel_febrer%3A_-_Pluja%2C_pluja%2C_fins_que_se%27m_podreixi_la_cua',
        '/?paremiotipus=Dona+d%27altre+marit%2C+olla+de+caldo+afegitDona+d%27altre+marit%2C+olla+de+caldo+afegit' => '/p/Dona_d%27altre_marit%2C_olla_de_caldo_afegit',
        '/?paremiotipus=El+perol+diu+a+la+paella%3A+si+m%27embrutes%2C+t%27emmascaroSer+el+rei+del+mambo' => '/p/El_perol_diu_a_la_paella%3A_si_m%27embrutes%2C_t%27emmascaro',
        '/?paremiotipus=El+perol+diu+a+la+paella:+si+m%27embrutes%2C+t%27emmascaroSer+el+rei+del+mambo' => '/p/El_perol_diu_a_la_paella%3A_si_m%27embrutes%2C_t%27emmascaro',
        '/?paremiotipus=El+perol+diu+a+la+paella:+si+m%27embrutes,+t%27emmascaroSer+el+rei+del+mambo' => '/p/El_perol_diu_a_la_paella%3A_si_m%27embrutes%2C_t%27emmascaro',
        '/?paremiotipus=En+X%C3%A0bia%2C+desculats%3B+en+Ondara%2C+fanfarrons%3B+en+Benissa%2C+senyorets%2C+i+en+Teulada%2C+boquimollls' => '/p/En_X%C3%A0bia%2C_desculats%3B_en_Ondara%2C_fanfarrons%3B_en_Benissa%2C_senyorets%2C_i_en_Teulada%2C_boquimolls',
        '/?paremiotipus=En+Xàbia,+desculats;+en+Ondara,+fanfarrons;+en+Benissa,+senyorets,+i+en+Teulada,+boquimollls' => '/p/En_X%C3%A0bia%2C_desculats%3B_en_Ondara%2C_fanfarrons%3B_en_Benissa%2C_senyorets%2C_i_en_Teulada%2C_boquimolls',
        '/?paremiotipus=Fer+me%CC%81s+badalls+que+rots' => '/p/Fer_m%C3%A9s_badalls_que_rots',
        '/?paremiotipus=Fer+uin+merder' => '/p/Fer_merder',
        '/?paremiotipus=Fer-li+una+mala+jugada' => '/p/Mala_jugada',
        '/?paremiotipus=Ja+ports+xiular+si+l%27ase+no+vol+beure' => '/p/Ja_pots_xiular_si_l%27ase_no_vol_beure',
        '/?paremiotipus=Jugar-li+una+mala+passada' => '/p/Fer-li_una_mala_passada',
        '/?paremiotipus=Les+xiques+de+Vilella+(o+del+Poretal+d%27Horta%2C+o+de+Guardamar%2C+o+de+Torrevella+o+del+Vilar)%2C+a+la+llum+diuen+%C2%ABcandil%C2%BB%2C+a+la+finestra%2C+%C2%ABventana%C2%BB+i+al+julivert%2C+%C2%ABperegil%C2%BB' => '/p/Sas%2C_Malp%C3%A0s%2C_Peranera_i_Castell%C3%A0s%2C_Sent%C3%ADs%2C_C%C3%A9rvoles_i_Naens%3A_els_set_pobles_m%C3%A9s_dolents._Si_n%27hi_voleu_m%C3%A9s%2C_poseu-hi_Ben%C3%A9s%2C_si_no_n%27hi_ha_prou%2C_poseu-hi_Castellnou._Si_n%27hi_voleu_una_bona_renglera%2C_poseu-hi_tota_la_vall_de_Cabdella',
        '/?paremiotipus=Les+xiques+de+Vilella+o+del+Poretal+d%27Horta%2C+o+de+Guardamar%2C+o+de+Torrevella+o+del+Vilar%2C+a+la+llum+diuen+%C2%ABcandil%C2%BB%2C+a+la+finestra%2C+%C2%ABventana%C2%BB+i+al+julivert%2C+%C2%ABperegil%C2%BB' => '/p/Sas%2C_Malp%C3%A0s%2C_Peranera_i_Castell%C3%A0s%2C_Sent%C3%ADs%2C_C%C3%A9rvoles_i_Naens%3A_els_set_pobles_m%C3%A9s_dolents._Si_n%27hi_voleu_m%C3%A9s%2C_poseu-hi_Ben%C3%A9s%2C_si_no_n%27hi_ha_prou%2C_poseu-hi_Castellnou._Si_n%27hi_voleu_una_bona_renglera%2C_poseu-hi_tota_la_vall_de_Cabdella',
        '/?paremiotipus=Ni+fe+d%27enc%C3%A0rrec' => '/p/Ni_fet_d%27enc%C3%A0rrec',
        '/?paremiotipus=Ofegar-se+en+nu+got+d%27aigua' => '/p/Ofegar-se_en_un_got_d%27aigua',
        '/?paremiotipus=Ognominiosament' => '/p/Ignominiosament',
        '/?paremiotipus=Parar+taula' => '/p/Parar_taula',
        '/?paremiotipus=Passarse-li+l%27arr%C3%B2s' => '/p/Passar-se-li_l%27arr%C3%B2s',
        '/?paremiotipus=Passr+el+rosari' => '/p/Passar_el_rosari',
        '/?paremiotipus=Pel+setembre+o+desembre%2C+qui+tingui+blat%2C+que+en+sembri' => '/p/Pel_setembre%2C_qui_tingui_blat%2C_que_en_sembri',
        '/?paremiotipus=Posr+en+gu%C3%A0rdia' => '/p/Posar_en_guàrdia',
        '/?paremiotipus=Posra+barba' => '/p/Posar_barba',
        '/?paremiotipus=Prendre+a+manlleu' => '/p/Anar_a_manlleu',
        '/?paremiotipus=Quan+la+Murta+s%27emborrasca+i+la+Casella+o+Matamon+fa+capell%2C+llaurador%2C+ves-te%27n+a+casa%2C+pica+espart+i+fes+cordell' => '/p/Pica_espart_i_fes_cordell',
        '/?paremiotipus=Rompre-li+la+crisma' => '/p/Trencar_o_rompre_la_crisma',
        '/?paremiotipus=Romprer-li+el+cap' => '/p/Trencar-li_el_cap',
        '/?paremiotipus=Ser+un+figa+blana' => '/p/Figa_blana',
        '/?paremiotipus=Ser+un+figa+tova' => '/p/Figa_tova',
        '/?paremiotipus=Si+tens+una+filla+que+no+l%27estimis+gaire%2C+casa-la+a+Albons%2C+o+a+Bellcaire%2C+o+sin%C3%83%C2%B3+a+Vilademat%2C+que+ser%C3%83%C2%A0+morta+m%C3%83%C2%A9s+aviat' => '/p/Si_tens_una_filla_que_no_l%27estimis_gaire%2C_casa-la_a_Albons%2C_o_a_Bellcaire%2C_o_sin%C3%B3_a_Vilademat%2C_que_ser%C3%A0_morta_m%C3%A9s_aviat',
        '/?paremiotipus=Terra+on+vas%2C+costum+hi+trobes' => '/p/A_terra_que_vas%2C_usan%C3%A7a_o_costums_que_trobes',
        '/?paremiotipus=Tots+els+mosquits+volen+prendre+tabaco' => '/p/Totes_les_mosques_tenen_tos_i_els_mosquits_prenen_tabac',
        '/?paremiotipus=Treure-hi+la+pols' => '/p/Treure_la_pols',
        '/?paremiotipus=Treureli+la+son' => '/p/Treure-li_la_son',
        '/?paremiotipus=Vaig+anar+a+Constantinoble+i+al+punt+em+varen+constantinoblitzar+tan+b%C3%A9%2C+que+ara+cap+desconstantinoblitzador+no+f%C3%B3ra+poru+per+desconstantinoblitzar-me%2C+encara+que+fos+el+primer+desconstantinoblitzador+de+tots+els+desconstantinoblitzadors+de+Constantinoble' => '/p/Vaig_anar_a_Constantinoble_i_al_punt_em_varen_constantinoblitzar_tan_b%C3%A9%2C_que_ara_cap_desconstantinoblitzador_no_f%C3%B3ra_prou_per_desconstantinoblitzar-me%2C_encara_que_fos_el_primer_desconstantinoblitzador_de_tots_els_desconstantinoblitzadors_de_Constantinoble',
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
        '/obra/Casta%C3%B1eda%2C_Vicente_%281919-20%29%3A_Refranes_valencianos_recopilados_por_el_P._Luis_Galiana%2C_Dominico' => '/obra/Castañeda%2C_Vicente_%281770%29%3A_Refranes_valencianos_recopilados_por_el_P._Luis_Galiana%2C_Dominico%2C_ed._1919-20',
        '/obra/Casta%C3%B1eda%2C_Vicente_(1919-20)%3A_Refranes_valencianos_recopilados_por_el_P._Luis_Galiana%2C_Dominico' => '/obra/Castañeda%2C_Vicente_%281770%29%3A_Refranes_valencianos_recopilados_por_el_P._Luis_Galiana%2C_Dominico%2C_ed._1919-20',
        '/obra/Casta%c3%b1eda%2C_Vicente_(1919-20):_Refranes_valencianos_recopilados_por_el_P._Luis_Galiana%2C_Dominico' => '/obra/Castañeda%2C_Vicente_%281770%29%3A_Refranes_valencianos_recopilados_por_el_P._Luis_Galiana%2C_Dominico%2C_ed._1919-20',
        '/obra/Marrugat_Cuy%C3%A0s%2C_Ramon_%282018%29%3A_Alguna_cosa_m%C3%A9s_que_l%27anar_a_tocar_ferro' => '/obra/Marrugat_Cuyàs%2C_Ramon_%282018%29%3A_«Alguna_cosa_més_que_l%27anar_a_tocar_ferro»._La_fraseologia_tarragonina',
        '/obra/Marrugat_Cuy%c3%a0s%2C_Ramon_(2018):_Alguna_cosa_m%c3%a9s_que_l%27anar_a_tocar_ferro' => '/obra/Marrugat_Cuyàs%2C_Ramon_%282018%29%3A_«Alguna_cosa_més_que_l%27anar_a_tocar_ferro»._La_fraseologia_tarragonina',
        '/obra/Mettmann%2C_Walter_%281989%29%3A_%C2%ABProverbia_arabum%C2%BB_eine_altkatalanische_sprichw%C3%B6rter-Uns_sentenzensammlung' => '/obra/Mettmann%2C_Walter_%281298%29%3A_«Proverbia_arabum»_eine_altkatalanische_sprichwörter-Uns_sentenzensammlung%2C_ed._1989',
        '/obra/Mettmann%2C_Walter_(1989)%3A_%C2%ABProverbia_arabum%C2%BB_eine_altkatalanische_sprichw%C3%B6rter-Uns_sentenzensammlung' => '/obra/Mettmann%2C_Walter_%281298%29%3A_«Proverbia_arabum»_eine_altkatalanische_sprichwörter-Uns_sentenzensammlung%2C_ed._1989',
        '/obra/Mettmann%2C_Walter_(1989):_%c2%abProverbia_arabum%c2%bb_eine_altkatalanische_sprichw%c3%b6rter-Uns_sentenzensammlung' => '/obra/Mettmann%2C_Walter_%281298%29%3A_«Proverbia_arabum»_eine_altkatalanische_sprichwörter-Uns_sentenzensammlung%2C_ed._1989',
        '/obra/Mettmann,_Walter_(1989):_%C2%ABProverbia_arabum%C2%BB_eine_altkatalanische_sprichw%C3%B6rter-Uns_sentenzensammlung' => '/obra/Mettmann%2C_Walter_%281298%29%3A_«Proverbia_arabum»_eine_altkatalanische_sprichwörter-Uns_sentenzensammlung%2C_ed._1989',
        '/p/A_judici_i_pagar%C2%ABlo%C2%BB_judicat' => '/p/A_judici_i_pagar_«lo»_judicat',
        '/p/Com_un_eix_a_l%27aigua' => '/p/Com_un_peix_a_l%27aigua',
        '/p/Fer_uin_merder' => '/p/Fer_merder',
        '/p/Ni_fe_d%27enc%C3%A0rrec' => '/p/Ni_fet_d%27enc%C3%A0rrec',
        '/p/Ni_fe_d%27enc%c3%a0rrec' => '/p/Ni_fet_d%27enc%C3%A0rrec',
        '/p/Pel_setembre_o_desembre%2C_qui_tingui_blat%2C_que_en_sembri' => '/p/Pel_setembre%2C_qui_tingui_blat%2C_que_en_sembri',
        '/p/Posr_en_gu%C3%A0rdia' => '/p/Posar_en_guàrdia',
        '/p/Posra_barba' => '/p/Posar_barba',
        '/p/Qui_dolent_fou_a_Tortosa%2C_dolent_ser%C3%A0_a_TolosaQui_dolent_fou_a_Tortosa%2C_dolent_ser%C3%A0_a_Tolosa' => '/p/Qui_dolent_fou_a_Tortosa%2C_dolent_serà_a_Tolosa',
        '/p/Rompre-li_la_crisma' => '/p/Trencar_o_rompre_la_crisma',
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
    $pdo = get_db();

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

    $paremiotipus = $paremiotipus !== false ? $paremiotipus : '';
    assert(is_string($paremiotipus));

    return $paremiotipus;
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
    $pdo = get_db();

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
    $pdo = get_db();

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
    $pdo = get_db();

    $stmt = $pdo->prepare('SELECT `file` FROM `commonvoice` WHERE `paremiotipus` = :paremiotipus');
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
 * @phan-return false|array
 */
function get_obra(string $obra_title): bool|array
{
    $pdo = get_db();

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

    /**
     * @phpstan-var false|array{
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
     */
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Returns the number of paremiotipus for a specific font.
 */
function get_paremiotipus_count_by_font(string $font_id): int
{
    $pdo = get_db();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM 00_PAREMIOTIPUS WHERE ID_FONT = :id');
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
        set_prefetch_url(get_pager_url($page_num + 1), 'document');
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
    $pdo = get_db();

    $idiomes = function_exists('apcu_fetch') ? apcu_fetch('idiomes') : false;
    if ($idiomes === false) {
        $stmt = $pdo->query('SELECT CODI, IDIOMA FROM 00_EQUIVALENTS');
        $idiomes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        if (function_exists('apcu_store')) {
            apcu_store('idiomes', $idiomes);
        }
    }

    /** @var array<string, string> $idiomes */
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
 * @return list<string>
 */
function build_search_query(string $search, string $search_mode, string &$where_clause): array
{
    $pdo = get_db();

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
    $pdo = get_db();

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
    $pdo = get_db();

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
        $string = mb_strtolower($string);
        $string = str_replace('’', "'", $string);
        $string = str_replace(['à', 'á'], 'a', $string);
        $string = str_replace(['è', 'é'], 'e', $string);
        $string = str_replace(['í', 'ï'], 'i', $string);
        $string = str_replace(['ò', 'ó'], 'o', $string);
        $string = str_replace(['ú', 'ü'], 'u', $string);

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
                $string = str_replace(' - ', ' ', $string);

                // Nice to have: remove extra useless characters.
                $string = str_replace(['“', '”', '«', '»', '…', ',', ':', ';', '!', '¡', '¿'], '', $string);

                // Build the full-text query.
                $words = preg_split('/\\s+/', $string);

                /** @var list<string> $words */
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
    $pdo = get_db();

    $editorials = function_exists('apcu_fetch') ? apcu_fetch('nom_editorials') : false;
    if ($editorials === false) {
        $stmt = $pdo->query('SELECT CODI, NOM FROM 00_EDITORIA');
        $editorials = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        if (function_exists('apcu_store')) {
            apcu_store('nom_editorials', $editorials);
        }
    }

    /** @var array<string, string> $editorials */
    return $editorials;
}

/**
 * Returns array of 00_FONTS `Títol` values keyed by `Identificador`.
 *
 * @return array<string, string>
 */
function get_fonts(): array
{
    $pdo = get_db();

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

    /** @var array<string, string> $fonts */
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
    $pdo = get_db();

    $n_modismes = function_exists('apcu_fetch') ? apcu_fetch('n_modismes') : false;
    if ($n_modismes === false) {
        $stmt = $pdo->query('SELECT COUNT(*) FROM 00_PAREMIOTIPUS');
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
    $pdo = get_db();

    $n_paremiotipus = function_exists('apcu_fetch') ? apcu_fetch('n_paremiotipus') : false;
    if ($n_paremiotipus === false) {
        $stmt = $pdo->query('SELECT COUNT(DISTINCT PAREMIOTIPUS) FROM 00_PAREMIOTIPUS');
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
    $pdo = get_db();

    $n_fonts = function_exists('apcu_fetch') ? apcu_fetch('n_fonts') : false;
    if ($n_fonts === false) {
        $stmt = $pdo->query('SELECT COUNT(DISTINCT AUTOR, ANY, EDITORIAL) FROM 00_PAREMIOTIPUS');
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
 * @return list<string>
 */
function get_top100_paremiotipus(): array
{
    $pdo = get_db();

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
        $top_paremiotipus = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        if (function_exists('apcu_store')) {
            apcu_store('top_paremiotipus', $top_paremiotipus);
        }
    }

    /** @var list<string> $top_paremiotipus */
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
    $pdo = get_db();

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
    $pdo = get_db();

    // As this query has a limited number of results but runs many times, cache it in memory.
    /** @phpstan-var false|list<array{Imatge: string, Títol: string, URL: string, WIDTH: int, HEIGHT: int}> $books */
    $books = function_exists('apcu_fetch') ? apcu_fetch('books') : false;
    if ($books === false) {
        $stmt = $pdo->query('SELECT Imatge, `Títol`, URL, WIDTH, HEIGHT FROM `00_OBRESVPR`');

        /** @phpstan-var list<array{Imatge: string, Títol: string, URL: string, WIDTH: int, HEIGHT: int}> $books */
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (function_exists('apcu_store')) {
            apcu_store('books', $books);
        }
    }

    $random_key = array_rand($books);

    return $books[$random_key];
}

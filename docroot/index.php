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

require __DIR__ . '/../src/common.php';

// Redirect to the homepage any request where the URL contains 'index.php'.
if (str_contains(get_request_uri(), 'index.php')) {
    header('Location: /');

    exit;
}

check_db_or_exit();

header('Cache-Control: public, s-maxage=31536000, max-age=900');

// Build page content in advance.
$page_name = get_page_name();
$main_content = build_main_content($page_name);

?><!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <title><?php echo format_html_title(get_page_title(), 'PCCD'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2b5797">
    <meta property="og:title" content="<?php echo format_html_title(get_page_title()); ?>">
    <meta property="og:site_name" content="Paremiologia catalana comparada digital">
    <?php echo get_page_meta_tags($page_name); ?>
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="search" type="application/opensearchdescription+xml" href="/opensearch.xml" title="PCCD">
    <style>
<?php
require __DIR__ . '/css/base.min.css';

// If the page has page-specific CSS, include it.
/** @psalm-suppress UnresolvableInclude */
@include __DIR__ . "/css/{$page_name}.min.css";
?></style>
</head>
<body>
    <header>
        <div class="container-md">
            <a href="/" class="brand"><span class="brand-text">Paremiologia catalana comparada digital</span><span class="brand-text-xs">PCCD</span></a>
            <button id="nav-toggle" type="button" aria-label="Desplega el menú">
                <svg aria-hidden="true" viewBox="0 0 16 16">
                    <path fill="#fff" d="M3 9.5a1.5 1.5 0 1 1 0-3a1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3a1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3a1.5 1.5 0 0 1 0 3z"/>
                </svg>
            </button>
            <div class="d-none" id="menu">
                <nav>
                    <a href="/projecte">Projecte</a>
                    <a href="/" title="Ctrl + /">Cerca</a>
                    <a href="/instruccions">Instruccions d'ús</a>
                    <a href="/credits">Crèdits</a>
                </nav>
            </div>
        </div>
    </header>
    <main class="container-md">
        <div class="row">
            <section class="col-main"<?php echo $page_name === 'search' ? ' data-nosnippet' : ''; ?>>
                <article>
                    <h1><?php echo get_page_title(); ?></h1>
                    <?php echo $main_content; ?>
                </article>
            </section>
            <aside class="col-aside">
                <?php echo get_side_blocks($page_name); ?>
                <div class="bloc bloc-contact bloc-white">
                    <p>Ajudeu-nos a millorar</p>
                    <p><a href="mailto:vpamies@gmail.com?subject=PCCD"><img loading="lazy" alt="Contacteu-nos" width="80" height="44" src="/img/cargol.svg"></a></p>
                </div>
            </aside>
        </div>
    </main>
    <footer>
        <p><?php echo format_nombre(get_n_modismes()); ?> fitxes, corresponents a <?php echo format_nombre(get_n_paremiotipus()); ?> paremiotipus, de <?php echo format_nombre(get_n_fonts()); ?> fonts. Última actualització: <?php require __DIR__ . '/../tmp/db_date.txt'; ?></p>
        <p>© Víctor Pàmies i Riudor, 2020-2023.</p>
    </footer>
    <div id="snack" class="d-none">
        <div class="snack-inner" role="alert">
            <div class="snack-message">Aquest lloc web fa servir galetes de Google per analitzar el trànsit.</div>
            <button type="button">D'acord</button>
        </div>
    </div>
    <script async src="/js/script.min.js?v=5"></script>
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-CP42Y3NK1R"></script>
</body>
</html>

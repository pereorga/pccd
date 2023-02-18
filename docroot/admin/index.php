<?php

/**
 * This file is part of PCCD.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

declare(strict_types=1);

// Admin section.

require __DIR__ . '/../../src/third_party/urlLinker.php';

require __DIR__ . '/../../src/common.php';

require __DIR__ . '/../../src/db_settings.php';

ini_set('memory_limit', '512M');
set_time_limit(0);
session_start();

global $pdo;

header('X-Robots-Tag: noindex', true);

if (isset($_POST['password']) && $_POST['password'] === getenv('WEB_ADMIN_PASSWORD')) {
    $_SESSION['auth'] = true;
    header('Location: /admin/');

    exit;
}

if (isset($_SESSION['auth'])) {
    if (isset($_GET['phpinfo'])) {
        phpinfo();

        exit;
    }

    if (isset($_GET['apc']) || isset($_GET['SCOPE'])) {
        require __DIR__ . '/../../src/third_party/apc.php';

        exit;
    }

    if (isset($_GET['opcache'])) {
        require __DIR__ . '/../../src/third_party/opcache-gui.php';

        exit;
    }

    if (isset($_GET['spx'])) {
        header('Location: /?SPX_KEY=dev&SPX_UI_URI=/');

        exit;
    }

    if (isset($_GET['xhprof'])) {
        header('Location: /admin/xhprof/');

        exit;
    }

    if (isset($_GET['tideways_xhprof'])) {
        header('Location: /admin/xhprof/');

        exit;
    }
}

$deployment_date = '';
$timestamp = filemtime(__DIR__ . '/../index.php');
if ($timestamp !== false) {
    $deployment_date = date('Y/m/d H:i:s', $timestamp);
}
?><!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="theme-color" content="#495057">
    <title>Panell d'administració - Paremiologia catalana comparada digital</title>
    <style>
        body{font-family:system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Oxygen,Ubuntu,Cantarell,Fira Sans,Droid Sans,Helvetica Neue,sans-serif;line-height:1.4;margin:20px;padding:0 10px;color:#dbdbdb;background:#202b38;text-rendering:optimizeLegibility}button,input,textarea{transition:background-color .1s linear,border-color .1s linear,color .1s linear,box-shadow .1s linear,transform .1s ease}h1{font-size:2.2em;margin-top:0}h1,h2,h3,h4,h5,h6{margin-bottom:12px}h1,h2,h3,h4,h5,h6,strong{color:#fff}b,h1,h2,h3,h4,h5,h6,strong,th{font-weight:600}button,input[type=button],input[type=checkbox],input[type=submit]{cursor:pointer}input:not([type=checkbox]),select{display:block}button,input,select,textarea{color:#fff;background-color:#161f27;font-family:inherit;font-size:inherit;margin-right:6px;margin-bottom:6px;padding:10px;border:none;border-radius:6px;outline:none}button,input:not([type=checkbox]),select,textarea{-webkit-appearance:none}textarea{margin-right:0;width:100%;box-sizing:border-box;resize:vertical}button,input[type=button],input[type=submit]{padding-right:30px;padding-left:30px}button:hover,input[type=button]:hover,input[type=submit]:hover{background:#324759}button:focus,input:focus,select:focus,textarea:focus{box-shadow:0 0 0 2px rgba(0,150,191,.67)}button:active,input[type=button]:active,input[type=checkbox]:active,input[type=submit]:active{transform:translateY(2px)}input:disabled{cursor:not-allowed;opacity:.5}::-webkit-input-placeholder{color:#a9a9a9}:-ms-input-placeholder{color:#a9a9a9}::-ms-input-placeholder{color:#a9a9a9}::placeholder{color:#a9a9a9}a{text-decoration:none;color:#41adff}a:hover{text-decoration:underline}code,kbd{background:#161f27;color:#ffbe85;padding:5px;border-radius:6px}pre>code{padding:10px;display:block;overflow-x:auto}img{max-width:100%}hr{border:none;border-top:1px solid #dbdbdb}table{border-collapse:collapse;margin-bottom:10px;width:100%}td,th{padding:6px;text-align:left}th{border-bottom:1px solid #dbdbdb}tbody tr:nth-child(2n){background-color:#161f27}::-webkit-scrollbar{height:10px;width:10px}::-webkit-scrollbar-track{background:#161f27;border-radius:6px}::-webkit-scrollbar-thumb{background:#324759;border-radius:6px}::-webkit-scrollbar-thumb:hover{background:#415c73}p{margin:2em 0;}pre{overflow:auto;}
    </style>
</head>
<body>
    <h2>Panell d'administració<?php echo isset($_GET['test']) ? ' - informes' : ''; ?></h2>
    <hr>
<?php if (!isset($_SESSION['auth'])) { ?>
    <form method="post">
        <label for="password">Contrasenya:</label>
        <input type="password" id="password" name="password" autofocus>
        <input type="submit" value="Inicia sessió">
    </form>
    <?php
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    echo "<script>window.location.href = '/';</script>";
    echo '<p>Podeu visitar la pàgina principal de la PCCD a <a href=//pccd.dites.cat>https://pccd.dites.cat</a>.';

    exit;
}

if (isset($_GET['test'])) {
    require __DIR__ . '/../../src/reports/tests.php';

    session_write_close();

    if ($_GET['test'] === 'imatges') {
        test_imatges_paremiotipus();
        test_imatges_buides();
        test_imatges_no_referenciades();
        test_imatges_no_reconegudes();
        test_imatges_extensio();
        test_imatges_no_existents();
        test_imatges_repetides();
        test_imatges_format();
    } elseif ($_GET['test'] === 'obres') {
        test_obres_sense_paremia();
        test_paremies_sense_obra_existent();
    } elseif ($_GET['test'] === 'urls') {
        test_urls();
    } elseif ($_GET['test'] === 'espais') {
        test_espais();
    } elseif ($_GET['test'] === 'puntuació') {
        test_puntuacio();
    } elseif ($_GET['test'] === 'majúscules') {
        test_majuscules();
    } elseif ($_GET['test'] === 'equivalents') {
        test_equivalents();
    } elseif ($_GET['test'] === 'explicacions') {
        test_explicacio();
    } elseif ($_GET['test'] === 'lloc') {
        test_llocs();
    } elseif ($_GET['test'] === 'repeticions') {
        test_paremiotipus_accents();
        test_paremiotipus_modismes_diferents();
        test_paremiotipus_repetits();
    } elseif ($_GET['test'] === 'repeticions_caracters') {
        test_repeticio_caracters();
    } elseif ($_GET['test'] === 'longitud') {
        test_buits();
        test_paremiotipus_modismes_curts();
        test_paremiotipus_llargs();
    } elseif ($_GET['test'] === 'editorials') {
        test_obres_sense_editorial();
        test_editorial_sense_obres();
    } elseif ($_GET['test'] === 'fonts') {
        test_fonts();
    } elseif ($_GET['test'] === 'compostos') {
        test_paremies_separar();
    } elseif ($_GET['test'] === 'commonvoice_languagetool') {
        test_commonvoice_languagetool();
    } elseif ($_GET['test'] === 'cerques') {
        test_searches();
    }

    echo "<p>[<a href='/admin'>Torna endarrere</a>]</p>";
    echo '</body>';
    echo '</html>';

    exit;
}
?>
Informes:
<ul>
    <li><a href="?test=cerques">Cerques</a></li>
    <li><a href="?test=commonvoice_languagetool">Common Voice / LanguageTool</a></li>
    <li><a href="?test=compostos">Compostos</a></li>
    <li><a href="?test=editorials">Editorials</a></li>
    <li><a href="?test=equivalents">Equivalents</a></li>
    <li><a href="?test=espais">Espais</a></li>
    <li><a href="?test=explicacions">Explicacions</a></li>
    <li><a href="?test=fonts">Fonts</a></li>
    <li><a href="?test=imatges">Imatges</a></li>
    <li><a href="?test=longitud">Longitud</a></li>
    <li><a href="?test=majúscules">Majúscules</a></li>
    <li><a href="?test=obres">Obres</a></li>
    <li><a href="?test=puntuació">Puntuació</a></li>
    <li><a href="?test=repeticions_caracters">Repeticions de caràcters</a></li>
    <li><a href="?test=repeticions">Repeticions de paremiotipus</a></li>
    <li><a href="?test=urls">URLs</a></li>
</ul>
Monitorització:
<ul>
    <?php echo function_exists('apcu_enabled') && apcu_enabled() ? '<li><a href="?apc">APCu</a></li>' : '<li><b>Atenció</b>: APCu no està habilitat, el rendiment es veurà afectat significativament.</li>'; ?>
    <?php echo function_exists('opcache_get_status') && is_array(opcache_get_status()) ? '<li><a href="?opcache">OPcache</a></li>' : '<li><b>Atenció</b>: OPcache no està habilitat, el rendiment es veurà afectat significativament.</li>'; ?>
    <?php echo function_exists('phpinfo') ? '<li><a href="?phpinfo">phpinfo</a></li>' : ''; ?>
    <?php echo function_exists('spx_profiler_start') ? '<li><a href="?spx">SPX</a></li>' : ''; ?>
    <?php echo function_exists('xhprof_enable') ? '<li><a href="?xhprof">XHProf</a></li>' : ''; ?>
    <?php echo function_exists('tideways_xhprof_enable') ? '<li><a href="?tideways_xhprof">Tideways XHProf</a></li>' : ''; ?>
</ul>
<p>[<a href='?logout'>Tanca la sessió</a>]</p>
<small>
    Última base de dades: <?php require __DIR__ . '/../../tmp/date.txt'; ?>. Últim desplegament: <?php echo $deployment_date; ?>
    <br><?php echo 'PHP ' . PHP_VERSION . ' on ' . apache_get_version() . ' (' . PHP_OS . '). BD ' . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION); ?>
</small>
</body>
</html>

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

set_page_title('Les 100 parèmies més citades');
set_meta_description('Llista de les frases més citades.');

$records = get_top100_paremiotipus();
echo '<ol>';
foreach ($records as $r) {
    echo '<li><a href="' . get_paremiotipus_url($r) . '">' . get_paremiotipus_display($r) . '</a></li>';
}
echo '</ol>';

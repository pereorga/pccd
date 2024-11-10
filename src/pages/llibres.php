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

set_page_title('Llibres de Víctor Pàmies');
set_meta_description("Llibres de l'autor de la Paremiologia catalana comparada digital (PCCD).");

$books = cache_get('obresvpr', static function (): array {
    $stmt = get_db()->query('SELECT `Imatge`, `Títol`, `URL`, `WIDTH`, `HEIGHT` FROM `00_OBRESVPR`');

    return $stmt->fetchAll(PDO::FETCH_CLASS, Book::class);
});
echo '<div class="books">';
foreach ($books as $book) {
    echo $book->render(['lazy_loading' => false]);
}
echo '</div>';

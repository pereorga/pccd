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

/*
 * Reports a list of small images.
 *
 * This script is called by npm generate:reports script.
 */

require __DIR__ . '/functions.php';

echo background_test_small_image(__DIR__ . '/../../src/images/cobertes');
echo background_test_small_image(__DIR__ . '/../../src/images/paremies');

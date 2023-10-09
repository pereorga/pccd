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

/*
 * Reports a list of image files with unsupported extensions.
 *
 * This script is called by yarn generate:reports script.
 */

require __DIR__ . '/functions.php';

echo background_test_unsupported_extensions(__DIR__ . '/../../src/images/cobertes');
echo background_test_unsupported_extensions(__DIR__ . '/../../src/images/paremies');

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

const IMAGE_WIDTH = 500;

/*
 * Resizes and converts images to optimized formats.
 *
 * This script should be executed when new images are provided.
 */

require __DIR__ . '/functions.php';

resize_and_optimize_images_bulk(__DIR__ . '/../../src/images/paremies', __DIR__ . '/../../docroot/img/imatges', IMAGE_WIDTH);
resize_and_optimize_images_bulk(__DIR__ . '/../../src/images/cobertes', __DIR__ . '/../../docroot/img/obres', IMAGE_WIDTH);

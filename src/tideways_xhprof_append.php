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

// Code to append when using Tideways XHProf in dev environments.

file_put_contents(
    sys_get_temp_dir() . \DIRECTORY_SEPARATOR . uniqid() . '.myapplication.xhprof',
    serialize(tideways_xhprof_disable())
);

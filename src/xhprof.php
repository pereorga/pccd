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
 * Code to prepend when using XHProf in dev environments.
 *
 * This is not working with the latest PHP versions on ARM, use Tideways XHProf or SPX instead (see Dockerfile).
 */

xhprof_enable();
register_shutdown_function(
    function (): void {
        file_put_contents('/tmp/' . uniqid() . '.ApplicationName.xhprof', serialize(xhprof_disable()));
    },
);

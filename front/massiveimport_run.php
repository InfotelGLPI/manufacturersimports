<?php

/*
 -------------------------------------------------------------------------
 manufacturersimports plugin for GLPI
 Copyright (C) 2015-2026 by the manufacturersimports Development Team.

 https://github.com/InfotelGLPI/manufacturersimports
 -------------------------------------------------------------------------

 LICENSE

 This file is part of manufacturersimports.

 manufacturersimports is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 manufacturersimports is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with manufacturersimports. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

use GlpiPlugin\Manufacturersimports\Config;
use GlpiPlugin\Manufacturersimports\PostImport;
use Glpi\Progress\ProgressStorage;
use Symfony\Component\HttpFoundation\StreamedResponse;

if (!defined('GLPI_ROOT')) {
    die("Can not access directly to this file");
}

Session::checkLoginUser();
(new Config())->checkGlobal(UPDATE);

if (
    !isset($_POST['item'])
    || !is_array($_POST['item'])
    || !count(array_filter($_POST['item'], fn($v) => $v == 1))
    || !isset($_POST['manufacturers_id'])
) {
    http_response_code(400);
    exit;
}

Toolbox::safeIniSet('max_execution_time', '300');
session_write_close();

$storage  = new ProgressStorage();
$progress = $storage->spawnProgressIndicator();

return new StreamedResponse(
    function () use ($progress) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        echo $progress->getStorageKey();
        flush();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        ignore_user_abort(true);

        try {
            PostImport::massiveimportWithProgress($_POST, $progress);
            $progress->finish();
        } catch (Throwable $e) {
            Toolbox::logInfo($e->getMessage());
            $progress->fail();
        }
    },
    headers: [
        'Content-Type'   => 'text/html',
        'Content-Length' => strlen($progress->getStorageKey()),
        'Cache-Control'  => 'no-cache,no-store',
        'Pragma'         => 'no-cache',
        'Connection'     => 'close',
    ]
);

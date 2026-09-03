<?php

/**
 * -------------------------------------------------------------------------
 * manufacturersimports plugin for GLPI
 * Copyright (C) 2015-2026 by the manufacturersimports Development Team.
 *
 * https://github.com/InfotelGLPI/manufacturersimports
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of manufacturersimports.
 *
 * manufacturersimports is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * manufacturersimports is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with manufacturersimports. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

use GlpiPlugin\Manufacturersimports\Config;

Session::checkRight("plugin_manufacturersimports", UPDATE);

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

// Every key below is read unconditionally, so all of them must be present: the
// former `||` let a request carrying only `id` fall through and read undefined
// keys. Only the two actions this dropdown drives, on an itemtype the plugin
// handles, produce any output.
$action   = $_POST["action"] ?? '';
$itemtype = $_POST["itemtype"] ?? '';

if (
    isset($_POST["id"])
    && in_array($action, ['import', 'reinit_once'], true)
    && in_array($itemtype, Config::getTypes(true), true)
) {
    echo Html::hidden('action', ['value' => $action]);
    echo Html::hidden('id', ['value' => (int) $_POST["id"]]);
    echo Html::hidden('itemtype', ['value' => $itemtype]);
    echo Html::hidden('start', ['value' => (int) ($_POST["start"] ?? 0)]);
    echo Html::hidden('manufacturers_id', ['value' => (int) ($_POST["manufacturers_id"] ?? 0)]);
    echo Html::hidden('imported', ['value' => (int) ($_POST["imported"] ?? 0)]);
    echo Html::submit(_sx('button', 'Post'), ['name' => 'massiveaction', 'class' => 'btn btn-primary']);
}

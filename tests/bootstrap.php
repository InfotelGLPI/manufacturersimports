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

define('GLPI_ROOT', dirname(__DIR__, 3));

$loader = require GLPI_ROOT . '/vendor/autoload.php';

$loader->addPsr4('GlpiPlugin\\Manufacturersimports\\', dirname(__DIR__) . '/src/');
$loader->addPsr4('GlpiPlugin\\Manufacturersimports\\Manufacturers\\', dirname(__DIR__) . '/src/Manufacturers/');
$loader->addPsr4('GlpiPlugin\\Manufacturersimports\\Tests\\', dirname(__DIR__) . '/tests/');

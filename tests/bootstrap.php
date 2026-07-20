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
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 manufacturersimports is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with manufacturersimports. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(__DIR__, 3));
}

// Config encrypts its secrets through GLPIKey, whose constructor resolves the
// crypt key from GLPI_CONFIG_DIR. The unit suite runs without a GLPI install
// (no database, no generated key), so provide a throwaway config directory
// holding a valid 32-byte sodium key: encryption then works fully offline.
if (!defined('GLPI_CONFIG_DIR')) {
    $config_dir = sys_get_temp_dir() . '/manufacturersimports-tests-config';
    if (!is_dir($config_dir)) {
        mkdir($config_dir, 0o777, true);
    }
    $keyfile = $config_dir . '/glpicrypt.key';
    if (!file_exists($keyfile)) {
        file_put_contents(
            $keyfile,
            random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES)
        );
    }
    define('GLPI_CONFIG_DIR', $config_dir);
}

$loader = require GLPI_ROOT . '/vendor/autoload.php';

$loader->addPsr4('GlpiPlugin\\Manufacturersimports\\', dirname(__DIR__) . '/src/');
$loader->addPsr4('GlpiPlugin\\Manufacturersimports\\Manufacturers\\', dirname(__DIR__) . '/src/Manufacturers/');
$loader->addPsr4('GlpiPlugin\\Manufacturersimports\\Tests\\', dirname(__DIR__) . '/tests/');

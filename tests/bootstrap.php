<?php

define('GLPI_ROOT', dirname(__DIR__, 3));

$loader = require GLPI_ROOT . '/vendor/autoload.php';

$loader->addPsr4('GlpiPlugin\\Manufacturersimports\\', dirname(__DIR__) . '/src/');
$loader->addPsr4('GlpiPlugin\\Manufacturersimports\\Tests\\', dirname(__DIR__) . '/tests/');

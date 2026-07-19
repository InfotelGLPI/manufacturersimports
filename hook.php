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

use GlpiPlugin\Manufacturersimports\Config;
use GlpiPlugin\Manufacturersimports\Profile;
use GlpiPlugin\Manufacturersimports\Menu;
use GlpiPlugin\Manufacturersimports\Import;
use GlpiPlugin\Manufacturersimports\Manufacturers\Dell;

function plugin_manufacturersimports_install()
{
    global $DB;

    $migration = new Migration(PLUGIN_MANUFACTURERSIMPORTS_VERSION);
    $update    = false;

    //Root of SQL files for DB installation or upgrade
    $sql_root = PLUGIN_MANUFACTURERSIMPORTS_DIR . "/sql/";

    if (!$DB->tableExists("glpi_plugin_manufacturersimports_configs")) {
        $DB->runFile($sql_root . "/empty-3.0.6.sql");
    } elseif ($DB->tableExists("glpi_plugin_suppliertag_config")
               && !$DB->fieldExists("glpi_plugin_suppliertag_config", "FK_entities")) {
        $update = true;
        $DB->runFile($sql_root . "/update-1.1.sql");
        $DB->runFile($sql_root . "/update-1.2.0.sql");
        $DB->runFile($sql_root . "/update-1.3.0.sql");
        $DB->runFile($sql_root . "/update-1.4.1.sql");
        $DB->runFile($sql_root . "/update-1.5.0.sql");
        $DB->runFile($sql_root . "/update-1.7.0.sql");
    } elseif ($DB->tableExists("glpi_plugin_suppliertag_profiles")
               && $DB->fieldExists("glpi_plugin_suppliertag_profiles", "interface")) {
        $update = true;
        $DB->runFile($sql_root . "/update-1.2.0.sql");
        $DB->runFile($sql_root . "/update-1.3.0.sql");
        $DB->runFile($sql_root . "/update-1.4.1.sql");
        $DB->runFile($sql_root . "/update-1.5.0.sql");
        $DB->runFile($sql_root . "/update-1.7.0.sql");
    } elseif (!$DB->tableExists("glpi_plugin_manufacturersimports_profiles")
               && !$DB->fieldExists("glpi_plugin_manufacturersimports_configs", "supplier_key")) {
        $update = true;
        $DB->runFile($sql_root . "/update-1.3.0.sql");
        $DB->runFile($sql_root . "/update-1.4.1.sql");
        $DB->runFile($sql_root . "/update-1.5.0.sql");
        $DB->runFile($sql_root . "/update-1.7.0.sql");
    } elseif (!$DB->fieldExists("glpi_plugin_manufacturersimports_configs", "supplier_key")) {
        $DB->runFile($sql_root . "/update-1.7.0.sql");
    } elseif (!$DB->fieldExists("glpi_plugin_manufacturersimports_configs", "supplier_secret")) {
        $DB->runFile($sql_root . "/update-2.1.0.sql");
    }
    if (!$DB->fieldExists("glpi_plugin_manufacturersimports_configs", "warranty_url")) {
        $DB->runFile($sql_root . "/update-2.1.3.sql");
    }
    if ($DB->tableExists("glpi_plugin_manufacturersimports_models")) {
        $DB->runFile($sql_root . "/update-3.0.6.sql");
    }

    // One-shot migration: encrypt any pre-existing plaintext secrets. Idempotent —
    // rows already encrypted carry the Config::SECRET_PREFIX marker and are skipped.
    if ($DB->fieldExists("glpi_plugin_manufacturersimports_configs", "supplier_secret")) {
        foreach ($DB->request(['FROM' => 'glpi_plugin_manufacturersimports_configs']) as $row) {
            $changes = [];
            foreach (['supplier_key', 'supplier_secret'] as $secret_field) {
                $value = (string) ($row[$secret_field] ?? '');
                if ($value !== '' && !str_starts_with($value, 'crypt:')) {
                    $changes[$secret_field] = Config::encryptSecret($value);
                }
            }
            if (!empty($changes)) {
                $DB->update(
                    'glpi_plugin_manufacturersimports_configs',
                    $changes,
                    ['id' => (int) $row['id']]
                );
            }
        }
    }

    $DB->update('glpi_plugin_manufacturersimports_configs', [
        'Supplier_url' => 'https://www.dell.com/support/home/product-support/servicetag/',
        'warranty_url' => 'https://apigtwb2c.us.dell.com/PROD/sbil/eapi/v5/asset-entitlements?servicetags=',
        'token_url'    => 'https://apigtwb2c.us.dell.com/auth/oauth/v2/token',
    ], ['name' => Config::DELL]);

    $DB->update('glpi_plugin_manufacturersimports_configs', [
        'Supplier_url' => 'https://www.lenovo.com/us/en/warrantyApos?serialNumber=',
    ], ['name' => Config::LENOVO]);

    $DB->update('glpi_plugin_manufacturersimports_configs', [
        'Supplier_url' => 'http://support.ts.fujitsu.com/Warranty/WarrantyStatus.asp?lng=com&IDNR',
    ], ['name' => Config::FUJITSU]);

    $DB->update('glpi_plugin_manufacturersimports_configs', [
        'Supplier_url' => 'https://www.wortmann.de/fr-fr/profile/snsearch.aspx?SN=',
    ], ['name' => Config::WORTMANN_AG]);

    $DB->update('glpi_plugin_manufacturersimports_configs', [
        'Supplier_url' => 'https://support.hp.com/fr-fr/check-warranty/',
        'warranty_url' => 'https://warranty.api.hp.com/productwarranty/v2/queries',
        'token_url'    => 'https://warranty.api.hp.com/oauth/v1/token',
    ], ['name' => Config::HP]);


    $DB->runFile($sql_root . "/update-3.1.2.sql");

    $cron = new CronTask;
    if ($cron->getFromDBbyName(Dell::class, 'DataRecoveryDELL')) {
        CronTask::Unregister('ManufacturersimportsDell');
    }

    $cron = new CronTask();
    if (!$cron->getFromDBbyName(Import::class, 'DataWarrantyImport')) {
        CronTask::Register(Import::class, 'DataWarrantyImport', WEEK_TIMESTAMP, ['state' => CronTask::STATE_DISABLE]);
    }


    if ($update) {
        foreach ($DB->request('glpi_plugin_manufacturersimports_profiles') as $data) {
            $DB->update(
                'glpi_plugin_manufacturersimports_profiles',
                ['profiles_id' => (int) $data["id"]],
                ['id' => (int) $data["id"]]
            );
        }

        $migration->dropField('glpi_plugin_manufacturersimports_profiles', 'name');
    }

    //Migrate profiles to the system introduced in 0.85
    Profile::initProfile();
    Profile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);
    //Drop old profile table : not used anymore
    $migration->dropTable('glpi_plugin_manufacturersimports_profiles');

    return true;
}

function plugin_manufacturersimports_uninstall()
{

    $migration = new Migration(PLUGIN_MANUFACTURERSIMPORTS_VERSION);
    $tables    = ["glpi_plugin_manufacturersimports_configs",
                       "glpi_plugin_manufacturersimports_logs"];
    foreach ($tables as $table) {
        $migration->dropTable($table);
    }

    //old versions
    $tables = ["glpi_plugin_suppliertag_config",
                    "glpi_plugin_suppliertag_profiles",
                     "glpi_plugin_manufacturersimports_models",
                    "glpi_plugin_suppliertag_models",
                    "glpi_plugin_suppliertag_imported"];
    foreach ($tables as $table) {
        $migration->dropTable($table);
    }

    $cron = new CronTask;
    if ($cron->getFromDBbyName(Dell::class, 'DataRecoveryDELL')) {
        CronTask::Unregister('ManufacturersimportsDell');
    }
    if ($cron->getFromDBbyName(Import::class, 'DataWarrantyImport')) {
        CronTask::Unregister('ManufacturersimportsImport');
    }

    $profileRight = new ProfileRight();

    foreach (Profile::getAllRights() as $right) {
        $profileRight->deleteByCriteria(['name' => $right['field']]);
    }

    //Remove rigth from $_SESSION['glpiactiveprofile'] if exists
    Profile::removeRightsFromSession();

    //Remove entries in GLPI's menu and breadcrumb
    Menu::removeRightsFromSession();
    return true;
}

function plugin_manufacturersimports_postinit()
{
    foreach (Config::getTypes(true) as $type) {
        CommonGLPI::registerStandardTab($type, Config::class);
    }
}

// Define dropdown relations
function plugin_manufacturersimports_getDatabaseRelations()
{
    if (Plugin::isPluginActive("manufacturersimports")) {
        return ["glpi_entities"
                     => ["glpi_plugin_manufacturersimports_configs"
                              => "entities_id"],
                     "glpi_manufacturers"
                     => ["glpi_plugin_manufacturersimports_configs"
                              => "manufacturers_id"],
                     "glpi_suppliers"
                     => ["glpi_plugin_manufacturersimports_configs"
                              => "suppliers_id"],
                     "glpi_documentcategories"
                     => ["glpi_plugin_manufacturersimports_configs"
                              => "documentcategories_id"],
                     "glpi_documents"
                     => ["glpi_plugin_manufacturersimports_logs"
                              => "documents_id"]
        ];
    } else {
        return [];
    }
}

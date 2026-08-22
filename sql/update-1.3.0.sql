--
-- -------------------------------------------------------------------------
-- manufacturersimports plugin for GLPI
-- Copyright (C) 2015-2026 by the manufacturersimports Development Team.
--
-- https://github.com/InfotelGLPI/manufacturersimports
-- -------------------------------------------------------------------------
--
-- LICENSE
--
-- This file is part of manufacturersimports.
--
-- manufacturersimports is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- manufacturersimports is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with manufacturersimports. If not, see <http://www.gnu.org/licenses/>.
-- --------------------------------------------------------------------------
--

ALTER TABLE `glpi_plugin_suppliertag_config` RENAME `glpi_plugin_manufacturersimports_configs`;
ALTER TABLE `glpi_plugin_suppliertag_profiles` RENAME `glpi_plugin_manufacturersimports_profiles`;
ALTER TABLE `glpi_plugin_suppliertag_models` RENAME `glpi_plugin_manufacturersimports_models`;
ALTER TABLE `glpi_plugin_suppliertag_imported` RENAME `glpi_plugin_manufacturersimports_logs`;

ALTER TABLE `glpi_plugin_manufacturersimports_configs` 
   CHANGE `ID` `id` int(11) NOT NULL auto_increment,
   CHANGE `name` `name` varchar(255) collate utf8_unicode_ci default NULL,
   CHANGE `FK_entities` `entities_id` int(11) NOT NULL default '0',
   CHANGE `recursive` `is_recursive` tinyint(1) NOT NULL default '0',
   CHANGE `Supplier_url` `supplier_url` varchar(255) collate utf8_unicode_ci collate utf8_unicode_ci default NULL,
   CHANGE `FK_glpi_enterprise` `manufacturers_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_manufacturers (id)',
   CHANGE `FK_enterprise` `suppliers_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_suppliers (id)',
   CHANGE `warranty` `warranty_duration` int(11) NOT NULL default '0',
   CHANGE `adddoc` `document_adding` int(11) NOT NULL default '0',
   CHANGE `rubrique` `documentcategories_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_documentcategories (id)',
   CHANGE `comments` `comment_adding` int(11) NOT NULL default '0',
   ADD INDEX (`name`),
   ADD INDEX (`entities_id`),
   ADD INDEX (`manufacturers_id`),
   ADD INDEX (`suppliers_id`),
   ADD INDEX (`documentcategories_id`);

ALTER TABLE `glpi_plugin_manufacturersimports_profiles` 
   CHANGE `ID` `id` int(11) NOT NULL auto_increment,
   ADD `profiles_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_profiles (id)',
   CHANGE `suppliertag` `manufacturersimports` char(1) collate utf8_unicode_ci default NULL,
   ADD INDEX (`profiles_id`);

ALTER TABLE `glpi_plugin_manufacturersimports_models` 
   CHANGE `ID` `id` int(11) NOT NULL auto_increment,
   CHANGE `FK_model` `model_name` varchar(100) collate utf8_unicode_ci default NULL,
   CHANGE `FK_device` `items_id` int(11) NOT NULL default '0' COMMENT 'RELATION to various tables, according to itemtype (id)',
   CHANGE `device_type` `itemtype` varchar(100) collate utf8_unicode_ci NOT NULL COMMENT 'see .class.php file',
   DROP INDEX `FK_model`,
   DROP INDEX `FK_model_2`,
   DROP INDEX `FK_device`,
   ADD UNIQUE `unicity` (`model_name`,`items_id`,`itemtype`),
   ADD INDEX `FK_device` (`items_id`,`itemtype`),
   ADD INDEX `item` (`itemtype`,`items_id`);

ALTER TABLE `glpi_plugin_manufacturersimports_logs` 
   CHANGE `ID` `id` int(11) NOT NULL auto_increment,
   CHANGE `FK_device` `items_id` int(11) NOT NULL default '0' COMMENT 'RELATION to various tables, according to itemtype (id)',
   CHANGE `device_type` `itemtype` varchar(100) collate utf8_unicode_ci NOT NULL COMMENT 'see .class.php file',
   CHANGE `FK_doc` `documents_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_documents (id)',
   CHANGE `import_date` `date_import` DATE default NULL,
   DROP INDEX `FK_suppliertag`,
   DROP INDEX `FK_suppliertag_2`,
   DROP INDEX `FK_device`,
   ADD UNIQUE `unicity` (`import_status`,`items_id`,`itemtype`),
   ADD INDEX `FK_device` (`items_id`,`itemtype`),
   ADD INDEX `item` (`itemtype`,`items_id`);
   
UPDATE `glpi_plugin_manufacturersimports_configs` SET `Supplier_url` = 'http://aps2.toshiba-tro.de/unit-details-php/unitdetails.aspx?'  WHERE `name` ='Toshiba';

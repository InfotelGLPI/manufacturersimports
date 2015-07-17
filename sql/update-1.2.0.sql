ALTER TABLE `glpi_plugin_suppliertag_imported` CHANGE `import_date` `import_date` DATE NULL default NULL;
UPDATE `glpi_plugin_suppliertag_imported` SET `import_date` = NULL WHERE `import_date` ='0000-00-00';

UPDATE `glpi_plugin_suppliertag_config` SET `Supplier_url` = 'http://support.dell.com/support/topics/global.aspx/support/my_systems_info/details?c=us&l=en&s=bsd&ServiceTag='  WHERE `name` ='Dell';

ALTER TABLE `glpi_plugin_suppliertag_profiles` DROP COLUMN `interface`, DROP COLUMN `is_default`;
ALTER TABLE `glpi_plugin_suppliertag_imported` CHANGE `FK_suppliertag` `import_status`  int(11) NOT NULL default '0';
ALTER TABLE `glpi_plugin_suppliertag_config` ADD `recursive` tinyint(1) NOT NULL default '0' AFTER FK_entities;
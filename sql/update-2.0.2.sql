ALTER TABLE `glpi_plugin_manufacturersimports_configs` ADD `supplier_secret` VARCHAR(255) NULL DEFAULT NULL;
ALTER TABLE `glpi_plugin_manufacturersimports_configs` ADD `warranty_url` VARCHAR(255) NULL DEFAULT NULL;
ALTER TABLE `glpi_plugin_manufacturersimports_configs` ADD `token_url` VARCHAR(255) NULL DEFAULT NULL;
ALTER TABLE `glpi_plugin_manufacturersimports_configs` CHANGE `supplier_key` `supplier_key` VARCHAR(255);
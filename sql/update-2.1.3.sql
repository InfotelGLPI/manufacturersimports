ALTER TABLE `glpi_plugin_manufacturersimports_configs` ADD `warranty_url` VARCHAR(255) NULL DEFAULT NULL;
ALTER TABLE `glpi_plugin_manufacturersimports_configs` ADD `token_url` VARCHAR(255) NULL DEFAULT NULL;

ALTER TABLE `glpi_plugin_manufacturersimports_configs`
	ENGINE=InnoDB;
ALTER TABLE `glpi_plugin_manufacturersimports_logs`
	ENGINE=InnoDB;
ALTER TABLE `glpi_plugin_manufacturersimports_models`
	ENGINE=InnoDB;
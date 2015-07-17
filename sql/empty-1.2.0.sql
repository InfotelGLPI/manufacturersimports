DROP TABLE IF EXISTS `glpi_plugin_suppliertag_config`;
CREATE TABLE `glpi_plugin_suppliertag_config` (
	`ID` int(11) NOT NULL auto_increment,
	`name` varchar(255) default NULL,
	`FK_entities` int(11) NOT NULL default '0',
	`recursive` tinyint(1) NOT NULL default '0',
	`Supplier_url` varchar(255) collate utf8_unicode_ci collate utf8_unicode_ci default NULL,
	`FK_glpi_enterprise` int(11) NOT NULL default '0',
	`FK_enterprise` int(11) NOT NULL default '0',
	`warranty` int(11) NOT NULL default '0',
	`adddoc` int(11) NOT NULL default '0',
	`rubrique` int(11) NOT NULL default '0',
	`comments` int(11) NOT NULL default '0',
	PRIMARY KEY  (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_suppliertag_profiles`;
CREATE TABLE `glpi_plugin_suppliertag_profiles` (
	`ID` int(11) NOT NULL auto_increment,
	`name` varchar(255) default NULL,
	`suppliertag` char(1) default NULL,
	PRIMARY KEY  (`ID`),
	KEY `name` (`name`)
) TYPE=MyISAM;

INSERT INTO `glpi_plugin_suppliertag_config` ( `ID`,`name`,`FK_entities`,`recursive`, `Supplier_url` , `FK_glpi_enterprise`, `FK_enterprise`, `warranty`, `adddoc`, `rubrique`) VALUES ('1','Dell',0,0,'http://support.dell.com/support/topics/global.aspx/support/my_systems_info/details?c=us&l=en&s=bsd&ServiceTag=', '0', '0','36', '0','0');
INSERT INTO `glpi_plugin_suppliertag_config` ( `ID`,`name`,`FK_entities`,`recursive`, `Supplier_url` , `FK_glpi_enterprise`, `FK_enterprise`, `warranty`, `adddoc`, `rubrique`) VALUES ('2','HP',0,0,'http://www11.itrc.hp.com/service/ewarranty/warrantyResults.do?', '0', '0','36', '0','0');
INSERT INTO `glpi_plugin_suppliertag_config` ( `ID`,`name`,`FK_entities`,`recursive`, `Supplier_url` , `FK_glpi_enterprise`, `FK_enterprise`, `warranty`, `adddoc`, `rubrique`) VALUES ('3','Fujitsu-Siemens',0,0,'http://sali.fujitsu-siemens.co.uk/ServiceEntitlement/service.asp?', '0', '0','36', '0','0');
INSERT INTO `glpi_plugin_suppliertag_config` ( `ID`,`name`,`FK_entities`,`recursive`, `Supplier_url` , `FK_glpi_enterprise`, `FK_enterprise`, `warranty`, `adddoc`, `rubrique`) VALUES ('4','IBM',0,0,'http://www-304.ibm.com/jct01004c/systems/support/supportsite.wss/warranty?', '0', '0','36', '0','0');
INSERT INTO `glpi_plugin_suppliertag_config` ( `ID`,`name`,`FK_entities`,`recursive`, `Supplier_url` , `FK_glpi_enterprise`, `FK_enterprise`, `warranty`, `adddoc`, `rubrique`) VALUES ('5','Toshiba',0,0,'http://toshiba.eclaim.com/toshiba/tsbclok2.asp?', '0', '0','36', '0','0');

DROP TABLE IF EXISTS `glpi_plugin_suppliertag_models`;
CREATE TABLE `glpi_plugin_suppliertag_models` (
	`ID` int(11) NOT NULL auto_increment,
	`FK_model` varchar(255) default NULL,
	`FK_device` int(11) NOT NULL default '0',
	`device_type` int(11) NOT NULL default '0',
	PRIMARY KEY  (`ID`),
	UNIQUE KEY `FK_model` (`FK_model`,`FK_device`,`device_type`),
	KEY `FK_model_2` (`FK_model`),
	KEY `FK_device` (`FK_device`,`device_type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_suppliertag_imported`;
CREATE TABLE `glpi_plugin_suppliertag_imported` (
	`ID` int(11) NOT NULL auto_increment,
	`import_status` int(11) NOT NULL default '0',
	`FK_device` int(11) NOT NULL default '0',
	`device_type` int(11) NOT NULL default '0',
	`FK_doc` int(11) NOT NULL default '0',
	`import_date` DATE NULL default NULL,
	PRIMARY KEY  (`ID`),
	UNIQUE KEY `FK_suppliertag` (`import_status`,`FK_device`,`device_type`),
	KEY `FK_suppliertag_2` (`import_status`),
	KEY `FK_device` (`FK_device`,`device_type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
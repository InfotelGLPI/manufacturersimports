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

ALTER TABLE `glpi_plugin_suppliertag_imported` CHANGE `import_date` `import_date` DATE NULL default NULL;
UPDATE `glpi_plugin_suppliertag_imported` SET `import_date` = NULL WHERE `import_date` ='0000-00-00';

UPDATE `glpi_plugin_suppliertag_config` SET `Supplier_url` = 'http://support.dell.com/support/topics/global.aspx/support/my_systems_info/details?c=us&l=en&s=bsd&ServiceTag='  WHERE `name` ='Dell';

ALTER TABLE `glpi_plugin_suppliertag_profiles` DROP COLUMN `interface`, DROP COLUMN `is_default`;
ALTER TABLE `glpi_plugin_suppliertag_imported` CHANGE `FK_suppliertag` `import_status`  int(11) NOT NULL default '0';
ALTER TABLE `glpi_plugin_suppliertag_config` ADD `recursive` tinyint(1) NOT NULL default '0' AFTER FK_entities;
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

namespace GlpiPlugin\Manufacturersimports\Manufacturers;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Ibm
 */
class Ibm extends Manufacturer
{
    /**
     * @see Manufacturer::getSupplierInfo()
     */
    public function getSupplierInfo(
        $compSerial = null,
        $otherSerial = null,
        $key = null,
        $apisecret = null,
        $supplierUrl = null
    )
    {
        $info["name"]="IBM";
        $info["supplier_url"] = "http://www-304.ibm.com/jct01004c/systems/support/supportsite.wss/warranty?";
        $info["url"] = $supplierUrl."type=".$otherSerial."&serial=".$compSerial."&brandind=5000008&Submit=Submit&action=warranty";
        return $info;
    }
}

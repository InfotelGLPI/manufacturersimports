<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Manufacturersimports plugin for GLPI
 Copyright (C) 2003-2016 by the Manufacturersimports Development Team.

 https://github.com/InfotelGLPI/manufacturersimports
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Manufacturersimports.

 Manufacturersimports is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Manufacturersimports is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Manufacturersimports. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Manufacturersimports;

use CommonDBTM;
use Document;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Log
 */
class Log extends CommonDBTM
{

   /**
    * @param $items_id
    * @param $itemtype
    *
    * @return bool
    */
    function getFromDBbyDevice($items_id, $itemtype)
    {
        global $DB;

        $query = "SELECT * FROM `".$this->getTable()."` " .
         "WHERE `items_id` = '" . $items_id . "'
         AND `itemtype` = '" . $itemtype . "' ";
        if ($result = $DB->doQuery($query)) {
            if ($DB->numrows($result) != 1) {
                return false;
            }
            $this->fields = $DB->fetchAssoc($result);
            if (is_array($this->fields) && count($this->fields)) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

   /**
    * @param $itemtype
    * @param $items_id
    *
    * @return bool
    */
    function checkIfAlreadyImported($itemtype, $items_id)
    {

        if ($this->getFromDBbyDevice($items_id, $itemtype)) {
            return $this->fields["id"];
        } else {
            return false;
        }
    }

   /**
    * @param $itemtype
    * @param $items_id
    */
    function reinitializeImport($itemtype, $items_id)
    {
        global $DB;

        if ($this->getFromDBbyDevice($items_id, $itemtype)) {
            $doc= new Document();
            if ($doc->GetfromDB($this->fields["documents_id"])) {
                $query ="DELETE
              FROM `glpi_documents_items`
              WHERE `documents_id` = '".$this->fields["documents_id"]."';";
                $DB->doQuery($query);

                if (is_file(GLPI_DOC_DIR."/".$doc->fields["filename"])
                  && !is_dir(GLPI_DOC_DIR."/".$doc->fields["filename"])) {
                    unlink(GLPI_DOC_DIR."/".$doc->fields["filename"]);
                }

                $doc->delete(['id'=>$this->fields["documents_id"]], true);
            }
        }
        if (isset($this->fields["id"])) {
            $this->delete(['id'=>$this->fields["id"]]);
        }
    }
}

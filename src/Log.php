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

        $iterator = $DB->request([
            'FROM'  => $this->getTable(),
            'WHERE' => [
                'items_id' => $items_id,
                'itemtype' => $itemtype,
            ],
            'LIMIT' => 1,
        ]);
        if (count($iterator) !== 1) {
            return false;
        }
        $this->fields = $iterator->current();
        return is_array($this->fields) && count($this->fields) > 0;
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
                $DB->delete('glpi_documents_items', ['documents_id' => $this->fields["documents_id"]]);

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

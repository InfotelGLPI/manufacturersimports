<?php

/**
 * -------------------------------------------------------------------------
 * manufacturersimports plugin for GLPI
 * Copyright (C) 2015-2026 by the manufacturersimports Development Team.
 *
 * https://github.com/InfotelGLPI/manufacturersimports
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of manufacturersimports.
 *
 * manufacturersimports is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * manufacturersimports is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with manufacturersimports. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Manufacturersimports;

use CommonDBTM;
use Document;
use Document_Item;

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
    public function getFromDBbyDevice($items_id, $itemtype)
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
    public function checkIfAlreadyImported($itemtype, $items_id)
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
    public function reinitializeImport($itemtype, $items_id)
    {
        if ($this->getFromDBbyDevice($items_id, $itemtype)) {
            $documents_id = (int) $this->fields["documents_id"];
            $doc          = new Document();
            if ($doc->getFromDB($documents_id)) {
                // Only unlink the document from this item: it may have been attached
                // to other objects since the import (history kept).
                $doc_item = new Document_Item();
                $doc_item->deleteByCriteria([
                    'documents_id' => $documents_id,
                    'itemtype'     => $itemtype,
                    'items_id'     => $items_id,
                ], true);

                // Purge the document once orphaned, and only with the Document right.
                // The purge removes the file itself, only when no other document
                // shares it (same sha1): never unlink it by hand.
                if (countElementsInTable(Document_Item::getTable(), ['documents_id' => $documents_id]) === 0
                    && $doc->can($documents_id, PURGE)) {
                    $doc->delete(['id' => $documents_id], true);
                }
            }
        }
        if (isset($this->fields["id"])) {
            $this->delete(['id' => $this->fields["id"]]);
        }
    }
}

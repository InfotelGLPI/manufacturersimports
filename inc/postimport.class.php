<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Manufacturersimports plugin for GLPI
 Copyright (C) 2003-2011 by the Manufacturersimports Development Team.

 https://forge.indepnet.net/projects/manufacturersimports
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

if (!defined('GLPI_ROOT')) {
        die("Sorry. You can't access directly to this file");
}

class PluginManufacturersimportsPostImport extends CommonDBTM {

   static function checkDate($field,$reverse = false) {

      // Date is already "reformat" according to getDateFormat()

      if ($reverse) {
         $pattern  = "/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})";
         $pattern .= "([0-5][0-9]:[0-5]?[0-9]:[_][01][0-9]|2[0-3])?/";
      } else {
         $pattern  = "/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})";
         $pattern .= "([_][01][0-9]|2[0-3]:[0-5][0-9]:[0-5]?[0-9])?/";
      }
      preg_match($pattern, $field, $regs);
      if (empty($regs)) {
         return "0000-00-00";
      }
      return $field;
   }


   static function cURLData($options){
      global $CFG_GLPI;
      
      if(!function_exists('curl_init')) {
         return __('Curl PHP package not installed', 'manufacturersimports')."\n";
      }
      $data        = '';
      $timeout     = 10;
      $proxy_host  = $CFG_GLPI["proxy_name"].":".$CFG_GLPI["proxy_port"]; // host:port
      $proxy_ident = $CFG_GLPI["proxy_user"].":".
                           Toolbox::decrypt($CFG_GLPI["proxy_passwd"], GLPIKEY); // username:password 
       
      $url = $options["url"];
      
      $ch = curl_init();

      curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
      curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); 

      if (preg_match('`^https://`i', $options["url"])) {
         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
         curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      }
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_COOKIEFILE, "cookiefile");
      curl_setopt($ch, CURLOPT_COOKIEJAR, "cookiefile"); # SAME cookiefile 

      //Do we have post field to send?
      if (!empty($options["post"])) {
         //curl_setopt($ch, CURLOPT_POST,true);
         $post = '';
         foreach($options['post'] as $key=>$value) {
            $post .= $key.'='.$value.'&'; 
         }
         rtrim($post, '&');
         curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type:application/x-www-form-urlencoded")); 
         curl_setopt($ch, CURLOPT_POST, true);
         curl_setopt($ch, CURLOPT_POSTREDIR, 2);
         curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
      }

      if (!$options["download"]) {
         //curl_setopt($ch, CURLOPT_HEADER, 1);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
         curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
      }

      // Activation de l'utilisation d'un serveur proxy
      if (!empty($CFG_GLPI["proxy_name"])) {
         //curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);

         // Définition de l'adresse du proxy
         curl_setopt($ch, CURLOPT_PROXY, $proxy_host);

         // Définition des identifiants si le proxy requiert une identification
         if ($proxy_ident) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_ident);
         }
      }
      if ($options["download"]) {
         $fp = fopen($options["file"], "w");
         curl_setopt($ch, CURLOPT_FILE, $fp);
         curl_exec($ch);
      } else {
         $data = curl_exec($ch);
      }

      if (!$options["download"] && !$data) {
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch); // make sure we closeany current curl sessions
        //die($http_code.' Unable to connect to server. Please come back later.');
      } else {
         curl_close($ch);
      }

      if ($options["download"]) {
         fclose($fp);
      }
      if (!$options["download"] && $data) {
         return $data;
      }
   }
   
   static function massiveimport($values) {
      global $DB,$CFG_GLPI;

      $config = new PluginManufacturersimportsConfig();
      $log=new PluginManufacturersimportsLog();

      $_SESSION["glpi_plugin_manufacturersimports_total"]=0;
      echo "<table class='tab_cadre' width='70%' cellpadding='2'>";
      echo "<tr><th colspan='6'>".__('Post import', 'manufacturersimports')."</th></tr>";
      echo "<tr><th>".__('Name')."</th>";
      echo "<th>".__('Serial number')."</th>";

      $config->getFromDB($values["manufacturers_id"]);
      $suppliername=$config->fields["name"];

      echo "<th>"._n('Link' , 'Links' , 1)."</th>";
      echo "<th>".__('Result', 'manufacturersimports')."</th>";
      echo "<th>".__('Details', 'manufacturersimports')."</th>";
      echo "</tr>";
      foreach ($values["item"] as $key => $val) {
         if ($val==1) {
            $NotAlreadyImported=$log->checkIfAlreadyImported($values["itemtype"],$key);
            if (!$NotAlreadyImported) {
               self::seePostImport($values["itemtype"],
                                   $key,
                                   $values["to_suppliers_id$key"],
                                   $values["to_warranty_duration$key"],
                                   $values["manufacturers_id"]);
            }
         }
      }
      
      echo "<tr class='tab_bg_1'><td colspan='6'>";
      $total = $_SESSION["glpi_plugin_manufacturersimports_total"];
      echo sprintf(__('Total number of devices imported %s', 'manufacturersimports'), $total);
      echo "</td></tr>";
      echo "</table>";
      echo "<br><div align='center'>";
      echo "<a href='".$CFG_GLPI["root_doc"]."/plugins/manufacturersimports/front/import.php?back=back&amp;itemtype=".
      $values["itemtype"]."&amp;manufacturers_id=".$values["manufacturers_id"]."&amp;start=".$values["start"].
            "&amp;imported=".$values["imported"]."'>";
      echo __('Back');
      echo "</a>";
      echo "</div>";
   }
   
   /**
   * Fonction to select the search field on the website of the supplier
   *
   * @param $suppliername the suppliername
   * @return $field for date and warranty searching
   *
   */
   static function selectSupplierField ($suppliername) {

      $field = '';
      if (!empty($suppliername)) {
         $supplierclass = "PluginManufacturersimports".$suppliername;
         $supplier      = new $supplierclass();
         $field         = $supplier->getSearchField();
      }

      return $field;
   }

   static function importDate($suppliername, $contents) {
   
      $supplierclass = "PluginManufacturersimports".$suppliername;
      $supplier      = new $supplierclass();
      $importDate    = $supplier->getBuyDate($contents);
      
      return $importDate;
   }

   static function importWarrantyInfo($suppliername, $contents) {
       $supplierclass = "PluginManufacturersimports".$suppliername;
       $supplier = new $supplierclass();
       $importWarrantyInfo = $supplier->getWarrantyInfo($contents);
       
       return $importWarrantyInfo;
   }
   
   //static function importWarranty($suppliername, $maDate, $contents, $warranty) {
   //   if ($warranty==0) {
   //      if ($suppliername == PluginManufacturersimportsConfig::DELL) {
   //         $maDateFin = PluginManufacturersimportsDellSoap::getDates($contents, "EndDate");
   //      } else {
   //         $supplierclass = "PluginManufacturersimports".$suppliername;
   //         $supplier      = new $supplierclass();
   //         $maDateFin     = $supplier->getExpirationDate($contents);
   //      }

   //      if ($maDateFin != "0000-00-00") {
   //         list ($adebut, $mdebut, $jdebut) = explode ('-', $maDate);
   //         list ($afin, $mfin, $jfin)       = explode ('-', $maDateFin);

   //         $warranty = 0;
   //         $warranty = 12 - $mdebut;
   //         for ($year = $adebut + 1; $year < $afin;$year++) {
   //            $warranty += 12;
   //         }
   //         $warranty += $mfin;
   //      }
   //   }
   //   return $warranty;
   //}
   
   static function importDateFin($suppliername,$contents) {
       
      $supplierclass = "PluginManufacturersimports".$suppliername;
      $supplier = new $supplierclass();
      $maDateFin = $supplier->getExpirationDate($contents);
       
      return $maDateFin;
   }
   
   /**
   * Prints display post import
   *
   * @param $type the type of device
   * @param $ID the ID of device
   * @param $fromsupplier selection on pre import
   * @param $fromwarranty selection on pre import
   * @param $configID ID of supplier plugin config
   * @return results of data import
   *
   */
   static function seePostImport ($type,$ID,$fromsupplier,$fromwarranty,$configID) {
      global $DB,$CFG_GLPI;

      $config = new PluginManufacturersimportsConfig();
      $config->getFromDB($configID);
      $manufacturerId = $config->fields["manufacturers_id"];

      if ($fromsupplier) {
         $supplierId = $fromsupplier;
      } else {
         $supplierId = $config->fields["suppliers_id"];
      }
      $suppliername = $config->fields["name"];
      $supplierkey  = $config->fields["supplier_key"];
      $adddoc       = $config->fields["document_adding"];
      $rubrique     = $config->fields["documentcategories_id"];
      $addcomments  = $config->fields["comment_adding"];
      if ($fromwarranty) {
         $warranty = $fromwarranty;
      } else {
         $warranty = $config->fields["warranty_duration"];
      }

      $itemtable = getTableForItemType($type);

      $query = "SELECT `".$itemtable."`.`id`,
                        `".$itemtable."`.`name`,
                        `".$itemtable."`.`entities_id`,
                        `".$itemtable."`.`serial`
          FROM `".$itemtable."`, `glpi_manufacturers`
          WHERE `".$itemtable."`.`manufacturers_id` = `glpi_manufacturers`.`id`
          AND `".$itemtable."`.`is_deleted` = '0'
          AND `".$itemtable."`.`is_template` = '0'
          AND `glpi_manufacturers`.`id` = '".$manufacturerId."'
          AND `".$itemtable."`.`serial` != ''
          AND `".$itemtable."`.`id` = '".$ID."' ";
      $query.= " ORDER BY `".$itemtable."`.`name`";
      $result = $DB->query($query);

      while($line=$DB->fetch_array($result)) {

         $compSerial = $line['serial'];
         $ID = $line['id'];
         echo "<tr class='tab_bg_1' ><td>";
         $link=Toolbox::getItemTypeFormURL($type);
         $dID="";
         $model= new PluginManufacturersimportsModel();
         $otherSerial=$model->checkIfModelNeeds($type,$ID);

         if ($_SESSION["glpiis_ids_visible"]||empty($line["name"])) $dID.= " (".$line["id"].")";
         echo "<a href='".$link."?id=".$ID."'>".$line["name"].$dID."</a><br>".$otherSerial."</td>";

         $url=PluginManufacturersimportsPreImport::selectSupplier($suppliername,$compSerial,$otherSerial,$supplierkey);
         $post=PluginManufacturersimportsPreImport::getSupplierPost($suppliername,$compSerial,$otherSerial);
                 
         //On complete l url du support du fournisseur avec le serial
         echo "<td>".$compSerial."</td>";
         echo "<td>";
         echo "<a href='".$url."' target='_blank'>"._n('Manufacturer' , 'Manufacturers' , 1)."</a>";
         echo "</td>";
         
         $contents = "";
         $msgerr   ="";
         $options  = array ("url"          => $url,
                            "download"     => false,
                            "file"         => false,
                            "post"         => $post,
                            "suppliername" => $suppliername);

         $contents = self::cURLData($options);
         $allcontents = $contents;
         // On extrait la date de garantie de la variable contents.
         $field = self::selectSupplierfield($suppliername);
         
         if ($suppliername !=PluginManufacturersimportsConfig::DELL) {
         
            $contents = stristr($contents, $field);
         }
         if (!$contents === FALSE) {
            $maDate = self::importDate($suppliername,$contents);
            
            if ($suppliername ==PluginManufacturersimportsConfig::FUJITSU) {
         
               $contents = $allcontents;
            }
            $maDateFin = self::importDateFin($suppliername,$contents);
            $warrantyinfo = self::importWarrantyInfo($suppliername,$contents);

         } 
         //else {
            
         //   self::isInError($type,$ID);
         //}

         if (isset($maDate) 
               && $maDate != "0000-00-00" 
                  && $maDate != false
                     && isset($maDateFin) 
                        && $maDateFin != "0000-00-00"
                           && $maDateFin != false) {

            list ($adebut, $mdebut, $jdebut) = explode ('-', $maDate);
            list ($afin, $mfin, $jfin) = explode ('-', $maDateFin);
            $warranty = 0;
            $warranty = 12 - $mdebut;
            for ($year = $adebut + 1; $year < $afin;$year++) {
               $warranty += 12;
            }
            $warranty += $mfin;
         }
            
         if (isset($maDate) 
               && $maDate != "0000-00-00"
                  && $maDate != false) {
            //warranty for life
            if ($warranty > 120) {
               $warranty=-1;
            }

            $date=date("Y-m-d");
            $options = array ("itemtype" => $type,
               "ID" => $ID,
               "date" => $date,
               "supplierId" => $supplierId,
               "warranty" => $warranty,
               "suppliername" => $suppliername,
               "addcomments" => $addcomments,
               "maDate" => $maDate,
               "warranty_info" => $warrantyinfo);
            self::saveInfocoms ($options);
            
               // on cree un doc dans GLPI qu'on va lier au materiel
               if ($adddoc != 0 
                  && $suppliername != PluginManufacturersimportsConfig::DELL) {
                  $options = array ("itemtype"     => $type,
                                    "ID"           => $ID,
                                    "url"          => $url,
                                    "entities_id"  => $line["entities_id"],
                                    "rubrique"     => $rubrique,
                                    "suppliername" => $suppliername);
                  $values["documents_id"] = self::addDocument($options);
               }

               //insert base locale
               $values["import_status"] = 1;
               $values["items_id"]      = $ID;
               $values["itemtype"]      = $type;
               $values["date_import"]   = $date;
               $log                     = new PluginManufacturersimportsLog();
               $log->add($values);

               // cleanup Log
               $log_clean               = new PluginManufacturersimportsLog();
               $log_clean->deleteByCriteria(array(
                       'items_id' => $ID,
                       'itemtype' => $type,
                       'import_status' => 2,
                       'LIMIT' => 1
                   )
               );

               $_SESSION["glpi_plugin_manufacturersimports_total"]+=1;

         } else { // Failed check contents
            self::isInError($type,$ID);
         }

         echo "</tr>\n";
      }
   }

   static function saveInfocoms ($options) {

      //Original values
      $warranty_date     = "";
      $buy_date          = "";
      $warranty_duration = "";
      $warranty_info     = "";
      $suppliers_id      = "";
      $ic_comments       = "";

      //New values
      $input_infocom = array();
      if ($options["supplierId"] != 0) {
         $input_infocom["suppliers_id"] = $options["supplierId"];
      }
      $input_infocom["warranty_date"]     = $options["maDate"];
      $input_infocom["warranty_duration"] = $options["warranty"];
      $input_infocom["warranty_info"]     = $options["warranty_info"];
      $input_infocom["buy_date"]          = $options["maDate"];
      $input_infocom["items_id"]          = $options["ID"];
      $input_infocom["itemtype"]          = $options["itemtype"];
      
      //add new infocoms
      $ic= new infocom();
      if ($ic->getfromDBforDevice($options["itemtype"],$options["ID"])) {
      
         //Original values
         $warranty_date      = Html::convdate($ic->fields["warranty_date"]);
         $warranty_duration  = $ic->fields["warranty_duration"];
         $warranty_info      = $ic->fields["warranty_info"];
         $buy_date           = $ic->fields["buy_date"];
         $suppliers_id       = Dropdown::getDropdownName("glpi_suppliers",$ic->fields["suppliers_id"]);
         $ic_comment         = $ic->fields["comment"];

         //New values
         $input_infocom["id"] = $ic->fields["id"];

         if ($options["addcomments"]) {
            $input_infocom["comment"]=$ic_comment."\n".
               __('Imported from web site', 'manufacturersimports')." ".$options["suppliername"]." ".
               __('With the manufacturersimports plugin', 'manufacturersimports')." (".Html::convdate($options["date"]).")";
         }
         $infocom = new Infocom();
         $infocom->update($input_infocom);

      } else {

         if ($options["addcomments"]) {
            $input_infocom["comment"]=__('Imported from web site', 'manufacturersimports').
               " ".$options["suppliername"]." ".__('With the manufacturersimports plugin', 'manufacturersimports').
               " (".Html::convdate($options["date"]).")";
         }
         $infocom = new Infocom();
         $infocom->add($input_infocom);

      }

      //post message
      echo "<td><span class='plugin_manufacturersimports_import_OK'>";
      echo __('Import OK', 'manufacturersimports')." (".Html::convdate($options["date"]).")";
      echo "</span></td>";
      echo "<td>";
      echo _n('Supplier' , 'Suppliers' , 1).": ";
      echo $suppliers_id."->".Dropdown::getDropdownName("glpi_suppliers",$options["supplierId"])."<br>";
      echo __('Date of purchase').": ";
      echo Html::convdate($buy_date)."->".Html::convdate($options["maDate"])."<br>";
      echo __('Start date of warranty').": ";
      echo $warranty_date."->".Html::convdate($options["maDate"])."<br>";
      if ($warranty_duration==-1) {
         $warranty_duration=__('Lifelong');
         $warranty=__('Lifelong');
      } else {
         $warranty=$options["warranty"];
      }
      echo __('Warranty duration').": ".$warranty_duration."->".$warranty."<br>";
      echo "</td>";
   }

   static function addDocument($options){

      //configure adding doc

      $time_file = date("Y-m-d-H-i");
      $name      = "";
      switch ($options["itemtype"]) {
         case 'Computer':
            $name = "computer";
            break;
         case 'NetworkEquipment':
            $name = "networking";
            break;
         case 'Peripheral':
            $name = "peripheral";
            break;
         case 'Monitor':
            $name = "monitor";
            break;
         case 'Printer':
            $name = "printer";
            break;
         case 'PluginRacksRack':
            $name = "rack";
            break;
      }

      $filename = 
         "infocoms_".$options["suppliername"]
            ."_".$name."_".$options["ID"].".html";

      //on enregistre
      $path     = GLPI_DOC_DIR."/_uploads/";
      $filepath = $path.$filename;
      $datas    = array ("url"          => $options["url"],
                         "download"     => true,
                        "file"         => $filepath,
                        "suppliername" => $options["suppliername"]);
      self::cURLData($datas);

      $doc = new document();

      $input                          = array();
      $input["entities_id"]           = $options["entities_id"];
      $input["name"]                  = 
         addslashes("infocoms_".$options["suppliername"]."_".$name."_".$options["ID"]);
      $input["upload_file"]           = $filename;
      $input["documentcategories_id"] = $options["rubrique"];
      $input["mime"]                  = "text/html";
      $input["date_mod"]              = date("Y-m-d H:i:s");
      $input["users_id"]              = Session::getLoginUserID();

      $newdoc=$doc->add($input);
      $docitem=new Document_Item();
      $docitem->add(array('documents_id' => $newdoc,
                          'itemtype'     => $options["itemtype"],
                          'items_id'     => $options["ID"],
                          'entities_id'  => $input["entities_id"]));

      $temp = new PluginManufacturersimportsLog();
      $temp->deleteByCriteria(array('itemtype' => $options["itemtype"],
         'items_id' => $options["ID"]));

      return $newdoc;
   }


   static function isInError ($type, $ID, $contents = null) {

      $msgerr = "";
      $date   = date("Y-m-d");
      echo "<td>";
      echo "<span class='plugin_manufacturersimports_import_KO'>";
      echo __('Import failed', 'manufacturersimports')." (";
      echo Html::convdate($date).")</span></td>";

      $temp = new PluginManufacturersimportsLog();
      $temp->deleteByCriteria(array('itemtype' => $type,
                                    'items_id' => $ID));

      //insert base locale
      $values["import_status"] = 2;
      $values["items_id"]      = $ID;
      $values["itemtype"]      = $type;
      $values["date_import"]   = $date;
      $log                     = new PluginManufacturersimportsLog();
      $log->add($values);

      if (!empty($contents)) {
         $msgerr=__('Connection failed/data download from manufacturer web site', 'manufacturersimports');
      }
      echo "<td>$msgerr</td>";
   }
}

?>

<?php

/*
   ----------------------------------------------------------------------
   Monitoring plugin for GLPI
   Copyright (C) 2010-2011 by the GLPI plugin monitoring Team.

   https://forge.indepnet.net/projects/monitoring/
   ----------------------------------------------------------------------

   LICENSE

   This file is part of Monitoring plugin for GLPI.

   Monitoring plugin for GLPI is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 2 of the License, or
   any later version.

   Monitoring plugin for GLPI is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with Monitoring plugin for GLPI.  If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------
   Original Author of file: David DURIEUX
   Co-authors of file:
   Purpose of file:
   ----------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginMonitoringDisplay extends CommonDBTM {
   
   
   function defineTabs($options=array()){
      global $LANG,$CFG_GLPI;

      $ong = array();
      $ong[1] = $LANG['plugin_monitoring']['servicescatalog'][0];
      $ong[2] = $LANG['plugin_monitoring']['componentscatalog'][0];
      $ong[3] = $LANG['plugin_monitoring']['service'][21];
//      $ong[4] = $LANG['plugin_monitoring']['host'][0];
//      $ong[5] = $LANG['plugin_monitoring']['service'][0];
      return $ong;
   }
   
   
   
   function showTabs($options=array()) {
      global $LANG, $CFG_GLPI;
      
      // for objects not in table like central
      if (isset($this->fields['id'])) {
         $ID = $this->fields['id'];
      } else {
        $ID = 0;
      }

      $target         = $_SERVER['PHP_SELF'];
      $extraparamhtml = "";
      $extraparam     = "";
      $withtemplate   = "";

      if (is_array($options) && count($options)) {
         if (isset($options['withtemplate'])) {
            $withtemplate = $options['withtemplate'];
         }
         foreach ($options as $key => $val) {
            $extraparamhtml .= "&amp;$key=$val";
            $extraparam     .= "&$key=$val";
         }
      }

      if (empty($withtemplate) && $ID && $this->getType() && $this->displaylist) {
         $glpilistitems =& $_SESSION['glpilistitems'][$this->getType()];
         $glpilisttitle =& $_SESSION['glpilisttitle'][$this->getType()];
         $glpilisturl   =& $_SESSION['glpilisturl'][$this->getType()];

         if (empty($glpilisturl)) {
            $glpilisturl = $this->getSearchURL();
         }

         echo "<div id='menu_navigate'>";

         $next = $prev = $first = $last = -1;
         $current = false;
         if (is_array($glpilistitems)) {
            $current = array_search($ID,$glpilistitems);
            if ($current !== false) {

               if (isset($glpilistitems[$current+1])) {
                  $next = $glpilistitems[$current+1];
               }

               if (isset($glpilistitems[$current-1])) {
                  $prev = $glpilistitems[$current-1];
               }

               $first = $glpilistitems[0];
               if ($first == $ID) {
                  $first = -1;
               }

               $last = $glpilistitems[count($glpilistitems)-1];
               if ($last == $ID) {
                  $last = -1;
               }

            }
         }
         $cleantarget = cleanParametersURL($target);
         echo "<ul>";
         echo "<li><a href=\"javascript:showHideDiv('tabsbody','tabsbodyimg','".$CFG_GLPI["root_doc"].
                    "/pics/deplier_down.png','".$CFG_GLPI["root_doc"]."/pics/deplier_up.png')\">";
         echo "<img alt='' name='tabsbodyimg' src=\"".$CFG_GLPI["root_doc"]."/pics/deplier_up.png\">";
         echo "</a></li>";

         echo "<li><a href=\"".$glpilisturl."\">";

         if ($glpilisttitle) {
            if (utf8_strlen($glpilisttitle) > $_SESSION['glpidropdown_chars_limit']) {
               $glpilisttitle = utf8_substr($glpilisttitle, 0,
                                            $_SESSION['glpidropdown_chars_limit'])
                                . "&hellip;";
            }
            echo $glpilisttitle;

         } else {
            echo $LANG['common'][53];
         }
         echo "</a>&nbsp;:&nbsp;</li>";

         if ($first > 0) {
            echo "<li><a href='$cleantarget?id=$first$extraparamhtml'><img src='".
                       $CFG_GLPI["root_doc"]."/pics/first.png' alt=\"".$LANG['buttons'][55].
                       "\" title=\"".$LANG['buttons'][55]."\"></a></li>";
         } else {
            echo "<li><img src='".$CFG_GLPI["root_doc"]."/pics/first_off.png' alt=\"".
                       $LANG['buttons'][55]."\" title=\"".$LANG['buttons'][55]."\"></li>";
         }

         if ($prev > 0) {
            echo "<li><a href='$cleantarget?id=$prev$extraparamhtml'><img src='".
                       $CFG_GLPI["root_doc"]."/pics/left.png' alt=\"".$LANG['buttons'][12].
                       "\" title=\"".$LANG['buttons'][12]."\"></a></li>";
         } else {
            echo "<li><img src='".$CFG_GLPI["root_doc"]."/pics/left_off.png' alt=\"".
                       $LANG['buttons'][12]."\" title=\"".$LANG['buttons'][12]."\"></li>";
         }

         if ($current !== false) {
            echo "<li>".($current+1) . "/" . count($glpilistitems)."</li>";
         }

         if ($next > 0) {
            echo "<li><a href='$cleantarget?id=$next$extraparamhtml'><img src='".
                       $CFG_GLPI["root_doc"]."/pics/right.png' alt=\"".$LANG['buttons'][11].
                       "\" title=\"".$LANG['buttons'][11]."\"></a></li>";
         } else {
            echo "<li><img src='".$CFG_GLPI["root_doc"]."/pics/right_off.png' alt=\"".
                       $LANG['buttons'][11]."\" title=\"".$LANG['buttons'][11]."\"></li>";
         }

         if ($last > 0) {
            echo "<li><a href='$cleantarget?id=$last$extraparamhtml'><img src=\"".
                       $CFG_GLPI["root_doc"]."/pics/last.png\" alt=\"".$LANG['buttons'][56].
                       "\" title=\"".$LANG['buttons'][56]."\"></a></li>";
         } else {
            echo "<li><img src='".$CFG_GLPI["root_doc"]."/pics/last_off.png' alt=\"".
                       $LANG['buttons'][56]."\" title=\"".$LANG['buttons'][56]."\"></li>";
         }
         echo "</ul></div>";
         echo "<div class='sep'></div>";
      }

      echo "<div id='tabspanel' class='center-h'></div>";

      $active      = 0;
      $onglets     = $this->defineTabs($options);
      $display_all = true;
      if (isset($onglets['no_all_tab'])) {
         $display_all = false;
         unset($onglets['no_all_tab']);
      }
      $class = $this->getType();
      if ($_SESSION['glpi_use_mode']==DEBUG_MODE
          && ($ID > 0 || $this->showdebug)
          && (method_exists($class, 'showDebug')
              || in_array($class, $CFG_GLPI["infocom_types"])
              || in_array($class, $CFG_GLPI["reservation_types"]))) {

            $onglets[-2] = $LANG['setup'][137];
      }

      if (count($onglets)) {
         $tabpage = $this->getTabsURL();
         $tabs    = array();

         foreach ($onglets as $key => $val ) {
            $tabs[$key] = array('title'  => $val,
                                'url'    => $tabpage,
                                'params' => "target=$target&itemtype=".$this->getType().
                                            "&glpi_tab=$key&id=$ID$extraparam");
         }

         $plug_tabs = Plugin::getTabs($target,$this, $withtemplate);
         $tabs += $plug_tabs;
         // Not all tab for templates and if only 1 tab
         if ($display_all && empty($withtemplate) && count($tabs)>1) {
            $tabs[-1] = array('title'  => $LANG['common'][66],
                              'url'    => $tabpage,
                              'params' => "target=$target&itemtype=".$this->getType().
                                          "&glpi_tab=-1&id=$ID$extraparam");
         }
         createAjaxTabs('tabspanel', 'tabcontent', $tabs, $this->getType(), "'100%'");
      }
   }

   

   function showBoard($width='', $limit='') {
      global $DB,$CFG_GLPI,$LANG;

      $where = '';
      if ($limit == 'hosts') {
         $where = "`plugin_monitoring_services_id`='0' ";
      } else if ($limit == 'services') {
         $where = "`plugin_monitoring_services_id`>0 ";
      }      
      if (isset($_SESSION['plugin_monitoring']['service']['field'])) {
         foreach ($_SESSION['plugin_monitoring']['service']['field'] as $key=>$value) {
            $wheretmp = '';
            if (isset($_SESSION['plugin_monitoring']['service']['link'][$key])) {
               $wheretmp.= " ".$_SESSION['plugin_monitoring']['service']['link'][$key]." ";
            }

            $wheretmp .= Search::addWhere(
                                   "",
                                   0,
                                   "PluginMonitoringService",
                                   $_SESSION['plugin_monitoring']['service']['field'][$key],
                                   $_SESSION['plugin_monitoring']['service']['searchtype'][$key],
                                   $_SESSION['plugin_monitoring']['service']['contains'][$key]);
            if (!strstr($wheretmp, "``.``")) {
               if ($where != ''
                       AND !isset($_SESSION['plugin_monitoring']['service']['link'][$key])) {
                  $where .= " AND ";
               }
               $where .= $wheretmp;
            }
         }
      }

      if ($where != '') {
         $where = " WHERE ".$where;
         $where = str_replace("`".getTableForItemType("PluginMonitoringDisplay")."`.", 
                 "", $where);
         
      }
      $query = "SELECT * FROM `".getTableForItemType("PluginMonitoringService")."` ".$where."
         ORDER BY `name`";
      $result = $DB->query($query);
      
      $start = 0;
      if (isset($_GET["start"])) {
         $start = $_GET["start"];
      }
      
      $numrows = $DB->numrows($result);
      $parameters = '';
      
      printPager($_GET['start'], $numrows, $CFG_GLPI['root_doc']."/plugins/monitoring/front/display.php", $parameters);

      $limit = $numrows;
      if ($_SESSION["glpilist_limit"] < $numrows) {
         $limit = $_SESSION["glpilist_limit"];
      }
      $query .= " LIMIT ".intval($start)."," . intval($_SESSION['glpilist_limit']);
      
      $result = $DB->query($query);      
      if ($width == '') {
         echo "<table class='tab_cadrehov' style='width:100%;'>";
      } else {
         echo "<table class='tab_cadrehov' style='width:100%;'>";
      }
      
      echo "<tr class='tab_bg_1'>";
      echo "<th>";
      echo $LANG['joblist'][0];
      echo "</th>";
      echo "<th>";
      echo "</th>";
      echo "<th>";
      echo "</th>";
      echo "<th>";
      echo $LANG['state'][0];
      echo "</th>";
      echo "<th>";
      echo $LANG['stats'][7];
      echo "</th>";
      echo "<th>";
      echo $LANG['plugin_monitoring']['servicescatalog'][1];
      echo "</th>";
      echo "<th>";
      echo $LANG['plugin_monitoring']['service'][18];
      echo "</th>";
      echo "<th>";
      echo "</th>";     
      echo "</tr>";
      while ($data=$DB->fetch_array($result)) {
         echo "<tr class='tab_bg_3'>";

         $this->displayLine($data);
         
         echo "</tr>";         
      }
      echo "</table>";
      echo "<br/>";
      printPager($_GET['start'], $numrows, $CFG_GLPI['root_doc']."/plugins/monitoring/front/display.php", $parameters);

   }
   
   
   
   static function displayLine($data, $displayhost=1) {
      global $DB,$CFG_GLPI,$LANG;

      $pMonitoringService = new PluginMonitoringService();
      $pMonitoringServiceH = new PluginMonitoringService();
      $pMonitoringComponent = new PluginMonitoringComponent();
      $pmComponentscatalog_Host = new PluginMonitoringComponentscatalog_Host();
      
      $pMonitoringService->getFromDB($data['id']);
      
      echo "<td width='32' class='center'>";
      $shortstate = self::getState($data['state'], $data['state_type']);
      echo "<img src='".$CFG_GLPI['root_doc']."/plugins/monitoring/pics/box_".$shortstate."_32.png'/>";
      echo "</td>";
      if ($displayhost == '1') {
         $pmComponentscatalog_Host->getFromDB($data["plugin_monitoring_componentscatalogs_hosts_id"]);
         if (isset($pmComponentscatalog_Host->fields['itemtype']) 
                 AND $pmComponentscatalog_Host->fields['itemtype'] != '') {

            $itemtype = $pmComponentscatalog_Host->fields['itemtype'];
            $item = new $itemtype();
            $item->getFromDB($pmComponentscatalog_Host->fields['items_id']);
            echo "<td>";
            echo $item->getTypeName()." : ".$item->getLink();
            echo "</td>";

         } else {
            echo "<td>".$LANG['plugin_monitoring']['service'][0]."</td>";
         }
      }
      $pMonitoringComponent->getFromDB($data['plugin_monitoring_components_id']);
      echo "<td>".$pMonitoringComponent->getLink()."</td>";
      $nameitem = '';
      if (isset($itemmat->fields['name'])) {
         $nameitem = "[".$itemmat->getLink(1)."]";
      }
      //if ($pMonitoringService->fields['plugin_monitoring_services_id'] == '0') {
         //echo "<td>".$itemmat->getLink(1)."</td>";
//      } else {
//         $pMonitoringServiceH->getFromDB($pMonitoringService->fields['plugin_monitoring_services_id']);
//         $itemtypemat = $pMonitoringServiceH->fields['itemtype'];
//         $itemmat = new $itemtypemat();
//         $itemmat->getFromDB($pMonitoringServiceH->fields['items_id']);
//         echo "<td>".$pMonitoringService->getLink(1).$nameitem." ".$LANG['networking'][25]." ".$itemmat->getLink(1)."</td>";
//      }
      
      

      unset($itemmat);
      echo "<td class='center'>";
      echo $data['state'];
      echo "</td>";

      echo "<td class='center'>";
      $to = new PluginMonitoringRrdtool();
      $plu = new PluginMonitoringServiceevent();
      $img = '';

//      if ($to->displayGLPIGraph("PluginMonitoringService", $data['id'], "12h")) {
         if (file_exists(GLPI_ROOT."/files/_plugins/monitoring/PluginMonitoringService-".$data['id']."-12h.gif")) {
            $img = "<img src='".$CFG_GLPI['root_doc']."/plugins/monitoring/front/send.php?file=PluginMonitoringService-".$data['id']."-12h.gif'/>";
         }
        echo "<a href='".$CFG_GLPI['root_doc']."/plugins/monitoring/front/display.form.php?itemtype=PluginMonitoringService&items_id=".$data['id']."'>";
//      } else {
//         $img = '';
//      }
//      if ($img != '') {
         showToolTip($img, array('img'=>$CFG_GLPI['root_doc']."/plugins/monitoring/pics/stats_32.png"));
         echo "</a>";
//      }
      echo "</td>";

      // Mode dégradé
      echo "<td align='center'>";
//      // Get all services of this host
//      $a_serv = $pMonitoringComponent->find("`id`='".$data['plugin_monitoring_components_id']."'");
//      $globalserv_state = array();
//      $globalserv_state['red'] = 0;
//      $globalserv_state['red_soft'] = 0;
//      $globalserv_state['orange'] = 0;
//      $globalserv_state['orange_soft'] = 0;
//      $globalserv_state['green'] = 0;
//      $globalserv_state['green_soft'] = 0;
//      $tooltip = "<table class='tab_cadrehov' width='300'>";
//      $tooltip .= "<tr class='tab_bg_1'>
//         <td width='200'><strong>".$LANG['plugin_monitoring']['host'][8]."</strong> :</td><td>
//         <img src='".$CFG_GLPI['root_doc']."/plugins/monitoring/pics/box_".$shortstate."_32.png'/></td></tr>";
//      foreach ($a_serv as $sdata) {
////         $stateserv = self::getState($sdata['state'], $data['state_type']);
////         if (isset($globalserv_state[$stateserv])) {
////            $globalserv_state[$stateserv]++;
////         }
////         $tooltip .= "<tr class='tab_bg_1'><td>".$sdata['name']." :</td><td>
////                  <img src='".$CFG_GLPI['root_doc']."/plugins/monitoring/pics/box_".$stateserv."_32.png'/></td></tr>";
//      }
//      $tooltip .= "</table>";
//      if (!isset($globalserv_state[$shortstate])) {
//         $globalserv_state[$shortstate] = 0;
//      }
//      $globalserv_state[$shortstate]++;
//
//      $img = '';
//      if ($globalserv_state['red'] > 0) {
//         $img = $CFG_GLPI['root_doc']."/plugins/monitoring/pics/box_red_32.png";
//      } else if ($globalserv_state['red_soft'] > 0) {
//         $img = $CFG_GLPI['root_doc']."/plugins/monitoring/pics/box_red_32_soft.png";
//      } else if ($globalserv_state['orange'] > 0) {
//         $img = $CFG_GLPI['root_doc']."/plugins/monitoring/pics/box_orange_32.png";
//      } else if ($globalserv_state['orange_soft'] > 0) {
//         $img = $CFG_GLPI['root_doc']."/plugins/monitoring/pics/box_orange_32_soft.png";
//      } else if ($globalserv_state['green'] > 0) {
//         $img = $CFG_GLPI['root_doc']."/plugins/monitoring/pics/box_green_32.png";
//      } else if ($globalserv_state['green_soft'] > 0) {
//         $img = $CFG_GLPI['root_doc']."/plugins/monitoring/pics/box_green_32_soft.png";
//      }
//      showToolTip($tooltip, array('img'=>$img));
      echo "</td>";

      echo "<td>";
      echo convDate($data['last_check']).' '. substr($data['last_check'], 11, 8);
      echo "</td>";

      echo "<td>";
      echo $data['event'];
      echo "</td>";
   }

   
   static function getState($state, $state_type) {
      $shortstate = '';
      switch($state) {

         case 'UP':
         case 'OK':
            $shortstate = 'green';
            break;

         case 'DOWN':
         case 'UNREACHABLE':
         case 'CRITICAL':
         case 'DOWNTIME':
            $shortstate = 'red';
            break;

         case 'WARNING':
         case 'UNKNOWN':
         case 'RECOVERY':
         case 'FLAPPING':
         case '':
            $shortstate = 'orange';
            break;

      }
      if ($state_type == 'SOFT') {
         $shortstate.= '_soft';
      }
      return $shortstate;
   }
   
   
   
   function displayGraphs($itemtype, $items_id) {
      global $CFG_GLPI,$LANG;

      $to = new PluginMonitoringRrdtool();
      $plu = new PluginMonitoringServiceevent();
      $pmComponent = new PluginMonitoringComponent();

      $item = new $itemtype();
      $item->getFromDB($items_id);
 
      $pmComponent->getFromDB($item->fields['plugin_monitoring_components_id']);

      echo "<table class='tab_cadre_fixe'>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<th>";
      echo $item->getLink(1);
      echo "</th>";
      echo "<th width='200'>";
      echo "<form method='post'>";
      $a_timezones = PluginMonitoringConfig::getTimezones();
       if (!isset($_SESSION['plugin_monitoring_timezone'])) {
         $_SESSION['plugin_monitoring_timezone'] = '0';
      }
      Dropdown::showFromArray('plugin_monitoring_timezone', 
                              $a_timezones, 
                              array('value'=>$_SESSION['plugin_monitoring_timezone']));
      echo "&nbsp;<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit'>";
      echo "</form>";
      echo "</th>";
      echo "</tr>";

      $a_list = array();
      $a_list[] = "2h";
      $a_list[] = "12h";
      $a_list[] = "1d";
      $a_list[] = "1w";
      $a_list[] = "1m";
      $a_list[] = "0y6m";
      $a_list[] = "1y";
       
      foreach ($a_list as $time) {
      
      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='2'>";
      echo $time;
      echo "</th>";
      echo "</tr>";
         
         echo "<tr class='tab_bg_1'>";
         echo "<td align='center' colspan='2'>";
         $img = '';
         $timezone = '0';
         if (isset($_SESSION['plugin_monitoring_timezone'])) {
            $timezone = $_SESSION['plugin_monitoring_timezone'];
         }
         $timezone_file = str_replace("+", ".", $timezone);
         
         $to->displayGLPIGraph($pmComponent->fields['graph_template'], 
                               $itemtype, 
                               $items_id, 
                               $timezone, 
                               $time);
         $img = "<img src='".$CFG_GLPI['root_doc']."/plugins/monitoring/front/send.php?file=".$itemtype."-".$items_id."-".$time.$timezone_file.".gif'/>";
         echo $img;
         echo "</td>";
         echo "</tr>";
      }
      echo "</table>";
   }
   
   
   
   function displayCounters() {
      global $CFG_GLPI;
      
      $ok = countElementsInTable("glpi_plugin_monitoring_services", 
              "(`state`='OK' OR `state`='UP') AND `state_type`='HARD'");
      
      $warning = countElementsInTable("glpi_plugin_monitoring_services", 
              "(`state`='WARNING' OR `state`='UNKNOWN' OR `state`='RECOVERY' OR `state`='FLAPPING' OR `state` IS NULL)
                 AND `state_type`='HARD'");
      
      $critical = countElementsInTable("glpi_plugin_monitoring_services", 
              "(`state`='DOWN' OR `state`='UNREACHABLE' OR `state`='CRITICAL' OR `state`='DOWNTIME')
                 AND `state_type`='HARD'");
    
      $warning_soft = countElementsInTable("glpi_plugin_monitoring_services", 
              "(`state`='WARNING' OR `state`='UNKNOWN' OR `state`='RECOVERY' OR `state`='FLAPPING' OR `state` IS NULL)
                 AND `state_type`='SOFT'");
      
      $critical_soft = countElementsInTable("glpi_plugin_monitoring_services", 
              "(`state`='DOWN' OR `state`='UNREACHABLE' OR `state`='CRITICAL' OR `state`='DOWNTIME')
                 AND `state_type`='SOFT'");
      
      $ok_soft = countElementsInTable("glpi_plugin_monitoring_services", 
              "(`state`='OK' OR `state`='UP') AND `state_type`='SOFT'");
    
      echo "<table align='center'>";
      echo "<tr>";
      echo "<td>";
         echo "<table class='tab_cadre'>";
         echo "<tr class='tab_bg_1'>";
         echo "<th width='70'>";
         if ($critical > 0) {
            echo "<a href='".$CFG_GLPI['root_doc']."/plugins/monitoring/front/display.php?".
               "field[0]=3&searchtype[0]=contains&contains[0]=CRITICAL".
                  "&link[1]=OR&field[1]=3&searchtype[1]=contains&contains[1]=DOWN".
                  "&link[2]=OR&field[2]=3&searchtype[2]=contains&contains[2]=UNREACHABLE".
                  "&link[3]=AND&field[3]=3&searchtype[3]=contains&contains[3]=DOWNTIME".
                  "&itemtype=PluginMonitoringService&start=0&glpi_tab=2'>
               <img src='".$CFG_GLPI['root_doc']."/plugins/monitoring/pics/box_red_40.png'/>
                  </a>";
         }
         echo "</th>";
         echo "<th width='70'>";
         if ($warning > 0) {
            echo "<img src='".$CFG_GLPI['root_doc']."/plugins/monitoring/pics/box_orange_40.png'/>";
         }
         echo "</th>";
         echo "<th width='70'>";
         echo "<img src='".$CFG_GLPI['root_doc']."/plugins/monitoring/pics/box_green_40.png'/>";
         echo "</th>";
         echo "</tr>";

         echo "<th height='30'>";
         if ($critical > 0) {
            echo $critical;
         }
         echo "</th>";
         echo "<th>";
         if ($warning > 0) {
            echo $warning;
         }
         echo "</th>";
         echo "<th>";
         echo $ok;
         echo "</th>";
         echo "</tr>";      
         echo "</table>";
      echo "</td>";
      echo "<td width='100'>";
      
      echo "</td>";
      echo "<td>";
         echo "<table class='tab_cadre'>";
         echo "<tr class='tab_bg_1'>";
         echo "<th width='70' height='40'>";
         if ($critical_soft > 0) {
            echo "<img src='".$CFG_GLPI['root_doc']."/plugins/monitoring/pics/box_red_40_soft.png'/>";
         }
         echo "</th>";
         echo "<th width='70'>";
         if ($warning_soft > 0) {
            echo "<img src='".$CFG_GLPI['root_doc']."/plugins/monitoring/pics/box_orange_40_soft.png'/>";
         }
         echo "</th>";
         echo "<th width='70'>";
         if ($ok_soft > 0) {
            echo "<img src='".$CFG_GLPI['root_doc']."/plugins/monitoring/pics/box_green_40_soft.png'/>";
         }
         echo "</th>";
         echo "</tr>";
         echo "<th height='30'>";
         if ($critical_soft > 0) {
            echo $critical_soft;
         }
         echo "</th>";
         echo "<th>";
         if ($warning_soft > 0) {
            echo $warning_soft;
         }
         echo "</th>";
         echo "<th>";
         if ($ok_soft > 0) {
            echo $ok_soft;
         }
         echo "</th>";
         echo "</tr>";      
         echo "</table>";
      echo "</td>";
      echo "</tr>";
      echo "</table>";
      
   }

}

?>
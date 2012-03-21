<?php

/*
   ------------------------------------------------------------------------
   Plugin Monitoring for GLPI
   Copyright (C) 2011-2012 by the Plugin Monitoring for GLPI Development Team.

   https://forge.indepnet.net/projects/monitoring/
   ------------------------------------------------------------------------

   LICENSE

   This file is part of Plugin Monitoring project.

   Plugin Monitoring for GLPI is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   Plugin Monitoring for GLPI is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with Behaviors. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   Plugin Monitoring for GLPI
   @author    David Durieux
   @co-author 
   @comment   
   @copyright Copyright (c) 2011-2012 Plugin Monitoring for GLPI team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      https://forge.indepnet.net/projects/monitoring/
   @since     2011
 
   ------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginMonitoringShinken extends CommonDBTM {
   

   function generateConfig() {

      return true;
   }


   function constructFile($name, $array) {
      $config = '';
      $config .= "define ".$name."{\n";
      foreach ($array as $key => $value) {
         $c = 35;
         $c = $c - strlen($key);
         $config .= "       ".$key;
         for ($t=0; $t < $c; $t++) {
            $config .= " ";
         }
         $config .= $value."\n";
      }
      $config .= "}\n";
      $config .= "\n\n";
      return $config;
   }


   function generateCommandsCfg($file=0) {
      
      $pmCommand = new PluginMonitoringCommand();
      $pmNotificationcommand = new PluginMonitoringNotificationcommand();

      $a_commands = array();
      $i=0;

      $a_list = $pmCommand->find();
      $a_listnotif = $pmNotificationcommand->find();
      $a_list = array_merge($a_list, $a_listnotif);
      foreach ($a_list as $data) {
         if ($data['command_name'] != "bp_rule") {
            $a_commands[$i]['name'] = $data['name'];
            $a_commands[$i]['command_name'] = $data['command_name'];
            $a_commands[$i]['command_line'] = $data['command_line'];
            $i++;
         }
      }

      if ($file == "1") {
         $config = "# Generated by plugin monitoring for GLPI\n# on ".date("Y-m-d H:i:s")."\n\n";
         foreach ($a_commands as $data) {
            $config .= "# ".$data['name']."\n";
            unset($data['name']);
            $config .= $this->constructFile("command", $data);
         }
         return array('commands.cfg', $config);         
      } else {
         return $a_commands;
      }
   }


   
   function generateHostsCfg($file=0, $tag='') {
      global $DB;

      $pmCommand     = new PluginMonitoringCommand();
      $pmCheck       = new PluginMonitoringCheck();
      $pmComponent   = new PluginMonitoringComponent();
      $pmEntity      = new PluginMonitoringEntity();
      $pmHostconfig  = new PluginMonitoringHostconfig();
      $calendar      = new Calendar();
      $pmRealm       = new PluginMonitoringRealm();

      $a_hosts = array();
      $i=0;
      
      $a_entities_allowed = $pmEntity->getEntitiesByTag($tag);
      
      $command_ping = current($pmCommand->find("`command_name`='check_host_alive'", "", 1));
      $a_component = current($pmComponent->find("`plugin_monitoring_commands_id`='".$command_ping['id']."'", "", 1));

      $query = "SELECT * FROM `glpi_plugin_monitoring_componentscatalogs_hosts`
         GROUP BY `itemtype`, `items_id`";
      $result = $DB->query($query);
      while ($data=$DB->fetch_array($result)) {
         
         $classname = $data['itemtype'];
         $class = new $classname;
         if ($class->getFromDB($data['items_id'])) {
            
            if (isset($a_entities_allowed['-1'])
                    OR isset($a_entities_allowed[$class->fields['entities_id']])) {

               $a_hosts[$i]['host_name'] = $classname."-".$data['items_id']."-".preg_replace("/[^A-Za-z0-9]/","",$class->fields['name']);
               $a_hosts[$i]['alias'] = $a_hosts[$i]['host_name'];
               $ip = PluginMonitoringHostaddress::getIp($data['items_id'], $data['itemtype'], $class->fields['name']);

               $a_hosts[$i]['address'] = $ip;
               $a_hosts[$i]['parents'] = "";

               $a_fields = array();

               $a_fields = $a_component;

               $pmCommand->getFromDB($pmHostconfig->getValueAncestor('plugin_monitoring_commands_id', 
                                                                                    $class->fields['entities_id'],
                                                                                    $classname,
                                                                                    $class->getID()));
               $a_hosts[$i]['check_command'] = $pmCommand->fields['command_name'];
                  $pmCheck->getFromDB($pmHostconfig->getValueAncestor('plugin_monitoring_checks_id', 
                                                                                     $class->fields['entities_id'],
                                                                                     $classname,
                                                                                     $class->getID()));
               $a_hosts[$i]['check_interval'] = $pmCheck->fields['check_interval'];
               $a_hosts[$i]['retry_interval'] = $pmCheck->fields['retry_interval'];
               $a_hosts[$i]['max_check_attempts'] = $pmCheck->fields['max_check_attempts'];
               if ($calendar->getFromDB($pmHostconfig->getValueAncestor('calendars_id', 
                                                                        $class->fields['entities_id'],
                                                                        $classname,
                                                                        $class->getID()))) {
                  $a_hosts[$i]['check_period'] = $calendar->fields['name'];
               } else {
                  $a_hosts[$i]['check_period'] = "24x7";
               }

               $pmRealm->getFromDB($pmHostconfig->getValueAncestor('plugin_monitoring_realms_id', 
                                                                                    $class->fields['entities_id'],
                                                                                    $classname,
                                                                                    $class->getID()));
               $a_hosts[$i]['realm'] = $pmRealm->fields['name'];
               $a_hosts[$i]['contacts'] = '';
               $a_hosts[$i]['process_perf_data'] = '1';
               $a_hosts[$i]['notification_interval'] = '30';
               if ($calendar->getFromDB($a_fields['calendars_id'])) {
                  $a_hosts[$i]['notification_period'] = $calendar->fields['name'];
               } else {
                  $a_hosts[$i]['notification_period'] = "24x7";
               }
               $a_hosts[$i]['notification_options'] = 'd,u,r';
               $i++;
            }
         }
      }
      

      if ($file == "1") {
         $config = "# Generated by plugin monitoring for GLPI\n# on ".date("Y-m-d H:i:s")."\n\n";

         foreach ($a_hosts as $data) {
            $config .= $this->constructFile("host", $data);
         }
         return array('hosts.cfg', $config);

      } else {
         return $a_hosts;
      }
   }

   
   
   function generateServicesCfg($file=0, $tag='') {
      global $DB;

      $pMonitoringCommand      = new PluginMonitoringCommand();
      $pMonitoringCheck        = new PluginMonitoringCheck();
      $pmComponent             = new PluginMonitoringComponent();
      $pmEntity                = new PluginMonitoringEntity();
      $pmContact_Item          = new PluginMonitoringContact_Item();
      $calendar                = new Calendar();
      $user                    = new User();
      $pmLog                   = new PluginMonitoringLog();
      if (isset($_SERVER['HTTP_USER_AGENT'])
              AND strstr($_SERVER['HTTP_USER_AGENT'], 'xmlrpclib.py')) {
         if (!isset($_SESSION['glpi_currenttime'])) {
            $_SESSION['glpi_currenttime'] = date("Y-m-d H:i:s");
         }
         $input = array();
         $input['user_name'] = "Shinken";
         $input['action'] = "restart";
         $pmLog->add($input);
      }
      
      $hostnamebp = '';
      
      $a_services = array();
      $i=0;
      
      $a_entities_allowed = $pmEntity->getEntitiesByTag($tag);
      
      $query = "SELECT * FROM `glpi_plugin_monitoring_services`";
      $result = $DB->query($query);
      while ($data=$DB->fetch_array($result)) {
         $a_component = current($pmComponent->find("`id`='".$data['plugin_monitoring_components_id']."'", "", 1));
         $a_hostname = array();
         $queryh = "SELECT * FROM `glpi_plugin_monitoring_componentscatalogs_hosts` 
            WHERE `id` = '".$data['plugin_monitoring_componentscatalogs_hosts_id']."'
            LIMIT 1";
         $resulth = $DB->query($queryh);
         $hostname = '';
         $plugin_monitoring_componentscatalogs_id = 0;
         while ($datah=$DB->fetch_array($resulth)) {
            $itemtype = $datah['itemtype'];
            $item = new $itemtype();
            if ($item->getFromDB($datah['items_id'])) {
               if (isset($a_entities_allowed['-1'])
                       OR isset($a_entities_allowed[$item->fields['entities_id']])) {
               
                  $a_hostname[] = $itemtype."-".$datah['items_id']."-".preg_replace("/[^A-Za-z0-9]/","",$item->fields['name']);
                  $hostname = $item->fields['name'];
                  $plugin_monitoring_componentscatalogs_id = $datah['plugin_monitoring_componentscalalog_id'];
               }
            }
         }
         if (count($a_hostname) > 0) {
            if (isset($_SESSION['plugin_monitoring']['servicetemplates'][$a_component['id']])) {
               $a_services[$i]['use'] = $_SESSION['plugin_monitoring']['servicetemplates'][$a_component['id']];
            }         
            $a_services[$i]['host_name'] = implode(",", array_unique($a_hostname));
            $hostnamebp = $a_services[$i]['host_name']; // For business rules

            $a_services[$i]['service_description'] = preg_replace("/[^A-Za-z0-9]/","",$a_component['name'])."-".$data['id'];

            $pMonitoringCommand->getFromDB($a_component['plugin_monitoring_commands_id']);
            // Manage arguments
            $array = array();
            preg_match_all("/\\$(ARG\d+)\\$/", $pMonitoringCommand->fields['command_line'], $array);
            sort($array[0]);
            $a_arguments = importArrayFromDB($a_component['arguments']);
            $args = '';
            foreach ($array[0] as $arg) {
               if ($arg != '$PLUGINSDIR$'
                       AND $arg != '$HOSTADDRESS$'
                       AND $arg != '$MYSQLUSER$'
                       AND $arg != '$MYSQLPASSWORD$') {
                  $arg = str_replace('$', '', $arg);
                  if (!isset($a_arguments[$arg])) {
                     $args .= '!';
                  } else {
                     if (strstr($a_arguments[$arg], "[[HOSTNAME]]")) {
                        $a_arguments[$arg] = str_replace("[[HOSTNAME]]", $hostname, $a_arguments[$arg]);
                     } else if (strstr($a_arguments[$arg], "[")) {
                        $a_arguments[$arg] = PluginMonitoringService::convertArgument($data['id'], $a_arguments[$arg]);
                     }
                     $args .= '!'.$a_arguments[$arg];
                     if ($a_arguments[$arg] == ''
                             AND $a_component['alias_command'] != '') {
                        $args .= $a_component['alias_command'];
                     }
                  }
               }
            }
            // End manage arguments
            if ($a_component['remotesystem'] == 'nrpe') {
               if ($a_component['alias_command'] != '') {
                  $a_services[$i]['check_command'] = "check_nrpe!".$a_component['alias_command'];
               } else {
                  $a_services[$i]['check_command'] = "check_nrpe!".$pMonitoringCommand->fields['command_name'];
               }
            } else {
               $a_services[$i]['check_command'] = $pMonitoringCommand->fields['command_name'].$args;
            }
               $a_contacts = array();
               $a_list_contact = $pmContact_Item->find("`itemtype`='PluginMonitoringComponentscatalog'
                  AND `items_id`='".$plugin_monitoring_componentscatalogs_id."'");
               foreach ($a_list_contact as $data_contact) {
//                  $pmContact->getFromDB($data_contact['plugin_monitoring_contacts_id']);
                  $user->getFromDB($data_contact['users_id']);
                  $a_contacts[] = $user->fields['name'];
               }
            $a_services[$i]['contacts'] = implode(',', $a_contacts);

            // ** If shinken not use templates or template not defined : 
            if (isset($_SESSION['plugin_monitoring']['servicetemplates'][$a_component['id']])) {
                  $pMonitoringCheck->getFromDB($a_component['plugin_monitoring_checks_id']);
               $a_services[$i]['check_interval'] = $pMonitoringCheck->fields['check_interval'];
               $a_services[$i]['retry_interval'] = $pMonitoringCheck->fields['retry_interval'];
               $a_services[$i]['max_check_attempts'] = $pMonitoringCheck->fields['max_check_attempts'];
               if ($calendar->getFromDB($a_component['calendars_id'])) {
                  $a_services[$i]['check_period'] = $calendar->fields['name'];            
               }
               $a_services[$i]['notification_interval'] = '30';
               if ($calendar->getFromDB($a_component['calendars_id'])) {
                  $a_services[$i]['notification_period'] = $calendar->fields['name'];
               } else {
                  $a_services[$i]['notification_period'] = "24x7";
               }
               $a_services[$i]['notification_options'] = 'w,c,r';
               $a_services[$i]['active_checks_enabled'] = '1';
               $a_services[$i]['process_perf_data'] = '1';
               $a_services[$i]['active_checks_enabled'] = '1';
               $a_services[$i]['passive_checks_enabled'] = '1';
               $a_services[$i]['parallelize_check'] = '1';
               $a_services[$i]['obsess_over_service'] = '1';
               $a_services[$i]['check_freshness'] = '1';
               $a_services[$i]['freshness_threshold'] = '1';
               $a_services[$i]['notifications_enabled'] = '1';
               $a_services[$i]['event_handler_enabled'] = '0';
               $a_services[$i]['event_handler'] = 'super_event_kill_everyone!DIE';
               $a_services[$i]['flap_detection_enabled'] = '1';
               $a_services[$i]['failure_prediction_enabled'] = '1';
               $a_services[$i]['retain_status_information'] = '1';
               $a_services[$i]['retain_nonstatus_information'] = '1';
               $a_services[$i]['is_volatile'] = '0';
               $a_services[$i]['_httpstink'] = 'NO';
            }
            
            $i++;
         }
      }

//      // Business rules....
      $pmService = new PluginMonitoringService();
      $pmServicescatalog = new PluginMonitoringServicescatalog();
      $pMonitoringBusinessrulegroup = new PluginMonitoringBusinessrulegroup();
      $pmBusinessrule = new PluginMonitoringBusinessrule();
      $pmComponentscatalog_Host = new PluginMonitoringComponentscatalog_Host();
      
      $a_listBA = $pmServicescatalog->find();
      foreach ($a_listBA as $dataBA) {

         if (isset($a_entities_allowed['-1'])
                 OR isset($a_entities_allowed[$dataBA['entities_id']])) {

            $a_grouplist = $pMonitoringBusinessrulegroup->find("`plugin_monitoring_servicescatalogs_id`='".$dataBA['id']."'");
            $a_group = array();
            foreach ($a_grouplist as $gdata) {
               $a_listBR = $pmBusinessrule->find(
                       "`plugin_monitoring_businessrulegroups_id`='".$gdata['id']."'");
               foreach ($a_listBR as $dataBR) {
                  if ($pmService->getFromDB($dataBR['plugin_monitoring_services_id'])) {
                     $pmComponentscatalog_Host->getFromDB($pmService->fields['plugin_monitoring_componentscatalogs_hosts_id']);
                     $itemtype = $pmComponentscatalog_Host->fields['itemtype'];
                     $item = new $itemtype();
                     if ($item->getFromDB($pmComponentscatalog_Host->fields['items_id'])) {           
                        $hostname = $itemtype."-".$pmComponentscatalog_Host->fields['items_id']."-".preg_replace("/[^A-Za-z0-9]/","",$item->fields['name']);

                        if ($gdata['operator'] == 'and'
                                OR $gdata['operator'] == 'or'
                                OR strstr($gdata['operator'], ' of:')) {

                           $operator = '|';
                           if ($gdata['operator'] == 'and') {
                              $operator = '&';
                           }
                           if (!isset($a_group[$gdata['id']])) {
                              $a_group[$gdata['id']] = '';
                              if (strstr($gdata['operator'], ' of:')) {
                                 $a_group[$gdata['id']] = $gdata['operator'];
                              }
                              $a_group[$gdata['id']] .= $hostname.",".preg_replace("/[^A-Za-z0-9]/","",$pmService->fields['name'])."-".$pmService->fields['id'];
                           } else {
                              $a_group[$gdata['id']] .= $operator.$hostname.",".preg_replace("/[^A-Za-z0-9]/","",$pmService->fields['name'])."-".$pmService->fields['id'];
                           }
                        } else {
                           $a_group[$gdata['id']] = $gdata['operator']." ".$hostname.",".preg_replace("/[^A-Za-z0-9]/","",$item->getName())."-".$item->fields['id'];
                        }
                     }
                  }
               }
            }
            if (count($a_group) > 0) {
               $pMonitoringCheck->getFromDB($dataBA['plugin_monitoring_checks_id']);
               $a_services[$i]['check_interval'] = $pMonitoringCheck->fields['check_interval'];
               $a_services[$i]['retry_interval'] = $pMonitoringCheck->fields['retry_interval'];
               $a_services[$i]['max_check_attempts'] = $pMonitoringCheck->fields['max_check_attempts'];
               if ($calendar->getFromDB($dataBA['calendars_id'])) {
                  $a_services[$i]['check_period'] = $calendar->fields['name'];            
               }
               $a_services[$i]['host_name'] = $hostnamebp;
               $a_services[$i]['service_description'] = preg_replace("/[^A-Za-z0-9]/","",$dataBA['name'])."-".$dataBA['id']."-businessrules";
               $command = "bp_rule!";

               foreach ($a_group as $key=>$value) {
                  if (!strstr($value, "&")
                          AND !strstr($value, "|")) {
                     $a_group[$key] = trim($value);
                  } else {
                     $a_group[$key] = "(".trim($value).")";
                  }
               }
               $a_services[$i]['check_command'] = $command.implode("&", $a_group);
               $a_services[$i]['notification_interval'] = '30';
               if ($calendar->getFromDB($dataBA['calendars_id'])) {
                  $a_services[$i]['notification_period'] = $calendar->fields['name'];
               } else {
                  $a_services[$i]['notification_period'] = "24x7";
               }
               $a_services[$i]['notification_options'] = 'w,c,r';
               $a_services[$i]['active_checks_enabled'] = '1';
               $a_services[$i]['process_perf_data'] = '1';
               $a_services[$i]['active_checks_enabled'] = '1';
               $a_services[$i]['passive_checks_enabled'] = '1';
               $a_services[$i]['parallelize_check'] = '1';
               $a_services[$i]['obsess_over_service'] = '1';
               $a_services[$i]['check_freshness'] = '1';
               $a_services[$i]['freshness_threshold'] = '1';
               $a_services[$i]['notifications_enabled'] = '1';
               $a_services[$i]['event_handler_enabled'] = '0';
               $a_services[$i]['event_handler'] = 'super_event_kill_everyone!DIE';
               $a_services[$i]['flap_detection_enabled'] = '1';
               $a_services[$i]['failure_prediction_enabled'] = '1';
               $a_services[$i]['retain_status_information'] = '1';
               $a_services[$i]['retain_nonstatus_information'] = '1';
               $a_services[$i]['is_volatile'] = '0';
               $a_services[$i]['_httpstink'] = 'NO';
               $a_services[$i]['contacts'] = '';
               $i++;
            }
         }
      }
      
      if ($file == "1") {
         $config = "# Generated by plugin monitoring for GLPI\n# on ".date("Y-m-d H:i:s")."\n\n";

         foreach ($a_services as $data) {
            $config .= $this->constructFile("service", $data);
         }
         return array('services.cfg', $config);

      } else {
         return $a_services;
      }
   }

   
   
   function generateTemplatesCfg($file=0, $tag='') {
      global $DB;
      
      $pMonitoringCheck = new PluginMonitoringCheck();
      $calendar         = new Calendar();
      
      $a_servicetemplates = array();
      $i=0;
      $a_templatesdef = array();
      
      $query = "SELECT * FROM `glpi_plugin_monitoring_components`
         GROUP BY `plugin_monitoring_checks_id`, `active_checks_enabled`, 
            `passive_checks_enabled`, `calendars_id`
         ORDER BY `id`";
      $result = $DB->query($query);
      while ($data=$DB->fetch_array($result)) {

         $a_servicetemplates[$i]['name'] = 'template'.$data['id'].'-service';
            $pMonitoringCheck->getFromDB($data['plugin_monitoring_checks_id']);
         $a_servicetemplates[$i]['check_interval'] = $pMonitoringCheck->fields['check_interval'];
         $a_servicetemplates[$i]['retry_interval'] = $pMonitoringCheck->fields['retry_interval'];
         $a_servicetemplates[$i]['max_check_attempts'] = $pMonitoringCheck->fields['max_check_attempts'];
         if ($calendar->getFromDB($data['calendars_id'])) {
            $a_servicetemplates[$i]['check_period'] = $calendar->fields['name'];            
         }
         $a_servicetemplates[$i]['notification_interval'] = '30';
         if ($calendar->getFromDB($data['calendars_id'])) {
            $a_servicetemplates[$i]['notification_period'] = $calendar->fields['name'];
         } else {
            $a_servicetemplates[$i]['notification_period'] = "24x7";
         }
         $a_servicetemplates[$i]['notification_options'] = 'w,c,r';
         $a_servicetemplates[$i]['active_checks_enabled'] = '1';
         $a_servicetemplates[$i]['process_perf_data'] = '1';
         $a_servicetemplates[$i]['active_checks_enabled'] = '1';
         $a_servicetemplates[$i]['passive_checks_enabled'] = '1';
         $a_servicetemplates[$i]['parallelize_check'] = '1';
         $a_servicetemplates[$i]['obsess_over_service'] = '1';
         $a_servicetemplates[$i]['check_freshness'] = '1';
         $a_servicetemplates[$i]['freshness_threshold'] = '1';
         $a_servicetemplates[$i]['notifications_enabled'] = '1';
         $a_servicetemplates[$i]['event_handler_enabled'] = '0';
         $a_servicetemplates[$i]['event_handler'] = 'super_event_kill_everyone!DIE';
         $a_servicetemplates[$i]['flap_detection_enabled'] = '1';
         $a_servicetemplates[$i]['failure_prediction_enabled'] = '1';
         $a_servicetemplates[$i]['retain_status_information'] = '1';
         $a_servicetemplates[$i]['retain_nonstatus_information'] = '1';
         $a_servicetemplates[$i]['is_volatile'] = '0';
         $a_servicetemplates[$i]['_httpstink'] = 'NO';
         $a_servicetemplates[$i]['register'] = '0';
                  
         $queryc = "SELECT * FROM `glpi_plugin_monitoring_components`
            WHERE `plugin_monitoring_checks_id`='".$data['plugin_monitoring_checks_id']."'  
               AND `active_checks_enabled`='".$data['active_checks_enabled']."' 
               AND `passive_checks_enabled`='".$data['passive_checks_enabled']."'
               AND `calendars_id`='".$data['calendars_id']."'";
         $resultc = $DB->query($queryc);
         while ($datac=$DB->fetch_array($resultc)) {
            $a_templatesdef[$datac['id']] = $a_servicetemplates[$i]['name'];
         }
         $i++;
      }
      $_SESSION['plugin_monitoring']['servicetemplates'] = $a_templatesdef;
      if ($file == "1") {
         $config = "# Generated by plugin monitoring for GLPI\n# on ".date("Y-m-d H:i:s")."\n\n";

         foreach ($a_servicetemplates as $data) {
            $config .= $this->constructFile("service", $data);
         }
         return array('servicetemplates.cfg', $config);

      } else {
         return $a_servicetemplates;
      }
   }


   

   function generateContactsCfg($file=0) {
      global $DB;
      
      $a_contacts = array();
      $i=0;

      $query = "SELECT * FROM `glpi_plugin_monitoring_contacts_items`";
      $result = $DB->query($query);
      $a_users_used = array();
      while ($data=$DB->fetch_array($result)) {
         if ($data['users_id'] > 0) {
            if ((!isset($a_users_used[$data['users_id']]))) {
               $a_contacts = $this->_addContactUser($a_contacts, $data['users_id'], $i);
               $i++;  
               $a_users_used[$data['users_id']] = 1;
            }
         } else if ($data['groups_id'] > 0) {
            $queryg = "SELECT * FROM `glpi_groups_users`
               WHERE `groups_id`='".$data['groups_id']."'";
            $resultg = $DB->query($queryg);
            while ($datag=$DB->fetch_array($resultg)) {
               if ((!isset($a_users_used[$datag['users_id']]))) {
                  $a_contacts = $this->_addContactUser($a_contacts, $datag['users_id'], $i);
                  $i++;
                  $a_users_used[$data['users_id']] = 1;
               }
            }
         }        
      
      }

      if ($file == "1") {
         $config = "# Generated by plugin monitoring for GLPI\n# on ".date("Y-m-d H:i:s")."\n\n";

         foreach ($a_contacts as $data) {
            $config .= $this->constructFile("contact", $data);
         }
         return array('contacts.cfg', $config);

      } else {
         return $a_contacts;
      }
   }
   
   
   
   function _addContactUser($a_contacts, $users_id, $i) {
      
      $pmContact             = new PluginMonitoringContact();
      $pmNotificationcommand = new PluginMonitoringNotificationcommand();
      $pmContacttemplate = new PluginMonitoringContacttemplate();
      $user     = new User();
      $calendar = new Calendar();
      
      $user->getFromDB($users_id);
      
      // Get template
      $a_pmcontact = current($pmContact->find("`users_id`='".$users_id."'", "", 1));
      if (empty($a_pmcontact) OR 
              (isset($a_pmcontact['plugin_monitoring_contacttemplates_id'])
              AND $a_pmcontact['plugin_monitoring_contacttemplates_id'] == '0')) {
         $a_pmcontact = current($pmContacttemplate->find("`is_default`='1'", "", 1));
      } else {
         $a_pmcontact = current($pmContacttemplate->find("`id`='".$a_pmcontact['plugin_monitoring_contacttemplates_id']."'", "", 1));
      }     
      $a_contacts[$i]['contact_name'] = $user->fields['name'];
      $a_contacts[$i]['alias'] = $user->getName();
      $a_contacts[$i]['host_notifications_enabled'] = $a_pmcontact['host_notifications_enabled'];
      $a_contacts[$i]['service_notifications_enabled'] = $a_pmcontact['service_notifications_enabled'];
         $calendar->getFromDB($a_pmcontact['service_notification_period']);
      $a_contacts[$i]['service_notification_period'] = $calendar->fields['name'];
         $calendar->getFromDB($a_pmcontact['host_notification_period']);
      $a_contacts[$i]['host_notification_period'] = $calendar->fields['name'];
         $a_servicenotif = array();
         if ($a_pmcontact['service_notification_options_w'] == '1')
            $a_servicenotif[] = "w";
         if ($a_pmcontact['service_notification_options_u'] == '1')
            $a_servicenotif[] = "u";
         if ($a_pmcontact['service_notification_options_c'] == '1')
            $a_servicenotif[] = "c";
         if ($a_pmcontact['service_notification_options_r'] == '1')
            $a_servicenotif[] = "r";
         if ($a_pmcontact['service_notification_options_f'] == '1')
            $a_servicenotif[] = "f";
         if ($a_pmcontact['service_notification_options_n'] == '1')
            $a_servicenotif = array("n");
         if (count($a_servicenotif) == "0")
            $a_servicenotif = array("n");
      $a_contacts[$i]['service_notification_options'] = implode(",", $a_servicenotif);
         $a_hostnotif = array();
         if ($a_pmcontact['host_notification_options_d'] == '1')
            $a_hostnotif[] = "d";
         if ($a_pmcontact['host_notification_options_u'] == '1')
            $a_hostnotif[] = "u";
         if ($a_pmcontact['host_notification_options_r'] == '1')
            $a_hostnotif[] = "r";
         if ($a_pmcontact['host_notification_options_f'] == '1')
            $a_hostnotif[] = "f";
         if ($a_pmcontact['host_notification_options_s'] == '1')
            $a_hostnotif[] = "s";
         if ($a_pmcontact['host_notification_options_n'] == '1')
            $a_hostnotif = array("n");
         if (count($a_hostnotif) == "0")
            $a_hostnotif = array("n");
      $a_contacts[$i]['host_notification_options'] = implode(",", $a_hostnotif);
         $pmNotificationcommand->getFromDB($a_pmcontact['service_notification_commands']);
      $a_contacts[$i]['service_notification_commands'] = $pmNotificationcommand->fields['command_name'];
         $pmNotificationcommand->getFromDB($a_pmcontact['host_notification_commands']);
      $a_contacts[$i]['host_notification_commands'] = $pmNotificationcommand->fields['command_name'];
      $a_contacts[$i]['email'] = $user->fields['email'];
      $a_contacts[$i]['pager'] = $user->fields['phone'];
      return $a_contacts;
   }



   function generateTimeperiodsCfg($file=0) {

      $calendar = new Calendar();
      $calendarSegment = new CalendarSegment();

      $a_timeperiods = array();
      $i=0;
      
      $a_listcalendar = $calendar->find();
      foreach ($a_listcalendar as $datacalendar) {
         $a_timeperiods[$i]['timeperiod_name'] = $datacalendar['name'];
         $a_timeperiods[$i]['alias'] = $datacalendar['name'];
         $a_listsegment = $calendarSegment->find("`calendars_id`='".$datacalendar['id']."'");
         foreach ($a_listsegment as $datasegment) {
            $begin = preg_replace("/:00$/", "", $datasegment['begin']);
            $end = preg_replace("/:00$/", "", $datasegment['end']);
            $day = "";
            switch ($datasegment['day']) {

               case "0":
                  $day = "sunday";
                  break;

               case "1":
                  $day = "monday";
                  break;

               case "2":
                  $day = "tuesday";
                  break;

               case "3":
                  $day = "wednesday";
                  break;

               case "4":
                  $day = "thursday";
                  break;

               case "5":
                  $day = "friday";
                  break;

               case "6":
                  $day = "saturday";
                  break;

            }
            $a_timeperiods[$i][$day] = $begin."-".$end;
         }
//         if (!isset($a_timeperiods[$i]["sunday"])) {
//            $a_timeperiods[$i]["sunday"]= '';
//         }
//         if (!isset($a_timeperiods[$i]["monday"])) {
//            $a_timeperiods[$i]["monday"]= '';
//         }
//         if (!isset($a_timeperiods[$i]["tuesday"])) {
//            $a_timeperiods[$i]["tuesday"]= '';
//         }
//         if (!isset($a_timeperiods[$i]["wednesday"])) {
//            $a_timeperiods[$i]["wednesday"]= '';
//         }
//         if (!isset($a_timeperiods[$i]["thursday"])) {
//            $a_timeperiods[$i]["thursday"]= '';
//         }
//         if (!isset($a_timeperiods[$i]["friday"])) {
//            $a_timeperiods[$i]["friday"]= '';
//         }
//         if (!isset($a_timeperiods[$i]["saturday"])) {
//            $a_timeperiods[$i]["saturday"]= '';
//         }
         $i++;
      }

      if ($file == "1") {
         $config = "# Generated by plugin monitoring for GLPI\n# on ".date("Y-m-d H:i:s")."\n\n";

         foreach ($a_timeperiods as $data) {
            $config .= $this->constructFile("timeperiod", $data);
         }
         return array('timeperiods.cfg', $config);

      } else {
         return $a_timeperiods;
      }
   }


}

?>

<?php

/*
   ------------------------------------------------------------------------
   Plugin Monitoring for GLPI
   Copyright (C) 2011-2015 by the Plugin Monitoring for GLPI Development Team.

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
   along with Monitoring. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   Plugin Monitoring for GLPI
   @author    David Durieux
   @co-author
   @comment
   @copyright Copyright (c) 2011-2015 Plugin Monitoring for GLPI team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      https://forge.indepnet.net/projects/monitoring/
   @since     2011

   ------------------------------------------------------------------------
 */

include ("../../../inc/includes.php");

Session::checkCentralAccess();

Html::header(__('Monitoring', 'monitoring'), $_SERVER["PHP_SELF"], "plugins",
             "monitoring", "display");

$pmDisplay = new PluginMonitoringDisplay();
$pmDisplayview = new PluginMonitoringDisplayview();
$pmDisplayview_item = new PluginMonitoringDisplayview_item();
$pmMessage = new PluginMonitoringMessage();

$pmMessage->getMessages();

$pmDisplay->menu();

$pass = 0;
$a_views = $pmDisplayview->getViews();
if (isset($a_views[$_GET['id']])) {
   $pmDisplayview->getFromDB($_GET['id']);
   $pass = $pmDisplayview->haveVisibilityAccess();
}
if ($pass == 0) {
   PluginMonitoringProfile::checkRight("config_views", 'r');
}

if (isset($a_views[$_GET['id']])) {
   $pmDisplayview_item->view($_GET['id']);
} else {
   Html::displayRightError();
}
Html::footer();
?>
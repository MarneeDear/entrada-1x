<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Primary controller file for the Events module.
 * /admin/events
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/
require_once("Entrada/gradebook/handlers.inc.php");

if(!defined("PARENT_INCLUDED")) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed($MODULES[strtolower($MODULE)]["resource"], $MODULES[strtolower($MODULE)]["permission"], false)) {
	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	define("IN_GRADEBOOK",	true);
	
	/*$JQUERY[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.modal.js\"></script>\n";*/
	/*$JQUERY[] = "<link href=\"".ENTRADA_URL."/css/jquery/flexigrid.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";*/
	/*$JQUERY[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/flexigrid.js\"></script>\n";*/
	$JQUERY[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.editable.js\"></script>\n";
	$JQUERY[] = "<script type=\"text/javascript\">var ENTRADA_URL = '".ENTRADA_URL."';</script>";
	$JQUERY[] = "<link rel=\"stylesheet\" href=\"".  ENTRADA_URL ."/css/". $MODULE ."/". $MODULE .".css\" />";
	$ASSESSMENT_TYPES = array("Formative", "Summative");
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/courses", "title" => $translate->_("Courses"));

	if (($router) && ($router->initRoute())) {
		$PREFERENCES = preferences_load($MODULE);

		if (isset($_GET["id"]) && ($tmp_input = clean_input($_GET["id"], array("nows", "int")))) {
			$COURSE_ID = $tmp_input;
		} else {
			$COURSE_ID = 0;
		}
		
		?>
        <div class="alert alert-info">
            <p class="lead">This feature is available in Entrada ME Consortium Edition only at this time. Please feel free to <a href="http://www.entrada.org/contact" target="_blank"><strong>contact us</strong></a> to arrange a demo.</p>
            <p class="pull-right">
                <a class="btn btn-primary btn-large" href="http://www.entrada.org" target="_blank">
                    <i class="fa fa-info-circle"></i> Learn More
                </a>
            </p>
            <div class="clearfix"></div>
        </div>
        <?php
		/**
		 * Check if preferences need to be updated on the server at this point.
		 */
		preferences_update($MODULE, $PREFERENCES);
	}
}
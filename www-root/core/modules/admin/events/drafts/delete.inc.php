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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_EVENTS"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->amIAllowed('event', 'delete', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["group"]."] and role [".$_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[]	= array("url" => "", "title" => "Delete Drafts");

	echo "<h1>Delete Drafts</h1>";

	$DRAFT_IDS = array();

	// Error Checking
	switch($STEP) {
		case 2 :
		case 1 :
		default :
			if(((!isset($_POST["checked"])) || (!is_array($_POST["checked"])) || (!@count($_POST["checked"]))) && (!isset($_GET["id"]) || !$_GET["id"])) {
				header("Location: ".ENTRADA_URL."/admin/events/drafts");
				exit;
			} else {
				if ((isset($_POST["checked"])) && (is_array($_POST["checked"])) && (@count($_POST["checked"]))) {
					foreach($_POST["checked"] as $draft_id) {
						$draft_id = (int) trim($draft_id);
						if($draft_id) {
							$DRAFT_IDS[] = $draft_id;
						}
					}
				} elseif (isset($_GET["id"]) && ($draft_id = clean_input($_GET["id"], array("trim", "int")))) {
					$DRAFT_IDS[] = $draft_id;
				}

				if(!@count($DRAFT_IDS)) {
					$ERROR++;
					$ERRORSTR[] = "There were no valid draft identifiers provided to delete. Please ensure that you access this section through the event index.";
				}
			}

			if($ERROR) {
				$STEP = 1;
			}
		break;
	}

	// Display Page
	switch($STEP) {
		case 2 :
			$removed = array();
			$query = "SELECT `draft_id`, `name` AS `draft_title` FROM `drafts` WHERE `draft_id` IN (".implode(', ', $DRAFT_IDS).")";
			$drafts = $db->GetAssoc($query);
			foreach($DRAFT_IDS as $draft_id) {
				$allow_removal = false;
				
				if($draft_id = (int) $draft_id) {
					// delete the draft audience, contacts, eventtypes, events, creators, and drafts
					
					$query = "	SELECT `devent_id` 
								FROM `draft_events` AS a
								WHERE a.`draft_id` = ".$db->qstr($draft_id);
					$draft_events = $db->GetAll($query);
					
					foreach ($draft_events as $draft_event) {
						$devents[] = $draft_event["devent_id"];
					}
					
					$query = "	DELETE FROM `draft_audience` WHERE `devent_id` IN ('".implode("', '", $devents)."')";
					$db->Execute($query);
					
					$query = "	DELETE FROM `draft_contacts` WHERE `devent_id` IN ('".implode("', '", $devents)."')";
					$db->Execute($query);
					
					$query = "	DELETE FROM `draft_eventtypes` WHERE `devent_id` IN ('".implode("', '", $devents)."')";
					$db->Execute($query);
					
					$query = "	DELETE FROM `draft_events` WHERE `draft_id` = ".$db->qstr($draft_id);
					$db->Execute($query);
					
					$query = "	DELETE FROM `draft_creators` WHERE `draft_id` = ".$db->qstr($draft_id);
					$db->Execute($query);
					
					$query = "	DELETE FROM `drafts` WHERE `draft_id` = ".$db->qstr($draft_id);
					if ($db->Execute($query)) {
						$removed[$draft_id]["draft_title"] = $drafts[$draft_id];
					}
				}
			}

			$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/events/drafts\\'', 5000)";

			if($total_removed = @count($removed)) {
				$SUCCESS++;
				$SUCCESSSTR[$SUCCESS]  = "You have successfully removed ".$total_removed." drafts".(($total_removed != 1) ? "s" : "")." from the system:";
				$SUCCESSSTR[$SUCCESS] .= "<div style=\"padding-left: 15px; padding-bottom: 15px; font-family: monospace\">\n";
				foreach($removed as $result) {
					$SUCCESSSTR[$SUCCESS] .= html_encode($result["draft_title"])."<br />";
				}
				$SUCCESSSTR[$SUCCESS] .= "</div>\n";
				$SUCCESSSTR[$SUCCESS] .= "You will be automatically redirected to the event index in 5 seconds, or you can <a href=\"".ENTRADA_URL."/admin/events\">click here</a> if you do not wish to wait.";

				echo display_success();

				application_log("success", "Successfully removed draft ids: ".implode(", ", $DRAFT_IDS));
			} else {
				$ERROR++;
				$ERRORSTR[] = "Unable to remove the requested drafts from the system.<br /><br />The system administrator has been informed of this issue and will address it shortly; please try again later.";

				application_log("error", "Failed to remove draft from the remove request. Database said: ".$db->ErrorMsg());
			}

			if ($ERROR) {
				echo display_error();
			}
		break;
		case 1 :
		default :
			if($ERROR) {
				echo display_error();
			} else {
				
				$total_events	= count($DRAFT_IDS);
				
				$query		= "	SELECT *
								FROM `drafts`
								WHERE `draft_id` IN ('".implode("', '", $DRAFT_IDS)."')";
				$results	= $db->GetAll($query);
								
				if($results) {
					echo display_notice(array("Please review the following draft".(($total_events > 1) ? "s" : "")." to ensure that you wish to <strong>permanently delete</strong> ".(($total_events != 1) ? "them" : "it").".<br /><br />This will also remove any attached draft events and this action cannot be undone."));
					?>
					<form action="<?php echo ENTRADA_URL; ?>/admin/events/drafts?section=delete&amp;step=2" method="post">
					<table class="tableList" widht="100%" cellspacing="0" summary="List of Events">
					<colgroup>
						<col class="modified" />
						<col class="date" />
						<col class="title" />
						<col class="description" />
					</colgroup>
					<thead>
						<tr>
							<td class="modified" style="font-size: 12px">&nbsp;</td>
							<td class="date sortedASC" style="font-size: 12px"><div class="noLink">Creation Date &amp; Time</div></td>
							<td class="title" style="font-size: 12px">Draft Title</td>
							<td class="description" style="font-size: 12px">Description</td>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<td></td>
							<td colspan="3" style="padding-top: 10px">
								<input type="submit" class="button" value="Confirm Removal" />
							</td>
						</tr>
					</tfoot>
					<tbody>
						<?php
						foreach($results as $result) {
							$url			= "";
							$accessible		= true;
							$administrator	= true; // NEEDS TO BE FIXED

							/*if($ENTRADA_ACL->amIAllowed(new EventResource($result["event_id"], $result["course_id"], $result["organisation_id"]), 'delete')) {
								$administrator = true;
							} else {
								if((($result["release_date"]) && ($result["release_date"] > time())) || (($result["release_until"]) && ($result["release_until"] < time()))) {
									$accessible = false;
								}
							}*/
			
							if($administrator) {
								$url 	= ENTRADA_URL."/admin/events/drafts?section=edit&amp;draft_id=".$result["draft_id"];
								
								echo "<tr id=\"draft-".$result["draft_id"]."\" class=\"event".((!$url) ? " np" : ((!$accessible) ? " na" : ""))."\">\n";
								echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"checked[]\" value=\"".$result["draft_id"]."\" checked=\"checked\" /></td>\n";
								echo "	<td class=\"date".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Draft Creation Date\">" : "").date(DEFAULT_DATE_FORMAT, $result["created"]).(($url) ? "</a>" : "")."</td>\n";
								echo "	<td class=\"title".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Draft Title: ".html_encode($result["name"])."\">" : "").html_encode($result["name"]).(($url) ? "</a>" : "")."</td>\n";
								echo "	<td class=\"description".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Draft Description: ".html_encode($result["description"])."\">" : "").html_encode($result["description"]).(($url) ? "</a>" : "")."</td>\n";
								echo "</tr>\n";
							}
						}
						?>
					</tbody>
					</table>
					</form>
					<?php
				} else {
					application_log("error", "The confirmation of removal query returned no results... curious Database said: ".$db->ErrorMsg());
					header("Location: ".ENTRADA_URL."/admin/events/drafts");
					exit;	
				}
			}
		break;
	}
}
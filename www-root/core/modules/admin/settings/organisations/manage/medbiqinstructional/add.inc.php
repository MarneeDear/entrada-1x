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
 * @author Unit: MEdTech Unit
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @copyright Copyright 2011 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_MEDBIQINSTRUCTIONAL"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "create",false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["group"]."] and role [".$_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/eventtypes_list.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/AutoCompleteList.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	echo "<script language=\"text/javascript\">var DELETE_IMAGE_URL = '".ENTRADA_URL."/images/action-delete.gif';</script>";
	
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/organisations/manage/medbiqinstructional?".replace_query(array("section" => "add"))."&amp;org=".$ORGANISATION_ID, "title" => "Add Medbiquitous Instructional Method");
	
	// Error Checking
	switch ($STEP) {
		case 2 :
			/**
			 * Required field "instructional_method" / Instructional Method
			 */
			if (isset($_POST["instructional_method"]) && ($instructional_method = clean_input($_POST["instructional_method"], array("htmlbrackets", "trim")))) {
				$PROCESSED["instructional_method"] = $instructional_method;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Medbiquitous Instructional Method</strong> is a required field.";
			}

			/**
			 * Non-required field "instructional_method_description" / Description
			 */
			if (isset($_POST["instructional_method_description"]) && ($instructional_method_description = clean_input($_POST["instructional_method_description"], array("htmlbrackets", "trim")))) {
				$PROCESSED["instructional_method_description"] = $instructional_method_description;
			} else {
				$PROCESSED["instructional_method_description"] = "";
			}
			
			/**
			 * Non-required field "fk_eventtype_id" / Mapped Event Types
			 */
			if (isset($_POST["fk_eventtype_id"]) && is_array($_POST["fk_eventtype_id"])) {
				$SEMI_PROCESSED["fk_eventtype_id"] = $_POST["fk_eventtype_id"];
			}
			
			if (!$ERROR) {
				$PROCESSED["updated_date"] = time();
				$PROCESSED["updated_by"] = $_SESSION["details"]["id"];

				if ($db->AutoExecute("medbiq_instructional_methods", $PROCESSED, "INSERT")) {
					if(isset($SEMI_PROCESSED)) {
						// Insert keys into mapped table
						$MAPPED_PROCESSED = array();
						$MAPPED_PROCESSED["fk_instructional_method_id"] = $db->Insert_Id();
						$MAPPED_PROCESSED["updated_date"] = time();
						$MAPPED_PROCESSED["updated_by"] = $_SESSION["details"]["id"];
						
						foreach($SEMI_PROCESSED["fk_eventtype_id"] as $fk_eventtype_id) {
							$MAPPED_PROCESSED["fk_eventtype_id"] = $fk_eventtype_id;
							if(!$db->AutoExecute("map_events_eventtypes", $MAPPED_PROCESSED, "INSERT")) {
								$ERROR++;
								$ERRORSTR[] = "There was a problem inserting this instructional method into the system. The system administrator was informed of this error; please try again later.";
		
								application_log("error", "There was an error inserting an instructional method. Database said: ".$db->ErrorMsg());
							}
						}
					}
					
					if (!$ERROR) {
						$url = ENTRADA_URL . "/admin/settings/organisations/manage/medbiqinstructional?org=".$ORGANISATION_ID;
						$SUCCESS++;
						$SUCCESSSTR[] = "You have successfully added <strong>".html_encode($PROCESSED["instructional_method"])."</strong> to the system.<br /><br />You will now be redirected to the Event Types index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
						$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
						
						application_log("success", "New Medbiquitous Instructional Method [".$PROCESSED["instructional_method"]."] added to the system.");
					}
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "There was a problem inserting this instructional method into the system. The system administrator was informed of this error; please try again later.";

				application_log("error", "There was an error inserting an instructional method. Database said: ".$db->ErrorMsg());
			}
		

			if ($ERROR) {
				$STEP = 1;
			}
		break;
		case 1 :
		default :

		break;
	}

	// Display Content
	switch ($STEP) {
		case 2 :
			if ($SUCCESS) {
				echo display_success();
			}

			if ($NOTICE) {
				echo display_notice();
			}

			if ($ERROR) {
				echo display_error();
			}
		break;
		case 1 :
		default:	
			if ($ERROR) {
				echo display_error();
			}
			?>
			<form action="<?php echo ENTRADA_URL."/admin/settings/organisations/manage/medbiqinstructional"."?".replace_query(array("action" => "add", "step" => 2))."&org=".$ORGANISATION_ID; ?>" method="post">
			<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Adding Page">
			<colgroup>
				<col style="width: 30%" />
				<col style="width: 70%" />
			</colgroup>
			<thead>
				<tr>
					<td colspan="2"><h1>Add Medbiquitous Instructional Method</h1></td>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="2" style="padding-top: 15px; text-align: right">
						<input type="button" class="button" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/settings/organisations/manage/medbiqinstructional?org=<?php echo $ORGANISATION_ID;?>'" />
                        <input type="submit" class="button" value="<?php echo $translate->_("global_button_save"); ?>" />                           
					</td>
				</tr>
			</tfoot>
			<tbody>
				<tr>
					<td><label for="instructional_method" class="form-required">Medbiquitous Instructional Method:</label></td>
					<td><input type="text" id="instructional_method" name="instructional_method" value="<?php echo ((isset($PROCESSED["instructional_method"])) ? html_encode($PROCESSED["instructional_method"]) : ""); ?>" maxlength="60" style="width: 300px" /></td>
				</tr>
				<tr>
					<td style="vertical-align: top;"><label for="instructional_method_description" class="form-nrequired">Description:</label></td>
					<td>
						<textarea id="instructional_method_description" name="instructional_method_description" style="width: 98%; height: 200px" rows="20" cols="70"><?php echo ((isset($PROCESSED["instructional_method_description"])) ? html_encode($PROCESSED["instructional_method_description"]) : ""); ?></textarea>
					</td>
				</tr>
				<tr>
					<td><label for="instructional_method" class="form-nrequired">Mapped Event Types:</label></td>
					<?php
						$event_type_list = array();
				
						$query = "	SELECT a.* FROM `events_lu_eventtypes` AS a 
							LEFT JOIN `eventtype_organisation` AS b 
							ON a.`eventtype_id` = b.`eventtype_id` 
							LEFT JOIN `".AUTH_DATABASE."`.`organisations` AS c
							ON c.`organisation_id` = b.`organisation_id` 
							WHERE b.`organisation_id` = ".$db->qstr($ORGANISATION_ID)."
							AND a.`eventtype_active` = '1' 
							ORDER BY a.`eventtype_order` ASC";
						
						if ($results = $db->GetAll($query)) {
							foreach($results as $result) {
								$event_type_list[] = array("eventtype_id"=>$result['eventtype_id'], "eventtype_title" => $result["eventtype_title"]);
							}
						}
						if (isset($event_type_list) && is_array($event_type_list) && !empty($event_type_list)) {
							echo "<td>";
							foreach($event_type_list as $eventtype) {
								if(isset($SEMI_PROCESSED["fk_eventtype_id"])) {
									if(in_array($eventtype["eventtype_id"], $SEMI_PROCESSED["fk_eventtype_id"])) {
										$checked = "CHECKED";
									} else {
										$checked = "";
									}
								} else {
									$checked = "";
								}
								echo "<input type=\"checkbox\" name=\"fk_eventtype_id[]\" value=\"".$eventtype["eventtype_id"]."\" ".$checked.">".$eventtype["eventtype_title"]."<br>";
							}
						}
					?>
					</td>
				</tr>
			</tbody>
			</table>
			</form>
			<?php
		break;
	}
}

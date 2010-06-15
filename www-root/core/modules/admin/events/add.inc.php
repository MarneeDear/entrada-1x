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
 * This file is used to add events to the entrada.events table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 * @version $Id: add.inc.php 1181 2010-05-04 19:27:22Z jellis $
 */

if((!defined("PARENT_INCLUDED")) || (!defined("IN_EVENTS"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->amIAllowed('event', 'create', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["group"]."] and role [".$_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/admin/events?".replace_query(array("section" => "add")), "title" => "Adding Event");

	$PROCESSED["associated_faculty"]	= array();
	$PROCESSED["event_audience_type"]	= "grad_year";
	$PROCESSED["associated_grad_years"]	= "";
	$PROCESSED["associated_group_ids"]	= array();
	$PROCESSED["associated_proxy_ids"]	= array();
	$PROCESSED["event_types"]			= array();

	echo "<h1>Adding Event</h1>\n";

	// Error Checking
	switch($STEP) {
		case 2 :
		/**
		 * Required field "event_title" / Event Title.
		 */
			if((isset($_POST["event_title"])) && ($event_title = clean_input($_POST["event_title"], array("notags", "trim")))) {
				$PROCESSED["event_title"] = $event_title;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Event Title</strong> field is required.";
			}

			/**
			 * Non-required field "associated_faculty" / Associated Faculty (array of proxy ids).
			 * This is actually accomplished after the event is inserted below.
			 */
			if((isset($_POST["associated_faculty"]))) {
				$associated_faculty = explode(',',$_POST["associated_faculty"]);
				foreach($associated_faculty as $contact_order => $proxy_id) {
					if($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
						$PROCESSED["associated_faculty"][(int) $contact_order] = $proxy_id;
					}
				}
			}

			/**
			 * Non-required field "associated_faculty" / Associated Faculty (array of proxy ids).
			 * This is actually accomplished after the event is inserted below.
			 */
			if(isset($_POST["event_audience_type"])) {
				$PROCESSED["event_audience_type"] = clean_input($_POST["event_audience_type"], array("page_url"));

				switch($PROCESSED["event_audience_type"]) {
					case "grad_year" :
					/**
					 * Required field "associated_grad_years" / Graduating Year
					 * This data is inserted into the event_audience table as grad_year.
					 */
						if((isset($_POST["associated_grad_years"]))) {
							$associated_grad_years = explode(',', $_POST["associated_grad_years"]);
							if((isset($associated_grad_years)) && (is_array($associated_grad_years)) && (count($associated_grad_years))) {
								foreach($associated_grad_years as $year) {
									if($year = clean_input($year, array("trim", "int"))) {
										$PROCESSED["associated_grad_years"][] = $year;
									}
								}
								if(!count($PROCESSED["associated_grad_years"])) {
									$ERROR++;
									$ERRORSTR[] = "You have chosen <strong>Entire Class Event</strong> as an <strong>Event Audience</strong> type, but have not selected any graduating years.";
								}
							} else {
								$ERROR++;
								$ERRORSTR[] = "You have chosen <strong>Entire Class Event</strong> as an <strong>Event Audience</strong> type, but have not selected any graduating years.";
							}
						}

						break;
					case "group_id" :
						$ERROR++;
						$ERRORSTR[] = "The <strong>Group Event</strong> as an <strong>Event Audience</strong> type, has not yet been implemented.";
						break;
					case "proxy_id" :
					/**
					 * Required field "associated_proxy_ids" / Associated Students
					 * This data is inserted into the event_audience table as proxy_id.
					 */
						if((isset($_POST["associated_student"]))) {
							$associated_proxies = explode(',', $_POST["associated_student"]);
							if((isset($associated_proxies)) && (is_array($associated_proxies)) && (count($associated_proxies))) {
								foreach($associated_proxies as $proxy_id) {
									if($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
										$query = "	SELECT a.*
													FROM `".AUTH_DATABASE."`.`user_data` AS a
													LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
													ON a.`id` = b.`user_id`
													WHERE a.`id` = ".$db->qstr($proxy_id)."
													AND b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
													AND b.`account_active` = 'true'
													AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
													AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")";
										$result	= $db->GetRow($query);
										if($result) {
											$PROCESSED["associated_proxy_ids"][] = $proxy_id;
										}
									}
								}
								if(!count($PROCESSED["associated_proxy_ids"])) {
									$ERROR++;
									$ERRORSTR[] = "You have chosen <strong>Individual Student Event</strong> as an <strong>Event Audience</strong> type, but have not selected any individuals.";
								}
							} else {
								$ERROR++;
								$ERRORSTR[] = "You have chosen <strong>Individual Student Event</strong> as an <strong>Event Audience</strong> type, but have not selected any individuals.";
							}
						}
						break;
					case "organisation_id":
						if((isset($_POST["associated_organisation_id"])) && ($associated_organisation_id = clean_input($_POST["associated_organisation_id"], array("trim", "int")))) {
							if($ENTRADA_ACL->amIAllowed('resourceorganisation'.$associated_organisation_id, 'create')) {
								$PROCESSED["associated_organisation_id"] = $associated_organisation_id;
							} else {
								$ERROR++;
								$ERRORSTR[] = "You do not have permission to add an event for this organisation, please select a different one.";
							}
						} else {
							$ERROR++;
							$ERRORSTR[] = "You have chosen <strong>Entire Class Event</strong> as an <strong>Event Audience</strong> type, but have not selected a graduating year.";
						}
						break;
					default :
						$ERROR++;
						$ERRORSTR[] = "Unable to proceed because the <strong>Event Audience</strong> type is unrecognized.";

						application_log("error", "Unrecognized event_audience_type [".$_POST["event_audience_type"]."] encountered.");
						break;
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "Unable to proceed because the <strong>Event Audience</strong> type is unrecognized.";

				application_log("error", "The event_audience_type field has not been set.");
			}

			/**
			 * Required field "event_start" / Event Date & Time Start (validated through validate_calendar function).
			 */
			$start_date = validate_calendar("event", true, false);
			if((isset($start_date["start"])) && ((int) $start_date["start"])) {
				$PROCESSED["event_start"] = (int) $start_date["start"];
			}


			/**
			 * Required fields "eventtype_id" / Event Type
			 */
			if(isset($_POST["eventtype_duration_order"])) {
				$event_types = explode(',', $_POST["eventtype_duration_order"]);
				$eventtype_durations = $_POST["duration_segment"];
				foreach($event_types as $order => $eventtype_id) {
					if(($eventtype_id = clean_input($eventtype_id, array("trim", "int"))) && ($duration = clean_input($eventtype_durations[$order], array("trim", "int")))) {
						if(!($duration > 0)) {
							$ERROR++;
							$ERRORSTR[] = "Event type <strong>durations</strong> may not be 0 or negative.";
						}
						$query	= "SELECT eventtype_title FROM `events_lu_eventtypes` WHERE `eventtype_id` = ".$db->qstr($eventtype_id);
						$result	= $db->GetRow($query);
						if ($result) {
							$PROCESSED["event_types"][] = array($eventtype_id, $duration, $result['eventtype_title']);
						} else {
							$ERROR++;
							$ERRORSTR[] = "One of the <strong>event types</strong> you specified was invalid.";
						}
					} else {
						$ERROR++;
						$ERRORSTR[] = "One of the <strong>event types</strong> you specified is invalid.";
					}
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Event Types</strong> field is required.";
			}

			/**
			 * Non-required field "event_location" / Event Location
			 */
			if((isset($_POST["event_location"])) && ($event_location = clean_input($_POST["event_location"], array("notags", "trim")))) {
				$PROCESSED["event_location"] = $event_location;
			} else {
				$PROCESSED["event_location"] = "";
			}

			/**
			 * Required field "course_id" / Course
			 */
			if((isset($_POST["course_id"])) && ($course_id = clean_input($_POST["course_id"], array("int")))) {
				$query	= "	SELECT * FROM `courses` 
							WHERE `course_id` = ".$db->qstr($course_id)."
							AND `course_active` = '1'";
				$result	= $db->GetRow($query);
				if ($result) {
					if($ENTRADA_ACL->amIAllowed(new EventResource(null, $course_id, $_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["organisation_id"]), "create")) {
						$PROCESSED["course_id"] = $course_id;
					} else {
						$ERROR++;
						$ERRORSTR[] = "You do not have permission to add an event for the course you selected. <br /><br />Please re-select the course you would like to place this event into.";
						application_log("error", "A program coordinator attempted to add an event to a course [".$course_id."] they were not the coordinator of.");
					}
				} else {
					$ERROR++;
					$ERRORSTR[] = "The <strong>Course</strong> you selected does not exist.";
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Course</strong> field is a required field.";
			}

			/**
			 * Non-required field "event_phase" / Phase
			 */
			if((isset($_POST["event_phase"])) && ($event_phase = clean_input($_POST["event_phase"], array("notags", "trim")))) {
				$PROCESSED["event_phase"] = $event_phase;
			} else {
				$PROCESSED["event_phase"] = "";
			}

			/**
			 * Non-required field "release_date" / Viewable Start (validated through validate_calendar function).
			 * Non-required field "release_until" / Viewable Finish (validated through validate_calendar function).
			 */
			$viewable_date = validate_calendar("viewable", false, false);
			if((isset($viewable_date["start"])) && ((int) $viewable_date["start"])) {
				$PROCESSED["release_date"] = (int) $viewable_date["start"];
			} else {
				$PROCESSED["release_date"] = 0;
			}
			if((isset($viewable_date["finish"])) && ((int) $viewable_date["finish"])) {
				$PROCESSED["release_until"] = (int) $viewable_date["finish"];
			} else {
				$PROCESSED["release_until"] = 0;
			}

			if(isset($_POST["post_action"])) {
				switch($_POST["post_action"]) {
					case "content" :
						$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "content";
						break;
					case "new" :
						$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "new";
						break;
					case "index" :
					default :
						$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "index";
						break;
				}
			} else {
				$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "content";
			}

			if(!$ERROR) {
				$PROCESSED["updated_date"]	= time();
				$PROCESSED["updated_by"]	= $_SESSION["details"]["id"];

				$PROCESSED["event_finish"] = $PROCESSED["event_start"];
				$PROCESSED["event_duration"] = 0;
				foreach($PROCESSED["event_types"] as $event_type) {
					$PROCESSED["event_finish"] += $event_type[1]*60;
					$PROCESSED["event_duration"] += $event_type[1];
				}
				$PROCESSED["eventtype_id"] = $PROCESSED["event_types"][0][0];
				if($db->AutoExecute("events", $PROCESSED, "INSERT")) {
					if($EVENT_ID = $db->Insert_Id()) {

						foreach($PROCESSED["event_types"] as $event_type) {
							if(!$db->AutoExecute("event_eventtypes", array("event_id" => $EVENT_ID, "eventtype_id" => $event_type[0], "duration" => $event_type[1]), "INSERT")) {
								$ERROR++;
								$ERRORSTR[] = "There was an error while trying to save the selected <strong>Event Type</strong> for this event.<br /><br />The system administrator was informed of this error; please try again later.";

								application_log("error", "Unable to insert a new event_eventtype record while adding a new event. Database said: ".$db->ErrorMsg());
							}
						}
						
						switch($PROCESSED["event_audience_type"]) {
							case "grad_year" :
							/**
							 * If there are any graduating years associated with this event,
							 * add it to the event_audience table.
							 */
								if($PROCESSED["associated_grad_years"]) {
									foreach($PROCESSED["associated_grad_years"] as $year) {
										if(!$db->AutoExecute("event_audience", array("event_id" => $EVENT_ID, "audience_type" => "grad_year", "audience_value" => $year, "updated_date" => time(), "updated_by" => $_SESSION["details"]["id"]), "INSERT")) {
											$ERROR++;
											$ERRORSTR[] = "There was an error while trying to attach the selected <strong>Graduating Year</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.";

											application_log("error", "Unable to insert a new event_audience record while adding a new event. Database said: ".$db->ErrorMsg());
										}
									}
								}
								break;
							case "proxy_id" :
							/**
							 * If there are proxy_ids associated with this event,
							 * add them to the event_audience table.
							 */
								if(count($PROCESSED["associated_proxy_ids"])) {
									foreach($PROCESSED["associated_proxy_ids"] as $proxy_id) {
										if(!$db->AutoExecute("event_audience", array("event_id" => $EVENT_ID, "audience_type" => "proxy_id", "audience_value" => (int) $proxy_id, "updated_date" => time(), "updated_by" => $_SESSION["details"]["id"]), "INSERT")) {
											$ERROR++;
											$ERRORSTR[] = "There was an error while trying to attach the selected <strong>Proxy ID</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.";

											application_log("error", "Unable to insert a new event_audience, proxy_id record while adding a new event. Database said: ".$db->ErrorMsg());
										}
									}
								}
								break;
							case "organisation_id":
								if(isset($PROCESSED["associated_organisation_id"])) {
									if(!$db->AutoExecute("event_audience", array("event_id" => $EVENT_ID, "audience_type" => "organisation_id", "audience_value" => $PROCESSED["associated_organisation_id"], "updated_date" => time(), "updated_by" => $_SESSION["details"]["id"]), "INSERT")) {
										$ERROR++;
										$ERRORSTR[] = "There was an error while trying to attach the selected <strong>Proxy ID</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.";
										application_log("error", "Unable to insert a new event_audience, proxy_id record while adding a new event. Database said: ".$db->ErrorMsg());
									}
								}
								break;
							default :
								application_log("error", "Unrecognized event_audience_type [".$_POST["event_audience_type"]."] encountered, no audience added for event_id [".$EVENT_ID."].");
								break;
						}

						/**
						 * If there are faculty associated with this event, add them
						 * to the event_contacts table.
						 */
						if((is_array($PROCESSED["associated_faculty"])) && (count($PROCESSED["associated_faculty"]))) {
							foreach($PROCESSED["associated_faculty"] as $contact_order => $proxy_id) {
								if(!$db->AutoExecute("event_contacts", array("event_id" => $EVENT_ID, "proxy_id" => $proxy_id, "contact_order" => (int) $contact_order, "updated_date" => time(), "updated_by" => $_SESSION["details"]["id"]), "INSERT")) {
									$ERROR++;
									$ERRORSTR[] = "There was an error while trying to attach an <strong>Associated Faculty</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.";

									application_log("error", "Unable to insert a new event_contact record while adding a new event. Database said: ".$db->ErrorMsg());
								}
							}
						}

						switch($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
							case "content" :
								$url	= ENTRADA_URL."/admin/events?section=content&id=".$EVENT_ID;
								$msg	= "You will now be redirected to the event content page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
								break;
							case "new" :
								$url	= ENTRADA_URL."/admin/events?section=add";
								$msg	= "You will now be redirected to add another new event; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
								break;
							case "index" :
							default :
								$url	= ENTRADA_URL."/admin/events";
								$msg	= "You will now be redirected to the event index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
								break;
						}

						$SUCCESS++;
						$SUCCESSSTR[]	= "You have successfully added <strong>".html_encode($PROCESSED["event_title"])."</strong> to the system.<br /><br />".$msg;
						$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000)";

						application_log("success", "New event [".$EVENT_ID."] added to the system.");
					}

				} else {
					$ERROR++;
					$ERRORSTR[] = "There was a problem inserting this event into the system. The system administrator was informed of this error; please try again later.";

					application_log("error", "There was an error inserting a event. Database said: ".$db->ErrorMsg());
				}
			}

			if($ERROR) {
				$STEP = 1;
			}
			break;
		case 1 :
		default :
			continue;
			break;
	}

	// Display Content
	switch($STEP) {
		case 2 :
			if($SUCCESS) {
				echo display_success();
			}
			if($NOTICE) {
				echo display_notice();
			}
			if($ERROR) {
				echo display_error();
			}
			break;
		case 1 :
		default :
			$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/elementresizer.js\"></script>\n";
			$ONLOAD[]	= "selectEventAudienceOption('".$PROCESSED["event_audience_type"]."')";

			/**
			 * Compiles the full list of faculty members.
			 */
			$FACULTY_LIST	= array();
			$query			= "	SELECT a.`id` AS `proxy_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`organisation_id`
								FROM `".AUTH_DATABASE."`.`user_data` AS a
								LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
								ON b.`user_id` = a.`id`
								WHERE b.`app_id` = '".AUTH_APP_ID."'
								AND (b.`group` = 'faculty' OR (b.`group` = 'resident' AND b.`role` = 'lecturer'))
								ORDER BY a.`lastname` ASC, a.`firstname` ASC";
			$results		= $db->GetAll($query);
			if($results) {
				foreach($results as $result) {
					$FACULTY_LIST[$result["proxy_id"]] = array('proxy_id'=>$result["proxy_id"], 'fullname'=>$result["fullname"], 'organisation_id'=>$result['organisation_id']);
				}
			}

			/**
			 * Compiles the list of students.
			 */
			$STUDENT_LIST	= array();
			$query			= "
							SELECT a.`id` AS `proxy_id`, b.`role`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`organisation_id`
							FROM `".AUTH_DATABASE."`.`user_data` AS a
							LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
							ON a.`id` = b.`user_id`
							WHERE b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
							AND b.`account_active` = 'true'
							AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
							AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")
							AND b.`group` = 'student'
							AND b.`role` >= '".(date("Y") - ((date("m") < 7) ?  2 : 1))."'
										ORDER BY b.`role` ASC, a.`lastname` ASC, a.`firstname` ASC";
			$results		= $db->GetAll($query);
			if($results) {
				foreach($results as $result) {
					$STUDENT_LIST[$result["proxy_id"]] = array('proxy_id'=>$result["proxy_id"], 'fullname'=>$result["fullname"], 'organisation_id'=>$result['organisation_id']);
				}
			}

			if($ERROR) {
				echo display_error();
			}

			$query					= "SELECT `organisation_id`, `organisation_title` FROM `".AUTH_DATABASE."`.`organisations` ORDER BY `organisation_title` ASC";
			$organisation_results	= $db->GetAll($query);
			if ($organisation_results) {
				$organisations = array();
				foreach ($organisation_results as $result) {
					if ($ENTRADA_ACL->amIAllowed('resourceorganisation'.$result["organisation_id"], 'create')) {
						$organisation_categories[$result["organisation_id"]] = array('text' => $result["organisation_title"], 'value' => 'organisation_'.$result["organisation_id"], 'category'=>true);
					}
				}
			}
			?>
			<form action="<?php echo ENTRADA_URL; ?>/admin/events?section=add&amp;step=2" method="post" id="addEventForm">
				<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Adding Event">
					<colgroup>
						<col style="width: 3%" />
						<col style="width: 20%" />
						<col style="width: 77%" />
					</colgroup>
					<tr>
						<td colspan="3"><h2>Event Details</h2></td>
					</tr>
					<tr>
						<td></td>
						<td><label for="event_title" class="form-required">Event Title</label></td>
						<td><input type="text" id="event_title" name="event_title" value="<?php echo html_encode($PROCESSED["event_title"]); ?>" maxlength="255" style="width: 95%" /></td>
					</tr>
					<tr>
						<td colspan="3">&nbsp;</td>
					</tr>
					<?php echo generate_calendars("event", "Event Date & Time", true, true, ((isset($PROCESSED["event_start"])) ? $PROCESSED["event_start"] : 0)); ?>
					<tr>
						<td></td>
						<td><label for="event_location" class="form-nrequired">Event Location</label></td>
						<td><input type="text" id="event_location" name="event_location" value="<?php echo $PROCESSED["event_location"]; ?>" maxlength="255" style="width: 203px" /></td>
					</tr>
					<tr>
						<td colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="eventtype_ids" class="form-required">Event Types</label></td>
						<td>
							<select id="eventtype_ids" name="eventtype_ids">
								<option id="-1"> -- Pick a type to add -- </option>
								<?php
								$query		= "SELECT * FROM `events_lu_eventtypes` WHERE `eventtype_active` = '1' ORDER BY `eventtype_order` ASC";
								$results	= $db->GetAll($query);
								if($results) {
									$event_types = array();
									foreach($results as $result) {
										$title = html_encode($result["eventtype_title"]);
										echo "<option value=\"".$result["eventtype_id"]."\">".$title."</option>";
									}
								}
								?>
							</select>
							<div id="duration_notice" class="content-small" >Use the list above to select the different components of this event. When you select one, it will appear here and you can change the order and duration.</div>
							<ol id="duration_container" class="sortableList" style="display: none;">
								<?php
								foreach($PROCESSED["event_types"] as $eventtype) {
									echo "<li id=\"type_".$eventtype[0]."\" class=\"\">".$eventtype[2]."
										<a href=\"#\" onclick=\"$(this).up().remove(); cleanupList(); return false;\" class=\"remove\">
											<img src=\"".ENTRADA_URL."/images/action-delete.gif\">
										</a>
										<span class=\"duration_segment_container\">
											Duration: <input class=\"duration_segment\" name=\"duration_segment[]\" onchange=\"cleanupList();\" value=\"".$eventtype[1]."\"> minutes
										</span>
									</li>";
								}
								?>
							</ol>
							<div id="total_duration" class="content-small">Total time: 0 minutes.</div>
							<input id="eventtype_duration_order" name="eventtype_duration_order" style="display: none;">
							<script type="text/javascript">
								var sortable;
								function cleanupList() {
									ol = $('duration_container');
									if(ol.immediateDescendants().length > 0) {
										ol.show();
										$('duration_notice').hide();
									} else {
										ol.hide();
										$('duration_notice').show();
									}
									total = $$('input.duration_segment').inject(0, function(acc, e) {
										seg = parseInt($F(e));
										if (Object.isNumber(seg)) {
											acc += seg;
										}
										return acc;
									});
									$('total_duration').update('Total time: '+total+' minutes.');
									sortable = Sortable.create('duration_container', {
										onUpdate: writeOrder
									});
									writeOrder(null);
								}

								function writeOrder(container) {
									$('eventtype_duration_order').value = Sortable.sequence('duration_container').join(',');
								}

								$('eventtype_ids').observe('change', function(event){
									select = $('eventtype_ids');
									option = select.options[select.selectedIndex];
									li = new Element('li', {id: 'type_'+option.value, 'class': ''});
									li.insert(option.text+"  ");
									li.insert(new Element('a', {href: '#', onclick: '$(this).up().remove(); cleanupList(); return false;', 'class': 'remove'}).insert(new Element('img', {src: '<?php echo ENTRADA_URL; ?>/images/action-delete.gif'})));
									span = new Element('span', {'class': 'duration_segment_container'});
									span.insert('Duration: ');
									name = 'duration_segment[]';
									span.insert(new Element('input', {'class': 'duration_segment', name: 'duration_segment[]', onchange: 'cleanupList();', 'value': 0}));
									span.insert(' minutes');
									li.insert(span);
									$('duration_container').insert(li);
									cleanupList();
									select.selectedIndex = 0;

								});
								cleanupList();
							</script>
						</td>
					</tr>
					<tr>
						<td colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="faculty_name" class="form-nrequired">Associated Faculty</label></td>
						<td>
							<script type="text/javascript">
							var sortables = new Array();
							function updateOrder(type) {
								$('associated_'+type).value = Sortable.sequence(type+'_list');
							}
							
							function addItem(type) {
								if (($(type+'_id') != null) && ($(type+'_id').value != '') && ($(type+'_'+$(type+'_id').value) == null)) {
									var li = new Element('li', {'class':'community', 'id':type+'_'+$(type+'_id').value, 'style':'cursor: move;'}).update($(type+'_name').value);
									$(type+'_name').value = '';
									li.insert({bottom: '<img src=\"<?php echo ENTRADA_URL; ?>/images/action-delete.gif\" class=\"list-cancel-image\" onclick=\"removeItem(\''+$(type+'_id').value+'\', \''+type+'\')\" />'});
									$(type+'_id').value	= '';
									$(type+'_list').appendChild(li);
									sortables[type] = Sortable.destroy($(type+'_list'));
									Sortable.create(type+'_list', {onUpdate : function(){updateOrder(type);}});
									updateOrder(type);
								} else if ($(type+'_'+$(type+'_id').value) != null) {
									alert('Important: Each user may only be added once.');
									$(type+'_id').value = '';
									$(type+'_name').value = '';
									return false;
								} else if ($(type+'_name').value != '' && $(type+'_name').value != null) {
									alert('Important: When you see the correct name pop-up in the list as you type, make sure you select the name with your mouse, do not press the Enter button.');
									return false;
								} else {
									return false;
								}
							}

							function addItemNoError(type) {
								if (($(type+'_id') != null) && ($(type+'_id').value != '') && ($(type+'_'+$(type+'_id').value) == null)) {
									addItem(type);
								}
							}

							function copyItem(type) {
								if (($(type+'_name') != null) && ($(type+'_ref') != null)) {
									$(type+'_ref').value = $(type+'_name').value;
								}

								return true;
							}

							function checkItem(type) {
								if (($(type+'_name') != null) && ($(type+'_ref') != null) && ($(type+'_id') != null)) {
									if ($(type+'_name').value != $(type+'_ref').value) {
										$(type+'_id').value = '';
									}
								}

								return true;
							}

							function removeItem(id, type) {
								if ($(type+'_'+id)) {
									$(type+'_'+id).remove();
									Sortable.destroy($(type+'_list'));
									Sortable.create(type+'_list', {onUpdate : function (type) {updateOrder(type)}});
									updateOrder(type);
								}
							}

							function selectItem(id, type) {
								if ((id != null) && ($(type+'_id') != null)) {
									$(type+'_id').value = id;
								}
							}

							</script>
							<input type="text" id="faculty_name" name="fullname" size="30" autocomplete="off" style="width: 203px; vertical-align: middle" onkeyup="checkItem('faculty')" onblur="addItemNoError('faculty')" />
							<script type="text/javascript">
								$('faculty_name').observe('keypress', function(event){
									if(event.keyCode == Event.KEY_RETURN) {
										addItem('faculty');
										Event.stop(event);
									}
								});
							</script>
							<?php
							$ONLOAD[] = "Sortable.create('faculty_list', {onUpdate : function() {updateOrder('faculty')}})";
							$ONLOAD[] = "$('associated_faculty').value = Sortable.sequence('faculty_list')";
							?>
							<div class="autocomplete" id="faculty_name_auto_complete"></div><script type="text/javascript">new Ajax.Autocompleter('faculty_name', 'faculty_name_auto_complete', '<?php echo ENTRADA_RELATIVE; ?>/api/personnel.api.php?type=faculty', {frequency: 0.2, minChars: 2, afterUpdateElement: function (text, li) {selectItem(li.id, 'faculty'); copyItem('faculty');}});</script>
							<input type="hidden" id="associated_faculty" name="associated_faculty" />
							<input type="button" class="button-sm" onclick="addItem('faculty');" value="Add" style="vertical-align: middle" />
							<span class="content-small">(<strong>Example:</strong> <?php echo html_encode($_SESSION["details"]["lastname"].", ".$_SESSION["details"]["firstname"]); ?>)</span>
							<ul id="faculty_list" class="menu" style="margin-top: 15px">
								<?php
								if (is_array($PROCESSED["associated_faculty"]) && count($PROCESSED["associated_faculty"])) {
									foreach ($PROCESSED["associated_faculty"] as $faculty) {
										if ((array_key_exists($faculty, $FACULTY_LIST)) && is_array($FACULTY_LIST[$faculty])) {
											?>
											<li class="community" id="faculty_<?php echo $FACULTY_LIST[$faculty]["proxy_id"]; ?>" style="cursor: move;"><?php echo $FACULTY_LIST[$faculty]["fullname"]; ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" class="list-cancel-image" onclick="removeItem('<?php echo $FACULTY_LIST[$faculty]["proxy_id"]; ?>', 'faculty');"/></li>
											<?php
										}
									}
								}
								?>
							</ul>
							<input type="hidden" id="faculty_ref" name="faculty_ref" value="" />
							<input type="hidden" id="faculty_id" name="faculty_id" value="" />
						</td>
					</tr>
					<tr>
						<td colspan="3"><h2>Event Audience</h2></td>
					</tr>
					<tr>
						<td></td>
						<td><label for="event_phase" class="form-nrequired">Phase / Term</label></td>
						<td>
							<select id="event_phase" name="event_phase" style="width: 203px">
								<option value="1"<?php echo (($PROCESSED["event_phase"] == "1") ? " selected=\"selected\"" : "") ?>>1</option>
								<option value="2"<?php echo (($PROCESSED["event_phase"] == "2") ? " selected=\"selected\"" : "") ?>>2</option>
								<option value="2A"<?php echo (($PROCESSED["event_phase"] == "2A") ? " selected=\"selected\"" : "") ?>>2A</option>
								<option value="2B"<?php echo (($PROCESSED["event_phase"] == "2B") ? " selected=\"selected\"" : "") ?>>2B</option>
								<option value="2C"<?php echo (($PROCESSED["event_phase"] == "2C") ? " selected=\"selected\"" : "") ?>>2C</option>
								<option value="2E"<?php echo (($PROCESSED["event_phase"] == "2E") ? " selected=\"selected\"" : "") ?>>2E</option>
								<option value="3"<?php echo (($PROCESSED["event_phase"] == "3") ? " selected=\"selected\"" : "") ?>>3</option>
							</select>
						</td>
					</tr>
					<tr>
						<td></td>
						<td><label for="course_id" class="form-required">Course</label></td>
						<td>
							<select id="course_id" name="course_id" style="width: 95%">
							<?php
							$query		= "	SELECT * FROM `courses` 
											WHERE `course_active` = '1'
											ORDER BY `course_name` ASC";
							$results	= $db->GetAll($query);
							if($results) {
								foreach($results as $result) {
									if ($ENTRADA_ACL->amIAllowed(new EventResource(null, $result["course_id"], $_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["organisation_id"]), "create")) {
										echo "<option value=\"".(int) $result["course_id"]."\"".(($PROCESSED["course_id"] == $result["course_id"]) ? " selected=\"selected\"" : "").">".html_encode($result["course_name"])."</option>\n";
									}
								}
							}
							?>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td style="vertical-align: top"><input type="radio" name="event_audience_type" id="event_audience_type_grad_year" value="grad_year" onclick="selectEventAudienceOption('grad_year')" style="vertical-align: middle"<?php echo (($PROCESSED["event_audience_type"] == "grad_year") ? " checked=\"checked\"" : ""); ?> /></td>
						<td colspan="2" style="padding-bottom: 15px">
							<label for="event_audience_type_grad_year" class="radio-group-title">Entire Class Event</label>
							<div class="content-small">This event is intended for an entire class.</div>
						</td>
					</tr>
					<tr class="event_audience grad_year_audience">
						<td></td>
						<td><label for="associated_grad_year" class="form-required">Graduating Year</label></td>
						<td>
							<select id="associated_grad_year" name="associated_grad_year" style="width: 203px">
							<?php
							for($year = (date("Y", time()) + 4); $year >= (date("Y", time()) - 1); $year--) {
								echo "<option value=\"".(int) $year."\"".(($PROCESSED["associated_grad_year"] == $year) ? " selected=\"selected\"" : "").">Class of ".html_encode($year)."</option>\n";
							}
							?>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td style="vertical-align: top"><input type="radio" name="event_audience_type" id="event_audience_type_proxy_id" value="proxy_id" onclick="selectEventAudienceOption('proxy_id')" style="vertical-align: middle"<?php echo (($PROCESSED["event_audience_type"] == "proxy_id") ? " checked=\"checked\"" : ""); ?> /></td>
						<td colspan="2" style="padding-bottom: 15px">
							<label for="event_audience_type_proxy_id" class="radio-group-title">Individual Student Event</label>
							<div class="content-small">This event is intended for a specific student or students.</div>
						</td>
					</tr>
					<tr class="event_audience proxy_id_audience">
						<td></td>
						<td style="vertical-align: top"><label for="associated_proxy_ids" class="form-required">Associated Students</label></td>
						<td>
							<input type="text" id="student_name" name="fullname" size="30" autocomplete="off" style="width: 203px; vertical-align: middle" onkeyup="checkItem('student')" onblur="addItemNoError('student')" />
							<script type="text/javascript">
								$('student_name').observe('keypress', function(event){
									if(event.keyCode == Event.KEY_RETURN) {
										addItem('student');
										Event.stop(event);
									}
								});
							</script>
							<?php
							if($PROCESSED["event_audience_type"] == "proxy_id") {
								$ONLOAD[] = "Sortable.create('student_list', {onUpdate : function() {updateOrder('student')}})";
								$ONLOAD[] = "$('associated_student').value = Sortable.sequence('student_list')";
							}
							?>
							<div class="autocomplete" id="student_name_auto_complete"></div><script type="text/javascript">new Ajax.Autocompleter('student_name', 'student_name_auto_complete', '<?php echo ENTRADA_RELATIVE; ?>/api/personnel.api.php?type=student', {frequency: 0.2, minChars: 2, afterUpdateElement: function (text, li) {selectItem(li.id, 'student'); copyItem('student');}});</script>
							<input type="hidden" id="associated_student" name="associated_student" />
							<input type="button" class="button-sm" onclick="addItem('student');" value="Add" style="vertical-align: middle" />
							<span class="content-small">(<strong>Example:</strong> <?php echo html_encode($_SESSION["details"]["lastname"].", ".$_SESSION["details"]["firstname"]); ?>)</span>
							<ul id="student_list" class="menu" style="margin-top: 15px">
								<?php
								if (is_array($PROCESSED["associated_proxy_ids"]) && count($PROCESSED["associated_proxy_ids"])) {
									foreach ($PROCESSED["associated_proxy_ids"] as $student) {
										if ((array_key_exists($student, $STUDENT_LIST)) && is_array($STUDENT_LIST[$student])) {
											?>
											<li class="community" id="student_<?php echo $STUDENT_LIST[$student]["proxy_id"]; ?>" style="cursor: move;"><?php echo $STUDENT_LIST[$student]["fullname"]; ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" class="list-cancel-image" onclick="removeItem('<?php echo $STUDENT_LIST[$student]["proxy_id"]; ?>', 'student');"/></li>
											<?php
										}
									}
								}
								?>
							</ul>
							<input type="hidden" id="student_ref" name="student_ref" value="" />
							<input type="hidden" id="student_id" name="student_id" value="" />
						</td>
					</tr>
					<tr>
						<td colspan="3">&nbsp;</td>
					</tr>
					<?php if ($ENTRADA_ACL->amIAllowed(new EventResource(null, null, $_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["organisation_id"]), 'create')) { ?>
					<tr>
						<td style="vertical-align: top"><input type="radio" name="event_audience_type" id="event_audience_type_organisation_id" value="organisation_id" onclick="selectEventAudienceOption('organisation_id')" style="vertical-align: middle"<?php echo (($PROCESSED["event_audience_type"] == "organisation_id") ? " checked=\"checked\"" : ""); ?> /></td>
						<td colspan="2" style="padding-bottom: 15px">
							<label for="event_audience_type_organisation_id" class="radio-group-title">Entire Organisation Event</label>
							<div class="content-small">This event is intended for every member of an organisation.</div>
						</td>
					</tr>
					<tr class="event_audience organisation_id_audience">
						<td></td>
						<td><label for="associated_organisation_id" class="form-required">Organisation</label></td>
						<td>
							<select id="associated_organisation_id" name="associated_organisation_id" style="width: 203px">
								<?php
								if (is_array($organisation_categories) && count($organisation_categories)) {
									foreach($organisation_categories as $organisation_id => $organisation_info) {
										echo "<option value=\"".$organisation_id."\"".(($PROCESSED["associated_organisation_id"] == $year) ? " selected=\"selected\"" : "").">".$organisation_info['text']."</option>\n";
									}
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan="3">&nbsp;</td>
					</tr>
					<?php } ?>
					<tr>
						<td colspan="3"><h2>Time Release Options</h2></td>
					</tr>
					<?php echo generate_calendars("viewable", "", true, false, ((isset($PROCESSED["release_date"])) ? $PROCESSED["release_date"] : 0), true, false, ((isset($PROCESSED["release_until"])) ? $PROCESSED["release_until"] : 0)); ?>
					<tr>
						<td colspan="3" style="padding-top: 25px">
							<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
								<tr>
									<td style="width: 25%; text-align: left">
										<input type="button" class="button" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/events'" />
									</td>
									<td style="width: 75%; text-align: right; vertical-align: middle">
										<span class="content-small">After saving:</span>
										<select id="post_action" name="post_action">
											<option value="content"<?php echo (((!isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"])) || ($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "content")) ? " selected=\"selected\"" : ""); ?>>Add content to event</option>
											<option value="new"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "new") ? " selected=\"selected\"" : ""); ?>>Add another event</option>
											<option value="index"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "index") ? " selected=\"selected\"" : ""); ?>>Return to event list</option>
										</select>
										<input type="submit" class="button" value="Save" />
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</form>
			<script type="text/javascript">
				function selectEventAudienceOption(type) {
					$$('.event_audience').invoke('hide');
					$$('.'+type+'_audience').invoke('show');
				}
			</script>
			<br /><br />
			<?php
		break;
	}
}
?>

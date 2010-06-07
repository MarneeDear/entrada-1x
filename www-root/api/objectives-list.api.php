<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Serves the categories list up in a select box.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2008 Queen's University. All Rights Reserved.
 *
 * @version $Id: index.php 600 2009-08-12 15:19:17Z simpson $
*/
@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if ((isset($_SESSION["isAuthorized"])) && ((bool) $_SESSION["isAuthorized"])) {

	if ($_SESSION["isAuthorized"]) {
		if (isset($_REQUEST["id"]) && ((int)$_REQUEST["id"])) {
			$objective_id = clean_input($_REQUEST["id"], array("int"));
		} else {
			$objective_id = 0;
		}
		if (isset($_REQUEST["excluded"]) && (count(explode(",", $_REQUEST["excluded"])))) {
			$excluded_array = explode(",", $_REQUEST["excluded"]);
			$excluded_valid = true;
			$excluded_clean = "";
			foreach ($excluded_array as $excluded_objective_id) {
				if (!(int)$excluded_objective_id) {
					$excluded_valid = false;
					break;
				} else {
					$excluded_clean .= ($excluded_clean ? ",".((int)$excluded_objective_id) : ((int)$excluded_objective_id));
				}
			}
			if ($excluded_valid && $excluded_clean) {
				$excluded = $excluded_clean;
			} else {
				$excluded = 0;
			}
		} else {
			$excluded = 0;
		}
		if (isset($_REQUEST["pid"]) && ((int)$_REQUEST["pid"])) {
			$parent_id = clean_input($_REQUEST["pid"], array("int"));
		} else {
			$parent_id = 0;
		}
		if (isset($_REQUEST["type"]) && $_REQUEST["type"] == "order") {
			if ($parent_id) {
				$query = "	SELECT * FROM `global_lu_objectives`
							WHERE `objective_parent` = ".$db->qstr($parent_id)."
							AND `objective_active` = '1'
							ORDER BY `objective_order` ASC";
			} else {
				$query = "	SELECT * FROM `global_lu_objectives`
							WHERE `objective_parent` = '0'
							AND `objective_active` = '1'
							ORDER BY `objective_order` ASC";
			}
			$objectives = $db->GetAll($query);
			if ($objectives) {
				$count = 0;
				echo "<select id=\"objective_order\" name=\"objective_order\">\n";
				$current_selected = false;
				$selected = false;
				$count = 0;
				foreach ($objectives as $objective) {
					if ($objective["objective_id"] != $objective_id) {
						$count++;
						echo "<option id=\"before_obj_".$objective["objective_id"]."\" value=\"".$count."\"".($selected ? " selected=\"selected\"" : "").">Before ".$objective["objective_name"]."</option>\n";
						$selected = false;
					} else {
						$selected = true;
					}
				}
				echo "<option id=\"after_obj_".$objective["objective_id"]."\" value=\"".($count+1)."\" >After ".$objective["objective_name"]."</option>\n";
				echo "</select>\n";
			}
		} else {
			if ($parent_id) {
				$query = "	SELECT * FROM `global_lu_objectives`
							WHERE `objective_id` = ".$db->qstr($parent_id)."
							AND `objective_active` = '1'";
				$objective = $db->GetRow($query);
				if ($objective) {
					if ($objective["objective_parent"]) {
						$last_parent_id								= $objective["objective_parent"];
						$objective_selected_reverse[1]["id"]		= 0;
						$objective_selected_reverse[1]["parent"]	= $parent_id;
						$objective_selected_reverse[2]["id"]		= $parent_id;
						$objective_selected_reverse[2]["parent"]	= $objective["objective_parent"];
						$count = 2;
						while ($last_parent_id) {
							$count++;
							$query = "	SELECT * FROM `global_lu_objectives`
										WHERE `objective_id` = ".$db->qstr($last_parent_id)."
										AND `objective_active` = '1'";
							$parent_objective = $db->GetRow($query);
							$objective_selected_reverse[$count]["parent"]	= $parent_objective["objective_parent"];
							$objective_selected_reverse[$count]["id"]		= $parent_objective["objective_id"];
							$last_parent_id									= $parent_objective["objective_parent"];
						}
						$index = $count;
						foreach ($objective_selected_reverse as $objective_item) {
							$objective_selected[$index]["parent"]	= $objective_item["parent"];
							$objective_selected[$index]["id"]		= $objective_item["id"];
							$index--;
						}
					} else {
							$objective_selected[1]["id"]		= $parent_id;
							$objective_selected[2]["id"]		= 0;
							$objective_selected[1]["parent"]	= 0;
							$objective_selected[2]["parent"]	= $parent_id;
							$count = 2;
					}
					echo "<input type=\"hidden\" name=\"objective_id\" value=\"".$parent_id."\" />\n";
				}
			} else {
				$objective_selected[1]["id"]		= 0;
				$objective_selected[1]["parent"]	= 0;
				$count = 1;
			}
			if ($objective_id) {
				echo "<input type=\"hidden\" name=\"delete[".$objective_id."][objective_parent]\" value=\"".$parent_id."\" />\n";
			}
			$last_title = false;
			$margin = 0;
			for ($level = 1; $level <= $count; $level++) {
				if ($objective_selected[$level]["parent"] !== false) {
					$query = "	SELECT * FROM `global_lu_objectives`
								WHERE `objective_parent` = ".$db->qstr($objective_selected[$level]["parent"])."
								AND `objective_active` = '1'".
								($excluded ? " AND `objective_id` NOT IN (".$excluded.")" : ($objective_id ? " AND `objective_id` != ".$db->qstr($objective_id) : ""));
					$results = $db->GetAll($query);
					if ($results) {
						echo "<div style=\"padding: 0px; margin-left: ".$margin."px;\">\n";
						echo "\t<img height=\"20\" width=\"15\" src=\"".ENTRADA_URL."/images/tree/minus".($margin ? "2" : "5").".gif\" alt=\"Level\" title=\"Level\" style=\"position: relative; top: 6px;\"/>";
						echo "\t<select id=\"objective-".$objective_selected[$level]["parent"]."\" name=\"objective-".$objective_selected[$level]["parent"]."\" onChange=\"selectObjective(this.options[this.selectedIndex].value".($objective_id ? ", ".$objective_id : "").($excluded ? ", '".$excluded."'" : "")."); selectOrder(".($objective_id ? $objective_id.", " : "")."this.options[this.selectedIndex].value);\">\n";
							if ($last_title) {
								echo "\t\t<option value=\"".$objective_selected[$level]["parent"]."\">-- Under ".clean_input($last_title, array("notags"))." --</option>\n";
							} else {
								echo "\t\t<option value=\"".($level > 1 ? $objective_selected[($level-1)]["id"] : 0)."\"".($objective_selected[$level]["id"] == 0 ? " selected=\"selected\"" : "").">-- No Parent --</option>\n";
							}
						foreach ($results as $result) {
							echo "\t\t<option value=\"".$result["objective_id"]."\"".($objective_selected[$level]["id"] == $result["objective_id"] ? " selected=\"selected\"" : "").">".clean_input($result["objective_name"], array("notags"))."</option>\n";
							if (($count - 1) == $level && $objective_selected[$level]["id"] == $result["objective_id"]) {
								$last_title = $result["objective_name"];
							}
						}
						echo "\t</select>\n";
						echo "</div>\n";
					}
					$margin += 20;
				}
			}
		}
	}
}
?>
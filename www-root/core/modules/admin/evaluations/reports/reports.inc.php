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
 * @author Organisation: University of Calgary
 * @author Unit: Undergraduate Medical Education
 * @author Developer: Doug Hall <hall@ucalgary.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EVALUATIONS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("evaluations", "update", false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["group"]."] and role [".$_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/admin/", "title" => "Evaluation Reports");

	/**
	 * Collect the course evaluation(s) to be reported.
	 */
	if(isset($_GET["evaluation"]))  {
		$EVALUATIONS[] =  trim($_GET["evaluation"]);
	} elseif((!isset($_POST["checked"])) || (!is_array($_POST["checked"])) || (!@count($_POST["checked"]))) {
		header("Location: ".$_SERVER['HTTP_REFERER']);
		exit;
	} else {
		foreach($_POST["checked"] as $evaluation) {
			$evaluation = trim($evaluation);
			if($evaluation) {
				$EVALUATIONS[] = $evaluation;
			}
		}
		if(!@count($EVALUATIONS)) {
			$ERROR++;
			$ERRORSTR[] = "There were no valid evaluation identifiers to report. Please ensure that you access this section through the event index.";
			echo display_error();
		}
	}
	/**
	 * Produce a report for each course evaluation
	 */
	foreach($EVALUATIONS as $evaluation){
        list($evaluator, $target) = explode(":",$evaluation);
		$STUDENTS = $evaluator=="s";

		/**
		 * Get generic form and evaluation information for selected target
		 */
		$report = $db->GetRow("	SELECT t.`evaluation_id` `evaluation`, t.`target_value` `target`, f.`eform_id` form_id, f.`form_title`, f.`form_description`,
								e.`evaluation_title`, e.`evaluation_description`, e.`evaluation_start`, e.`evaluation_finish`, e.`min_submittable`, e.`max_submittable`, e.`release_date`, e.`release_until`,
								CONCAT(UPPER(SUBSTRING(`target_shortname`, 1, 1)), LOWER(SUBSTRING(`target_shortname` FROM 2))) as `type` FROM `evaluation_targets` t
								INNER JOIN `evaluations` e ON t.`evaluation_id` = e.`evaluation_id`
								LEFT JOIN `evaluation_forms` f ON e.`eform_id` = f.`eform_id`
							  	INNER JOIN `evaluations_lu_targets` lt ON t.`target_id` = lt.`target_id`
								WHERE t.`etarget_id` = ".$db->qstr($target));

		switch($report["type"]) {
			case "Course" :
				$type = $db->GetRow("	SELECT `course_name` `name`, `course_code` `code` FROM `courses` 
							WHERE `course_id` = ".$db->qstr($report["target"]));
				$title = ($STUDENTS?"Student ":"")."Course Evaluation ";
			break;
			default:
			break;
		}

		echo	"<div class=\"no-printing\">";
		echo	"<table width=\"100%\" summary=\"Evaluation Reports\">";
		echo	"	<colgroup>
						<col style=\"width: 18%\" />
						<col style=\"width: 42%\" />
						<col style=\"width: 12%\" />
						<col style=\"width: 28%\" />
					</colgroup>";
		echo 	"	<tr><td colspan=\"4\"><h2>$title - $report[evaluation_title]</h2></td></tr>\n";
		echo	"	<tr><td><h3> $report[type]:</h3></td><td colspan=\"3\">'$type[name]' [$type[code]]</td></tr>";
		echo	"	<tr><td><h3> Evaluation period:</h3></td><td>".date("M jS", $report["evaluation_start"])."  -  ".date("M jS Y", $report["evaluation_finish"])."</td>";
		echo	"		<td><h3> Released:</h3></td><td>".date("M jS Y", $report["release_date"])."</td></tr>";

		/**
		 * Get number of evaluators: individual student(s) and whole class
		 */
		$query = "	SELECT COUNT(DISTINCT(`evaluator`)) FROM
					(
						SELECT ev.`evaluator_value` `evaluator`
						FROM `evaluation_evaluators` ev
						WHERE ev.`evaluator_type` = 'proxy_id'
						AND ev.`evaluation_id` = ".$db->qstr($report["evaluation"])."
						UNION
						SELECT a.`user_id` `evaluator`
						FROM `".AUTH_DATABASE."`.`user_access` a , `evaluation_evaluators` ev
						WHERE ev.`evaluator_type` = 'grad_year' AND ev.`evaluator_value` = a.`role`
						AND ev.`evaluation_id` = ".$db->qstr($report["evaluation"])."
					) t";
		$evaluators	= $db->GetOne($query);

		if ($STUDENTS) {		
			$query = "	SELECT COUNT(DISTINCT(a.`user_id`)) `total`,  a.`role` `year`
						FROM `".AUTH_DATABASE."`.`user_access` a, `evaluation_evaluators` ev
						WHERE ev.`evaluator_type` = 'grad_year' AND ev.`evaluator_value` = a.`role`
						AND ev.`evaluation_id` = ".$db->qstr($report["evaluation"]);
			$class	= $db->GetRow($query);	
		}
		
		$updated = $db->GetOne("SELECT MAX(`updated_date`) FROM `evaluation_progress`
								WHERE `etarget_id` = ".$db->qstr($target)." AND `progress_value` <> 'cancelled'");
				
		$cancelled = $db->GetOne("	SELECT COUNT(`eprogress_id`) FROM `evaluation_progress`
									WHERE `etarget_id` = ".$db->qstr($target)." AND `progress_value` = 'cancelled'");
				
		$progress = $db->GetOne("	SELECT COUNT(`eprogress_id`) FROM `evaluation_progress`
									WHERE `etarget_id` = ".$db->qstr($target)." AND `progress_value` = 'inprogress'");
				
		$completed = $db->GetOne("	SELECT COUNT(`eprogress_id`) FROM `evaluation_progress`
									WHERE `etarget_id` = ".$db->qstr($target)." AND `progress_value` = 'complete'");

		echo	"<tr><td><h3>Evaluators:</h3></td>";

		/**
		 * Calculate number of evaluators, the class, and extra indviduals not in the class.
		 */
		if ($STUDENTS && ($class["total"]>0)) {
			$indies = $evaluators-$class["total"];
			echo "	<td>Class of $class[year] (#$class[total])".($indies?" plus $indies individual".($indies>1?"s":""):"")."</td>";
		} else {
			echo "	<td>$evaluators</td>";
		}
		echo	"<td><h3>Updated:</h3></td><td>".date("M jS", $updated)."</td></tr>";
		echo	"<tr><td><h3> Progress:</h3></td><td colspan=\"3\">".($completed?"$completed - Completed ":"").($progress?"$progress - In progress ":"").($cancelled?"$cancelled - Cancelled ":"")."</td></tr>";


		echo	"<tr><td /><td colspan=\"2\"><hr></td><td /></tr>";
		echo	"<tr><td /><td><h3>$report[form_title]</h3></td><td  colspan=\"2\"><h3>$report[form_description]</h3></td></tr>";

		/**
		 * Get the question information to process for this report
		 */
		$query = "	SELECT q.`efquestion_id` `id`, t.`questiontype_title`, q.`question_text`
					FROM `evaluation_form_questions` q
					INNER JOIN `evaluations_lu_questiontypes` t ON q.`questiontype_id` = t.`questiontype_id`
					WHERE `eform_id` = ".$db->qstr($report["form_id"])."
					ORDER BY q.`question_order`";
		$questions = $db->GetAll($query);

		/**
		 * Process report by getting response statistics for each question
		 */ 
		$number = 0;		
		foreach($questions as $question){
			$number++;
			echo	"<tr><td><h3>Question: $number</h3></td><td  colspan=\"2\"><h3>$question[question_text]</h3></td><td>$question[questiontype_title]</td></tr>";
			
			echo	"<tr><td  colspan=\"4\">&nbsp;</td></tr>";
			echo	"<tr><td  colspan=\"4\">&nbsp;</td></tr>";
			echo	"<tr><td  colspan=\"4\"><table>";
			echo	"	<tr><td style=\"width: 22%\" />";
			echo	"		<td style=\"width: 18%\">Frequency</td>";
			echo	"		<td style=\"width: 20%\">Percent</td>";
			echo	"		<td style=\"width: 20%\">Valid_%</td>";
			echo	"		<td style=\"width: 20%\">Cumul_%</td></tr>";
			
			/**
			 * Get available responses for each question
			 */
			$query = "	SELECT `efresponse_id` `id`, `response_order` `order` ,`response_text` `text`,  `response_is_html` `html`, `minimum_passing_level` `mpl`, 0 `freq`, 0 `percent`, 0 `valid`, 0 `cumul`
						FROM `evaluation_form_responses`
						WHERE `efquestion_id` = ".$db->qstr($question["id"])."
						ORDER BY `response_order`";
			$results = $db->GetAll($query);
			
			/**
			 * Build response array
			 */
			foreach ($results as $result) {
				$responses[array_shift($result)] = $result;
			}	

			/**
			 * Tally all responses for each question
			 */
			$query = "	SELECT r.`eresponse_id`, r.`efresponse_id`, r.`comments`
						FROM `evaluation_responses` r
						INNER JOIN `evaluation_progress` p ON r.`eprogress_id` = r.`eprogress_id`
						WHERE p.`progress_value` <> 'cancelled'
						AND `r.eform_id` = ".$db->qstr($report["form_id"])."
						AND `efquestion_id` = ".$db->qstr($question["id"]);
			$result	= $db->GetRow($query);
			if ($result) {
			}

		}
		
		echo	"</table>";
		echo	"</div>";		
//	echo "<br>$query<br>"; print_r($type);
	}
}
?>

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
 * @version $Id: edit.inc.php 1169 2010-05-01 14:18:49Z simpson $
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_GRADEBOOK"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("gradebook", "read", false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["group"]."] and role [".$_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	if ($COURSE_ID) {
		$query			= "	SELECT * FROM `courses` 
							WHERE `course_id` = ".$db->qstr($COURSE_ID)."
							AND `course_active` = '1'";
		$course_details	= $db->GetRow($query);
		
		if ($course_details && $ENTRADA_ACL->amIAllowed(new GradebookResource($course_details["course_id"], $course_details["organisation_id"]), "read")) {
			$BREADCRUMB[]	= array("url" => ENTRADA_URL."/admin/gradebook/assessments?".replace_query(array("section" => "grade", "id" => $COURSE_ID, "step" => false)), "title" => "Grading Assessment");
			
			$query = "	SELECT `assessments`.*,`assessment_marking_schemes`.`handler`
						FROM `assessments`
						LEFT JOIN `assessment_marking_schemes` ON `assessment_marking_schemes`.`id` = `assessments`.`marking_scheme_id`
						WHERE `assessments`.`assessment_id` = ".$db->qstr($ASSESSMENT_ID);
						
			$assessment = $db->GetRow($query);
			
			if($assessment) {
				$GRAD_YEAR = $assessment["grad_year"];
				
				courses_subnavigation($course_details);

				?>
				<h1><?php echo $course_details["course_name"]; ?> Gradebook: <?php echo $assessment["name"]; ?> (Class of <?php echo $assessment["grad_year"]; ?>)</h1>
			
				<div style="float: right; text-align: right;">
					<ul class="page-action">
						<li><a href="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE . "/assessments/?" . replace_query(array("section" => "edit", "step" => false)); ?>" class="strong-green">Edit Assessment</a></li>
					</ul>
				</div>
				<div style="clear: both"><br/></div>
			
				<?php
				$query	= 	"SELECT b.`id` AS `proxy_id`, CONCAT_WS(', ', b.`lastname`, b.`firstname`) AS `fullname`, b.`number`";
				$query 	.=  ", g.`grade_id` AS `grade_id`, g.`value` AS `grade_value` ";
				$query 	.=  " FROM `".AUTH_DATABASE."`.`user_data` AS b
							  LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS c
							  ON c.`user_id` = b.`id` AND c.`app_id`=".$db->qstr(AUTH_APP_ID)."
							  AND c.`account_active`='true'
							  AND (c.`access_starts`='0' OR c.`access_starts`<=".$db->qstr(time()).")
							  AND (c.`access_expires`='0' OR c.`access_expires`>=".$db->qstr(time()).") ";
				$query .=   " LEFT JOIN `".DATABASE_NAME."`.`assessment_grades` AS g ON b.`id` = g.`proxy_id` AND g.`assessment_id` = ".$db->qstr($assessment["assessment_id"])."\n";
				$query .= 	" WHERE c.`group` = 'student' AND c.`role` = ".$db->qstr($GRAD_YEAR);
				
				$students = $db->GetAll($query);
				if(count($students) >= 1): ?>
					<table class="gradebook single">
						<thead>
							<tr>
								<th style="width: 200px;">Student</th>
								<th>Student Number</th>
								<?php echo "<th>{$assessment["name"]}</th>\n"; ?>
							</tr>
						</thead>
						<tbody>
						<?php foreach($students as $key => $student): ?>
							<tr id="grades<?php echo $student["proxy_id"]; ?>">
								<td><?php echo $student["fullname"]; ?></td>
								<td><?php echo $student["number"]; ?></td>
								<?php
								if(isset($student["grade_id"])) {
									$grade_id = $student["grade_id"];
								} else {
									$grade_id = "";
								}
								if(isset($student["grade_value"])) {
									$grade_value = format_retrieved_grade($student["grade_value"], $assessment);
								} else {
									$grade_value = "-";
								} ?>
									<td>
										<span class="grade" 
											data-grade-id="<?php echo $grade_id; ?>"
											data-assessment-id="<?php echo $assessment["assessment_id"]; ?>"
											data-proxy-id="<?php echo $student["proxy_id"] ?>"
										><?php echo $grade_value; ?></span>
										<span class="gradesuffix" <?php echo (($grade_value === "-") ? "style=\"display: none;\"" : "") ?>>
											<?php echo assessment_suffix($assessment); ?>
										</span>
									</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php
				else:
				?>
				<div class="display-notice">There are no students in the system for this assessment's Graduating Year <strong><?php echo $GRAD_YEAR; ?></strong>.</div>
				<?php endif;
			} else {
				$ERROR++;
				$ERRORSTR[] = "In order to edit an assessment's grades you must provide a valid assessment identifier.";

				echo display_error();

				application_log("notice", "Failed to provide a valid assessment identifier when attempting to edit an assessment's grades.");
			}

		} else {
			$ERROR++;
			$ERRORSTR[] = "In order to edit a course you must provide a valid course identifier. The provided ID does not exist in this system.";

			echo display_error();

			application_log("notice", "Failed to provide a valid course identifier when attempting to edit an assessment's grades.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "In order to edit a course you must provide a valid course identifier.";

		echo display_error();

		application_log("notice", "Failed to provide course identifier when attempting to edit an assessment's grades.");
	}
}
?>

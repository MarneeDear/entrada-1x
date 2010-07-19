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
 * This file is used by quiz authors to enable a particular quiz.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_QUIZZES"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('quiz', 'update', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["group"]."] and role [".$_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	if ($RECORD_ID) {
		$query			= "	SELECT a.*
							FROM `quizzes` AS a
							WHERE a.`quiz_id` = ".$db->qstr($RECORD_ID)."
							AND a.`quiz_active` = '0'";
		$quiz_record	= $db->GetRow($query);
		if ($quiz_record && $ENTRADA_ACL->amIAllowed(new QuizResource($quiz_record["quiz_id"]), "update")) {
			if ($db->AutoExecute("quizzes", array("quiz_active" => 1), "UPDATE", "`quiz_id` = ".$RECORD_ID)) {
				$url = ENTRADA_URL."/admin/".$MODULE;
				$SUCCESS++;
				$SUCCESSSTR[]	= "You have successfully enabled this quiz.<br /><br />You will now be redirected back to the quiz index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";

				$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000)";

				echo display_success();

				application_log("success", "Successfully enabled quiz [".$RECORD_ID."].");
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "In order to enable a quiz, you must provide a disabled quiz identifier.";

			echo display_error();

			application_log("notice", "Failed to provide a valid disabled quiz identifer [".$RECORD_ID."] when attempting to endable a quiz.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "In order to endable a quiz, you must provide a disabled quiz identifier.";

		echo display_error();

		application_log("notice", "Failed to provide a quiz identifier when attempting to endable a quiz.");
	}
}
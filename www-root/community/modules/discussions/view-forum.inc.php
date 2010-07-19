<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to view all available posts within a particular discussion forum in
 * a community.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_DISCUSSIONS"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

if ($RECORD_ID) {
	$query				= "SELECT * FROM `community_discussions` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `cdiscussion_id` = ".$db->qstr($RECORD_ID);
	$discussion_record	= $db->GetRow($query);
	if ($discussion_record) {
		if (discussions_module_access($RECORD_ID, "view-forum")) {
			$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-forum&id=".$RECORD_ID, "title" => $discussion_record["forum_title"]);

			/**
			 * Update requested sort column.
			 * Valid: date, title
			 */
			if (isset($_GET["sb"])) {
				if (@in_array(trim($_GET["sb"]), array("date", "title", "poster", "replies"))) {
					$_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"] = trim($_GET["sb"]);
				}

				$_SERVER["QUERY_STRING"]	= replace_query(array("sb" => false));
			} else {
				if (!isset($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"])) {
					$_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"] = "date";
				}
			}

			/**
			 * Update requested order to sort by.
			 * Valid: asc, desc
			 */
			if (isset($_GET["so"])) {
				$_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"] = ((strtolower($_GET["so"]) == "desc") ? "desc" : "asc");

				$_SERVER["QUERY_STRING"]	= replace_query(array("so" => false));
			} else {
				if (!isset($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"])) {
					$_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"] = "desc";
				}
			}

			/**
			 * Update requsted number of rows per page.
			 * Valid: any integer really.
			 */
			if ((isset($_GET["pp"])) && ((int) trim($_GET["pp"]))) {
				$integer = (int) trim($_GET["pp"]);

				if (($integer > 0) && ($integer <= 250)) {
					$_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"] = $integer;
				}

				$_SERVER["QUERY_STRING"] = replace_query(array("pp" => false));
			} else {
				if (!isset($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"])) {
					$_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"] = 15;
				}
			}

			/**
			 * Provide the queries with the columns to order by.
			 */
			switch($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"]) {
				case "title" :
					$sort_by	= "a.`topic_title` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]);
				break;
				case "replies" :
					$sort_by	= "COUNT(b.`cdtopic_id`) ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]);
				break;
				case "poster" :
					$sort_by	= "CONCAT_WS(', ', c.`lastname`, c.`firstname`) ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]);
				break;
				case "date" :
				default :
					$sort_by	= "`latest_activity` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]);
				break;
			}

			/**
			 * Get the total number of results using the generated queries above and calculate the total number
			 * of pages that are available based on the results per page preferences.
			 */
			$query	= "
					SELECT  COUNT(*) AS `total_rows`
					FROM `community_discussion_topics`
					WHERE `cdiscussion_id` = ".$db->qstr($RECORD_ID)."
					AND `community_id` = ".$db->qstr($COMMUNITY_ID)."
					AND `topic_active` = '1'
					AND `cdtopic_parent` = '0'
					".((!$COMMUNITY_ADMIN) ? " AND ((`proxy_id` = ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]).") OR (`release_date` = '0' OR `release_date` <= ".$db->qstr(time()).") AND (`release_until` = '0' OR `release_until` > ".$db->qstr(time())."))" : "");
			$result	= $db->GetRow($query);
			if ($result) {
				$total_rows	= $result["total_rows"];

				if ($total_rows <= $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]) {
					$total_pages = 1;
				} elseif (($total_rows % $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]) == 0) {
					$total_pages = (int) ($total_rows / $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]);
				} else {
					$total_pages = (int) ($total_rows / $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]) + 1;
				}
			} else {
				$total_rows		= 0;
				$total_pages	= 1;
			}

			/**
			 * Check if pv variable is set and see if it's a valid page, other wise page 1 it is.
			 */
			if (isset($_GET["pv"])) {
				$page_current = (int) trim($_GET["pv"]);

				if (($page_current < 1) || ($page_current > $total_pages)) {
					$page_current = 1;
				}
			} else {
				$page_current = 1;
			}

			if ($total_pages > 1) {
				$pagination = new Pagination($page_current, $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"], $total_rows, COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL, replace_query());
			}

			/**
			 * Provides the first parameter of MySQLs LIMIT statement by calculating which row to start results from.
			 */
			$limit_parameter = (int) (($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"] * $page_current) - $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]);
			?>
			<h1><?php echo html_encode($discussion_record["forum_title"]); ?></h1>

			<div id="module-header">
				<?php
				if ($total_pages > 1) {
					echo "<div id=\"pagination-links\">\n";
					echo "Pages: ".$pagination->GetPageLinks();
					echo "</div>\n";
				}
				?>
				<a href="<?php echo COMMUNITY_URL."/feeds".$COMMUNITY_URL.":".$PAGE_URL."/rss?id=".$RECORD_ID; ?>" class="feeds rss">Subscribe to RSS</a>
				<?php if (COMMUNITY_NOTIFICATIONS_ACTIVE && $_SESSION["details"]["notifications"]) { ?>
					<div id="notifications-toggle" style="display: inline;"></div>
					<script type="text/javascript">
					function promptNotifications(enabled) {
						Dialog.confirm('Do you really wish to '+ (enabled == 1 ? "disable" : "enable") +' notifications for this forum?',
							{
								id:				'requestDialog',
								width:			350,
								height:			75,
								title:			'Notification Confirmation',
								className:		'medtech',
								okLabel:		'Yes',
								cancelLabel:	'No',
								closable:		'true',
								buttonClass:	'button small',
								destroyOnClose:	true,
								ok:				function(win) {
													new Window(	{
																	id:				'resultDialog',
																	width:			350,
																	height:			75,
																	title:			'Notification Result',
																	className:		'medtech',
																	okLabel:		'close',
																	buttonClass:	'button small',
																	resizable:		false,
																	draggable:		false,
																	minimizable:	false,
																	maximizable:	false,
																	recenterAuto:	true,
																	destroyOnClose:	true,
																	url:			'<?php echo ENTRADA_URL."/api/notifications.api.php?community_id=".$COMMUNITY_ID."&id=".$RECORD_ID; ?>&type=post&action=edit&active='+(enabled == 1 ? '0' : '1'),
																	onClose:			function () {
																						new Ajax.Updater('notifications-toggle', '<?php echo ENTRADA_URL."/api/notifications.api.php?community_id=".$COMMUNITY_ID."&id=".$RECORD_ID; ?>&type=post&action=view');
																					}
																}
													).showCenter();
													return true;
												}
							}
						);
					}
					
					</script>
					<?php
					$ONLOAD[] = "new Ajax.Updater('notifications-toggle', '".ENTRADA_URL."/api/notifications.api.php?community_id=".$COMMUNITY_ID."&id=".$RECORD_ID."&type=post&action=view')";
				}
				?>
			</div>
			
			<div style="padding-top: 10px; clear: both">
				<?php
				if (discussions_module_access($RECORD_ID, "add-post")) {
					?>
					<div style="float: right; padding-top: 10px;">
						<ul class="page-action">
							<li><a href="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-post&id=<?php echo $RECORD_ID; ?>">New Post</a></li>
						</ul>
					</div>
					<div style="clear: both"></div>
					<?php
				}
				
				$query		= "	SELECT a.*, COUNT(b.`cdtopic_id`) AS `total_replies`, IF(MAX(b.`updated_date`) IS NOT NULL, MAX(b.`updated_date`), a.`updated_date`) AS `latest_activity`, CONCAT_WS(' ', c.`firstname`, c.`lastname`) AS `original_poster_fullname`, c.`username` AS `original_poster_username`, CONCAT_WS(' ', d.`firstname`, d.`lastname`) AS `latest_poster_fullname`, d.`username` AS `latest_poster_username`
								FROM `community_discussion_topics` AS a
								LEFT JOIN `community_discussion_topics` AS b
								ON a.`cdtopic_id` = b.`cdtopic_parent`
								AND b.`community_id` = ".$db->qstr($COMMUNITY_ID)."
								AND b.`topic_active` = '1'
								LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
								ON a.`proxy_id` = c.`id`
								LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS d
								ON b.`proxy_id` = d.`id`
								WHERE a.`cdiscussion_id` = ".$db->qstr($RECORD_ID)."
								AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
								AND a.`topic_active` = '1'
								AND (b.`topic_active` IS NULL OR b.`topic_active` = '1')
								AND a.`cdtopic_parent` = '0'
								".((!$COMMUNITY_ADMIN) ? " AND ((a.`proxy_id` = ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]).") OR (a.`release_date` = '0' OR a.`release_date` <= ".$db->qstr(time()).") AND (a.`release_until` = '0' OR a.`release_until` > ".$db->qstr(time())."))" : "")."
								GROUP BY a.`cdtopic_id`
								ORDER BY %s, b.`updated_date` DESC
								LIMIT %s, %s";
				$query		= sprintf($query, $sort_by, $limit_parameter, $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]);
				$results	= $db->GetAll($query);
				if ($results) {
					?>
					<table class="discussions forums" style="width: 100%" cellspacing="0" cellpadding="0" border="0">
					<colgroup>
						<col style="width: 45%" />
						<col style="width: 11%" />
						<col style="width: 20%" />
						<col style="width: 24%" />
					</colgroup>
					<thead>
						<tr>
							<td<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"] == "title") ? " class=\"sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"])."\"" : ""); ?>><?php echo communities_order_link("title", "Topic Title"); ?></td>
							<td<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"] == "replies") ? " class=\"sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"])."\"" : ""); ?> style="border-left: none; text-align: left"><?php echo communities_order_link("replies", "Replies"); ?></td>
							<td<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"] == "poster") ? " class=\"sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"])."\"" : ""); ?> style="border-left: none"><?php echo communities_order_link("poster", "Topic Starter"); ?></td>
							<td<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"] == "date") ? " class=\"sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"])."\"" : ""); ?> style="border-left: none"><?php echo communities_order_link("date", "Latest Action"); ?></td>
						</tr>
					</thead>
					<tbody>
					<?php
					foreach($results as $key => $result) {
						$accessible	= true;

						if ((($result["release_date"]) && ($result["release_date"] > time())) || (($result["release_until"]) && ($result["release_until"] < time()))) {
							$accessible = false;
						}

						if ((!$latest_activity = trim($result["latest_activity"])) || (!$latest_poster_username = trim($result["latest_poster_username"])) || (!$latest_poster_fullname = trim($result["latest_poster_fullname"]))) {
							$latest_poster_username = $result["original_poster_username"];
							$latest_poster_fullname = $result["original_poster_fullname"];
							$latest_activity		= $result["updated_date"];
						}

						echo "<tr".((!$accessible) ? " class=\"na\"" : "").">\n";
						echo "	<td>\n";
						echo "		<a id=\"topic-".(int) $result["cdtopic_id"]."-title\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-post&amp;id=".$result["cdtopic_id"]."\" style=\"font-weight: bold\">".limit_chars(html_encode($result["topic_title"]), 65, true)."</a>\n";
						echo "	</td>\n";
						echo "	<td>".(int) $result["total_replies"]."</td>\n";
						echo "	<td style=\"font-size: 10px; white-space: nowrap; overflow: hidden\"><a href=\"".ENTRADA_URL."/people?profile=".html_encode($result["original_poster_username"])."\" style=\"font-size: 10px\">".html_encode($result["original_poster_fullname"])."</a></td>\n";
						echo "	<td style=\"font-size: 10px; white-space: nowrap; overflow: hidden\">\n";
						echo "		".date(DEFAULT_DATE_FORMAT, $latest_activity)."<br />\n";
						echo "		<strong>By:</strong> <a href=\"".ENTRADA_URL."/people?profile=".html_encode($latest_poster_username)."\" style=\"font-size: 10px\">".html_encode($latest_poster_fullname)."</a>\n";
						echo "	</td>\n";
						echo "</tr>\n";

					}
					?>
					</tbody>
					</table>
					<?php
				} else {
					$NOTICE++;
					$NOTICESTR[] = "<strong>No topics in this forum.</strong><br /><br />".((discussions_module_access($RECORD_ID, "add-post")) ? "If you would like to create a new post, <a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add-post&id=".$RECORD_ID."\">click here</a>." : "Please check back later.");

					echo display_notice();
				}
				?>
			</div>
			<?php
		} else {
			if ($ERROR) {
				echo display_error();
			}
			if ($NOTICE) {
				echo display_notice();
			}
		}
	} else {
		application_log("error", "The provided discussion forum id was invalid [".$RECORD_ID."] (View Forum).");

		header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
		exit;
	}
} else {
	application_log("error", "No discussion forum id was provided to view. (View Forum)");

	header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
	exit;
}
?>
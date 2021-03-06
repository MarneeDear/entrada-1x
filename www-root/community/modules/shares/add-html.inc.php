<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to add html documents to a specific folder of a community.
 * 
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2014 Regents of The University of California. All Rights Reserved.
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_SHARES"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

$HEAD[] = "<link href=\"".ENTRADA_URL."/javascript/calendar/css/xc2_default.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/config/xc2_default.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/script/xc2_inpage.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".COMMUNITY_URL."/javascript/shares.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";

echo "<h1>Add HTML Document</h1>\n";

$isCommunityCourse = Models_Community_Course::is_community_course($COMMUNITY_ID);

if ($RECORD_ID) {
	$query			= "SELECT * FROM `community_shares` WHERE `cshare_id` = ".$db->qstr($RECORD_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID);
	$folder_record	= $db->GetRow($query);
	if ($folder_record) {
        if (shares_module_access($RECORD_ID, "add-html")) {
            $BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-folder&id=".$folder_record["cshare_id"], "title" => limit_chars($folder_record["folder_title"], 32));
            $BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add-html&id=".$RECORD_ID, "title" => "Add HTML Document");

            if ($isCommunityCourse) {
                $course_groups_query = "SELECT a.*, b.`course_code`, b.`course_name`
                          FROM `course_groups` AS a
                          JOIN `courses` AS b
                          ON b.`course_id` = a.`course_id`
                          JOIN `community_courses` AS c
                          ON c.`course_id` = b.`course_id`
                          WHERE a.`active` = 1
                          AND c.`community_id` = ".$db->qstr($COMMUNITY_ID);
                $community_course_groups = $db->GetAll($course_groups_query);
                ?>
                <script type="text/javascript">
                    jQuery(document).ready(function() {
                       function hideCourseGroups() {
                           if (jQuery("#course-group-checkbox").is(':checked')) {
                                jQuery(".course-group-permissions").show();
                           } else {
                                jQuery(".course-group-permissions").hide();
                           }
                       }
                       //Set the initial UI state
                       hideCourseGroups();

                       jQuery(".permission-type-checkbox").click(function() {
                           hideCourseGroups();
                       });
                    });
                </script>
                <?php
            }

            // Error Checking
            switch($STEP) {
                case 2 :

                    /**
                     * Required field "title" / HTML Title.
                     */
                    if (isset($_POST["html_title"]) && ($title = clean_input($_POST["html_title"], array("notags", "trim")))) {
                        $PROCESSED["html_title"] = $title;
                    } else {
                        $ERROR++;
                        $ERRORSTR[] = "The <strong>HTML Title</strong> field is required.";
                    }

                    /**
                     * Non-Required field "description" / HTML Description.
                     *
                     */
                    if (isset($_POST["html_description"]) && $description = clean_input($_POST["html_description"], array("notags", "trim"))) {
                        $PROCESSED["html_description"] = $description;
                    } else {
                        $PROCESSED["html_description"] = "";
                    }

                    /**
                    * Required field  "page_content" / Page Contents.
                    */
                    if (isset($_POST["html_content"])) {
                        $PROCESSED["html_content"] = clean_input($_POST["html_content"], array("trim", "allowedtags"));
                    } else {
                        $ERROR++;
                        $ERRORSTR[] = "The <strong>HTML Content</strong> field is required.";
                    }

                    /**
                     * Non-Required field "access_method" / View Method.
                     */
                    if (isset($_POST["access_method"]) && clean_input($_POST["access_method"], array("int")) == 1) {
                        $PROCESSED["access_method"] = 1;
                    } else {
                        $PROCESSED["access_method"] = 0;
                    }

                    /**
                     * Non-Required field "student_hidden" / View Method.
                     */
                    if (isset($_POST["student_hidden"]) && clean_input($_POST["student_hidden"], array("int")) == 1) {
                        $PROCESSED["student_hidden"] = 1;
                    } else {
                        $PROCESSED["student_hidden"] = 0;
                    }

                    /**
                     * Required field "permission_acl_style" for community courses
                     */
                    if ($isCommunityCourse) {
                        if (!isset($_POST["permission_acl_style"])) {
                            $ERROR++;
                            $ERRORSTR[] = "The <strong>Permission Level</strong> field is required.";
                        }
                    }

                    /**
                     * Permission checking for member access.
                     */
                    if ((isset($_POST["allow_member_read"])) && (clean_input($_POST["allow_member_read"], array("int")) == 1)) {
                        $PROCESSED["allow_member_read"]	= 1;
                    } else {
                        $PROCESSED["allow_member_read"]	= 0;
                    }

                    /**
                     * Permission checking for troll access.
                     * This can only be done if the community_registration is set to "Open Community"
                     */
                    if (!(int) $community_details["community_registration"]) {
                        if ((isset($_POST["allow_troll_read"])) && (clean_input($_POST["allow_troll_read"], array("int")) == 1)) {
                            $PROCESSED["allow_troll_read"]	= 1;
                        } else {
                            $PROCESSED["allow_troll_read"]	= 0;
                        }
                    } else {
                        $PROCESSED["allow_troll_read"]		= 0;
                    }


                    /**
                     * Required field "release_from" / Release Start (validated through validate_calendars function).
                     * Non-required field "release_until" / Release Finish (validated through validate_calendars function).
                     */
                    $release_dates = validate_calendars("release", true, false);
                    if ((isset($release_dates["start"])) && ((int) $release_dates["start"])) {
                        $PROCESSED["release_date"]	= (int) $release_dates["start"];
                    } else {
                        $ERROR++;
                        $ERRORSTR[] = "The <strong>Release Start</strong> field is required.";
                    }
                    if ((isset($release_dates["finish"])) && ((int) $release_dates["finish"])) {
                        $PROCESSED["release_until"]	= (int) $release_dates["finish"];
                    } else {
                        $PROCESSED["release_until"]	= 0;
                    }

                    if (!$ERROR) {
                        $PROCESSED["cshare_id"]		= $RECORD_ID;
                        $PROCESSED["community_id"]	= $COMMUNITY_ID;
                        $PROCESSED["proxy_id"]		= $ENTRADA_USER->getActiveId();
                        $PROCESSED["html_active"]	= 1;
                        $PROCESSED["updated_date"]	= time();
                        $PROCESSED["updated_by"]	= $ENTRADA_USER->getID();


                        unset($PROCESSED["cshtml_id"]);
                        if ($db->AutoExecute("community_share_html", $PROCESSED, "INSERT")) {
                            if ($HTML_ID = $db->Insert_Id()) {
                                //Add course group permissions to community_acl_groups
                                if ($_POST['permission_acl_style'] === 'CourseGroupMember' && $community_course_groups && !empty($community_course_groups)) {
                                    foreach ($community_course_groups as $community_course_group) {
                                        //Set the default value to '0'
                                        $PROCESSED[$community_course_group['cgroup_id']] = array("create" => 0, "read" => 0, "update" => 0, "delete" => 0);

                                        if ($_POST[$community_course_group['cgroup_id']]) {
                                            foreach ($_POST[$community_course_group['cgroup_id']] as $perms) {
                                                //Update the value to '1' if it was submitted
                                                $PROCESSED[$community_course_group['cgroup_id']][clean_input($perms)] = 1;
                                            }
                                        }

                                        $db->AutoExecute("community_acl_groups", array("cgroup_id" => $community_course_group['cgroup_id'], "resource_type" => "communityhtml", "resource_value" => $HTML_ID, "create" => $PROCESSED[$community_course_group['cgroup_id']]['create'], "read" => $PROCESSED[$community_course_group['cgroup_id']]['read'], "update" => $PROCESSED[$community_course_group['cgroup_id']]['update'], "delete" => $PROCESSED[$community_course_group['cgroup_id']]['delete']), "INSERT");
                                    }
                                }

                                //If the user's role is 'admin', use the submitted form values
                                if ($COMMUNITY_ADMIN) {
                                    $update_perm = array(
                                        'read' => (($_POST['read']) ? 1 : 0),
                                        'create' => (($_POST['create']) ? 1 : 0),
                                        'update' => (($_POST['update']) ? 1 : 0),
                                        'delete' => (($_POST['delete']) ? 1 : 0),
                                        'assertion' => $_POST['permission_acl_style']
                                    );
                                } else {
                                    //If the user is not an admin, set these default permissions
                                    $update_perm = array(
                                        'read' => 1,
                                        'create' => 0,
                                        'update' => 0,
                                        'delete' => 0,
                                        'assertion' => $_POST['permission_acl_style']
                                    );
                                }
                                $update_perm['resource_type'] = "communityhtml";
                                $update_perm['resource_value'] = $HTML_ID;
                                $results = $db->AutoExecute("`community_acl`", $update_perm, "INSERT");
                                if ($results === false) {
                                    $ERROR++;
                                    $ERRORSTR[] = "Error updating the community ACL.";
                                }

                                $PROCESSED["cshtml_id"]	= $HTML_ID;
                                $url = COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-folder&id=".$RECORD_ID;
                                $ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000)";

                                $SUCCESS++;
                                $SUCCESSSTR[]	= "You have successfully added ".html_encode($PROCESSED["html_title"]).".<br /><br />You will now be redirected to this html document; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
                                add_statistic("community:".$COMMUNITY_ID.":shares", "html_add", "cshtml_id", $VERSION_ID);
                                communities_log_history($COMMUNITY_ID, $PAGE_ID, $HTML_ID, "community_history_add_html", 1, $RECORD_ID);  
                            }
                        }
                    }

                    if ($ERROR) {
                        $STEP = 1;
                    }
            break;
            case 1 :
            default :
                continue;
                break;
            }

            // Page Display
            switch($STEP) {
                case 2 :
                    if ($NOTICE) {
                        echo display_notice();
                    }
                    if ($SUCCESS) {
                        echo display_success();
                        if (COMMUNITY_NOTIFICATIONS_ACTIVE) {
                            community_notify($COMMUNITY_ID, $HTML_ID, "html", COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-html&id=".$HTML_ID, $RECORD_ID, $PROCESSES["release_date"]);
                        }
                    }
                break;
                case 1 :
                default :					
                    if ($ERROR) {
                        echo display_error();
                        $NOTICE++;
                        $NOTICESTR[] = "There was an error while trying to add your html document.";
                    }
                    if ($NOTICE) {
                        echo display_notice();
                    }
                    load_rte("communityadvanced");
                    ?>
                    <form class="form-horizontal" action="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-html&amp;id=<?php echo $RECORD_ID; ?>&amp;step=2" method="post">
                        <table class="community-add-table" summary="Adding HTML">
                            <colgroup>
                                <col style="width: 3%" />
                                <col style="width: 20%" />
                                <col style="width: 77%" />
                            </colgroup>
                        <tbody>
                            <tr>
                                <td colspan="2">
                                    <label for="html_title" class="form-required">HTML Title</label>
                                </td>
                                <td>
                                    <input type="text" id="menu_title" name="html_title" value="<?php echo ((isset($PROCESSED["html_title"])) ? html_encode($PROCESSED["html_title"]) : ""); ?>" maxlength="128" style="width: 90%; margin-bottom: 10px;"/>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <label for="html_description" class="form-nrequired">HTML Description</label>
                                </td>
                                <td>
                                    <textarea id="html_description" name="html_description" maxlength="256" class="expandable" style="width: 90%; margin-bottom: 10px; "><?php echo ((isset($PROCESSED["html_description"])) ? html_encode($PROCESSED["html_description"]) : ""); ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3"><label for="html_content" class="form-required">HTML Content</label></td>
                            </tr>
                            <tr>
                                <td colspan="3">
                                    <textarea id="html_content" name="html_content" style="width: 98%; height: 200px" rows="20" cols="70"><?php echo ((isset($PROCESSED["html_content"])) ? html_encode($PROCESSED["html_content"]) : ""); ?></textarea>
                                </td>
                            </tr>

                            <tr>
                                <td colspan="2">
                                    <label for="access_method" class="form-nrequired">Access Method</label>
                                </td>
                                <td>
                                    <table class="table table-bordered no-thead">
                                        <colgroup>
                                            <col style="width: 5%" />
                                            <col style="width: auto" />
                                        </colgroup>
                                        <tbody>
                                        <tr>
                                            <td class="center">
                                                <input type="radio" id="access_method_0" name="access_method" value="0"<?php echo (((!isset($PROCESSED["access_method"])) || ((isset($PROCESSED["access_method"])) && (!(int) $PROCESSED["access_method"]))) ? " checked=\"checked\"" : ""); ?> />
                                            </td>
                                            <td>
                                                <label for="access_method_0" class="content-small">Open this document in <?php echo APPLICATION_NAME ?></label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="center">
                                                <input type="radio" id="access_method_1" name="access_method" value="1"<?php echo (((isset($PROCESSED["access_method"])) && ((int) $PROCESSED["access_method"])) ? " checked=\"checked\"" : ""); ?> />
                                            </td>
                                            <td>
                                                <label for="access_method_1" class="content-small">Open this document in a new window.</label>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <label for="student_hidden" class="form-nrequired">Would you like to hide this file from students?</label>
                                </td>
                                <td>
                                    <table class="table table-bordered no-thead">
                                        <colgroup>
                                            <col style="width: 5%" />
                                            <col style="width: auto" />
                                        </colgroup>
                                        <tbody>
                                        <tr>
                                            <td class="center">
                                                <input type="radio" id="student_hidden_0" name="student_hidden" value="0" style="vertical-align: middle"<?php echo (((!isset($PROCESSED["student_hidden"])) || ((isset($PROCESSED["student_hidden"])) && (!(int) $PROCESSED["student_hidden"]))) ? " checked=\"checked\"" : ""); ?> />
                                            </td>
                                            <td>
                                                <label for="student_hidden_0" class="content-small">Allow students to view this file.</label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="center">
                                                <input type="radio" id="student_hidden_1" name="student_hidden" value="1" style="vertical-align: middle"<?php echo (((isset($PROCESSED["student_hidden"])) && ((int) $PROCESSED["student_hidden"])) ? " checked=\"checked\"" : ""); ?> />
                                            </td>
                                            <td>
                                                <label for="student_hidden_1" class="content-small">Hide this file from students.</label>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3"><h2>HTML Document Permissions</h2></td>
                            </tr>

                            <?php if ($isCommunityCourse) { ?>
                            <tr>
                                <td colspan="2" style="vertical-align: top !important">
                                    <label for="permission_level" class="form-required">Permission Level: </label>
                                </td>
                                <td style="vertical-align: top">
                                    <table class="table table-bordered no-thead">
                                        <colgroup>
                                            <col style="width: 5%" />
                                            <col style="width: auto" />
                                        </colgroup>
                                        <tr>
                                            <td>
                                                <input id="community-all-checkbox" class="permission-type-checkbox" type="radio" name="permission_acl_style" value="CourseCommunityEnrollment" checked="checked"  />
                                            </td>
                                            <td>
                                                <label for="community-all-checkbox" class="content-small">All Community Members</label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <input id="course-group-checkbox" class="permission-type-checkbox" type="radio" name="permission_acl_style" value="CourseGroupMember" />
                                            </td>
                                            <td>
                                                <label for="course-group-checkbox" class="content-small">Course Groups</label>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <?php if ($COMMUNITY_ADMIN) { ?>
                            <tr class="file-permissions">
                                <td colspan="3"><h3>HTML Document Permissions</h3></td>
                            </tr>
                            <tr class="file-permissions">
                                <td colspan="3">
                                    <table class="table table-striped table-bordered table-community-centered">
                                        <colgroup>
                                            <col style="width: 50%" />
                                            <col style="width: 50%" />
                                        </colgroup>
                                        <thead>
                                            <tr>
                                                <td>View HTML Document</td>
                                                <td style="border-left: none">&nbsp;</td>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="on"><input type="checkbox" id="read" name="read" value="read" checked="checked" /></td>
                                                <td>&nbsp;</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <?php } ?>

                            <tr class="course-group-permissions">
                                <td colspan="3"><h3>Course Group Permissions</h3></td>
                            </tr>
                            <tr class="course-group-permissions">
                                <td colspan="3">
                                <?php
                                $course_ids = array_unique(array_map(function($item) { return (int)$item['course_id']; }, $community_course_groups));
                                foreach ($course_ids as $course_id) {
                                    $course_groups = array_filter($community_course_groups, function($item) use ($course_id) {
                                        return (int)$item['course_id'] === $course_id;
                                    });
                                    usort($course_groups, function($a, $b) {
                                        if ($a['group_name'] < $b['group_name']) {
                                            return -1;
                                        } else if ($a['group_name'] > $b['group_name']) {
                                            return 1;
                                        } else {
                                            return 0;
                                        }
                                    });
                                    $course_code = $course_groups[0]['course_code'];
                                    $course_name = $course_groups[0]['course_name'];

                                    echo "<h4>$course_code: $course_name</h4>";
                                    ?>
                                    <table class="table table-striped table-bordered table-community-centered-list">
                                        <colgroup>
                                            <col style="width: 50%" />
                                            <col style="width: 50%" />
                                        </colgroup>
                                        <thead>
                                            <tr>
                                                <td >Group</td>
                                                <td style="border-left: none">View HTML Document</td>
                                            </tr>
                                        </thead>
                                    <tbody>
                                    <?php

                                    foreach ($course_groups as $course_group) {
                                    ?>
                                        <tr>
                                            <td class="left"><strong><?php echo $course_group['group_name']; ?></strong></td>
                                            <td class="on"><input type="checkbox" id="<?php echo $course_group['cgroup_id']; ?>_read" name="<?php echo $course_group['cgroup_id']; ?>[]" value="read" /></td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                    </tbody>
                                    </table>
                                    <?php
                                }
                                ?>

                                <?php if (!(int) $community_details["community_registration"]) { ?>
                                    <h4>Non-members</h4>
                                    <table class="table table-striped table-bordered table-community-centered-list">
                                        <colgroup>
                                            <col style="width: 50%" />
                                            <col style="width: 50%" />
                                        </colgroup>
                                        <thead>
                                            <tr>
                                                <td>Group</td>
                                                <td style="border-left: none">View HTML Document</td>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="left"><strong>Browsing Non-Members</strong></td>
                                                <td class="on"><input type="checkbox" id="allow_troll_read" name="allow_troll_read" value="1"<?php echo (((!isset($PROCESSED["allow_troll_read"])) || ((isset($PROCESSED["allow_troll_read"])) && ($PROCESSED["allow_troll_read"] == 1))) ? " checked=\"checked\"" : ""); ?> /></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                <?php } ?>
                                </td>
                            </tr>

                            <?php } else { ?>
                            <tr>
                                <td colspan="3">
                                    <table class="table table-striped table-bordered table-community-centered-list">
                                    <colgroup>
                                        <col style="width: 40%" />
                                        <col style="width: 20%" />
                                        <col style="width: 25%" />
                                        <col style="width: 15%" />
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <td>Group</td>
                                            <td>View HTML Document</td>
                                            <td style="border-left: none">&nbsp;</td>
                                            <td style="border-left: none">&nbsp;</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="left"><strong>Community Administrators</strong></td>
                                            <td class="on"><input type="checkbox" id="allow_admin_read" name="allow_admin_read" value="1" checked="checked" onclick="this.checked = true" /></td>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td class="left"><strong>Community Members</strong></td>
                                            <td class="on"><input type="checkbox" id="allow_member_read" name="allow_member_read" value="1"<?php echo (((!isset($PROCESSED["allow_member_read"])) || ((isset($PROCESSED["allow_member_read"])) && ($PROCESSED["allow_member_read"] == 1))) ? " checked=\"checked\"" : ""); ?> /></td>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                        </tr>
                                        <?php if (!(int) $community_details["community_registration"]) : ?>
                                        <tr>
                                            <td class="left"><strong>Browsing Non-Members</strong></td>
                                            <td class="on"><input type="checkbox" id="allow_troll_read" name="allow_troll_read" value="1"<?php echo (((!isset($PROCESSED["allow_troll_read"])) || ((isset($PROCESSED["allow_troll_read"])) && ($PROCESSED["allow_troll_read"] == 1))) ? " checked=\"checked\"" : ""); ?> /></td>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    </table>
                                </td>
                            </tr>
                            <?php } ?>
                            <tr>
                                <td colspan="3"><h2>Time Release Options</h2></td>
                            </tr>
                            <tr>
                                <td colspan="3">
                                    <table class="date-time">
                                        <?php
                                        echo generate_calendars("release", "", true, true, ((isset($PROCESSED["release_date"])) ? $PROCESSED["release_date"] : time()), true, false, ((isset($PROCESSED["release_until"])) ? $PROCESSED["release_until"] : 0));
                                        ?>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" style="padding-top: 15px; text-align: right">
                                    <input type="submit" class="btn btn-primary" value="<?php echo $translate->_("global_button_save"); ?>" />
                                </td>
                            </tr>
                        </table>
                    </form>
                    <?php
                break;
            }
        } else {
			if ($ERROR) {
				echo display_error();
			}
			if ($NOTICE) {
				echo display_notice();
			}
		}
	} else {
		application_log("error", "The provided folder id was invalid [".$RECORD_ID."] (Add HTML).");

		header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
		exit;
	}
} else {
	application_log("error", "No folder id was provided to create into. (Add HTML)");

	header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
	exit;
}
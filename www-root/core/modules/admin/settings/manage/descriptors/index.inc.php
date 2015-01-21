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
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_DESCRIPTORS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "read", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.dataTables.min.js\"></script>";
    $HEAD[] = "<script type=\"text/javascript\">

        jQuery(function($) {
            jQuery('#descriptors').dataTable(
                {
                    'sPaginationType': 'full_numbers',
                    'bInfo': false,
                    'bAutoWidth': false,
                    'sAjaxSource': '?org=".$ORGANISATION_ID."&section=api-list',
                    'bServerSide': true,
                    'bProcessing': true,
                    'aoColumns': [
                        { 'mDataProp': 'checkbox', 'bSortable': false },
                        { 'mDataProp': 'descriptor' }
                    ],
                    'oLanguage': {
                        'sEmptyTable': 'There are currently no evaluation response descriptors in the system.',
                        'sZeroRecords': 'No evaluation response descriptors found to display.'
                    }
                }
            );
        });
    </script>";

    echo "<h1>Manage Evaluation Response Descriptors</h1>";
    ?>
    <?php if ($ENTRADA_ACL->amIAllowed("configuration", "create", false)) { ?>
        <div class="row-fluid space-below">
            <a href="<?php echo ENTRADA_URL."/admin/settings/manage/descriptors?org=".$ORGANISATION_ID; ?>&section=add" class="btn btn-success pull-right"><i class="icon-plus-sign icon-white"></i> Add Descriptor</a>
        </div>
    <?php } ?>
    <form action="<?php echo ENTRADA_URL."/admin/settings/manage/descriptors?org=".$ORGANISATION_ID; ?>&section=delete" method="POST">
        <table id="descriptors" class="table table-striped table-bordered">
            <thead>
            <tr>
                <th width="5%"></th>
                <th>Descriptor</th>
            </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
        <?php if ($ENTRADA_ACL->amIAllowed("configuration", "delete", false)) { ?>
            <input type="submit" value="Delete" class="btn" />
        <?php } ?>
    </form>
<?php

}
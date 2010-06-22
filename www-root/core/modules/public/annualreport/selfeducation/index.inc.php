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
 * @author Developer: Andrew Dos-Santos <andrew.dos-santos@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/
if((!defined("PARENT_INCLUDED")) || (!defined("IN_ANNUAL_REPORT"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
}

switch($_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["group"]) {
	case "faculty" :
		/**
		 * Display Annual Report Sections to the Faculty.
		 */
		if ($ENTRADA_ACL->amIAllowed('annualreport', 'read')) {			
			if(!isset($_SESSION["self_expand_grid"])) {
				$_SESSION["self_expand_grid"] = "self_education_grid";
			}
			?>
			<h1>Section <?php echo (!$_SESSION["details"]["clinical_member"] ? "V" : "IV"); ?> - Self Education / Faculty Development</h1>
			
			<table id="flex1" style="display:none"></table>
			
			<script type="text/javascript">
			
			var jQuerydialog = jQuery('<div></div>')
				.html('<span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>You must select at least one record in order to delete.')
				.dialog({
					autoOpen: false,
					title: 'Please Select a Record',
					buttons: {
						Ok: function() {
							jQuery(this).dialog('close');
						}
					}
				});
				
			<?php $fields = "ar_self_education,self_education_id,institution,description,activity_type,year_reported"; ?>
			var self_education_grid = jQuery("#flex1").flexigrid
			(
				{
				url: '<?php echo ENTRADA_URL; ?>/api/ar_loadgrid.api.php?id=<?php echo $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]; ?>&t=<?php echo $fields; ?>',
				dataType: 'json',
				method: 'POST',
				colModel : [
					{display: 'Institution', name : 'institution', width : 104, sortable : true, align: 'left'},
					{display: 'Description', name : 'description', width : 385, sortable : true, align: 'left'},
					{display: 'Activity Type', name : 'activity_type', width : 75, sortable : true, align: 'left'},
					{display: 'Year', name : 'year_reported', width : 50, sortable : true, align: 'left'},
					{display: 'Edit', name : 'ctled', width : 25,  sortable : false, align: 'center', process:edRow}
					],
				searchitems : [
					{display: 'Institution', name : 'institution'},
					{display: 'Description', name : 'description'},
					{display: 'Activity Type', name : 'activity_type'},
					{display: 'Year', name : 'year_reported', isdefault: true}
					],
				sortname: "year_reported",
				sortorder: "desc",
				resizable: false, 
				usepager: true,
				showToggleBtn: false,
				collapseTable: <?php echo ($_SESSION["self_expand_grid"] == "self_education_grid" ? "false" : "true"); ?>,
				title: 'A. Meetings / Courses',
				useRp: true,
				rp: 15,
				showTableToggleBtn: true,
				width: 732,
				height: 200,
				nomsg: 'No Results', 
				buttons : [
	                {name: 'Add', bclass: 'add', onpress : addRecord},
	                {separator: true}, 
	                {name: 'Delete Selected', bclass: 'delete', onpress : deleteRecord}
	                ]
				}
			);
				
			function addRecord(com,grid) {
                if (com=='Add') {
                     window.location='<?php echo ENTRADA_URL; ?>/annualreport/selfeducation?section=add_self';
                }            
            }
             
            function edRow(celDiv,id) {
            	celDiv.innerHTML = "<a href='<?php echo ENTRADA_URL; ?>/annualreport/selfeducation?section=edit_self&amp;rid="+id+"' style=\"cursor: pointer; cursor: hand\" text-decoration: none><img src=\"<?php echo ENTRADA_RELATIVE; ?>/images/action-edit.gif\" style=\"border: none\"/></a>";
		    }
		    
            function deleteRecord(com,grid) {
			    if (com=='Delete Selected') {
			    	jQuery(function() {
						if(jQuery('.trSelected',grid).length>0) {
				    		// a workaround for a flaw in the demo system (http://dev.jqueryui.com/ticket/4375), ignore!
							jQuery("#dialog-confirm").dialog("destroy");
						
							jQuery("#dialog-confirm").dialog({
								resizable: false,
								height:180,
								modal: true,
								buttons: {
									'Delete all items': function() {
										var ids = "";
					               		jQuery('.trSelected', grid).each(function() {
											var id = jQuery(this).attr('id');
											id = id.substring(id.lastIndexOf('row')+3);
											if(ids == "") {
												ids = id;
											} else {
												ids = id+"|"+ids;
											}
										});
										jQuery.ajax
							            ({
							               type: "POST",
							               dataType: "json",
							               url: '<?php echo ENTRADA_URL; ?>/api/ar_delete.api.php?id=<?php echo $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]; ?>&t=<?php echo $fields; ?>&rid='+ids
							             });
								       	
								       	window.setTimeout('self_education_grid.flexReload()', 1000);
										jQuery(this).dialog('close');
									},
									Cancel: function() {
										jQuery(this).dialog('close');
									}
								}
							});
				    	} else {
					    	jQuerydialog.dialog('open');
				    	}
					});
			    }          
			} 
			</script>
			
			<div id="dialog-confirm" title="Delete?" style="display: none">
				<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>These items will be permanently deleted and cannot be recovered. Are you sure?</p>
			</div>

			<?php
		}
	break;
}
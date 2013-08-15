var ENTRADA_URL;

jQuery(document).ready(function($) {
	var loading_html = '<img width="16" height="16" src="'+ENTRADA_URL+'/images/loading.gif">';
	
	var flexiopts = {
		resizable: false,
		height: 'auto',
		width: 'auto',
		disableSelect: true,
		showToggleBtn: false
	};
	
	var gradebookize = function() {
		var singleOptions = [
			{display: 'Student Name', name: 'name', width: $('.late-submissions').length >= 1 ? 239 : 400, sortable: false},
			{display: 'Student Number', name: 'number', width: 100, sortable: false},
			{display: 'Student Mark', name: 'name', width: 73, sortable: false},
			{display: 'Percent', name: 'name', width: 30, sortable: false}
		];
		var lateSubmissions = null;
		if ($('.late-submissions').length >= 1) {
			singleOptions.push({display: 'Late Submissions', name: 'name', width: 30, sortable: false});
		}
		var reSubmissions = null;
		if ($('.resubmissions').length >= 1) {
			singleOptions.push({display: 'Resubmission', name: 'name', width: 30, sortable: false});
		}
		$('table.gradebook.single').flexigrid($.extend({}, flexiopts, {
			colModel: singleOptions			
		}));

		$('table.gradebook.numeric').flexigrid($.extend({}, flexiopts, {
			colModel: [
				{display: 'Student Name', name: 'name', width: 233, sortable: false},
				{display: 'Student Number', name: 'number', width: 100, sortable: false},
				{display: 'Student Mark', name: 'name', width: 73, sortable: false},
				{display: 'Percent', name: 'name', width: 30, sortable: false}
			]
		}));

		$('table.gradebook.assignment').flexigrid($.extend({}, flexiopts, {
			colModel: [

			]
		}));

		$('table.gradebook').flexigrid($.extend({}, flexiopts, {
			title: "Gradebook",
			buttons : [
				{name: "Close", bclass: "gradebook_edit_close"},
				{name: "Add Assessment", bclass: "gradebook_edit_add"},
				{name: "Export Shown Assessments", bclass: "gradebook_export"},
				{separator: true},
				{name: "Change Grad Year", bclass: "change_gradebook_year"}
			]
		}));

		$('.change_gradebook_year').html( $('#toolbar').html() ).css('margin', '-3px');
		$('.change_gradebook_year select').change(function(e) {
			$('.gradebook_edit').html(loading_html);
			$.ajax({
				url: $('#fullscreen-edit').attr('href') + "&cohort=" + $(this).val(),
				cache: false, 
				success: function(data, status, request) {
					$('.gradebook_edit').html(data);
					gradebookize();
				},
				error: function()  {
					alert("Error loading the new gradebook to edit! Please refresh the page.");
				}
			});
		});

		$('table.gradebook.gradebook_editable .grade').editable(ENTRADA_URL+'/api/gradebook.api.php', {
			placeholder: '-',
			indicator: loading_html,
			onblur: 'submit',
			width: 40,
			cssclass: 'editing',
			onsubmit: function(settings, original) {
			},
			submitdata: function(value, settings) {
				return {
					grade_id: $(this).attr('data-grade-id'),
					assessment_id: $(this).attr('data-assessment-id'),
					proxy_id: $(this).attr('data-proxy-id')
				};
			},
			callback: function(value, settings) {
				// If grade came back deleted remove the grade ID data
				if(value != "-") {
					var values = value.split("|");
					var grade_id = values[0];
					value = values[1];
					$(this).html(value);				
				}
					
				var suffix = $(this).next('.gradesuffix').html().split('|');

				if(value == "-") {
					var percent = 0;
					$(this).attr('data-grade-id', '');
					$(this).next('.gradesuffix').hide();
				} else {
					if (suffix[1]) {
						var percent = (value/suffix[1]*100).toFixed(2);
					}
					
					$(this).attr('data-grade-id', grade_id);
					$(this).next('.gradesuffix').show();
				}
				
				if (suffix[1]) {
					var id_suffix = $(this).attr('id').substring(5);
					$('#percentage'+id_suffix).html('<div style="width: 45px; ">'+percent+'%</div>');
                }
                if ($('#grades'+$(this).attr('data-proxy-id')).hasClass('highlight')) {
                    $('#grades'+$(this).attr('data-proxy-id')).removeClass('highlight');
                }
			}
		}).keyup(function(e){
			var dest;
			
			switch(e.which) {
				case 38: // Up
				case 40: // Down
				case 13: // Enter
					// Go up or down a line
					$('input', this).trigger('blur');
					var pos = $(this).parent().parent().prevAll().length;
					var row = $(this).parent().parent().parent();
					if(e.which == 38) { //going up!
						dest = row.prev();
						if($(dest).attr('class').indexOf('comment-row') !== -1){
							dest = dest.prev();
						}
					} else {
						dest = row.next();
						if($(dest).attr('class').indexOf('comment-row') !== -1){
							dest = dest.next();
						}
					}

					if(dest) {
						var next = dest.children()[pos];
						if(next) {
							next = $(next).find('.grade');
						}
					}

					$(next).trigger('click');
				break;
				default:
				break;
			}
		});
	};	
	
	gradebookize();
	
	$('.gradebook_edit').jqm({
		ajax: '@data-href',
		ajaxText: loading_html,
		trigger: $("#fullscreen-edit"),
		modal: true,
		toTop: true,
		overlay: 100,
		onShow: function(hash) {
			hash.w.show();
		},
		onLoad: function(hash) {
			gradebookize();
			
		},
		onHide: function(hash) {
			hash.o.hide();
			hash.w.hide();
		}
	});
	
	$("#fullscreen-edit").click(function(e) {
		e.preventDefault();
		$('#navigator-container').hide();
	});
	
	$('.gradebook_edit_close').live('click', function(e) {
		e.preventDefault();
		$('#navigator-container').show();
		$('.gradebook_edit').jqmHide();
	});
	
	$('.gradebook_edit_add').live('click', function(e) {
		window.location = $("#gradebook_assessment_add").attr('href');
	});
	$('.gradebook_export').live('click', function(e) {
        var ids = [];
        $$('#assessment_ids').each(function(input) {
            ids.push($F(input));
        });
        if(ids.length > 0) {
            window.location = $("#gradebook_export_url").attr('value')+ids.join(',');
        } else {
            alert("There are no assessments to export for this cohort.");
        }
        return false;
	});
	$(".late-button").on("click", function(e) {
		$(this).siblings(".late").click();
		e.preventDefault();
	});
	$('.late').on("click", function(e) {
		$(this).hide();
		var input = $(document.createElement("input"));
		input.attr("type", "text")
			 .attr("data-id", $(this).attr("data-id"))
			 .attr("data-proxy-id", $(this).attr("data-proxy-id"))
			 .attr("data-aovalue-id", $(this).attr("data-aovalue-id"))
			 .addClass("input-mini")
			 .addClass("late-input")
			 .appendTo($(this).parent()).focus();
		e.preventDefault();
	});
	$('.late-submissions').on('blur', '.late-input', function(e) {
		var input = $(this);
		$.ajax({
			url: ENTRADA_URL + "/admin/gradebook/assessments?section=grade",
			data: "ajax=ajax&method=store-late&value=" + $(this).attr("value") + "&aoption_id=" + $(this).attr("data-id") + "&proxy_id=" + $(this).attr("data-proxy-id") + "&aovalue_id=" + $(this).attr("data-aovalue-id"),
			type: "POST",
			success: function(data) {
				var jsonResponse = JSON.parse(data);
				if (jsonResponse.status == "success") {
					if (jsonResponse.data.value > 0) {
						input.siblings(".late")
							 .html(jsonResponse.data.value)
							 .attr("data-aovalue-id", jsonResponse.data.aovalue_id)
							 .attr("data-proxy-id", jsonResponse.data.proxy_id);
					} else {
						input.siblings(".late").html("-");
					}
				} else {
					input.siblings(".late").html("-");
				}
				input.hide();
				input.siblings(".late").show();
				input.remove();
			}
		})
		e.preventDefault();
	});
	$(".resubmissions input").on("change", function(e) {
		var input = $(this);
		var value = "0";
		if ($(this).is(":checked")) {
			value = "1";
		}
		$.ajax({
			url: ENTRADA_URL + "/admin/gradebook/assessments?section=grade",
			data: "ajax=ajax&method=store-resubmit&value=" + value + "&aoption_id=" + $(this).attr("data-id") + "&proxy_id=" + $(this).attr("data-proxy-id") + "&aovalue_id=" + $(this).attr("data-aovalue-id"),
			type: "POST",
			success: function(data) {
				var jsonResponse = JSON.parse(data);
				if (jsonResponse.status == "success") {
					input.attr("data-aovalue-id", jsonResponse.data.aovalue_id)
						 .attr("data-proxy-id", jsonResponse.data.proxy_id);
				}
			}
		})
		e.preventDefault();
	});
});
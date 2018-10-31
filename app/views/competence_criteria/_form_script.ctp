<script type="text/javascript">
	$(function () {
		<?php if (isset($competence_criterion_rubrics_definitions)): ?>
			var rubrics_definitions = <?php echo $this->Javascript->object($competence_criterion_rubrics_definitions); ?>;

			$.widget( "custom.rubricselectmenu", $.ui.selectmenu, {
		      	_renderItem: function(ul, item) {
				  	var li = $("<li>"),
				  		definition = rubrics_definitions[item.value] || '--- Sin evaluar ---',
				  		col1 = $('<div class="ui-menu-item-cell ui-menu-item-cell--nowrap">').text(item.label);
				  		col2 = $('<div class="ui-menu-item-cell">').text(definition);

				  	if ( item.disabled ) {
				    	li.addClass( "ui-state-disabled" );
				  	}

				  	return li.append(col1).append(col2).appendTo(ul);
				},
				_renderMenu: function(ul, items) {
					var that = this;
					$.each(items, function(index, item) {
					    that._renderItemData( ul, item );
					});
					$(ul).addClass('ui-menu--table').find("li:odd").addClass("ui-menu-item--odd");
				},
				_resizeMenu: function () {
				}
		    });

	    	$('#competence_criterion_grades select').each(function () {
	    		$(this).rubricselectmenu({
		    		change: function(event, data) {
			    		$(this).closest('tr').find('.competence_rubric_definition').text(
			    			rubrics_definitions[data.item.value] || ''
		    			);
			       	}
		    	});
	    	});
		<?php endif; ?>
  	});

	function addRubricRow() {
		var container = $(document.getElementById('rubrics'));
		var index = "_new_" + (new Date).getTime();
		var rowId = "rubrics_row_" + index;
		container.append('<tr id="' + rowId + '" data-new="true"><td><input type="text" required class="td_input" name="data[CompetenceCriterionRubric][' + index + '][title]" /></td><td><input type="text" class="td_input" name="data[CompetenceCriterionRubric][' + index + '][definition]" /></td><td><input type="number" required step="0.01" class="td_input" name="data[CompetenceCriterionRubric][' + index + '][value]" /></td><td><a href="javascript:;" onclick="deleteRow(\'rubrics\', \'' + index + '\')">Eliminar</a></td></tr>');
	}

	function addAutocompleteSubjectRow(autocompleteUrl) {
		var container = $('#subjects');
		var index = "_new_" + (new Date).getTime();
		var rowId = "subjects_row_" + index;
		var inputId = "autocomplete_" + rowId;
		container.append('<tr id="' + rowId + '" data-new="true"><td><input type="text" id="' + inputId + '" class="td_input" /></td><td><input id="' + rowId + '_id" type="hidden" name="data[CompetenceCriterionSubject][' + index + '][subject_id]" value="" disabled="disabled"><input id="' + rowId + '_name" type="hidden" name="data[CompetenceCriterionSubject][' + index + '][Subject][name]" value="" disabled="disabled"><a href="javascript:;" onclick="deleteRow(\'subjects\', \'' + index + '\')">Eliminar</a></td></tr>');
		$(document.getElementById(inputId))
			.autocomplete(autocompleteUrl, {
				formatItem: function (row) {
					if (row[1] != null) {
						return row[0];
					} else {
						return 'No se ha encontrado nada por ese nombre.';
					}
				}
			})
			.result(function(event, item) {
				$(document.getElementById(rowId + '_id')).val(item[1]).prop('disabled', false);
				$(document.getElementById(rowId + '_name')).val(item[0]).prop('disabled', false);
			});
	}

	function addAutocompleteTeacherRow(autocompleteUrl) {
		var container = $('#teachers');
		var index = "_new_" + (new Date).getTime();
		var rowId = "teachers_row_" + index;
		var inputId = "autocomplete_" + rowId;
		container.append('<tr id="' + rowId + '" data-new="true"><td><input type="text" id="' + inputId + '" class="td_input" /></td><td><input id="' + rowId + '_id" type="hidden" name="data[CompetenceCriterionTeacher][' + index + '][teacher_id]" value="" disabled="disabled"><input id="' + rowId + '_full_name" type="hidden" name="data[CompetenceCriterionTeacher][' + index + '][Teacher][full_name]" value="" disabled="disabled"><a href="javascript:;" onclick="deleteRow(\'teachers\', \'' + index + '\')">Eliminar</a></td></tr>');
		$(document.getElementById(inputId))
			.autocomplete(autocompleteUrl, {
				formatItem: function (row) {
					if (row[1] != null) {
						return row[0];
					} else {
						return 'No se ha encontrado nada por ese nombre.';
					}
				}
			})
			.result(function(event, item) {
				$(document.getElementById(rowId + '_id')).val(item[1]).prop('disabled', false);
				$(document.getElementById(rowId + '_full_name')).val(item[0]).prop('disabled', false);
			});
	}

	function editRow(listName, index) {
		var row = $(document.getElementById(listName + '_row_' + index));
		var actions = row.find('>td:last');
		actions
			.prevAll()
			.find(':input').each((i, elem) => {
				var input = $(elem);
				input
					.data('originalVal', input.val())
					.show()
					.siblings('span').hide();
			});
		actions.find('[data-action="edit"],[data-action="delete"]').hide();
		actions.find('[data-action="cancel"]').show();
	}
	
	function deleteRow(listName, index) {
		var list = $(document.getElementById(listName));
		var row = $(document.getElementById(listName + '_row_' + index));
		if (row.data('new')) {
			row.remove();
		} else {
			var deleteCallback = () => {
				var actions = row.find('>td:last');
				actions.find('[data-action="edit"],[data-action="delete"]').hide();
				actions.find('[data-action="cancel"]').show();	
				actions.prevAll().addClass('line_through');
				row.find(':input').prop('disabled', true);
			};
			var alert = list.data('delete-confirm');
			if (alert) {
				if (confirm(alert)) {
					deleteCallback();
				}
			} else {
				deleteCallback();
			}
			
		}
	}

	function cancelRow(listName, index) {
		var row = $(document.getElementById(listName + '_row_' + index));
		var actions = row.find('>td:last');
		actions
			.prevAll()
			.removeClass('line_through')
			.find(':input').each((i, elem) => {
				var input = $(elem);
				input
					.val(input.data('originalVal'))
					.prop('disabled', false)
					.hide()
					.siblings('span').show();
			});
		actions.find(':input').prop('disabled', false);
		actions.find('[data-action="edit"],[data-action="delete"]').show();
		actions.find('[data-action="cancel"]').hide();
	}	
</script>
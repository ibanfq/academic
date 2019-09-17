<?php $html->addCrumb('Usuarios', '/institutions/ref:users'); ?>
<?php $html->addCrumb(Environment::institution('name'), Environment::getBaseUrl() . '/users'); ?>
<?php $html->addCrumb("{$student['User']['first_name']} {$student['User']['last_name']}", Environment::getBaseUrl() . "/users/view/{$student['User']['id']}"); ?>
<?php $html->addCrumb('E-portfolio', Environment::getBaseUrl() . "/competence/by_student/{$student['User']['id']}"); ?>
<?php $html->addCrumb("Competencia {$competence['Competence']['code']}", Environment::getBaseUrl() . "/competence/view_by_student/{$student['User']['id']}/{$competence['Competence']['id']}"); ?>
<?php $html->addCrumb("Objetivo {$competence_goal['CompetenceGoal']['code']}", Environment::getBaseUrl() . "/competence_goals/view_by_student/{$student['User']['id']}/{$competence_goal['CompetenceGoal']['id']}"); ?>
<?php $html->addCrumb('Evaluar criterios', Environment::getBaseUrl() . "/competence_goals/grade_by_student/{$student['User']['id']}/{$competence_goal['CompetenceGoal']['id']}"); ?>

<h1>
	<?php echo isset($competence_goal_request)? 'Evaluar solicitud de evaluación del estudiante' : 'Evaluar criterios por estudiante' ?>:
	<?php echo h("{$student['User']['first_name']} {$student['User']['last_name']}") ?>
</h1>

<?php echo $form->create('CompetenceCriterionGrade', array('id' => 'competence_criterion_grade_form', 'url' => $this->Html->url(null, true)));?>

<?php require('_view_resume.ctp') ?>

<fieldset>
<legend>Criterios de evaluación</legend>
	<div class="horizontal-scrollable-content">
		<table>
			<thead>
				<tr>
					<th style="width:6em">Código</th>
					<th style="width:40%">Definición</th>
					<th style="width:18em">Valoración nota final</th>
					<th style="width:40%">Rúbrica</th>
				</tr>
			</thead>
			<tbody id="competence_criterion_grades">
				<?php foreach ($competence_goal['CompetenceCriterion'] as $criterion): ?>
				<?php $criterion_id = $criterion['id'] ?>
				<?php $competence_criterion_rubrics_values = set::combine($criterion, 'CompetenceCriterionRubric.{n}.id', 'CompetenceCriterionRubric.{n}.title'); ?>
				<?php $competence_criterion_rubrics_definitions = set::combine($criterion, 'CompetenceCriterionRubric.{n}.id', 'CompetenceCriterionRubric.{n}.definition'); ?>
					<tr>
						<td>
							<?php echo h($criterion['code']) ?>
						</td>
						<td>
							<?php echo h($criterion['definition']) ?>
						</td>
						<td>
							<?php echo $form->hidden("CompetenceCriterionGrade.{$criterion_id}.criterion_id", array('value' => $criterion_id)); ?>
							<?php echo $form->select("CompetenceCriterionGrade.{$criterion_id}.rubric_id", $competence_criterion_rubrics_values, null, array('required' => isset($competence_goal_request), 'data-definitions' => $this->Javascript->object($competence_criterion_rubrics_definitions))); ?>
							<div class="message invalid-message position-relative ui-tooltip ui-corner-all ui-widget ui-widget-content" style="display:none;">
								<span class="ui-tooltip-content">Elija una opción válida</span>
							</div>
						</td>
						<td>
							<span class="competence_rubric_definition"><?php echo empty($this->data['CompetenceCriterionGrade'][$criterion_id]['rubric_id']) ? '' : h($competence_criterion_rubrics_definitions[$this->data['CompetenceCriterionGrade'][$criterion_id]['rubric_id']]) ?></span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</fieldset>
	
<?php echo $form->end(isset($competence_goal_request)? 'Completar solicitud de evaluación' : 'Modificar'); ?>

<script type="text/javascript">
	$(function () {
		$.widget( "custom.rubricselectmenu", $.ui.selectmenu, {
	      	_renderItem: function(ul, item) {
			  	var li = $('<li class="ui-menu-item-row-group">'),
			  		definitions = $(item.element).closest('select').data('definitions'),
					definition = definitions[item.value] || '--- Sin evaluar ---',
					row = $('<div class="ui-menu-item-row">')
						.append($('<div class="ui-menu-item-cell ui-menu-item-cell--nowrap">').text(item.label))
			  			.append($('<div class="ui-menu-item-cell">').text(definition));

			  	if ( item.disabled ) {
			    	li.addClass( "ui-state-disabled" );
				}

				<?php if (isset($competence_goal_request)): ?>
					if ( !item.value ) {
						li.addClass( "ui-state-disabled" );
					}	
				<?php endif; ?>

			  	return li.append(row).appendTo(ul);
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
	    			var select = $(this),
	    				definitions = select.data('definitions');
		    		select.closest('tr').find('.competence_rubric_definition').text(
		    			definitions[data.item.value] || ''
	    			);
		       	}
	    	});
		});
	});
</script>

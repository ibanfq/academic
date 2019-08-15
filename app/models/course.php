<?php

App::import('model', 'academicModel');

class Course extends AcademicModel {
    var $name = 'Course';
    var $actsAs = array('Course');
    var $hasMany = array(
        'Subject' => array(
            'className' => 'Subject',
            'order' => 'Subject.code ASC',
            'dependent' => true
        )
    );
    var $validate = array(
        'institution_id' => array(
            'rule' => 'notEmpty',
            'required' => true,
            'message' => 'Debe especificar el centro academico'
        ),
        'name' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Debe especificar un nombre para el curso' 
        ),
        'initial_date' => array(
            'date' => array(
                'rule' => 'date',
                'required' => true,
                'message' => 'La fecha de inicio no puede estar vacía y debe tener la forma día/mes/año (p.ej 01/01/2010)'
            ), 
            'course_overlap' => array(
                'rule' => array('courseOverlap'),
                'message' => 'Existe un curso que coincide con las fechas seleccionadas'
            )
        ), 
        'final_date' => array(
            'date' => array(
                'rule' => 'date',
                'required' => true,
                'message' => 'La fecha de fin no puede estar vacía y debe tener la forma día/mes/año (p.ej 01/01/2010)'
            ), 
            '' => array(
                'rule' => array('ltInitialDate'), 
                'message' => "La fecha de fin debe ser posterior a la fecha de inicio"
            )
        )
    );
    
    function courseOverlap($initial_date) {
    	App::import('Core', 'Sanitize');;
        $institution_id = Sanitize::escape($this->data[$this->alias]['institution_id']);
        $initial_date = Sanitize::escape($this->data[$this->alias]['initial_date']);
        $final_date = Sanitize::escape($this->data[$this->alias]['final_date']);
        $query = "
            SELECT *
            FROM courses
            WHERE institution_id = '{$institution_id}' AND (
                (initial_date <= '{$initial_date}' AND final_date >= '{$initial_date}')
                OR (initial_date <= '{$final_date}' AND final_date >= '{$final_date}')
                OR (initial_date >= '{$initial_date}' AND final_date <= '{$final_date}')
            )
            ";

        if (isset($this->data[$this->alias]['id'])) {
            $id = Sanitize::escape($this->data[$this->alias]['id']);
            $query .= " AND id <> '{$id}'";
        }

        $overlaped_courses = $this->query($query);

        return count($overlaped_courses) == 0;
    }

    /**
     * Returns the latest final date of all courses
     *
     * @return string Latest final date
     * @since 2012-05-19
     */
    function latestFinalDate() {
        App::import('Lib', 'Environment');
        App::import('Core', 'Sanitize');;
        $institution_id = Sanitize::escape(Environment::institution('id'));
        $course = $this->query(
            sprintf(
                "SELECT MAX(%s.final_date) AS max_final_date FROM %s AS %s WHERE %s.institution_id = '%s'",
                $this->alias,
                $this->useTable,
                $this->alias,
                $this->alias,
                $institution_id
            )
        );

        if ($course) {
            return $course[0][0]['max_final_date'];
        } else {
            return date('Y-m-d');
        }
    }

    function ltInitialDate($final_date) {
        $initial_date = $this->data[$this->alias]['initial_date'];
        return $final_date > $initial_date;
    }
    
    function afterFind($results, $primary) {
        if ($primary) {
            foreach ($results as $key => $val) {
                if (array_key_exists('Course', $results[$key]) && array_key_exists('initial_date', $results[$key]['Course'])) {
                    $results[$key]['Course']['initial_date'] =  $this->dateFormatUser($val['Course']['initial_date']);
                }
                if (array_key_exists('Course', $results[$key]) && array_key_exists('final_date', $results[$key]['Course'])) {
                    $results[$key]['Course']['final_date'] = $this->dateFormatUser($val['Course']['final_date']);
                }
            }
        } else {
            if (array_key_exists('initial_date', $results)) {
                $results['initial_date'] =  $this->dateFormatUser($results['initial_date']);
            }
            if (array_key_exists('final_date', $results)) {
                $results['final_date'] = $this->dateFormatUser($results['final_date']);
            }
        }
        return $results;
    }

    function beforeValidate(){
        if (!empty($this->data['Course']['initial_date']))
            $this->data['Course']['initial_date'] = $this->dateFormatInternal($this->data['Course']['initial_date']);
        if (!empty($this->data['Course']['final_date']))
            $this->data['Course']['final_date'] = $this->dateFormatInternal($this->data['Course']['final_date']);

        return true;
    }
    
    function onError() {
        if (!empty($this->data['Course']['initial_date']))
            $this->data['Course']['initial_date'] = $thist->dateFormatInternal($this->data['Course']['initial_date']);
        if (!empty($this->data['Course']['initial_date']))
            $this->data['Course']['initial_date'] = $thist->dateFormatInternal($this->data['Course']['initial_date']);
    }
    
    function current() {
        App::import('Lib', 'Environment');
        $today = date("Y-m-d");
        
        $course = $this->find(
            'first',
            array(
                'conditions' => array(
                    'Course.institution_id' => Environment::institution('id'),
                    'Course.initial_date <=' => $today,
                    'Course.final_date >=' => $today,
                ),
                'recursive' => -1
            )
        );

        if ($course == null) {
            $course = $this->find(
                'first',
                array(
                    'conditions' => array('Course.institution_id' => Environment::institution('id')),
                    'order' => array('Course.initial_date desc')
                )
            );
        }
        
        return $course['Course'];
    }
}

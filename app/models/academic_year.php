<?php

App::import('model', 'academicModel');

class AcademicYear extends AcademicModel {
    var $name = 'AcademicYear';
    var $validate = array(
        'initial_date' => array(
            'date' => array(
                'rule' => 'date',
                'required' => true,
                'message' => 'La fecha de inicio no puede estar vacía y debe tener la forma día/mes/año (p.ej 01/01/2010)'
            ), 
            'date_overlap' => array(
                'rule' => array('date_overlap'),
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
    
    function date_overlap($initial_date) {
    	App::import('Core', 'Sanitize');
        $initial_date = Sanitize::escape($this->data[$this->alias]['initial_date']);
        $final_date = Sanitize::escape($this->data[$this->alias]['final_date']);
        $query = "
            SELECT *
            FROM academic_years
            WHERE (
                (initial_date <= '{$initial_date}' AND final_date >= '{$initial_date}')
                OR (initial_date <= '{$final_date}' AND final_date >= '{$final_date}')
                OR (initial_date >= '{$initial_date}' AND final_date <= '{$final_date}')
            )
        ";

        if (isset($this->data[$this->alias]['id'])) {
            $id = Sanitize::escape($this->data[$this->alias]['id']);
            $query .= " AND id <> '{$id}'";
        }

        $overlaped_year = $this->query($query);

        return count($overlaped_year) == 0;
    }

    /**
     * Returns the latest final date of all dates
     *
     * @return string Latest final date
     * @since 2012-05-19
     */
    function latestFinalDate() {
        App::import('Lib', 'Environment');
        App::import('Core', 'Sanitize');
        $academic_year = $this->query(
            sprintf(
                "SELECT MAX(%s.final_date) AS max_final_date FROM %s AS %s",
                $this->alias,
                $this->useTable,
                $this->alias,
                $this->alias
            )
        );

        if ($academic_year) {
            return $academic_year[0][0]['max_final_date'];
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
                if (array_key_exists('AcademicYear', $results[$key]) && array_key_exists('initial_date', $results[$key]['AcademicYear'])) {
                    $results[$key]['AcademicYear']['initial_date'] =  $this->dateFormatUser($val['AcademicYear']['initial_date']);
                }
                if (array_key_exists('AcademicYear', $results[$key]) && array_key_exists('final_date', $results[$key]['AcademicYear'])) {
                    $results[$key]['AcademicYear']['final_date'] = $this->dateFormatUser($val['AcademicYear']['final_date']);
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
        if (!empty($this->data['AcademicYear']['initial_date']))
            $this->data['AcademicYear']['initial_date'] = $this->dateFormatInternal($this->data['AcademicYear']['initial_date']);
        if (!empty($this->data['AcademicYear']['final_date']))
            $this->data['AcademicYear']['final_date'] = $this->dateFormatInternal($this->data['AcademicYear']['final_date']);

        return true;
    }
    
    function onError() {
        if (!empty($this->data['AcademicYear']['initial_date']))
            $this->data['AcademicYear']['initial_date'] = $thist->dateFormatInternal($this->data['AcademicYear']['initial_date']);
        if (!empty($this->data['AcademicYear']['initial_date']))
            $this->data['AcademicYear']['initial_date'] = $thist->dateFormatInternal($this->data['AcademicYear']['initial_date']);
    }
    
    function current() {
        App::import('Lib', 'Environment');
        $today = date("Y-m-d");
        
        $academic_year = $this->find(
            'first',
            array(
                'conditions' => array(
                    'AcademicYear.initial_date <=' => $today,
                    'AcademicYear.final_date >=' => $today,
                ),
                'recursive' => -1
            )
        );

        if ($academic_year == null) {
            $academic_year = $this->find(
                'first',
                array(
                    'order' => array('AcademicYear.initial_date desc')
                )
            );
        }
        
        return $academic_year['AcademicYear'];
    }
}

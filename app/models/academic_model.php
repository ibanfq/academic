<?php
class AcademicModel extends AppModel {
    function dateFormatUser($dateString) {
        return date('d-m-Y', strtotime($dateString));
    }
    
    function dateFormatInternal($dateString) {
        /*list($day,$month,$year) = explode('/', $dateString);
        $timestamp = mktime(0,0,0,$month,$day, $year);*/
        return date('Y-m-d', strtotime($dateString));
    }
}

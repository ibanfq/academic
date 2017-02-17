<?php 

class CleanUpDayShell extends Shell {
  function main() {
    require_once(CAKE . 'dispatcher.php');
    
    $dispatcher = new Dispatcher;
    $dispatcher->dispatch('attendance_registers/clean_up_day', array('return' => true));
  }
}
?>
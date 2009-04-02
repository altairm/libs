<?php

require_once dirname(__FILE__) . '/Abstract.php';

class Conveyor_Object_Test extends Conveyor_Object_Abstract {
    public function conditionFail($modifire = null) {
        $this->_result = false;
    }
    public function conditionSuccess($modifire = null) {
        $this->_result = true;
    }
}
?>
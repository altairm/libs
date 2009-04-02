<?php

require_once dirname(__FILE__) . '/Abstract.php';

class Conveyor_Object_Permission extends Conveyor_Object_Abstract {
    public function conditionFail($modifire = null) {
        $this->_result[$modifire] = false;
    }
    public function conditionSuccess($modifire = null) {
        $this->_result[$modifire] = true;
    }
}
?>
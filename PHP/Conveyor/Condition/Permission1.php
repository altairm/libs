<?php
require_once dirname(__FILE__) . '/Abstract.php';

class Conveyor_Condition_Permission1 extends Conveyor_Condition_Abstract{
    const CONVEYOR_CONDITION_PERMESSION1_CODE = 1;
    const CONVEYOR_CONDITION_PERMESSION1_MODIFIRE = "PERMISSION 1";
    /**
     * @param Conveyor_Object_Abstract $object
     */
    public function process($object) {
        $this->_checkObject($object);
        $data = $object->getData();
        if($data & Conveyor_Condition_Permission1::CONVEYOR_CONDITION_PERMESSION1_CODE) {
            $object->conditionSuccess(Conveyor_Condition_Permission1::CONVEYOR_CONDITION_PERMESSION1_MODIFIRE);
            return;
        }
        $object->conditionFail(Conveyor_Condition_Permission1::CONVEYOR_CONDITION_PERMESSION1_MODIFIRE);
    }
}
?>
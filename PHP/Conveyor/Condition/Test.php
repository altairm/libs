<?php

require_once dirname(__FILE__) . '/Abstract.php';

class Conveyor_Condition_Test extends Conveyor_Condition_Abstract{

    /**
     * @param Conveyor_Object_Abstract $object
     */
    public function process($object) {
        if(!($object instanceof Conveyor_Object_Abstract)) {
            require_once dirname(__FILE__) . '/Exception.php';
            throw new Conveyor_Condition_Exception('Variable is not object');
        }
        $data = $object->getData();
        if(empty($data)) {
            $object->conditionFail();
            return;
        }
        $object->conditionSuccess();
    }
}
?>
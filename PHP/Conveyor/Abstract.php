<?php

abstract class Conveyor_Abstract {
    protected $_conveyor = array();

    /**
     * add condition to conveyor
     * @param Conveyor_Condition_Abstract $condition
     * @return void
     */
    public function addCondition($condition) {
        if(!is_object($condition) && !($condition instanceof Conveyor_Condition_Abstract)) {
            require_once dirname(__FILE__) . '/Exception.php';
            throw new Conveyor_Exception('Invalid condition');
        }
        if(!isset($this->_conveyor[$condition->getPriority()])) {
            $this->_conveyor[$condition->getPriority()] = array();
        }
        $this->_conveyor[$condition->getPriority()][] = $condition;
    }
    public function process($object) {
        foreach($this->_conveyor as $priority => $conditions) {
            foreach ($conditions as $condition) {
                $condition->process($object);
            }
        }
    }
}
?>
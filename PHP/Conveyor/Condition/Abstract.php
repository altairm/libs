<?php
abstract class Conveyor_Condition_Abstract {
    
    const CONVEYOR_CONDITION_PRIORITY_HIGH      = 3;
    const CONVEYOR_CONDITION_PRIORITY_NORMAL    = 2;
    const CONVEYOR_CONDITION_PRIORITY_LOW       = 1;
    
    protected $_priority = 1;
    
    public function __construct($priority = 1) {
        $this->_priority = (int) $priority;
    }
    public function getPriority() {
        return $this->_priority;
    }
    public function setPriority($priority = 1) {
        $this->_priority = (int) $priority;
    }
    protected function _checkObject($object) {
        if(!($object instanceof Conveyor_Object_Abstract)) {
            require_once dirname(__FILE__) . '/Exception.php';
            throw new Conveyor_Condition_Exception('Variable is not object');
        }
    }
    /**
     * 
     * @param Conveyor_Object_Abstract $object
     * @return void
     */
    abstract public function process($object);
}
?>
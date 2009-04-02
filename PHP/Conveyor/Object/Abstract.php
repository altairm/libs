<?php
abstract class Conveyor_Object_Abstract {
    
    protected $_data;
    protected $_result;
    
    public function __construct($data = null) {
        $this->_data = $data;
    }
    public function setData($data = null) {
        $this->_data = $data;
    }
    public function getData() {
        return $this->_data;
    }
    public function getResult() {
        return $this->_result;
    }
    abstract public function conditionFail($modifire = null);
    abstract public function conditionSuccess($modifire = null);
}
?>
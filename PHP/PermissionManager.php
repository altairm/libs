<?php
class PermissionManager {
    /**
     * @var array
     */
    protected $_conf;
    /**
     * @param  array $config
     *<p>array(
     *      'perm1'         => array(), // allowed to all
     *      'perm2:perm1'   => array('!rule1', '!rule2'), // extended from perm1 + deny to rule1 and rule2
     *      'perm3'         => array('!*', 'rule3'), // deny to all, allowed to rule 3
     *      'perm4:perm3'   => 'rule4', // extended from perm3 + allowed to rule4
     *  )</p>
     * @return PermissionManager
     */
    public function __construct(array $config) {
        foreach ($config as $key => $value) {
            if(false !== strpos($key, ':')) {
                list($realKey, $parentKey) = explode(":", $key);
            } else {
                list($realKey, $parentKey) = array($key, null);
            }
            if(!empty($parentKey)) {
                if(is_string($config[$key])) {
                    $config[$key] = array($config[$key]);
                }
                if(is_string($config[$parentKey])) {
                    $config[$parentKey] = array($config[$parentKey]);
                }
                $config[$realKey] = array_merge($config[$parentKey], $config[$key]);
                unset($config["$realKey:$parentKey"]);
            }
        }
        $this->_conf = $config;
    }
    /**
     * return config
     * @return array
     */
    public function get() {
        return $this->_conf;
    }
    /**
     * check permission for rule
     * @param  string $key
     * @param  string $value
     * @return boolean
     */
    public function check($key, $value) {
        if (!isset($this->_conf[$key])) {
            return false;
        }
        if(in_array('!*', $this->_conf[$key])) {
            return in_array($value, $this->_conf[$key]);
        } else {
            return !in_array("!$value", $this->_conf[$key]);
        }
    }
}
?>
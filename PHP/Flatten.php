<?php
/**
 *  Make array|json as flatten array
 * 
 * @author altair
 * @since 2013.07.31 12:47:01 PM
 */
class Flatten {

    const KEY_PATH_DELIMITER = '.';
    /**
     * key path delimiter
     * @var string
     */
    protected $keyPathDelim;
    /**
     * hold flatten keys
     * @var array
     */
    protected $key_container;
    /**
     * result array
     * @var array
     */
    protected $res;
    /**
     * constructor
     * @author altair
     * @since  2013.07.31 12:54:26 PM
     * @param  string $key_path_delimiter
     */
    public function __construct( $key_path_delimiter = Flatten::KEY_PATH_DELIMITER ) {
        $this->keyPathDelim = $key_path_delimiter;
    }
    /**
     * make array flateen
     * @author altair
     * @since  2013.07.31 12:48:22 PM
     * @param  array  $array
     * @return array|flase
     */
    public function flateArray( array $array) {
        unset($this->res);
        return $this->array_walk_recursive_with_keys($array);
    }
    /**
     * make json string as flatten array
     * @author altair
     * @since  2013.07.31 12:49:03 PM
     * @param  string $json
     * @return array|false
     */
    public function flateJson( $json = '') {
        unset($this->res);
        $array = @json_decode($json, true);
        if ( empty($array) ) {
            return false;
        }
        return $this->array_walk_recursive_with_keys($array);
    }
    /**
     * array walk and keep keys as path
     * @author altair
     * @since  2013.07.31 12:50:11 PM
     * @param  array  $array
     * @param  integer $depth
     * @return array
     */
    protected function array_walk_recursive_with_keys(array $array, $depth = 0) {
        if ( is_array($array) ) {
            foreach ($array as $key => $value) {
                $curr_key = $this->build_key($key, $depth);
                if ( is_array($value) ) {
                    $this->array_walk_recursive_with_keys($value, $depth+1);
                } else {
                    $this->res[$curr_key] = $value;
                }
            }
            return $this->res;
        } else {
            return false;
        }
    }
    /**
     * buil key path
     * @author altair
     * @since  2013.07.31 12:51:10 PM
     * @param  string  $key_part
     * @param  integer $depth
     * @return string
     */
    protected function build_key( $key_part, $depth = 0) {
        $this->key_container[$depth] = $key_part;
        $key_str = join($this->keyPathDelim, array_slice($this->key_container, 0, $depth+1));
        return $key_str;
    }
}


$a = new Flatten();
$array = array( 
    'A' => array('B' => array( 1, 2, 3, 4, 5)), 
    'C' => array( 6,7,8,9) 
); 

print_r($a->flateArray($array));
print_r($a->flateJson('{"profiles":[{"type":"user","network":"facebook","name":"Neamma","b8_id":"1523598278","_id":"eb8d2eabb471646cedd285472b98c37b_1523598278","userid":"1523598278","source_url":"https:\/\/m.facebook.com\/profile.php?v=info&id=1523598278","num_friends":"1611"}],"profiler":{"mongo":["total: 0.000352, start: 2013-07-31 08:34:40.976982, end: 2013-07-31 08:34:40.977334, query: \"Connect to: mongodb:\\\/\\\/10.0.0.160:27017\"","total: 0.000051, start: 2013-07-31 08:34:40.977349, end: 2013-07-31 08:34:40.977401, query: \"Use default\"","total: 0.000492, start: 2013-07-31 08:34:40.977411, end: 2013-07-31 08:34:40.977904, query: {\"Find one: profile\":{\"_id\":{\"$id\":\"51ed0d56a11890e8cf28c216\"}},\"0\":true}","total: 0.000715, start: 2013-07-31 08:34:40.979939, end: 2013-07-31 08:34:40.980654, query: {\"Find one: facebook_users\":{\"_id\":\"eb8d2eabb471646cedd285472b98c37b_1523598278\"},\"0\":true}"],"db":{"QueryCount":1,"TotalTime":0.00096392631530762},"solr":{"QueryCount":0,"TotalTime":0},"framework":{"start":"2013-07-31 08:34:40.878925","end":"2013-07-31 08:34:40.985291"}}}'));
<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
include_once '../Dom/MyDomDocument.php';
/*$xml = '<?xml version="1.0" encoding="UTF-8"?><root><test id="3">OK</test><test id="4">Fuck</test></root>';*/
/*$xml = '<?xml version="1.0" encoding="UTF-8"?><root><test id="3">OK</test><test id="4"/></root>';*/
$xml = '<?xml version="1.0" encoding="UTF-8"?><root><test id="3">OK</test></root>';
$dom = new MyDomDocument();
$dom->loadXML($xml);
var_dump($dom->saveArray());
?>

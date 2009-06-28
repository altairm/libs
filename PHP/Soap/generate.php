<?php
include_once 'WSDLInterpreter/WSDLInterpreter.php';

$generator = new WSDLInterpreter('WSDL');
$generator->savePHP('classes');
?>

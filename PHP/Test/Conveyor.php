<?php
require_once dirname(__FILE__) . '/../Conveyor/Test.php';
require_once dirname(__FILE__) . '/../Conveyor/Condition/Test.php';
require_once dirname(__FILE__) . '/../Conveyor/Condition/Permission1.php';
require_once dirname(__FILE__) . '/../Conveyor/Object/Test.php';
require_once dirname(__FILE__) . '/../Conveyor/Object/Permission.php';

$testConveyor = new Conveyor_Test();
$testConveyor->addCondition(new Conveyor_Condition_Test(Conveyor_Condition_Abstract::CONVEYOR_CONDITION_PRIORITY_HIGH));
$testObj = new Conveyor_Object_Test('1');
$testConveyor->process($testObj);
if(!$testObj->getResult()) {
    die ("conveyor not work!");
}
$testObj->setData(null);
$testConveyor->process($testObj);
if($testObj->getResult()) {
    die ("conveyor not work!");
}

$testConveyor1 = new Conveyor_Test();
$testConveyor1->addCondition(new Conveyor_Condition_Permission1(Conveyor_Condition_Abstract::CONVEYOR_CONDITION_PRIORITY_HIGH));
$testObj1 = new Conveyor_Object_Permission(3);
$testConveyor1->process($testObj1);
$res = $testObj1->getResult();
if(!isset($res['PERMISSION 1']) || !$res['PERMISSION 1']) {
    die ("conveyor not work!");
}
$testObj1->setData(0);
$testConveyor1->process($testObj1);
$res = $testObj1->getResult();
if(!isset($res['PERMISSION 1']) || !$res['PERMISSION 1']) {
    die ("conveyor not work!");
}
?>
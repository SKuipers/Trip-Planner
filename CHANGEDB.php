<?php
//USE ;end TO SEPERATE SQL STATEMENTS. DON'T USE ;end IN ANY OTHER PLACES!

$sql = array();
$count = 0;

//v0.0.01
$sql[$count][0]="0.0.01";
$sql[$count][1]="-- First version, nothing to update";


//v0.0.02
$count++
$sql[$count][0]="0.0.02";
$sql[$count][1]="INSERT INTO gibbonSetting SET scope='Trip Planner', name='missedClassWarningThreshold', nameDisplay='Missed Class Warning Threshold', description='The threshold for displaying a warning that student has missed a class too many times. Set to 0 to disable warnings.', value='5';end";

?>
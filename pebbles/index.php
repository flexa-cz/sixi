<?php
// nezbytnosti
require_once('../core/include.php');
$sixi=new core\Sixi;
$sixi->setEnableDebuger(true);
$sixi->getDb()->setMysqlDatabase('doctor')->connect();

$sixi->getSite()->requiredCss('general.css');
$sixi->getSite()->requiredJs('jquery-1.11.0.min.js');

echo $sixi->printSite();

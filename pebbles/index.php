<?php
// nezbytnosti
require_once('../include.php');
$sixi=new core\Sixi;

$sixi->getSite()->requiredCss('general.css');
$sixi->getSite()->requiredJs('jquery-1.11.0.min.js');

echo $sixi->printSite();

<?php
// nezbytnosti
require_once('../core/include.php');
core\debuger::set_enable_report(true);
$core=new core\Core;

$core->getDb()->setMysqlDatabase('doctor')->connect();

$core->getSite()->requiredCss('general.css');
$core->getSite()->requiredJs('jquery-1.11.0.min.js');

echo $core->printSite();

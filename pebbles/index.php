<?php
// nezbytnosti
require_once('../core/include.php');
core\debuger::set_enable_report(true);
$core=new core\Core;

$core->db->setMysqlDatabase('doctor')->connect();

$core->site->requiredCss('general.css');
$core->site->requiredJs('jquery-1.11.0.min.js');

echo $core->printSite();

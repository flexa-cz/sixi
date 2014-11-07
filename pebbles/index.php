<?php
$site_title='Pebbles';

// nezbytnosti
require_once('../core/include.php');
core\debuger::set_enable_report(true);
$core=new core\Core;
$core->db->setMysqlDatabase('doctor')->connect();

$allowed_controllers=array('persons');
$controller=(!empty($_GET['controller']) && in_array($_GET['controller'], $allowed_controllers) ? $_GET['controller'] : 'persons');

$core->site->setTitle($site_title);
$core->site->addContent(_N.'<h1>'.$site_title.'</h1>');
$core->site->requiredCss('general.css');
$core->site->requiredJs('jquery-1.11.0.min.js');

if($controller==='persons'){
	$core->loader->getController('Person', array('Core'=>$core))->render();
}


echo $core->site;
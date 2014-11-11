<?php
/**
 * spolecna nastaveni pro vsechny testovaci skripty
 *
 * @since 28.11.11 9:57
 * @author Vlahovic
 */


// pokud neni definovana uz ze souboru include.php
if(!defined('_ROOT')){
	$root=str_replace(array('\\','core/constants.php'),array('/',false),__FILE__);
	/**
	*  absolutni cesta do rootu domeny
	 * vcetne lomitka na konci
	*/
	define('_ROOT', $root);
}


$backtrace=debug_backtrace();
$parent=$backtrace[1]['file'];
$project_str=str_replace(array('\\',_ROOT),array('/',false), $parent);
$project_arr=explode('/', $project_str);
$project=reset($project_arr);
$project_root=_ROOT.$project.'/';
define('_PROJECT', $project);
define('_PROJECT_ROOT', $project_root);


define('_N',"\r\n");
define('_T',"\t");
define('_N_T',"\r\n\t");
define('_N_T_T',"\r\n\t\t");
define('_N_T_T_T',"\r\n\t\t\t");
define('_N_T_T_T_T',"\r\n\t\t\t\t");

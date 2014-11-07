<?php
namespace core;
/**
 * spravuje hlaseni (potvrzovaci, varovna, chybova, informativni)
 */
class Report{
	private static $default_report_name='default';
	private static $report_name=false;
	private static $instances=array();
	private static $instances_last_index=array();
	private static $index=0;
	private static $reports=array();
	private static $reports_allowed_types=array('info'=>array('info'),'accept'=>array('accept','ok'),'alert'=>array('alert'),'warning'=>array('warning','bad'));

	/* ************************************************************************ */
	/* magic methods																														*/
	/* ************************************************************************ */
	private function __construct()
	{
	}

	final public function __clone()
	{
			throw new Exception('Clone is not allowed');
	}

	final public function __wakeup()
	{
			throw new Exception('Unserialization is not allowed');
	}

	/* ************************************************************************ */
	/* public methods																														*/
	/* ************************************************************************ */
	/**
	 * vraci instanci pro danou skupinu hlaseni, pokud neexistuje tak ji vytvori
	 *
	 * @param string $name [optinal] nazev skupiny hlaseni
	 * @return object
	 *
	 * @since  29.11.11 8:27
	 * @author Vlahovic
	 */
	final public static function getInstance($name=false)
	{
		self::$report_name=($name ? $name : self::$default_report_name);
		if (!isset(self::$instances[self::$report_name])) {
			self::$instances[self::$report_name]=new self;
			self::$instances_last_index[self::$report_name]=self::$index;
		}
		else{
			self::$index=++self::$instances_last_index[self::$report_name];
		}
		return self::$instances[self::$report_name];
	}

	/**
	 * do skupiny prida hlaseni
	 *
	 * @param string $report samotne hlaseni
	 * @param string $type [optional] typ hlaseni
	 * @param integer $errno [optional] cislo chyby
	 *
	 * @since 29.11.11 8:28
	 * @author Vlahovic
	 */
	final public static function setReport($report,$type='accept',$errno=false){
		$report_type=false;
		// nejdriv hleda v nazvech
		if(isset(self::$reports_allowed_types[$type])){
			$report_type=$type;
		}
		// pak v aliasech
		else{
			foreach(self::$reports_allowed_types as $report_type => $aliases){
				if(isset($aliases[$type])){
					break;
				}
			}
		}
		self::$reports[self::$report_name][$report_type][self::$index]['report']=$report;
		self::$reports[self::$report_name][$report_type][self::$index]['errno']=$errno;
	}

	/**
	 * vraci retezec s chybovymi hlaskami
	 *
	 * @param string $type [optional] lze vypsat jen vybrany typ hlasek
	 * @return string
	 *
	 * @since 29.11.11 8:25
	 * @author Vlahovic
	 */
	final public static function getReport($type=false){
		$r=array();
		$return=false;
		if($type){

		}
		else{
			if(isset(self::$reports[self::$report_name]) && is_array(self::$reports[self::$report_name]) && count(self::$reports[self::$report_name])){
				foreach(self::$reports[self::$report_name] as $type => $items){
					unset($r);
					$r=array();
					foreach($items as $item){
						$r[]=$item['report'];
					}
					if(count($r)){
						$return.=_N.'<div class="report '.$type.'">'._N_T.'<p>'.implode('</p>'._N_T.'<p>',$r).'</p>'._N.'</div>';
					}
				}
				unset(self::$reports[self::$report_name]);
			}
		}
		return $return;
	}
}

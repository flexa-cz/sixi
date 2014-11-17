<?php

namespace core;

/**
 * spravuje hlaseni (potvrzovaci, varovna, chybova, informativni)
 */
class Report extends Core{

	private $default_report_name='default';
	private $report_name=false;
	private $instances=array();
	private $instances_last_index=array();
	private $index=0;
	private $reports=array();
	private $reports_allowed_types=array(
			'info'=>array(
					'info'),
			'accept'=>array(
					'accept',
					'ok'),
			'alert'=>array(
					'alert'),
			'warning'=>array(
					'warning',
					'bad'));

	/*	 * *********************************************************************** */
	/* magic methods																														 */
	/*	 * *********************************************************************** */

	final public function __clone(){
		throw new SixiException('Clone is not allowed');
	}

	final public function __wakeup(){
		throw new SixiException('Unserialization is not allowed');
	}
	/*	 * *********************************************************************** */
	/* public methods																														 */
	/*	 * *********************************************************************** */


	/**
	 * vraci instanci pro danou skupinu hlaseni, pokud neexistuje tak ji vytvori
	 *
	 * @param string $name [optinal] nazev skupiny hlaseni
	 * @return object
	 *
	 * @since  29.11.11 8:27
	 * @author Vlahovic
	 */
	final public function getInstance($name=false){
		$this->report_name=($name ? $name : $this->default_report_name);
		if(!isset($this->instances[$this->report_name])){
			$this->instances[$this->report_name]=new self;
			$this->instances_last_index[$this->report_name]=$this->index;
		}
		else{
			$this->index=++$this->instances_last_index[$this->report_name];
		}
		return $this->instances[$this->report_name];
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
	final public function setReport($report, $type='accept', $errno=false){
		$report_type=false;
		// nejdriv hleda v nazvech
		if(isset($this->reports_allowed_types[$type])){
			$report_type=$type;
		}
		// pak v aliasech
		else{
			foreach($this->reports_allowed_types as $report_type=> $aliases){
				if(isset($aliases[$type])){
					break;
				}
			}
		}
		$this->reports[$this->report_name][$report_type][$this->index]['report']=$report;
		$this->reports[$this->report_name][$report_type][$this->index]['errno']=$errno;
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
	final public function getReport($type=false){
		$r=array();
		$return=false;
		if($type){

		}
		else{
			if(isset($this->reports[$this->report_name]) && is_array($this->reports[$this->report_name]) && count($this->reports[$this->report_name])){
				foreach($this->reports[$this->report_name] as $type => $items){
					unset($r);
					$r=array();
					foreach($items as $item){
						$r[]=$item['report'];
					}
					if(count($r)){
						$return.=_N.'<div class="report '.$type.'">'._N_T.'<p>'.implode('</p>'._N_T.'<p>', $r).'</p>'._N.'</div>';
					}
				}
				unset($this->reports[$this->report_name]);
			}
		}
		return $return;
	}
}

<?php
namespace core;
/**
 * Description of DateTime
 *
 * @author Pragodata {@link http://www.pragodata.cz} Vlahovic
 * @since 18.11.2014, 11:39:53
 */
class DateTime extends Core{
	private $date_time;
	private $machine_date_time;

	/*	 * *********************************************************************** */
	/* magic methods */
	/*	 * *********************************************************************** */

	/*	 * *********************************************************************** */
	/* public methods */
	/*	 * *********************************************************************** */

	public function setDateTime($date_time){
		$this->date_time=(string)$date_time;
		$this->machine_date_time=null;
		return $this;
	}

	public function printMachineDateTime(){
		if(!$this->machine_date_time){
			$this->doMachineDateTime();
		}
		return $this->machine_date_time;
	}



	/*	 * *********************************************************************** */
	/* protected methods */
	/*	 * *********************************************************************** */

	/*	 * *********************************************************************** */
	/* private methods */
	/*	 * *********************************************************************** */

	private function doMachineDateTime(){
		if(empty($this->date_time)){
			throw new \SixiException('No data to compute machine date_time.');
		}
		else{

			$parsed_date=new \DateTime($this->date_time);
			$date=$parsed_date->format( 'Y-m-d' );
		}
		return $this;
	}
}
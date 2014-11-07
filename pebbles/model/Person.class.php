<?php
namespace pebbles\model;
use core;
/**
 * Description of Person
 *
 * @author Vlahovic
 */
class Person{
	private $db;

	public function setDb(core\Db $db){
		$this->db=$db;
		return $db;
	}

	public function printPersons(){
		return $this->controllReadiness()->db->query("SELECT * FROM persons")->getRows();
	}

	private function controllReadiness(){
		if(empty($this->db)){
			throw new Exception('Param Db is required.');
		}
		return $this;
	}
}

<?php
namespace pebbles\model;
use core;
/**
 * Description of Person
 *
 * @author Vlahovic
 */
class Person extends core\Model{

	public function printPersons(){
		return $this->db->query("SELECT * FROM person")->getRecords();
	}

	public function printPerson($id){
		$_id=(int)$id;
		$records=$this->db->query("SELECT * FROM person WHERE id=".$_id)->getRecords();
		$return=null;
		if(count($records)>1){
			throw new \SixiException('Selected more than one record.');
		}
		else{
			$return=$records[0];
		}
		return $return;
	}
}

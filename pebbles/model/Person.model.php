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
		return $this->db->query("SELECT * FROM persons")->getRows();
	}
}

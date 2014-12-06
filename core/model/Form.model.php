<?php
namespace core\Model;
use core;
/**
 * Description of Form
 *
 * @author Vlahovic
 */
class Form extends core\Model{
	public function insertRow($table, array $row){
		return $this->db->insertRecord($table, $row);
	}
}

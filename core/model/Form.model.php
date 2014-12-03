<?php
namespace core\Model;
use core;
/**
 * Description of Form
 *
 * @author Vlahovic
 */
class Form extends core\Model{
	public function insertRow($table, array $columns, array $values){
		$query="INSERT INTO `".$table."` (`".implode('`,`', $columns)."`) VALUES ('".implode("','", $values)."')";
		$this->db->query($query);
		return $this;
	}
}

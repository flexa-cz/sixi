<?php
namespace core;
class Db extends Core{
	// pripojeni k db
	private $mysql_address=false;
	private $mysql_user=false;
	private $mysql_password=false;
	private $mysql_database=false;
	private $mysql_connect=null;
	private $result=null;
	private $rows=null;
	private $tables_structure=array();

	/* ------------------------------------------------------------------------ */
	/* magic methods */
	/* ------------------------------------------------------------------------ */

	public function __construct()
	{
//		$this->connect();
	}

	/* ------------------------------------------------------------------------ */
	/* public methods */
	/* ------------------------------------------------------------------------ */

	/**
	 * pripojeni k db
	 * @staticvar mixed $cache pokud se podari pripojit tak obsahuje ukazatel pripojeni, jinak false
	 * @return mixed bud ukazatel pripojeni, nebo false
	 */
	public final function connect(array $config){
		if($this->mysql_connect===null){
			// nacte nastaveni pripojeni
			if(isset($config['mysql']) && is_array($config['mysql']) && count($config['mysql'])){
				// nastaveni ze souboru
				$this->mysql_address=$config['mysql']['address'];
				$this->mysql_user=$config['mysql']['user'];
				$this->mysql_password=$config['mysql']['password'];
				$this->mysql_database=(!$this->mysql_database ? $config['mysql']['database'] : $this->mysql_database);

				// pripojeni
				$this->mysql_connect=mysql_connect($this->mysql_address, $this->mysql_user, $this->mysql_password);
				if($this->mysql_connect){
					$this->debuger->breakpoint('Database server connected.');
					$this->selectDatabase();
				}
				else{
					$this->debuger->breakpoint('Cant connect database server.');
					$this->able_to_vote=false;
				}
			}
			else{
				$this->debuger->breakpoint('Doesnt set database login and password.');
				$this->able_to_vote=false;
			}
		}
		return $this->mysql_connect;
	}

	public final function setMysqlDatabase($database){
		$this->mysql_database=$database;
		return $this;
	}


	/**
	 * proste polozeni dotazu
	 *
	 * @param string $query
	 */
	public final function query($query){
		$this->result=mysql_query($query,$this->mysql_connect);
		$this->debuger->breakpoint(
						$this->result ?
						$query :
						mysql_error($this->mysql_connect)._N._N.$query
		);
		return $this;
	}

	public final function getResult(){
		return $this->result;
	}


	public final function getRecords(){
		$this->rows=array();
		if($this->result){
			while($data=mysql_fetch_assoc($this->result)){
				$this->rows[]=(object)$data;
			}
		}
		else{
			$this->debuger->breakpoint();
		}
		return $this->rows;
	}

	public final function getRecord($index=0){
		$this->getRecords();
		return $this->rows[(integer) $index];
	}

	public final function getMysqlConnect(){
		return $this->mysql_connect;
	}

	/**
	 * vlozi novy zaznam do db
	 * @param string $table
	 * @param array $record (column=>value, ...)
	 */
	public function insertRecord($table, array $record){
		$return=false;
		$columns=array_keys($record);
		if($this->controlTableStructure($table, $columns)){
			$columns=array();
			$values=array();
			foreach($record as $column => $value){
				$columns[]=mysql_real_escape_string($column);
				$values[]=mysql_real_escape_string($value);
			}
			$query="INSERT INTO `".$table."` (`".implode('`,`', $columns)."`) VALUES ('".implode("','", $values)."');";
			if($this->query($query)->result){
				$return=$this->query("SELECT LAST_INSERT_ID() id;")->getRecord()->id;
			}
		}
		return $return;
	}

	/* ------------------------------------------------------------------------ */
	/* private methods */
	/* ------------------------------------------------------------------------ */

	/**
	 * @param string $table
	 * @param array $columns (column, ...)
	 */
	private function controlTableStructure($table, array $columns){
		$return=true;
		$table_structure=$this->printTableStructure($table);
		if($table_structure){
			foreach($columns as $column){
				if(!in_array($column, $table_structure['resume'])){
					$return=false;
					throw new \SixiException('Column '.$column.' not exists at table '.$table.'.');
					break;
				}
			}
		}
		else{
			$return=false;
			throw new \SixiException('Table '.$table.' not exists.');
		}
		return $return;
	}

	private function printTableStructure($table){
		$return=false;
		if(!isset($this->tables_structure[$table])){
			$query="SHOW COLUMNS FROM ".mysql_real_escape_string($table);
			$records=$this->query($query)->getRecords();
			if($records){
				$resume=array();
				foreach($records as $record){
					$resume[]=$record->Field;
				}
				$records['resume']=$resume;
				$return=$this->tables_structure[$table]=$records;
			}
			else{
				$this->tables_structure[$table]=$return;
			}
		}
		else{
			$return=$this->tables_structure[$table];
		}
		return $return;
	}

	private function selectDatabase(){
		if($this->mysql_database){
			$sel=mysql_select_db($this->mysql_database);
			if($sel){
				mysql_query("SET CHARACTER SET utf8",$this->mysql_connect);
				mysql_query('SET NAMES utf8',$this->mysql_connect);
				$this->mysql_connect=$this->mysql_connect;
				$this->debuger->breakpoint('Database selected.');
			}
			else{
				$this->debuger->breakpoint('Cant select database.');
				$this->able_to_vote=false;
			}
		}
		else{
			$this->debuger->breakpoint('Doesnt set database name.');
		}
		return $this;
	}
}

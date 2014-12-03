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

	public function __construct()
	{
//		$this->connect();
	}

	/**
	 * pripojeni k db
	 *
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


	public final function getRows(){
		$this->rows=array();
		if($this->result){
			while($data=mysql_fetch_assoc($this->result)){
				$this->rows[]=$data;
			}
		}
		else{
			$this->debuger->breakpoint();
		}
		return $this->rows;
	}

	public final function getRow($index=0){
		$this->getRows();
		return $this->rows[(integer) $index];
	}

	public final function getMysqlConnect(){
		return $this->mysql_connect;
	}
}

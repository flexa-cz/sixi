<?php
namespace core;
class Db extends Core{
	private $config_file_address="core/config.ini";
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
	public final function connect(){
		if($this->mysql_connect===null){
			// nacte nastaveni pripojeni
			$config=parse_ini_file(_ROOT.$this->config_file_address,true);
			if(isset($config['mysql']) && is_array($config['mysql']) && count($config['mysql'])){
				// nastaveni ze souboru
				$this->mysql_address=$config['mysql']['address'];
				$this->mysql_user=$config['mysql']['user'];
				$this->mysql_password=$config['mysql']['password'];
				$this->mysql_database=(!$this->mysql_database ? $config['mysql']['database'] : $this->mysql_database);

				// pripojeni
				$this->mysql_connect=mysql_connect($this->mysql_address, $this->mysql_user, $this->mysql_password);
				if($this->mysql_connect){
					$this->debuger->breakpoint('Databázový server je připojený.');
					$this->selectDatabase();
				}
				else{
					$this->debuger->breakpoint('Nepodařilo se připojit k databázovemu serveru.');
					$this->able_to_vote=false;
				}
			}
			else{
				$this->debuger->breakpoint('Nepodařilo se přihlašovací údaje k databázi.');
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
				$this->debuger->breakpoint('Databaze je vybraná.');
			}
			else{
				$this->debuger->breakpoint('Nepořilo se vybrat databázi.');
				$this->able_to_vote=false;
			}
		}
		else{
			$this->debuger->breakpoint('Není nastavena databáze, ke které se má přihlásit.');
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
		$this->result=@mysql_query($query,$this->mysql_connect);
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

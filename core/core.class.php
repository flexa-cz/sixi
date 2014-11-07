<?php
namespace core;
/**
 * hlavni objekt
 */
 class Core{
	 /** @var Site $site */
	public $site;
	/** @var Db $db */
	public $db;
	/** @var Table $table */
	public $table;
	/** @var Loader $mvc */
	public $loader;

	/**
	 * jadro "frameworku" :-)
	 * @param Db $db
	 * @param Site $site
	 * @param Table $table
	 */
	public function __construct(Db $db=null, Site $site=null, Table $table=null){
		$loader=new Loader();
		$this
						->setLoader($loader)
						->setDb($db)
						->setSite($site)
						->setTable($table)
						;
	}

	private function setLoader(Loader $loader){
		$this->loader=$loader;
		return $this;
	}

	private function setDb(Db $db=null){
		$this->db=($db ? $db : $this->loader->getCore('Db'));
		return $this;
	}

	private function setSite(Site $site=null){
		$this->site=($site ? $site : $this->loader->getCore('Site'));
		return $this;
	}

	private function setTable(Table $table=null){
		$this->table=($table ? $table : $this->loader->getCore('Table'));
		return $this;
	}
 }

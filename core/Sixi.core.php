<?php
namespace core;
/**
 * hlavni objekt
 */
 class Sixi extends Core{
	 /** @var Site $site */
	private $site;
	/** @var Db $db */
	private $db;
	/** @var Loader $loader */
	private $loader;
	/** @var Url $url */
	private $url;

	private $allowed_controllers=array();
	private $default_controller;
	private $actual_controller;
	private $config_file_address="core/config.ini";
	private $config;

	/**
	 * jadro "frameworku" :-)
	 * @param Table $table
	 */
	public function __construct(){
		$this->setLoader();
		$this->loader
						->requireCore('Exception')
						->requireCore('Debuger')
						->requireCore('Url')
						;
		$this
						->setConfig()
						->setDebuger()
						;
		$this->loader
						->setDebuger($this->debuger)
						;
		$this
						->setDb()
						->setSite()
						->setUrl()
						->setAllowedControllers()
						;
		$this->loader
						->setDb($this->db)
						->setSite($this->site)
						->setUrl($this->url)
						->requireCore('Report')
						->requireCore('Model')
						->requireCore('Controller')
						->requireCore('View')
						;
	}

	public function printSite(){
		$this->setActualController();
		$this->loader->getController($this->actual_controller)->render();
		return $this->site;
	}

	public function setDefaultController($controller){
		$_controller=(string)$controller;
		if(in_array($_controller, $this->allowed_controllers)){
			$this->default_controller=$_controller;
		}
		else{
			throw new Exception('Not allowed controller "'.$_controller.'".');
		}
		return $this;
	}

	public function getDb(){
		return $this->db;
	}

	public function getSite(){
		return $this->site;
	}

	public function setEnableDebuger($enable_debuger){
		$this->debuger->set_enable_report(($enable_debuger ? true : false));
	}

	public function setDebuger(debuger $debuger=null){
		$this->debuger=new Debuger();
		$this->debuger->set_localhost($this->config['general']['localhost']);
		$this->debuger->set_ui($this->config['general']['debuger_ui']);
		return $this;
	}

	/* ************************************************************************ */
	/* private methods */
	/* ************************************************************************ */

	private function setConfig(){
		$config=parse_ini_file(_ROOT.$this->config_file_address,true);
		unset($config['mysql']);
		$this->config=$config;
		return $this;
	}

	private function setActualController(){
		if(!$this->default_controller){
			throw new Exception('Default controller didnt set.');
		}
		else{
			$controller=$this->site->printGetVariable('controller', $this->default_controller);
			if(!in_array($controller, $this->allowed_controllers)){
				throw new Exception('Not allowed controller "'.$controller.'".');
			}
			else{
				$this->actual_controller=$controller;
			}
		}
		return $this;
	}

	private function setUrl(){
		$this->url=new Url();
		return $this;
	}

	private function setLoader(){
		$this->loader=new Loader();
		return $this;
	}

	private function setDb(){
		$this->db=$this->loader->getCore('Db');
		return $this;
	}

	private function setSite(Site $site=null){
		$this->site=($site ? $site : $this->loader->getCore('Site'));
		return $this;
	}

	private function setAllowedControllers(){
		$dir=_PROJECT_ROOT.'controller';
		$files=scandir($dir);
		unset($files[0],$files[1]);
		foreach($files as $file){
			$file_explode=explode('.',$file);
			$controller_name=reset($file_explode);
			$this->allowed_controllers[]=$controller_name;
		}
		if(count($this->allowed_controllers)===1){
			$this->default_controller=$this->allowed_controllers[0];
		}
		return $this;
	}
 }
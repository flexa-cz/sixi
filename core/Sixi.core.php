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
	/** @var Session $session */
	private $session;
	/** @var Report $report */
	private $report;

	private $allowed_controllers=array();
	private $default_controller;
	private $actual_controller;
	private $config;

	/**
	 * jadro "frameworku" :-)
	 * @param Table $table
	 */
	public function __construct(){
		$this->setLoader();
		$this->loader
						->requireCore('SixiException')
						->requireCore('Debuger')
						->requireCore('Url')
						->requireCore('Session')
						->requireCore('Report')
						;
		$this
						->setConfig()
						->setDebuger()
						;
		$this->loader
						->setDebuger($this->debuger)
						;
		$this
						->setSession()
						->setReport()
						->setDb()
						->setSite()
						->setUrl()
						->setAllowedControllers()
						;
		$this->loader
						->setSession($this->session)
						->setReport($this->report)
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
		return $this->loader->printLayout();
//		return $this->site;
	}

	public function setDefaultController($controller){
		$_controller=(string)$controller;
		if(in_array($_controller, $this->allowed_controllers)){
			$this->default_controller=$_controller;
		}
		else{
			throw new SixiException('Not allowed controller "'.$_controller.'".');
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

	public function setDebuger(Debuger $debuger=null){
		$this->debuger=($debuger ? $debuger : new Debuger());
		$this->debuger->set_localhost($this->config['general']['localhost']);
		$this->debuger->set_ui($this->config['general']['debuger_ui']);
		$this->setEnableDebuger(true);
		return $this;
	}

	/* ************************************************************************ */
	/* private methods */
	/* ************************************************************************ */

	private function setConfig(){
		$this->config=$this->loader->printConfig();
		return $this;
	}

	private function setActualController(){
		if(!$this->default_controller){
			throw new SixiException('Default controller didnt set.');
		}
		else{
			$controller=$this->site->printGetVariable('controller', $this->default_controller);
			if(!in_array($controller, $this->allowed_controllers)){
				throw new SixiException('Not allowed controller "'.$controller.'".');
			}
			else{
				$this->actual_controller=$controller;
			}
		}
		return $this;
	}

	private function setSession(){
		$this->session=$this->loader->getCore('Session');
		return $this;
	}

	private function setReport(){
		$this->report=$this->loader->getCore('Report');
		return $this;
	}

	private function setUrl(){
		$this->url=$this->loader->getCore('Url');
		return $this;
	}

	private function setLoader(){
		$this->loader=new Loader();
		return $this;
	}

	private function setDb(){
		$this->db=$this->loader->getCore('Db');
		$this->db->connect($this->config);
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
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
	/** @var Loader $mvc */
	public $loader;

	private $allowed_controllers=array();
	private $default_controller;
	private $actual_controller;

	/**
	 * jadro "frameworku" :-)
	 * @param Db $db
	 * @param Site $site
	 * @param Table $table
	 */
	public function __construct(Db $db=null, Site $site=null){
		$loader=new Loader();
		$this
						->setLoader($loader)
						->setDb($db)
						->setSite($site)
						->setAllowedControllers()
						;
		$this->loader
						->setDb($this->db)
						->setSite($this->site)
						->requireCore('Report')
						->requireCore('Url')
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

	private function setActualController(){
		if(!$this->default_controller){
			throw new Exception('Default controller didnt set.');
		}
		else{
			$controller=(!empty($_GET['controller']) ? $_GET['controller'] : $this->default_controller);
			if(!in_array($controller, $this->allowed_controllers)){
				throw new Exception('Not allowed controller "'.$controller.'".');
			}
			else{
				$this->actual_controller=$controller;
			}
		}
		return $this;
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

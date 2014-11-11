<?php
namespace core;
/**
 * Description of Loader
 *
 * @author Pragodata {@link http://www.pragodata.cz} Vlahovic
 * @since 5.11.2014, 15:27:55
 */
class Loader extends Core{
	private $objects=array('core','model','view','controller');
	private $already_required_classes=array();
	private $db;
	private $site;
	private $url;

	/*	 * *********************************************************************** */
	/* magic methods */
	/*	 * *********************************************************************** */

	/*	 * *********************************************************************** */
	/* public methods */
	/*	 * *********************************************************************** */

	public function getCore($core_name, array $params=null){
		return $this->getObject('core', $core_name, $params);
	}

	public function getModel($model_name, array $params=null){
		return $this->getObject('model', $model_name, $params);
	}

	public function getView($view_name, array $params=null){
		return $this->getObject('view', $view_name, $params);
	}

	public function getController($controller_name, array $params=null){
		return $this->getObject('controller', $controller_name, $params);
	}

	public function requireCore($object_name){
		$this->requireFile('core', $object_name);
		return $this;
	}

	public function setDb(Db $db){
		$this->db=$db;
		return $this;
	}

	public function setSite(Site $site){
		$this->site=$site;
		return $this;
	}

	public function setUrl(Url $url){
		$this->url=$url;
		return $this;
	}

	public function printConfig(){
		$return=null;
		$file_addresses=array(
				_PROJECT_ROOT.'config.ini',
				_ROOT.'core/config.ini',
		);
		foreach($file_addresses as $file_address){
			if(file_exists($file_address)){
				$return=parse_ini_file($file_address, true);
				break;
			}
		}
		return $return;
	}

	public function printLayout(){
		$layout=$this->site->getLayout();
		$return=false;
		if($layout){
			$file_paths=$this->printFilePaths('layout', $layout);
			$layout_find=false;
			foreach($file_paths as $file_path){
				if(file_exists($file_path)){
					$site=$this->site;
					ob_start();
					include($file_path);
					$return=ob_get_contents();
					ob_end_clean();
					$layout_find=true;
					break;
				}
			}
			if(!$layout_find){
				throw new Exception('Layout didnt find ('.$layout.').');
			}
		}
		else{
			throw new Exception('Layout didnt set.');
		}
		return $return;
	}

	/*	 * *********************************************************************** */
	/* protected methods */
	/*	 * *********************************************************************** */

	/*	 * *********************************************************************** */
	/* private methods */
	/*	 * *********************************************************************** */

	private function getObject($object_type, $object_name, array $params=null){
		$return=null;
		if(in_array($object_type, $this->objects)){
			if(empty($this->objects[$object_type][$object_name])){
				$object=$this->requireFile($object_type, $object_name)->loadObject($object_type, $object_name);
				$return=$this->setObjectParams($object, $params);
				$this->objects[$object_type][$object_name]=$return;
			}
			else{
				$return=$this->objects[$object_type][$object_name];
			}
		}
		else{
			throw new Exception('Unsupported object type "'.$object_type.'".');
		}
		return $return;
	}

	private function setObjectParams($object, array $params=null){
		if(!empty($params)){
			foreach($params as $param_name => $param_value){
				$method='set'.$param_name;
				$object->$method($param_value);
			}
		}
		return $object;
	}

	private function printFilePaths($object_type, $object_name){
		$lower_object_name=strtolower($object_name);
		$uc_first_object_name=ucfirst($object_name);
		$file_paths=array(
				// nejdriv hleda v adresari projektu, aby slo vse prepsat
				_PROJECT_ROOT.($object_type!=='core' ? $object_type.'/' : false).$object_name.'.'.$object_type.'.php',
				_PROJECT_ROOT.($object_type!=='core' ? $object_type.'/' : false).$lower_object_name.'.'.$object_type.'.php',
				_PROJECT_ROOT.($object_type!=='core' ? $object_type.'/' : false).$uc_first_object_name.'.'.$object_type.'.php',
				// az potom v adresari core
				_ROOT.'core/'.($object_type!=='core' ? $object_type.'/' : false).$object_name.'.'.$object_type.'.php',
				_ROOT.'core/'.($object_type!=='core' ? $object_type.'/' : false).$lower_object_name.'.'.$object_type.'.php',
				_ROOT.'core/'.($object_type!=='core' ? $object_type.'/' : false).$uc_first_object_name.'.'.$object_type.'.php',
		);
		return $file_paths;
	}

	private function requireFile($object_type, $object_name){
		if(!$this->isAlreadyRequiredFile($object_type, $object_name)){
			$file_exists=false;
			$file_paths=$this->printFilePaths($object_type, $object_name);
			foreach($file_paths as $path){
				if(file_exists($path)){
					require_once($path);
					$file_exists=true;
					break;
				}
			}
			if(!$file_exists){
				throw new Exception('Unexisting file '.$object_type.'/'.$object_name.'.');
			}
		}
		return $this;
	}

	private function isAlreadyRequiredFile($object_type, $object_name){
		$return=false;
		$object_str=strtolower($object_type).'/'.strtolower($object_name);
		if(in_array($object_str, $this->already_required_classes)){
			$return=true;
		}
		else{
			$this->already_required_classes[]=$object_str;
		}
		return $return;
	}

	private function loadObject($object_type, $object_name){
		$object=false;
		$class_my=($object_type==='core' ? 'core\\'.$object_name : _PROJECT.'\\'.$object_type.'\\'.$object_name);
		$class_core=($object_type==='core' ? false : 'core\\'.$object_type.'\\'.$object_name);
		if(class_exists($class_my)){
			$object=new $class_my;
		}
		elseif($class_core && class_exists($class_core)){
			$object=new $class_core;
		}
		if($object_type==='model'){
			$object->setDb($this->db);
		}
		elseif($object_type==='controller'){
			$object
							->setLoader($this)
							->setSite($this->site)
							;
		}
		elseif($object_type==='view'){
			$object->setUrl($this->url);
		}
		if(method_exists($object, 'setDebuger')){
			$object->setDebuger($this->debuger);
		}
		if(!$object){
			throw new Exception('Unexisting class "'.$object_type.'/'.$object_name.'".');
		}
		return $object;
	}
}

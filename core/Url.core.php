<?php
namespace core;

class Url{
	private $addr_string;
	private $query_string;
	private $url_array;
	private $server_name;

	public final function getAddrString(){
		if(!$this->addr_string){
			$this->parseUrl();
		}
		return $this->addr_string;
	}

	public final function getQueryString($exceptions=false){
		static $cache=array();
		if(!$this->query_string){
			$this->parseUrl();
		}
		if(!empty($exceptions) && is_array($exceptions) && count($exceptions)){
			$arr=array();
			$hash=serialize($exceptions);
			if(!isset($cache[$hash])){
				if(isset($_GET) && count($_GET)){
					foreach($_GET as $key => $val){
						if(!in_array($key,$exceptions)){
							$arr[$key]=$val;
						}
					}
				}
				$ret=array();
				foreach($arr as $key => $val){
					$ret[]=$key.(strlen($val) ? '='.$val : false);
				}
				$cache[$hash]=implode('&',$ret);
			}
			return $cache[$hash];
		}
		return $this->query_string;
	}

	/**
	 * vraci pole z get promennych
	 *
	 * @param array $exceptions
	 * @return array
	 */
	public final function getUrlArray($exceptions=false){
		if(!$this->url_array){
			$this->parseUrl();
		}
		if(!empty($exceptions) && is_array($exceptions) && count($exceptions)){
			$arr=array();
			if(isset($this->url_array) && count($this->url_array)){
				foreach($this->url_array as $key => $val){
					if(!in_array($key,$exceptions)){
						$arr[$key]=$val;
					}
				}
			}
			return $arr;
		}
		return $this->url_array;
	}

	public final function getServerName(){
		if(!$this->server_name){
			$this->parseUrl();
		}
		return $this->server_name;
	}

	private function parseUrl(){
		$this->server_name=$_SERVER['SERVER_NAME'];
		$this->query_string=$_SERVER['QUERY_STRING'];
		$this->addr_string=str_replace('?'.$this->query_string, false, $_SERVER['REQUEST_URI']);
		$arr=explode('/', $this->addr_string);
		foreach($arr as $val){
			if($val){
				$this->url_array[]=$val;
			}
		}
	}
}
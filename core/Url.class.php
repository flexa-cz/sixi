<?php
class Url{
	private static $addr_string;
	private static $query_string;
	private static $url_array;
	private static $server_name;

	public static final function getAddrString(){
		if(!self::$addr_string){
			self::parseUrl();
		}
		return self::$addr_string;
	}

	public static final function getQueryString($exceptions=false){
		static $cache=array();
		if(!self::$query_string){
			self::parseUrl();
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
		return self::$query_string;
	}

	/**
	 * vraci pole z get promennych
	 *
	 * @param array $exceptions
	 * @return array
	 */
	public static final function getUrlArray($exceptions=false){
		if(!self::$url_array){
			self::parseUrl();
		}
		if(!empty($exceptions) && is_array($exceptions) && count($exceptions)){
			$arr=array();
			if(isset(self::$url_array) && count(self::$url_array)){
				foreach(self::$url_array as $key => $val){
					if(!in_array($key,$exceptions)){
						$arr[$key]=$val;
					}
				}
			}
			return $arr;
		}
		return self::$url_array;
	}

	public static final function getServerName(){
		if(!self::$server_name){
			self::parseUrl();
		}
		return self::$server_name;
	}

	private function parseUrl(){
		self::$server_name=$_SERVER['SERVER_NAME'];
		self::$query_string=$_SERVER['QUERY_STRING'];
		self::$addr_string=str_replace('?'.self::$query_string, false, $_SERVER['REQUEST_URI']);
		$arr=explode('/',self::$addr_string);
		foreach($arr as $val){
			if($val){
				self::$url_array[]=$val;
			}
		}
	}
}
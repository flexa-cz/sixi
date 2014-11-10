<?php
namespace core;
/**
 * resi html stranu a jeji nezbytne soucasti...
 */
class Site extends Core{
	private $site=false;
	private $content=false;
	private $title=false;
	private $header=array();
	private $highlight;

	/* ************************************************************************ */
	/* magic methods																														*/
	/* ************************************************************************ */
	public function __construct($title=false)
	{
		$this->setTitle($title);
	}

	public function __toString()
	{
		return	$this->header().
						$this->site.
						Report::getInstance()->getReport().
						zvyraznit($this->content,$this->highlight).
						$this->footer();
	}

	/* ************************************************************************ */
	/* public methods																														*/
	/* ************************************************************************ */
	/**
	 * pripoji dodany retezec k obsahu
	 *
	 * @param type $content
	 *
	 * @since 28.11.11 10:34
	 * @author Vlahovic
	 */
	final public function addContent($content){
		$this->content.=$content;
		return $this;
	}

	final public function addHeader($header){
		$this->header[]=$header;
		return $this;
	}

	final public function setTitle($title){
		$this->title=$title;
		return $this;
	}

	final public function setHighlight($highlight){
		$this->highlight=$highlight;
		return $this;
	}

	final public function requiredJs($file_name){
		return $this->required('js', $file_name);
	}

	final public function requiredCss($file_name){
		return $this->required('css', $file_name);
	}

	final public function printGetVariable($name, $default=null){
		return $this->printVariable('get',$name,$default);
	}

	final public function printPostVariable($name, $default=null){
		return $this->printVariable('post',$name,$default);
	}

	final public function printSessionVariable($name, $default=null){
		return $this->printVariable('session',$name,$default);
	}

	final public function printServerVariable($name, $default=null){
		return $this->printVariable('server',$name,$default);
	}

	final public function printCookieVariable($name, $default=null){
		return $this->printVariable('cookie',$name,$default);
	}

	final public function printFilesVariable($name, $default=null){
		return $this->printVariable('files',$name,$default);
	}

	final public function printRequesVariable($name, $default=null){
		return $this->printVariable('request',$name,$default);
	}

	final public function printEnvVariable($name, $default=null){
		return $this->printVariable('env',$name,$default);
	}

	/* ************************************************************************ */
	/* private methods																													*/
	/* ************************************************************************ */

	final private function printVariable($type, $name, $default){
		$return=$default;
		$source=false;
		$types=array(
				'get'=>$_GET,
				'post'=>$_POST,
				'session'=>(!empty($_SESSION) ? $_SESSION : null),
				'server'=>$_SERVER,
				'cookie'=>$_COOKIE,
				'files'=>$_FILES,
				'request'=>$_REQUEST,
				'env'=>$_ENV,
				);
		if(isset($types[$type])){
			$source=$types[$type];
		}
		else{
			throw new Exception('Unknown type of data source "'.$type.'".');
		}
		if($source){
			$return=(!empty($source[$name]) ? $source[$name] : $default);
		}
		return $return;
	}

	final private function required($type, $file_name){
		$file_url='www/'.$type.'/'.$file_name;
		$file_address=_PROJECT_ROOT.$file_url;
		if(file_exists($file_address)){
			if($type==='css'){
				$header='<link rel="stylesheet" type="text/css" href="/'.$file_url.'" title="style" media="screen" />';
				$this->addHeader($header);
			}
			elseif($type==='js'){
				$header='<script type="text/javascript" src="/'.$file_url.'"></script>';
				$this->addHeader($header);
			}
			else{
				throw new Exception('Unsupported file type.');
			}
		}
		else{
			throw new Exception('Unexisting file "'.$file_address.'".');
		}
		return $this;
	}

	/**
	 * html hlavicka
	 *
	 * @param string $title [optional]
	 * @return string
	 *
	 * @since 28.11.11 10:30
	 * @author Vlahovic
	 */
	final private function header(){
		$r='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
		$r.=_N.'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="cs" lang="cs">';
		$r.=_N.'<head>';
		$r.=_N_T.'<meta http-equiv="content-type" content="text/html; charset=utf-8" />';
		$r.=_N_T.'<meta http-equiv="Content-language" content="cs" />';
		$r.=_N_T.'<meta http-equiv="imagetoolbar" content="no" />';
		$r.=_N_T.'<meta http-equiv="cache-control" content="cache" />';
		$r.=($this->title ? _N_T.'<title>'.$this->title.'</title>' : false);
		if(!empty($this->header)){
			$r.=_N_T.implode(_N_T,$this->header);
		}
		$r.=_N.'<style>';
		$r.=_N.$this->debuger->get_css();
		$r.=_N.'</style>';
		$r.=_N.'</head>';
		$r.=_N.'<body>';
		return $r;
	}

	/**
	 * html paticka
	 *
	 * @since 28.11.11 10:30
	 * @author Vlahovic
	 * @return string
	 */
	final private function footer(){
		$r=$this->debuger->get_panel();
		$r.=_N.'</body>';
		$r.=_N.'</html>';
		return $r;
	}

}

function odzvyraznit($matches) {
    return preg_replace('~<span class="search-result">([^<]*)</span>~i', '\\1', $matches[0]);
}

function zvyraznit($text,$search) {
    if ($search) {
			$search = preg_quote(htmlspecialchars($search), '~');
			$text = preg_replace("~$search~i", '<span class="search-result">\\0</span>', $text);
			// odstranění zvýrazňování z obsahu <option> a <textarea> a zevnitř značek a entit
			$span = '<span class="search-result">[^<]*</span>';
			$pattern = "~<(option|textarea)[\\s>]([^<]*$span)+|<([^>]*$span)+|&([^;]*$span)+~i";
			$text = preg_replace_callback($pattern, 'odzvyraznit', $text);
    }
    return $text;
}


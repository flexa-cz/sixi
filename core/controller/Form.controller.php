<?php
namespace core\controller;
use core;
/**
 * Description of Form
 *
 * @author Pragodata {@link http://www.pragodata.cz} Vlahovic
 * @since 12.11.2014, 11:27:32
 */
class Form extends core\Controller{
	private $snippet_name;
	private $items;

	/*	 * *********************************************************************** */
	/* magic methods */
	/*	 * *********************************************************************** */

	/*	 * *********************************************************************** */
	/* public methods */
	/*	 * *********************************************************************** */

	public function setSnippetName($snippet_name) {
		$this->snippet_name = $snippet_name;
		return $this;
	}

	public function render(){
		$return=false;
		if(empty($this->snippet_name)){
			throw new Exception('Snippet name must be set.');
		}
		else{
			$this->loader->requireLibrary('simple_html_dom');
			$return=$this->loader->getSnippet($this->snippet_name);
			$this->parseHtml($return);
		}
		return $return;
	}

	/*	 * *********************************************************************** */
	/* protected methods */
	/*	 * *********************************************************************** */

	/*	 * *********************************************************************** */
	/* private methods */
	/*	 * *********************************************************************** */

	private function parseHtml($html){
		$dom=str_get_html($html);
		$form=$dom->getElementsByTagName('form');
		$tag_types=array('input','select','textarea');
		foreach($tag_types as $tag_type){
			$inputs=$form->find($tag_type);
			foreach($inputs as $input){
				$item=str_get_html($input)->nodes[1]->attr;
				$this->addItem($tag_type, $item);
			}
		}
	}

	private function addItem($tag_type, array $item){
		$index=count($this->items);
		$this->items[$index]=$item;
		$this->items[$index]['tag_type']=$tag_type;
		return $this;
	}
}
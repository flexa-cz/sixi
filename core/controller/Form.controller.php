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
	private $form_name;
	private $items;

	/*	 * *********************************************************************** */
	/* magic methods */
	/*	 * *********************************************************************** */

	/*	 * *********************************************************************** */
	/* public methods */
	/*	 * *********************************************************************** */

	public function setFormName($form_name) {
		$this->form_name = $form_name;
		return $this;
	}

	public function render(){
		$return=false;
		if(empty($this->form_name)){
			throw new Exception('Form name must be set.');
		}
		else{
			$this->loader->requireLibrary('simple_html_dom');
			$return=$this->loader->getForm($this->form_name);
			$this->parseHtml($return);
//			echo '<div class="flexa-debug" style="background: red; color: #ffc; font-weight: bold; padding: .3em 1em; margin: 1em 0 0 0; font-size: 130%; font-family: Courier, monospace;">$this->items</div><div style="border: 1px solid red; background: #ffc; padding: 1em; margin: 0 0 1em 0; overflow: auto; font-family: Courier, monospace;"><pre>'; var_export($this->items);echo '</pre><p style="font-size: 75%; color: red;">'; echo '<em># <b>file:</b> '.__FILE__.'; <b>line:</b> '.__LINE__.(__FUNCTION__ ? '; <b>function:</b> ' : false).(__CLASS__ ? __CLASS__.'::' : false).(__FUNCTION__ ? __FUNCTION__ : false).'</em>';foreach(debug_backtrace() as $values){echo (!empty($values['file']) && !empty($values['line']) ? '<br><em># <b>file:</b> '.$values['file'].'; <b>line:</b> '.$values['line'].'; <b>function:</b> '.(!empty($values['class']) ? $values['class'].'::' : false).$values['function'].'</em>' : false);}echo '</p></div>';
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
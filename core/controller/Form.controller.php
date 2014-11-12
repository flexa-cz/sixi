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
	private $form;
	private $form_attributes;
	private $original_html;
	private $dom;

	/*	 * *********************************************************************** */
	/* magic methods */
	/*	 * *********************************************************************** */

	/*	 * *********************************************************************** */
	/* public methods */
	/*	 * *********************************************************************** */

	public function setSnippetName($snippet_name) {
		$this->debuger->breakpoint('Begin setSnippetName');
		if($snippet_name){
			$this->snippet_name = $snippet_name;
			$this->loader->requireLibrary('simple_html_dom');
			$this->original_html=$this->loader->getSnippet($this->snippet_name);
			$this
							->setDom()
							->setForm()
							->setItems();
		}
		else{
			throw new Exception('Snippet name must be set.');
		}
		$this->debuger->breakpoint('End setSnippetName');
		return $this;
	}

	public function render(){
		$return=false;
		if(empty($this->snippet_name)){
			throw new Exception('Snippet name must be set.');
		}
		else{
			$return=$this->dom->root->innertext();
//			$return=$this->original_html;
		}
		return $return;
	}

	public function setValues(array $values){
		$this->debuger->breakpoint('Begin setValues');
		foreach($values as $item_name => $item_value){
			$item_index=$this->printItemIndex($item_name);
			if($item_index!==false){
				$this->items[$item_index]['value']=$item_value;
				$this->setValue($this->items[$item_index]);
			}
		}
		$this->debuger->breakpoint('End setValues');
		return $this;
	}

	/*	 * *********************************************************************** */
	/* protected methods */
	/*	 * *********************************************************************** */

	/*	 * *********************************************************************** */
	/* private methods */
	/*	 * *********************************************************************** */

	private function printItemIndex($item_name){
		$return=false;
		foreach($this->items as $index => $item){
			if(!empty($item['name']) && $item['name']===$item_name){
				$return=$index;
				break;
			}
		}
		return $return;
	}

	private function setDom(){
		$this->dom=str_get_html($this->original_html);
		return $this;
	}

	private function setForm(){
		$this->form=$this->dom->getElementsByTagName('form');
		$this->form_attributes=$this->form->nodes[2]->parent->attr;
		return $this;
	}

	private function setValue(array $item){
		if($item['tag_type']==='input'){
			$this->setInputValue($item);
		}
		return $this;
	}

	private function setInputValue($item){
		$inputs=$this->form->find($item['tag_type']);
		foreach($inputs as $input){
			$tag_item=str_get_html($input)->nodes[1]->attr;
			if($tag_item['name']===$item['name']){
				$input->value=$item['value'];
			}
		}
		return $this;
	}

	private function setItems(){
		$tag_types=array('input','select','textarea');
		foreach($tag_types as $tag_type){
			$inputs=$this->form->find($tag_type);
			foreach($inputs as $input){
				$item=str_get_html($input)->nodes[1]->attr;
				$this->setItem($tag_type, $item);
			}
		}
	}

	private function setItem($tag_type, array $item){
		$index=count($this->items);
		$this->items[$index]=$item;
		$this->items[$index]['tag_type']=$tag_type;
		return $this;
	}
}
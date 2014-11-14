<?php
namespace core\controller;
use core;
/**
 * Description of Form
 *
 * @author Milan Vlahovic aka Flexa {@link http://flexa.cz}
 * @since 12.11.2014, 11:27:32
 * @todo predelat hledani v domu tak, aby se pokazde neprochazelo vse, ale nasypat to jednou na zacatku do zasobniku
 * @todo pro kazdy formular na strance vygenerovat nejaky hash s omezenou platnosti pro kontrolu
 * @todo navic hash bude slouzit k identifikaci daneho formulare v prida, kdyz jich bude na strance vice
 */
class Form extends core\Controller{
	private $snippet_name;
	private $items;
	private $form;
	private $form_attributes;
	private $original_html;
	private $dom;
	private $sixi_security_hash;
	private $submited_data;

	/*	 * *********************************************************************** */
	/* magic methods */
	/*	 * *********************************************************************** */

	/*	 * *********************************************************************** */
	/* public methods */
	/*	 * *********************************************************************** */

	public function setSnippetName($snippet_name) {
		$this->debuger->breakpoint('Begin setSnippetName');
		if($snippet_name){
			$this->snippet_name=$snippet_name;
			$this
							->initForm()
							->setSubmitedData()
							;
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
			if(strpos($return, '</form>')!==false){
				$hidden_hash_input='<input type="hidden" name="sixi_security_hash" value="'.$this->sixi_security_hash.'" />';
				$return="\r\n<!-- BEGIN of form parsed by form controller -->\r\n".str_replace(array('</form>',"\t","\r\n\r\n"), array("\r\n".$hidden_hash_input."\r\n</form>","\r\n",''), $return)."\r\n<!-- END of form parsed by form controller -->\r\n";
			}
		}
		return $return;
	}

	public function setValues(array $values){
		if(empty($this->submited_data)){
			$this->debuger->breakpoint('Begin setValues');
			foreach($values as $item_name => $item_value){
				$item_index=$this->printItemIndex($item_name);
				if($item_index!==false && (empty($this->items[$item_index]['type']) || $this->items[$item_index]['type']!=='submit')){
					$this->items[$item_index]['value']=$item_value;
					$this->setValue($this->items[$item_index]);
				}
			}
			$this->debuger->breakpoint('End setValues');
		}
		return $this;
	}

	/*	 * *********************************************************************** */
	/* protected methods */
	/*	 * *********************************************************************** */

	/*	 * *********************************************************************** */
	/* private methods */
	/*	 * *********************************************************************** */

	private function setSubmitedData(){
		$method=(empty($this->form_attributes['method']) ? 'post' : $this->form_attributes['method']);
		$data=($method==='post' ? $_POST : $_GET);
		// zpracovava jen po odeslani naseho formulare
		if(!empty($data['sixi_security_hash'])){
			// dal posle jen data, ktera jsou skutecne ve formulari
			$submited_data=array();
			foreach($this->items as $item){
				if(isset($data[$item['name']])){
					$submited_data[$item['name']]=$data[$item['name']];
				}
			}
			$this->setValues($data);
			$this->submited_data=$submited_data;
		}
	}

	private function initForm(){
		$this->loader->requireLibrary('simple_html_dom');
		$this->original_html=$this->loader->getSnippet($this->snippet_name);
		$this
						->setDom()
						->setForm()
						->setSixiSecurityHash()
						->setItems();
		return $this;
	}

	private function setSixiSecurityHash(){
		$this->sixi_security_hash=hash('md5', serialize($this->form).time());
		return $this;
	}

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
//		echo '<div style="background: red; color: #ffc; font-weight: bold; padding: .3em 1em; margin: 1em 0 0 0; font-size: 130%; font-family: Courier, monospace;">$item</div><div style="border: 1px solid red; background: #ffc; padding: 1em; margin: 0 0 1em 0; overflow: auto; font-family: Courier, monospace;"><pre>';var_export($item);echo '</pre><p style="font-size: 75%; color: red;">';foreach(debug_backtrace() as $values){echo '<em># <b>file:</b> '.$values['file'].'; <b>line:</b> '.$values['line'].'; <b>function:</b> '.$values['class'].'::'.$values['function'].'</em><br>';}echo '<br><b>file: </b>'.__FILE__.'<br><b>line: </b>'.__LINE__.'</p></div>';
		if($item['tag_type']==='input' && ($item['type']==='text' || $item['type']==='hidden' || $item['type']==='date')){
			$this->setInputTextValue($item);
		}
		elseif($item['tag_type']==='input' && $item['type']==='radio'){
			$this->setInputRadioValue($item);
		}
		elseif($item['tag_type']==='input' && $item['type']==='checkbox'){
			$this->setInputCheckboxValue($item);
		}
		elseif($item['tag_type']==='select'){
			$this->setSelectValue($item);
		}
		elseif($item['tag_type']==='textarea'){
			$this->setTextareaValue($item);
		}
		else{
			throw new \Exception('Unknown form item type "'.$item['tag_type'].':'.$item['type'].'"');
		}
		return $this;
	}

	private function setTextareaValue($item){
		$textareas=$this->form->find($item['tag_type']);
		foreach($textareas as $textarea){
			$tag_item=str_get_html($textarea)->nodes[1]->attr;
			if($tag_item['name']===$item['name']){
				$textarea->innertext=htmlentities($item['value']);
			}
		}
		return $this;
	}

	private function setSelectValue($item){
		$selects=$this->form->find($item['tag_type']);
		foreach($selects as $select){
			$tag_item=str_get_html($select)->nodes[1]->attr;
			if($tag_item['name']===$item['name']){
				$this->setOptionSelected($select, $item['value']);
			}
		}
		return $this;
	}

	private function setOptionSelected($select, $value){
		$options=$select->find('option');
		foreach($options as $option){
			$tag_item=str_get_html($option)->nodes[1]->attr;
			if($tag_item['value']===$value){
				$option->selected='selected';
			}
		}
		return $this;
	}

	private function setInputCheckboxValue($item){
		$inputs=$this->form->find($item['tag_type']);
		foreach($inputs as $input){
			$tag_item=str_get_html($input)->nodes[1]->attr;
			if($tag_item['name']===$item['name'] && $item['value']){
				$input->checked='checked';
			}
		}
		return $this;
	}

	private function setInputRadioValue($item){
		$inputs=$this->form->find($item['tag_type']);
		foreach($inputs as $input){
			$tag_item=str_get_html($input)->nodes[1]->attr;
			if($tag_item['name']===$item['name'] && $tag_item['value']===$item['value']){
				$input->checked='checked';
			}
		}
		return $this;
	}

	private function setInputTextValue($item){
		$inputs=$this->form->find($item['tag_type']);
		foreach($inputs as $input){
			$tag_item=str_get_html($input)->nodes[1]->attr;
			if($tag_item['name']===$item['name']){
				$input->value=htmlentities($item['value']);
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
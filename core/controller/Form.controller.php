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
 * @todo navic hash bude slouzit k identifikaci daneho formulare v pripade, kdyz jich bude na strance vice
 * @todo inspiraci na moznosti formularu nabrat zde http://www.w3schools.com/tags/tag_input.asp
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
	private $session_group_name='form_controller';
	private $allow_insert_to_db;
	private $hash_name='sixi_security_hash';
	private $plan=array();

	/*	 * *********************************************************************** */
	/* magic methods */
	/*	 * *********************************************************************** */

	/*	 * *********************************************************************** */
	/* public methods */
	/*	 * *********************************************************************** */

	/**
	 * @param string $snippet_name
	 * @return \core\controller\Form
	 * @throws SixiException
	 */
	public function setSnippetName($snippet_name) {
		if($snippet_name){
			$this->snippet_name=$snippet_name;
			$this
							->initForm()
							;
		}
		else{
			throw new SixiException('Snippet name must be set.');
		}
		return $this;
	}

	/**
	 * zpracuje odeslana data a ulozi
	 * @return \core\controller\Form
	 * @throws SixiException
	 */
	public function process(){
		if($this->snippet_name){
			$this
							->setSubmitedData()
							->setAllowInsertToDb()
							->setSixiSecurityHash()
							->setPlan()
							->insertToDb()
							;
		}
		else{
			throw new SixiException('Snippet name must be set.');
		}
		return $this;
	}

	/**
	 * naparsuje a vrati upraveny formular
	 * @return string
	 * @throws SixiException
	 */
	public function render(){
		$return=false;
		if(empty($this->snippet_name)){
			throw new SixiException('Snippet name must be set.');
		}
		else{
			$return=$this->dom->root->innertext();
			if(strpos($return, '</form>')!==false){
				$hidden_hash_input='<input type="hidden" name="'.$this->hash_name.'" value="'.$this->sixi_security_hash.'" />';
				$return="\r\n<!-- BEGIN of form parsed by form controller -->\r\n".str_replace(array('</form>',"\t","\r\n\r\n"), array("\r\n".$hidden_hash_input."\r\n</form>","\r\n",''), $return)."\r\n<!-- END of form parsed by form controller -->\r\n";
			}
		}
		return $return;
	}

	/**
	 * nastavi defaultni hodnoty formulare
	 * @param array $values
	 * @return \core\controller\Form
	 */
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

	private function insertToDb(){
		if($this->allow_insert_to_db && $this->plan){
			foreach($this->plan as $table => $rows){
				foreach($rows as $index => $row){
					if(!empty($row['meta']['id'])){
						$this->updateRowAtDb($table, $row['data'], $row['meta']['id']);
					}
					else{
						$id=$this->insertRowToDb($table, $row['data']);
						$this->addPlanItem($index, $table, 'id', $id, 'meta');
					}
				}
			}
			$this->debuger->var_dump($this->plan, 'plan');
		}
		return $this;
	}

	private function insertRowToDb($table, array $row){
		$return=false;
		if(!empty($row)){
			$return=$this->loader->getModel('Form')->insertRow($table, $row);
		}
		return $return;
	}

	private function updateRowAtDb($table, array $row, $id){
		$return=false;
		if(!empty($row)){
			$return=$this->loader->getModel('Form')->updateRow($table, $row, $id);
		}
		return $return;
	}

	private function setPlan(){
		$this->plan=array();
		if($this->submited_data){
			foreach($this->submited_data as $key => $value){
				if(strpos($key, ':')!==false){
					list($table, $column)=explode(':', $key);
				}
				// pokud nazev neobsahuje : pak neni sloupcem tabulky
				else{
					continue;
				}
				// muze byt i vic radku v jednom fomulari
				if(is_array($value)){
					foreach($value as $index => $val){
						$this->addPlanItem($index, $table, $column, $val);
					}
				}
				// nebo muze byt radek jen jeden
				else{
					$this->addPlanItem(0, $table, $column, $value);
				}
			}
		}
		if(empty($this->plan)){
			$this->plan=false;
		}
		return $this;
	}

	private function addPlanItem($index, $table, $column, $value, $type='data'){
		if($column==='id' && (int)$value){
			$this->plan[$table][$index]['meta'][$column]=(int)$value;
		}
		elseif($column!=='id'){
			$this->plan[$table][$index][$type][$column]=$value;
		}
		return $this;
	}

	private function setSubmitedData(){
		$method=(empty($this->form_attributes['method']) ? 'post' : $this->form_attributes['method']);
		$data=($method==='post' ? $_POST : $_GET);
		// zpracovava jen po odeslani naseho formulare
		if(!empty($data[$this->hash_name])){
			// dal posle jen data, ktera jsou skutecne ve formulari
			$submited_data=array();
			foreach($this->items as $item){
				if(isset($data[$item['name']])){
					$submited_data[$item['name']]=$data[$item['name']];
				}
			}
			$this->setValues($data);
			$this->submited_data=$submited_data;
			$this->submited_data[$this->hash_name]=(!empty($data[$this->hash_name]) ? $data[$this->hash_name] : false);
		}
		return $this;
	}

	private function setAllowInsertToDb(){
		$this->allow_insert_to_db=null;
		$submited_sixi_security_hash=$this->session->getVariable($this->session_group_name, $this->hash_name);
		if(!$this->submited_data[$this->hash_name]){
			$this->allow_insert_to_db=false;
		}
		elseif($submited_sixi_security_hash!==$this->submited_data[$this->hash_name]){
			$this->report->setReport('Vypršela platnost kontrolního řetězce. Odešlete prosím formulář znovu.', 'alert');
		}
		elseif($this->controlData()){
			$this->allow_insert_to_db=true;
		}
		return $this;
	}

	private function controlData(){
		$return=true;
//		echo '<div style="background: red; color: #ffc; font-weight: bold; padding: .3em 1em; margin: 1em 0 0 0; font-size: 130%; font-family: Courier, monospace;">$this->items</div><div style="border: 1px solid red; background: #ffc; padding: 1em; margin: 0 0 1em 0; overflow: auto; font-family: Courier, monospace;"><pre>';var_export($this->items);echo '</pre><p style="font-size: 75%; color: red;">';foreach(debug_backtrace() as $values){echo '<em># <b>file:</b> '.$values['file'].'; <b>line:</b> '.$values['line'].'; <b>function:</b> '.$values['class'].'::'.$values['function'].'</em><br>';}echo '<br><b>file: </b>'.__FILE__.'<br><b>line: </b>'.__LINE__.'</p></div>';
		foreach($this->items as $item_key => $item){
			if($item['tag_type']==='input' && $item['type']==='date'){
//				$this->contollDate($item_key, $item);
			}
		}
		return $return;
	}

	private function contollDate($item_key, $item){
		$date=$this->loader->getCore('DateTime')->setDateTime($this->submited_data[$item['name']])->printMachineDateTime();
		if($date){

		}
		else{
			$this->items[$item_key]['add_class']='incorrect';
			$this->report->setReport('datum neni ve spravnem formatu', 'alert');
		}
		return $this;
	}

	private function initForm(){
		$this->loader->requireLibrary('simple_html_dom');
		$this->original_html=$this->loader->getSnippet($this->snippet_name);
		$this
						->setDom()
						->setForm()
						->setItems()
						;
		return $this;
	}

	private function setSixiSecurityHash(){
		$this->sixi_security_hash=hash('md5', serialize($this->form).time());
		$this->session->setVariable($this->session_group_name, $this->hash_name,$this->sixi_security_hash);
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
			throw new SixiException('Unknown form item type "'.$item['tag_type'].':'.$item['type'].'"');
		}
		return $this;
	}

	private function setTextareaValue($item){
		$textareas=$this->form->find($item['tag_type']);
		foreach($textareas as $textarea){
			$tag_item=str_get_html($textarea)->nodes[1]->attr;
			if($tag_item['name']===$item['name']){
				$textarea->innertext=htmlspecialchars($item['value'], ENT_QUOTES, "UTF-8");
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
				$input->value=htmlspecialchars($item['value'], ENT_QUOTES, "UTF-8");
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
		return $this;
	}

	private function setItem($tag_type, array $item){
		$index=count($this->items);
		$this->items[$index]=$item;
		$this->items[$index]['tag_type']=$tag_type;
		return $this;
	}
}
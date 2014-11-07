<?php
namespace pebbles\controller;
use core;
/**
 * Description of Persons
 *
 * @author Vlahovic
 */
class Person{
	private $core;
	private $title='Osoby';
	private $model_person;

	public function setCore(core\Core $core){
		$this->core=$core;
		return $this;
	}

	public function setModelPerson(){

	}

	public function render(){
		// seznam osob
		$model_person=$this->core->loader->getModel('Person',array('Db'=>$this->core->db));
		$persons=$model_person->printPersons();
		$this->core->site->addContent(_N.'<h2>'.$this->title.'</h2>');
		$this->core->site->addContent($this->core->table->setHeader(array('ID','typ','jméno','příjmení','datum narození'))->setRows($persons));
		return $this;
	}
}

<?php
namespace pebbles\controller;
use core;
/**
 * Description of Persons
 *
 * @author Vlahovic
 */
class Person extends core\Controller{
	private $site_title='Pebbles';
	private $title='Osoby';

	public function render(){
		$this->site->setTitle($this->site_title);
		$this->site->addContent(_N.'<h1>'.$this->site_title.'</h1>');
		// seznam osob
		$model_person=$this->loader->getModel('Person');
		$persons=$model_person->printPersons();
		$table=array();
		foreach($persons as $person){
			$buttons=array(
					'<a href="?child_id='.$person['id'].'" class="button info">rodice</a>',
					'<a href="?child_id='.$person['id'].'" class="button delete">del.</a>',
			);
			$person[]=implode(false,$buttons);
			$table[]=$person;
		}
		$this->site->addContent(_N.'<h2>'.$this->title.'</h2>');
		$person_table=$this->loader->getView('Table')->setOrderBy(true)->setHeader(array('ID','typ','jméno','příjmení','datum narození','akce'))->setRows($table);
		$this->site->addContent($person_table);
		$add_button='<a href="?child_id='.$person['id'].'" class="button add">novy</a>';
		$this->site->addContent($add_button);
		return $this;
	}
}

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
	private $person_types=array('child'=>'dítě','parent'=>'rodič','doctor'=>'lékař');

	public function render(){
		$action=$this->site->printGetVariable('action');
		$this->debuger->var_dump($action);
		$this->site->setTitle($this->site_title);
		$this->site->addContent(_N.'<h1>'.$this->site_title.'</h1>');
		// seznam osob
		$model_person=$this->loader->getModel('Person');
		$persons=$model_person->printPersons();
		$table=array();
		foreach($persons as $person){
			$buttons=array(
					'<a href="?child_id='.$person['id'].'&action=edit_person" class="button edit">upravit</a>',
					'<a href="?child_id='.$person['id'].'&action=delete_person" class="button delete">odstranit</a>',
			);
			if($person['person_type']==='child'){
				$buttons[]='<a href="?child_id='.$person['id'].'&action=show_parents" class="button info">rodiče</a>';
				$buttons[]='<a href="?child_id='.$person['id'].'&action=show_doctor" class="button info">lékař</a>';
			}
			$person[]=implode(false,$buttons);
			$person['person_type']=$this->person_types[$person['person_type']];
			$table[]=$person;
		}
		$this->site->addContent(_N.'<h2>'.$this->title.'</h2>');
		$person_table=$this->loader->getView('Table')->setOrderBy(true)->setHeader(array('id'=>'ID','type'=>'typ','name'=>'jméno','surname'=>'příjmení','date_birth'=>'datum narození','action'=>'akce'))->setRows($table);
		$this->site->addContent($person_table);
		$add_button='<a href="?action=add_person" class="button add">nový záznam</a>';
		$this->site->addContent($add_button);
		return $this;
	}
}

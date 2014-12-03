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

	/* ************************************************************************ */
	/* public methods */
	/* ************************************************************************ */

	public function render(){
		$this->site->setLayout('Persons');
		$action=$this->site->printGetVariable('action');
		$this->site->setTitle($this->site_title);
		// seznam osob
		if($action!='add_person'){
			$this->renderPersonsTable();
		}
		else{
			$this->site->data['title_h2']='Nový uživatel';
			$default_values=array(
					'persons:name'=>'Rohovin',
					'persons:surname'=>'Ctyrrohy',
					'radio'=>'3',
					'checkbox'=>'1',
					'persons:person_type'=>'parent',
					'bigarea'=>'lorem ipsum dolor sit amet... SUPER!!!',
			);
			$this->site->data['add_person_form']=$this->loader->getController('Form')
							->setSnippetName('person_form')
							->process()
							->setValues($default_values)
							->render();
		}
		$this->site->data['report']=$this->report->getReport();
		return $this;
	}

	/* ************************************************************************ */
	/* private methods */
	/* ************************************************************************ */

	private function renderPersonsTable(){
		$model_person=$this->loader->getModel('Person');
		$persons=$model_person->printPersons();
		$table=$this->preparePersonsTableData($persons);
		$this->site->data['title_h2']=$this->title;
		$person_table=$this->loader->getView('Table')
						->setOrderBy(true)
						->setHeader(array('id'=>'ID','type'=>'typ','name'=>'jméno','surname'=>'příjmení','date_birth'=>'datum narození','action'=>'akce'))
						->setRows($table);
		$this->site->data['person_table']=$person_table;
		return $this;
	}

	private function preparePersonsTableData(array $persons){
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
		return $table;
	}
}

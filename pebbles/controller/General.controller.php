<?php
namespace pebbles\controller;
use core;
/**
 * Description of Persons
 *
 * @author Vlahovic
 */
class General extends core\Controller{
	private $site_title='Pebbles';
	private $title='Osoby';
	private $person_types=array('child'=>'dítě','parent'=>'rodič','doctor'=>'lékař');
	private $actions=array('patient','scheme','reviews','photos','therapy','list');

	/* ======================================================================== */
	/* public methods */
	/* ======================================================================== */

	public function render(){
		$this->site->setLayout('General');
		$action=$this->site->printGetVariable('action');
		$this->site->setTitle($this->site_title);
		$this->site->data['menu']=$this->printMenuData();
		$this->site->data['title_h2']=$action;
		// seznam osob
		if($action==='list'){
			$this->renderPersonsTable();
		}
		elseif($action==='new'){
			$this->renderAddPatient();
		}
		elseif($action==='therapy'){
			$this->renderTherapyForm();
		}
		$this->site->data['report']=$this->report->getReport();
		return $this;
	}

	/* ======================================================================== */
	/* private methods */
	/* ======================================================================== */

	private function renderTherapyForm(){
		$default_values=array();
		$id=(int)$this->site->printGetVariable('id');
		if($id){
			$model_person=$this->loader->getModel('person');
			$person_data=$model_person->printPerson($id);
			$default_values=array(
					'person:id'=>$person_data->id,
					'person:name'=>$person_data->name,
					'person:surname'=>$person_data->surname,
					'person:birth_date'=>$person_data->birth_date,
					'person:personal_identification_number'=>$person_data->personal_identification_number,
					'person:insurance'=>$person_data->insurance,
					'bigarea'=>'lorem ipsum dolor sit amet... SUPER!!!',
			);
		}
		$this->site->data['content']=$this->loader->getController('Form')
						->setSnippetName('therapy_form')
						->process()
						->setValues($default_values)
						->render();
		return $this;
	}

	private function renderAddPatient(){
		$this->site->data['title_h2']='Nový uživatel';
		$default_values=array(
				'person:name'=>'Rohovin',
				'person:surname'=>'Ctyrrohy',
				'radio'=>'3',
				'checkbox'=>'1',
				'person:person_type'=>'parent',
				'bigarea'=>'lorem ipsum dolor sit amet... SUPER!!!',
		);
		$this->site->data['content']=$this->loader->getController('Form')
						->setSnippetName('person_form')
						->process()
						->setValues($default_values)
						->render();
		return $this;
	}

	private function renderPersonsTable(){
		$model_person=$this->loader->getModel('Person');
		$persons=$model_person->printPersons();
		$table=$this->preparePersonsTableData($persons);
		$this->site->data['title_h2']=$this->title;
		$this->site->data['content']=$this->loader->getView('Table')
						->setOrderBy(true)
						->setHeader(array('id'=>'ID','type'=>'typ','name'=>'jméno','surname'=>'příjmení','date_birth'=>'datum narození','personal_identification_number'=>'rodné číslo','insurance'=>'pojišťovna','action'=>'akce'))
						->setRows($table);
		return $this;
	}

	private function preparePersonsTableData(array $persons){
		$table=array();
		foreach($persons as $person){
			$person=(array)$person;
			$buttons=array(
					$this->loader->getView('Button')->printLinkButton('edit', 'terapie', '?id='.$person['id'].'&action=therapy'),
					$this->loader->getView('Button')->printLinkButton('delete', 'odstranit', '?id='.$person['id'].'&action=delete'),
			);
			if($person['person_type']==='child'){
				$buttons[]=$this->loader->getView('Button')->printLinkButton('info', 'rodiče', '?id='.$person['id'].'&action=show_parents');
				$buttons[]=$this->loader->getView('Button')->printLinkButton('info', 'lékař', '?id='.$person['id'].'&action=show_doctor');
			}
			$person[]=implode(false,$buttons);
			$person['person_type']=(!empty($person['person_type']) ? $this->person_types[$person['person_type']] : false);
			$table[]=$person;
		}
		return $table;
	}

	private function printMenuData(){
		$return=array();
		$actual_action=$this->site->printGetVariable('action');
		foreach($this->actions as $action){
			$return[]=array(
					'text'=>$action,
					'active'=>($action===$actual_action ? true : false),
					'url'=>'/?action='.$action,
					);
		}
		return $return;
	}
}

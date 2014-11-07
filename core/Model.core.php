<?php
namespace core;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Model
 *
 * @author Pragodata {@link http://www.pragodata.cz} Vlahovic
 * @since 7.11.2014, 11:35:05
 */
class Model{
	protected $db;

	/*	 * *********************************************************************** */
	/* magic methods */
	/*	 * *********************************************************************** */

	/*	 * *********************************************************************** */
	/* public methods */
	/*	 * *********************************************************************** */

	public function setDb(Db $db){
		$this->db=$db;
		return $this;
	}

	/*	 * *********************************************************************** */
	/* protected methods */
	/*	 * *********************************************************************** */

	/*	 * *********************************************************************** */
	/* private methods */
	/*	 * *********************************************************************** */
}
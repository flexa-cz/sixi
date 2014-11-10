<?php
namespace core;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Core
 *
 * @author Pragodata {@link http://www.pragodata.cz} Vlahovic
 * @since 10.11.2014, 9:00:55
 */
class Core{
	/** @var Debuger $debuger */
	protected  $debuger;
	/*	 * *********************************************************************** */
	/* magic methods */
	/*	 * *********************************************************************** */

	/*	 * *********************************************************************** */
	/* public methods */
	/*	 * *********************************************************************** */

	public function setDebuger(Debuger $debuger){
		$this->debuger=$debuger;
		return $this;
	}

	/*	 * *********************************************************************** */
	/* protected methods */
	/*	 * *********************************************************************** */

	/*	 * *********************************************************************** */
	/* private methods */
	/*	 * *********************************************************************** */
}
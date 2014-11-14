<?php
namespace core;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Session
 *
 * @author Pragodata {@link http://www.pragodata.cz} Vlahovic
 * @since 14.11.2014, 16:30:05
 */
class Session extends Core{
	/*	 * *********************************************************************** */
	/* magic methods */
	/*	 * *********************************************************************** */

	public function __construct() {
		session_start();
		$this->debuger->breakpoint('session started');
	}

	/*	 * *********************************************************************** */
	/* public methods */
	/*	 * *********************************************************************** */

	/*	 * *********************************************************************** */
	/* protected methods */
	/*	 * *********************************************************************** */

	/*	 * *********************************************************************** */
	/* private methods */
	/*	 * *********************************************************************** */
}
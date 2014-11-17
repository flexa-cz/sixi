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

	public function setDebuger(debuger $debuger) {
		parent::setDebuger($debuger);
		session_start();
		$this->debuger->breakpoint('Session started.');
	}

	public function setVariable($group, $name, $value){
		$_SESSION[$group][$name]=$value;
		return $this;
	}

	public function getVariable($group, $name){
		return (empty($_SESSION[$group][$name]) ? null : $_SESSION[$group][$name]);
	}

	public function unsetVariable($group, $name){
		unset($_SESSION[$group][$name]);
		return $this;
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
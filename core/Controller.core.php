<?php
namespace core;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Controller
 *
 * @author Pragodata {@link http://www.pragodata.cz} Vlahovic
 * @since 7.11.2014, 11:56:55
 */
class Controller extends Core{
	/* @var $loader \core\Loader */
	protected $loader;
	protected $site;
	protected $session;
	protected $report;

	/*	 * *********************************************************************** */
	/* magic methods */
	/*	 * *********************************************************************** */

	/*	 * *********************************************************************** */
	/* public methods */
	/*	 * *********************************************************************** */

	public function setLoader(Loader $loader){
		$this->loader=$loader;
		return $this;
	}

	public function setSite(Site $site){
		$this->site=$site;
		return $this;
	}

	public function setSession(Session $session){
		$this->session=$session;
		return $this;
	}

	public function setReport(Report $report){
		$this->report=$report;
		return $this;
	}

	/*	 * *********************************************************************** */
	/* protected methods */
	/*	 * *********************************************************************** */

	/*	 * *********************************************************************** */
	/* private methods */
	/*	 * *********************************************************************** */
}
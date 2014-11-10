<?php
/**
 * aby se nemusely vsechny soubory vkladat porad rucne do vsech souboru, ktere s nimi budou pracovat...
 * navic definuje konstantu _ROOT s absolutni adresou do rootu frameworku
 *
 * @since 29.11.11 9:07
 * @author Vlahovic
 */

/**
 *  absolutni cesta do rootu domeny vcetne lomitka na konci
 */
$root=str_replace(array('\\','include.php'),array('/',false),__FILE__);
define('_ROOT',$root);

// vlozi nezbytne soubory
require_once(_ROOT.'constants.php');

// core tridy
require_once(_ROOT.'core/Core.core.php');
require_once(_ROOT.'core/Sixi.core.php');
require_once(_ROOT.'core/Loader.core.php');

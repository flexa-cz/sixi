<?php
/**
 * aby se nemusely vsechny soubory vkladat porad rucne do vsech souboru, ktere s nimi budou pracovat...
 * navic definuje konstantu _ROOT s absolutni adresou do rootu domeny
 *
 * @since 29.11.11 9:07
 * @author Vlahovic
 */

/**
 *  absolutni cesta do rootu domeny vcetne lomitka na konci
 */
$root=str_replace(array('\\','core/include.php'),array('/',false),__FILE__);
define('_ROOT',$root);

// debugovaci skript
// cim driv se vlozi tim driv zacne pocitat dobu behu aplikace
require_once(_ROOT.'core/debuger/Debuger.class.php');
core\debuger::set_localhost(true);
core\debuger::set_ui('inline');

// vlozi nezbytne soubory
require_once(_ROOT.'core/constants.php');

// core tridy
require_once(_ROOT.'core/Core.class.php');
require_once(_ROOT.'core/Loader.class.php');

// staticke tridy
require_once(_ROOT.'core/Report.class.php');
require_once(_ROOT.'core/Url.class.php');

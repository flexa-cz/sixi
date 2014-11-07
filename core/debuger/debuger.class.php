<?php
namespace core;
/**
 * nastroj pro debugovani...
 * cim driv se nacte do programu tim driv se bude merit microtime
 * aby fungovalo je potreba zavolat set_localhost a na localhostu nastavit true
 * a musi existovat konstanta _ROOT s absolutni cestou do rootu projektu
 *
 * @since 12.1.12 13:04
 * @author Vlahovic
 */
class debuger{
	private static $ui='popup';
	private static $ui_posibilities=array('inline','popup');
	private static $ui_panel_displayed=false;
	private static $backtrace;
	private static $backtrace_level=1;
	private static $enable_report=false;
	private static $file;
	private static $line;
	private static $report;
	private static $type;
	private static $value;
	private static $localhost=false;


	/* ************************************************************************ */
	/* public methods 																													*/
	/* ************************************************************************ */

	/* public set methods */
	/**
	 * povoli reportovani
	 *
	 * @param boolean $enable
	 */
	static public final function set_enable_report($enable){
		if(self::$localhost){
			if(self::$enable_report || $enable){
				self::set_backtrace();
				self::$type='set_enable_report';
				self::$value=($enable ? 1 : 0);
				self::set_report();
			}

			self::$enable_report=($enable ? true : false);
		}
	}

	/**
	 * vsechna hlaseni se vypisuji jen na localhostu...
	 * musi byt prvni metodou ktera se spusti - jinak zustane nastaveno na false a nic jineho se neprovede
	 *
	 * @param boolean $localhost
	 */
	static public final function set_localhost($localhost){
		self::$localhost=($localhost ? true : false);
	}

	/**
	 * jakym zpusobem se ma vykreslovat
	 *
	 * @param string $ui [inline|popup]
	 */
	static public final function set_ui($ui){
		if(self::$localhost){
			if(in_array($ui, self::$ui_posibilities)){
				self::$ui=$ui;
			}
		}
	}

	/* public get methods */

	static public final function get_microtime(){
		$ms=0;
		$s=0;
		$microtime=microtime();
		list($ms,$s)=explode(' ', $microtime);
		return (float)$ms+(float)$s;
	}

	static public final function get_css(){
		if(self::$localhost){
			$tables="
	/* samotne tabulky s debug vypisem */
	body{margin-bottom: 70px !important;}
	div.debuger-report{width:100% !important;overflow: auto !important; background-color: white !important; text-align: left !important; padding: 5px  !important; font-family: Verdana, Geneva, Arial, Helvetica, sans-serif !important; font-size: 18px;clear: both;".(self::$ui=='popup' ? 'display: none;' : false)."}
	div.debuger-report h1{color:black;margin: 0 0 1em 0 !important; padding: 0 !important; font-size:100% !important;}
	div.debuger-report table{border-collapse: collapse !important;background-color: white !important;border: 1px solid black !important;z-index: 1000000000 !important; -moz-box-shadow: 0px 0px 5px 0px #ccc; -webkit-box-shadow: 0px 0px 5px 0px #ccc; box-shadow: 0px 0px 5px 0px #ccc;}
	div.debuger-report table tr{border:1px solid black  !important;}
	div.debuger-report table td,div.debuger-report table th{border:1px solid black  !important;padding: 3px 5px 3px 5px !important;font-size:80% !important;color:black !important;}
	div.debuger-report table th{background-color: black !important;color: white !important;}
	div.debuger-report table td div.value{max-height: 500px !important;overflow: auto !important;}
	div.debuger-report table td div.value pre{margin:0 !important;}
	div.debuger-report table tr:nth-child(even) td{background-color: #eee !important;}
	div.debuger-report table tr:hover td,div.debuger-report table tr:hover:nth-child(even) td{background-color: #ccc !important;}";
			$rows="
	/* barevne oznaceni typu radku vypisu */
	div.debuger-report table tr td.breakpoint,div.debuger-report table tr:nth-child(even) td.breakpoint{background-color: red !important;}
	div.debuger-report table tr td.set_enable_report,div.debuger-report table tr:nth-child(even) td.set_enable_report{background-color: blue !important;}
	div.debuger-report table tr td.report,div.debuger-report table tr:nth-child(even) td.report{background-color: greenyellow !important;}
	div.debuger-report table tr td.var_dump,div.debuger-report table tr:nth-child(even) td.var_dump{background-color: orange !important;}
	div.debuger-report table tr td.backtrace,div.debuger-report table tr:nth-child(even) td.backtrace{background-color: yellow !important;}";
			$panel="
	/* pokud se ma vykreslovat jako popup */
	div#debuger-panel{position: fixed !important; bottom: 0px !important; left: 0px !important; width: 100% !important; height: 50px !important; z-index:2000000000 !important;text-align:left !important;line-height: 50px !important; padding: 0 20px;color: white !important; font-size: 16px !important; border-top: 1px solid white !important;
	/* gradient */
	background: #aebcbf; /* Old browsers */
	background: -moz-linear-gradient(top,  #aebcbf 0%, #6e7774 50%, #0a0e0a 51%, #0a0809 100%); /* FF3.6+ */
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#aebcbf), color-stop(50%,#6e7774), color-stop(51%,#0a0e0a), color-stop(100%,#0a0809)); /* Chrome,Safari4+ */
	background: -webkit-linear-gradient(top,  #aebcbf 0%,#6e7774 50%,#0a0e0a 51%,#0a0809 100%); /* Chrome10+,Safari5.1+ */
	background: -o-linear-gradient(top,  #aebcbf 0%,#6e7774 50%,#0a0e0a 51%,#0a0809 100%); /* Opera 11.10+ */
	background: -ms-linear-gradient(top,  #aebcbf 0%,#6e7774 50%,#0a0e0a 51%,#0a0809 100%); /* IE10+ */
	background: linear-gradient(top,  #aebcbf 0%,#6e7774 50%,#0a0e0a 51%,#0a0809 100%); /* W3C */
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#aebcbf', endColorstr='#0a0809',GradientType=0 ); /* IE6-9 */
	}
	div#debuger-panel strong{font-weight: normal !important;}";
			$css="
	/* ************************************************************************** */
	/* debuger-report																															*/
	/* ************************************************************************** */
	$tables
	$rows
	".(self::$ui=='popup' ? $panel : false)."
	/* ************************************************************************** */
	";
			return $css;
		}
	}

	static public final function get_panel(){
		if(self::$localhost){
			// vypise pripadne nevypsane reporty
			$panel=self::report(false,false);
			if(self::$ui=='popup' && !self::$ui_panel_displayed){
				self::$ui_panel_displayed=true;
				$panel.='<div id="debuger-panel">';
				$panel.='<strong>DebugerPanel</strong>';
				$panel.='</id>';
			}
			return $panel;
		}
	}

	/* public other methods */

	/**
	 *
	 * @param string $value [optional]
	 *
	 * @author Vlahovic
	 */
	static public final function breakpoint($value=false){
		if(self::$localhost){
			if(self::$enable_report){
				self::set_backtrace();
				self::$type='breakpoint';
				self::$value=($value ? '<pre>'.htmlentities($value).'</pre>' : '&mdash;');
				self::set_report();
			}
		}
	}

	static public final function backtrace(){
		if(self::$enable_report){
			self::set_backtrace();
			self::$type='backtrace';
			self::$value='<pre>'.print_r(self::$backtrace,true).'</pre>';
			self::set_report();
		}
	}

	static public final function var_dump($var){
		if(self::$localhost){
			if(self::$enable_report){
				self::set_backtrace();
				self::$type='var_dump';
				self::$value=self::catch_var_dump($var);
				self::set_report();
			}
		}
	}


	/**
	 * vraci hlaseni
	 *
	 * @param string $debuber_name [optional] nazev daneho reportu
	 * @param boolean $echo [optional] kdyz je true, tak rovnou vypise, jinak vrati
	 * @return mixed
	 */
	static public final function report($report_name=false,$echo=true){
		if(self::$localhost){
			// info o tom kde bylo vypsano
			if(self::$report){
				self::set_backtrace();
				self::$type='report';
				self::$value='&mdash;';
				self::set_report();
			}
			// vypise
			$report=(self::$report ? "\r\n<div class=\"debuger-report\"><h1>debuger report".($report_name ? ': '.$report_name : false)."</h1>\r\n<table>\r\n\t<tr><th>&nbsp;</th><th>&micro; time</th><th>type</th><th>file</th><th>line</th><th>value</th></tr>".self::$report."\r\n</table>\r\n</div>" : false);
			self::$report=false;
			if($echo){
				echo $report;
				return true;
			}
			return $report;
		}
	}


	/**
	 * zkraceny zapis, ktery zaridi debug vypsani breakpointu
	 *
	 * @param string $data [optional]
	 *
	 * @author Vlahovic
	 */
	static public final function _b($data=false){
		self::set_backtrace_level_to_2(true);
		self::set_enable_report(true);
		self::breakpoint($data);
		self::set_enable_report(false);
		self::report();
		self::set_backtrace_level_to_2(false);
	}

	/**
	 * zkraceny zapis, ktery zaridi debug vypsani backtrace
	 */
	static public final function _bt(){
		self::set_backtrace_level_to_2(true);
		self::set_enable_report(true);
		self::backtrace();
		self::set_enable_report(false);
		self::report();
		self::set_backtrace_level_to_2(false);
	}

	/**
	 * zkraceny zapis, ktery zaridi debug vypsani var_dump
	 *
	 * @param mixed $var
	 */
	static public final function _vd($var){
		self::set_backtrace_level_to_2(true);
		self::set_enable_report(true);
		self::var_dump($var);
		self::set_enable_report(false);
		self::report();
		self::set_backtrace_level_to_2(false);
	}

	/* ************************************************************************ */
	/* private methods 																													*/
	/* ************************************************************************ */

	/**
	 * pokud se v ramci tridy volaji jeste jine metody a je potraba je trace-ovat
	 * tak je potreba z toho vyhodit jeste jednu uroven
	 *
	 * @param boolean $set
	 */
	private static function set_backtrace_level_to_2($set){
		self::$backtrace_level=($set ? 2 : 1);
	}

	/**
	 * naplneni backtrace
	 */
	private static function set_backtrace()
	{
		self::$backtrace=debug_backtrace();
		self::$file=str_ireplace(array('\\',_ROOT,'/'),array('/',false,' <b>/</b> '),self::$backtrace[self::$backtrace_level]['file']);
		self::$line=self::$backtrace[self::$backtrace_level]['line'];
	}

	/**
	 * posklada radek reportu
	 */
	private static function set_report(){
		self::$report.="\r\n\t<tr>";
		self::$report.="\r\n\t\t<td class=\"".self::$type."\">&nbsp;</td>";
		self::$report.="\r\n\t\t<td nowrap=\"nowrap\">".(round((self::get_microtime() - _DEBUGER_MICROTIME_START),4))."</td>";
		self::$report.="\r\n\t\t<td nowrap=\"nowrap\">".self::$type.'</td>';
		self::$report.="\r\n\t\t<td nowrap=\"nowrap\">".self::$file.'</td>';
		self::$report.="\r\n\t\t<td nowrap=\"nowrap\">".self::$line.'</td>';
		self::$report.="\r\n\t\t<td><div class=\"value\">".self::$value.'</div></td>';
		self::$report.="\r\n\t</tr>";
	}

	/**
	 * vrati vysledek funkce var_dump v retezci
	 *
	 * @param mixed $var
	 * @return string
	 */
	private static function catch_var_dump($var){
		ob_start();
		var_dump($var);
		return ob_get_clean();
	}
}


define('_DEBUGER_MICROTIME_START',debuger::get_microtime());

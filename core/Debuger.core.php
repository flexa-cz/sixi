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
	private $ui='popup';
	private $ui_posibilities=array('inline','popup');
	private $ui_panel_displayed=false;
	private $backtrace;
	private $backtrace_level=1;
	private $enable_report=false;
	private $file;
	private $line;
	private $report;
	private $type;
	private $value;
	private $localhost=false;

	public function __construct() {
		define('_DEBUGER_MICROTIME_START', $this->get_microtime());
	}

	/* ************************************************************************ */
	/* public methods 																													*/
	/* ************************************************************************ */

	/* public set methods */
	/**
	 * povoli reportovani
	 *
	 * @param boolean $enable
	 */
	public final function set_enable_report($enable){
		if($this->localhost){
			if($this->enable_report || $enable){
				$this->set_backtrace();
				$this->type='set_enable_report';
				$this->value=($enable ? 1 : 0);
				$this->set_report();
			}

			$this->enable_report=($enable ? true : false);
		}
	}

	/**
	 * vsechna hlaseni se vypisuji jen na localhostu...
	 * musi byt prvni metodou ktera se spusti - jinak zustane nastaveno na false a nic jineho se neprovede
	 *
	 * @param boolean $localhost
	 */
	public final function set_localhost($localhost){
		$this->localhost=($localhost ? true : false);
	}

	/**
	 * jakym zpusobem se ma vykreslovat
	 *
	 * @param string $ui [inline|popup]
	 */
	public final function set_ui($ui){
		if($this->localhost){
			if(in_array($ui, $this->ui_posibilities)){
				$this->ui=$ui;
			}
		}
	}

	/* public get methods */

	public final function get_microtime(){
		$microtime=microtime();
		list($ms,$s)=explode(' ', $microtime);
		return (float)$ms+(float)$s;
	}

	public final function get_css(){
		if($this->localhost){
			$tables="
	/* samotne tabulky s debug vypisem */
	body{margin-bottom: 70px !important;}
	div.debuger-report{width:100% !important;overflow: auto !important; background-color: white !important; text-align: left !important; padding: 5px  !important; font-family: Verdana, Geneva, Arial, Helvetica, sans-serif !important; font-size: 18px;clear: both;".($this->ui=='popup' ? 'display: none;' : false)."}
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
	".($this->ui=='popup' ? $panel : false)."
	/* ************************************************************************** */
	";
			return $css;
		}
	}

	public final function get_panel(){
		if($this->localhost){
			// vypise pripadne nevypsane reporty
			$panel=$this->report(false,false);
			if($this->ui=='popup' && !$this->ui_panel_displayed){
				$this->ui_panel_displayed=true;
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
	public final function breakpoint($value=false){
		if($this->localhost){
			if($this->enable_report){
				$this->set_backtrace();
				$this->type='breakpoint';
				$this->value=($value ? '<pre>'.htmlentities($value).'</pre>' : '&mdash;');
				$this->set_report();
			}
		}
	}

	public final function backtrace(){
		if($this->enable_report){
			$this->set_backtrace();
			$this->type='backtrace';
			$this->value='<pre>'.print_r($this->backtrace,true).'</pre>';
			$this->set_report();
		}
	}

	public final function var_dump($var){
		if($this->localhost){
			if($this->enable_report){
				$this->set_backtrace();
				$this->type='var_dump';
				$this->value=$this->catch_var_dump($var);
				$this->set_report();
			}
		}
		return $this;
	}


	/**
	 * vraci hlaseni
	 *
	 * @param string $report_name [optional] nazev daneho reportu
	 * @param boolean $echo [optional] kdyz je true, tak rovnou vypise, jinak vrati
	 * @return mixed
	 */
	public final function report($report_name=false,$echo=true){
		if($this->localhost){
			// info o tom kde bylo vypsano
			if($this->report){
				$this->set_backtrace();
				$this->type='report';
				$this->value='&mdash;';
				$this->set_report();
			}
			// vypise
			$report=false;
			if($this->report){
				$report.="\r\n<!-- *************************************************** -->";
				$report.="\r\n<!-- BEGIN DEBUGER -->";
				$report.="\r\n<!-- *************************************************** -->";
				$report.="\r\n<div class=\"debuger-report\"><h1>debuger report".($report_name ? ': '.$report_name : false)."</h1>\r\n<table>\r\n\t<tr><th>&nbsp;</th><th>&micro; time</th><th>type</th><th>file</th><th>line</th><th>value</th></tr>".$this->report."\r\n</table>\r\n</div>";
				$report.="\r\n<!-- *************************************************** -->";
				$report.="\r\n<!-- END DEBUGER -->";
				$report.="\r\n<!-- *************************************************** -->";
			}
			$this->report=false;
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
	public final function _b($data=false){
		$this->set_backtrace_level_to_2(true);
		$this->set_enable_report(true);
		$this->breakpoint($data);
		$this->set_enable_report(false);
		$this->report();
		$this->set_backtrace_level_to_2(false);
	}

	/**
	 * zkraceny zapis, ktery zaridi debug vypsani backtrace
	 */
	public final function _bt(){
		$this->set_backtrace_level_to_2(true);
		$this->set_enable_report(true);
		$this->backtrace();
		$this->set_enable_report(false);
		$this->report();
		$this->set_backtrace_level_to_2(false);
	}

	/**
	 * zkraceny zapis, ktery zaridi debug vypsani var_dump
	 *
	 * @param mixed $var
	 */
	public final function _vd($var){
		$this->set_backtrace_level_to_2(true);
		$this->set_enable_report(true);
		$this->var_dump($var);
		$this->set_enable_report(false);
		$this->report();
		$this->set_backtrace_level_to_2(false);
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
	private function set_backtrace_level_to_2($set){
		$this->backtrace_level=($set ? 2 : 1);
	}

	/**
	 * naplneni backtrace
	 */
	private function set_backtrace()
	{
		$this->backtrace=debug_backtrace();
		$this->file=str_ireplace(array('\\',_ROOT,'/'),array('/',false,' <b>/</b> '),$this->backtrace[$this->backtrace_level]['file']);
		$this->line=$this->backtrace[$this->backtrace_level]['line'];
	}

	/**
	 * posklada radek reportu
	 */
	private function set_report(){
		$this->report.="\r\n\t<tr>";
		$this->report.="\r\n\t\t<td class=\"".$this->type."\">&nbsp;</td>";
		$this->report.="\r\n\t\t<td nowrap=\"nowrap\">".(round(($this->get_microtime() - _DEBUGER_MICROTIME_START),4))."</td>";
		$this->report.="\r\n\t\t<td nowrap=\"nowrap\">".$this->type.'</td>';
		$this->report.="\r\n\t\t<td nowrap=\"nowrap\">".$this->file.'</td>';
		$this->report.="\r\n\t\t<td nowrap=\"nowrap\">".$this->line.'</td>';
		$this->report.="\r\n\t\t<td><div class=\"value\">".$this->value.'</div></td>';
		$this->report.="\r\n\t</tr>";
	}

	/**
	 * vrati vysledek funkce var_dump v retezci
	 *
	 * @param mixed $var
	 * @return string
	 */
	private function catch_var_dump($var){
		ob_start();
		var_dump($var);
		return ob_get_clean();
	}
}
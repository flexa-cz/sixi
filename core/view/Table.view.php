<?php
namespace core\view;
use core;
class Table extends core\View{
	private $table;
	private $rows;
	private $header;
	private $table_header;
	private $order_by=false;

	/* ************************************************************************ */
	/* magic methods																														*/
	/* ************************************************************************ */
	public function __construct(){
	}

	public function __toString()
	{
		$this->getTable();
		return ($this->table ? $this->table : '');
	}


	/* ************************************************************************ */
	/* public methods																														*/
	/* ************************************************************************ */
	public final function setRows(array $rows){
		$this->rows=$rows;
		$this->table=false;
		$this->table_header=false;
		return $this;
	}

	public final function setRow(array $row){
		$this->rows[]=$row;
		$this->table=false;
		$this->table_header=false;
		return $this;
	}

	public final function setHeader(array $header){
		$this->header=$header;
		return $this;
	}

	public final function getTable(){
		if(is_array($this->rows) && count($this->rows)){
			$this->table.=_N.'<table>';
			$line_num=1;
			foreach($this->rows as $row){
				if(!$this->table_header){
					$this->table.=$this->setTableHeader($row)->table_header;
				}
				$this->table.=_N_T.'<tr>';
				$this->table.=_N_T_T.'<td class="LineNum">'.$line_num.'</td>';
				foreach($row as $val){
					$val=trim($val);
					$this->table.=_N_T_T.'<td>'.($val ? $val : '&mdash;').'</td>';
				}
				$this->table.=_N_T.'</tr>';
				$line_num++;
			}
			$this->table.=_N.'</table>';
		}
		return $this;
	}

	public final function setOrderBy($ob){
		$this->order_by=($ob ? true : false);
		return $this;
	}

	/* ************************************************************************ */
	/* private methods																													*/
	/* ************************************************************************ */
	private function setTableHeader($row){
		$header_data=false;
		if($this->header){
			$header_data=$this->header;
		}
		else{
			if(!empty($row) && is_array($row) && count($row)){
				foreach($row as $i => $v){
					$header_data[$i]=$i;
				}
			}
		}
		if(!empty($header_data)){
			$this->table_header.=_N_T.'<tr>';
			$this->table_header.=_N_T_T.'<th class="th.LineNum">#</th>';
			foreach($header_data as $name => $value){
				$this->table_header.=_N_T_T.'<th nowrap="nowrap">'.$value.$this->getOrderBy($name).'</th>';
			}
			$this->table_header.=_N_T.'</tr>';
		}
		return $this;
	}

	private function getOrderBy($name){
		$return=false;
		if($this->order_by){
			$return.=' <span class="OrderBy">';

			// DESC
			if(isset($_GET['order_by']) && $_GET['order_by']==$name && isset($_GET['desc'])){
				$return.='<span class="desc">&darr;</span> ';
			}
			else{
				$return.='<a href="'.$this->url->getAddrString().'?'.$this->url->getQueryString(array('order_by','desc','asc')).'&order_by='.$name.'&desc" class="desc">&darr;</a> ';
			}

			// ASC
			if(isset($_GET['order_by']) && $_GET['order_by']==$name && isset($_GET['asc'])){
				$return.='<span class="asc">&uarr;</span> ';
			}
			else{
				$return.='<a href="'.$this->url->getAddrString().'?'.$this->url->getQueryString(array('order_by','desc','asc')).'&order_by='.$name.'&asc" class="asc">&uarr;</a>';
			}
			$return.='</span>';
		}
		return $return;
	}
}

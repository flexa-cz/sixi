<?php
/**
 * univerzalni nastroj pro traverzovani kolem stromu
 * @author Pragodata {@link http://www.pragodata.cz}; Vlahovic
 *
 * @version 1.1
 * 1.1
 * - drobne opravy
 * - moznost zakazat transakce
 *
 * 1.0
 * - zakladni operace se stromem
 * - presuny a zmeny uzlu ciste pomoci mysql
 */
class TreeTraversal{
	/** @var moodle_database $db */
	protected $db;
	protected $table;
	private $base_required_columns=array('lft','rgt','depth','timecreated','timemodified');
	private $required_columns=array();
	private $root;
	private $debug=false;
	private $all_roots=false;

	protected $id;
	protected $lft;
	protected $rgt;
	protected $depth;
	protected $timecreated;
	protected $timemodified;

	private $other_properties_for_reset=array('all_childrens_count','data','offset','range','where');
	public $data;
	private $all_childrens_count;

	/** @var integer $offset od ktere urovne dal ma vybrat, resp. vzdalenost na ktere ma zacit (null=aktualni) */
	private $offset;
	/** @var integer $range kolik urovni ma zahrnout (null=bez omezeni) */
	private $range;
	private $where=array();

	private $columns=array('*');
	private $columns_string='*';

	private $order_by='lft ASC';

	private $base_column;
	private $base_value;
	protected $base_string;
	/** @var moodle_transaction $transaction */
	private $transaction;
	private $allow_transaction=true;

	private $move_node_after_open_line;
	private $move_immediate_parent;
	private $move_new_parent;
	private $move_new_lft;
	private $move_actual_parent;
	private $move_tree_distance_jump;
	private $move_tree_size_jump;
	private $move_tree_depth_jump;

	/* ************************************************************************ */
	/* magic methods */
	/* ************************************************************************ */

	/**
	 * @param string $table
	 */
	function __construct($table, $base_key=null, $base_value=null){
		$this
						->setDb()
						->setTable($table)
						->setBase($base_key, $base_value)
						->setRequiredColumns()
						->controlTree()
						;
	}

	/**
	 * @return string
	 */
	public function __toString(){
		$to_string=$this->n();
		unset($to_string->db);
		return '<pre>'.var_export($to_string,true).'</pre>';
	}

	/* ************************************************************************ */
	/* public methods */
	/* ************************************************************************ */

	/**
	 * vrati kopii aktualni instance objektu
	 * @return \TreeTraversal
	 */
	public function n(){
		return clone $this;
	}

	/**
	 * @param stdClass $item
	 * @param integer $id
	 * @return \TreeTraversal
	 * @throws Exception
	 */
	public function load(stdClass $item=null, $id=null){
		$this->reset();
		if($item && !empty($item)){
			$data=(array)$item;
		}
		elseif((int)$id){
			$data=array('id'=>(int)$id);
		}
		else{
			throw new Exception('Cant load item without relevant data!');
		}
		$prepared_data=$this->getPreparedKeysValues($data);
		$query="
			SELECT *
			FROM {".$this->table."}
			WHERE ".$prepared_data['string']."
			";
		try{
			$record=$this->db->get_record_sql($query,$prepared_data['values']);
			if($record){
				$this->softLoadRecord($record);
			}
			else{
				throw new Exception('Cant load node!');
			}
		}
		catch(Exception $exc){
			throw new Exception('Error during loading node. ('.$exc->getMessage().'; file: '.$exc->getFile().'; line: '.$exc->getLine().')');
		}

		return $this;
	}

	/**
	 * facade pro pridani noveho potomka za vsechny stavajici
	 * @param stdClass $data
	 * @return stdClass
	 * @throws Exception
	 */
	public function addChildren(stdClass $data){
		$this->transactionStart();
		$return=$this->_addChildren($data);
		$this->transactionAllowCommit();
		return $return;
	}

	/**
	 * create new root item
	 * @param array $values
	 * @return stdClass
	 */
	public function addRoot($values){
		$return=null;
		if(!$this->getRoot()){
			$root=array();
			if(!empty($values) && is_array($values)){
				foreach($values as $column => $value){
					$root[$column]=$value;
				}
			}
			$this->transactionStart();
			$return=$this->_addItem((object)$root,true);
			$this->transactionAllowCommit();
		}
		return $return;
	}

	/**
	 * return root item
	 * @return stdClass
	 */
	public function getRoot(){
		return $this->root;
	}

	/**
	 * @return array
	 */
	public function getBreadcrumbs(){
		return $this->softLoadRecords($this->db->get_records_sql("SELECT ".$this->columns_string." FROM {".$this->table."} WHERE lft < ".$this->lft." AND rgt > ".$this->rgt." ORDER BY lft"));
	}

	/**
	 * @param boolean $all [optional] cely strom bez omezeni|od aktualniho uzlu (vcetne)
	 * @param boolean $count [optional] vraci jen pocet zaznamu stromu
	 * @return array
	 */
	public function getTree($all=false,$count=false){
		$conditions=array();
		if($this->base_string && !$this->all_roots){
			$conditions[]=$this->base_string;
		}
		if(!$all && !$this->all_roots){
			$conditions[]='lft>='.$this->lft;
			$conditions[]='rgt<='.$this->rgt;
		}
		if($this->offset!==null){
			$conditions[]='depth>='.($this->depth+$this->offset);
		}
		if($this->range!==null){
			$conditions[]='depth<='.($this->depth+$this->offset+$this->range);
		}
		if(!empty($this->where) && is_array($this->where)){
			$conditions=array_merge($conditions,$this->where);
		}
		$query="
			SELECT ".($count ? 'COUNT(*) c' : $this->columns_string)."
			FROM {".$this->table."}
			".(!empty($conditions) ? "WHERE ".implode(' AND ', $conditions) : false)."
			".($this->order_by ? "ORDER BY ".$this->order_by : false)."
			";
		return ($count ? $this->db->get_record_sql($query)->c : $this->softLoadRecords($this->db->get_records_sql($query)));
	}

	public function addWhere($where){
		if(!empty($where)){
			$this->where[]=$where;
		}
		return $this;
	}

	/**
	 * facade pro odebrani uzlu
	 * @return \TreeTraversal
	 */
	public function removeItem(){
		if(!empty($this->data)){
			$this
							->transactionStart()
							->_removeItem()
							->transactionAllowCommit()
							->reset();
		}
		return $this;
	}

	/**
	 * @return type
	 * @throws Exception
	 */
	public function getLastItem(){
		return $this->n()->softLoadRecord($this->db->get_record_sql("SELECT ".$this->columns_string." FROM {".$this->table."} ".($this->base_string ? ' WHERE '.$this->base_string : false)." ORDER BY id DESC LIMIT 1"));
	}

	/**
	 * @return \TreeTraversal
	 */
	public function loadParent(){
		$breadcrumbs=$this->getBreadcrumbs();
		if(!empty($breadcrumbs)){
			$parent=end($breadcrumbs);
			$this->softLoadRecord($parent->data);
		}
		return $this;
	}

	/**
	 * @return \TreeTraversal
	 */
	public function loadRoot(){
		$root=$this->getRoot();
		$this->softLoadRecord($root->data);
		return $this;
	}

	/**
	 * pomocna metoda pro rychlou vizualici stromu
	 * @param boolean $all [optional] vse, nebo jen aktualni strom
	 * @param string $print_data [optional] ktery sloupec se ma vypsat
	 * @return string
	 */
	public function getTreePreview($all=false,$print_data=false){
		$return=false;
		$tree=$this->n()->setOrderBy('lft ASC')->getTree($all);
		$depth = -1;
		foreach($tree as $row) {
				if ($depth < $row->depth) {
						$return.= "<ul>";
				} else {
						$return.= str_repeat("</li></ul>", $depth - $row->depth) . "</li>";
				}
				$return.= "<li>\n".($print_data ? $row->$print_data : serialize($row));
				$depth = $row->depth;
		}
		$return.= str_repeat("</li></ul>", $depth + 1) . "\n";
		return $return;
	}

	/**
	 * vraci jen prime potomky, nebo jejich pocet
	 * @param boolean $count muze vratit jen pocet potomku
	 * @return mixed [array|integer]
	 */
	public function getChildrens($count=false){
		return $this->n()->setRange(0)->setOffset(1)->getTree(false,$count);
	}

	/**
	 * na posledni misto stejne urovne a predka
	 * @return \TreeTraversal
	 */
	public function moveAtLastPosition(){
		$this->transactionStart();
		$parent=$this->n()->loadParent();
		$childrens=$parent->getChildrens();
		$move_after=$this->_moveAfter(end($childrens));
		$this->transactionAllowCommit();
		return $move_after;
	}

	/**
	 * na prvni misto stejne urovne a predka
	 * @return \TreeTraversal
	 */
	public function moveAtFirstPosition(){
		$this->transactionStart();
		$parent=$this->n()->loadParent();
		$childrens=$parent->getChildrens();
		$move_before=$this->_moveBefore(reset($childrens));
		$this->transactionAllowCommit();
		return $move_before;
	}

	/**
	 * ve stejne vetvi posune o jednu pozici nahoru
	 * @return \TreeTraversal
	 */
	public function moveUp(){
		$prev=$this->getPrev();
		if($prev){
			$this
							->transactionStart()
							->_moveBefore($prev)
							->transactionAllowCommit();
		}
		return $this;
	}

	/**
	 * predchozi uzel stejne vetve
	 * @return \TreeTraversal
	 */
	public function getPrev(){
		$return=null;
		$parent=$this->n()->loadParent();
		$childrens=$parent->getChildrens();
		if(!empty($childrens) && count($childrens)>1){
			foreach($childrens as $child){
				if($child->id===$this->id){
					break;
				}
				$return=$child;
			}
		}
		return $return;
	}

	/**
	 * ve stejne vetvi posune o jednu pozici dolu
	 * @return \TreeTraversal
	 */
	public function moveDown(){
		$next=$this->getNext();
		if($next){
			$this
							->transactionStart()
							->_moveAfter($next)
							->transactionAllowCommit();
		}
		return $this;
	}

	/**
	 * nasledujici uzel stejne vetve
	 * @return TreeTraversal
	 */
	public function getNext(){
		$return=null;
		$parent=$this->n()->loadParent();
		$childrens=$parent->getChildrens();
		if(!empty($childrens) && count($childrens)>1){
			$prev=(object)array('id'=>0);
			foreach($childrens as $child){
				if($prev->id===$this->id){
					$return=$child;
					break;
				}
				$prev=$child;
			}
		}
		return $return;
	}

	/**
	 * presune uzel na posledni pozici v novem umisteni
	 * @param integer $id_parent
	 * @return \TreeTraversal
	 */
	public function moveTo($id_parent){
		$transaction=$this->db->start_delegated_transaction();
		$parent=$this->n()->loadParent();
		if(empty($parent->data) || $parent->data->id!==$id_parent){
			$new_parent=$this->n()->load(null,$id_parent);
			if(!empty($new_parent)){
				$childrens=$new_parent->getChildrens();
				if(empty($childrens)){
					$this->_moveToAsFirstChildren($new_parent);
				}
				else{
					$this->_moveAfter(end($childrens));
				}
			}
			else{
				throw new Exception('No parent loaded.');
			}
		}
		$transaction->allow_commit();
		return $this;
	}

	/* ************************************************************************ */
	/* public setters */
	/* ************************************************************************ */

	/**
	 * @param string $order_by
	 * @return \TreeTraversal
	 */
	public function setOrderBy($order_by){
		$this->order_by=$order_by;
		return $this;
	}

	public function setAllRoots($all_roots){
		$this->all_roots=($all_roots ? true : false);
		return $this;
	}

	/**
	 * od ktere urovne dal ma vybrat, resp. vzdalenost na ktere ma zacit (null=aktualni)
	 * @param integer $offset
	 * @return \TreeTraversal
	 */
	public function setOffset($offset){
		$this->offset=(int)$offset;
		return $this;
	}

	/**
	 * kolik urovni ma zahrnout (null=bez omezeni)
	 * @param integer $range
	 * @return \TreeTraversal
	 */
	public function setRange($range){
		$this->range=(int)$range;
		return $this;
	}

	/**
	 * @param array $columns
	 * @return \TreeTraversal
	 */
	public function setColumns($columns){
		$this->columns=$columns;
		$this->columns_string=implode(',', array_merge($columns,$this->base_required_columns));
		return $this;
	}

	/**
	 * @global moodle_database $_db
	 * @return \TreeTraversal
	 */
	public function setDb(moodle_database $_db=null){
		global $DB;
		if(!empty($_db)){
			$this->db=&$_db;
		}
		else{
			$this->db=&$DB;
		}
		return $this;
	}

	/* ************************************************************************ */
	/* public getters */
	/* ************************************************************************ */

	/**
	 * pocet vsech poduzlu
	 * oproti getChildren(true) neposila dotaz do db, ale jen odecte rgt od lft a podeli
	 * @return integer
	 */
	public function getAllChildrensCount(){
		return $this->all_childrens_count;
	}

	/**
	 * parametr data
	 * @return stdClass
	 */
	public function getData(){
		return $this->data;
	}

	/**
	 * vrati prvniho nejblizho predka dvou uzlu
	 * @param TreeTraversal $node
	 * @return stdClass
	 */
	public function getImmediateParent(TreeTraversal $node){
		$return=null;
		$node_breadcrumbs=$node->getBreadcrumbs();
		foreach($this->getBreadcrumbs() as $crumb){
			if(!isset($node_breadcrumbs[$crumb->id])){
				break;
			}
			$return=$crumb;
		}
		return $return;
	}

	/**
	 * zakaze provadeni transakci
	 * @return \TreeTraversal
	 */
	public function transactionDisable(){
		$this->allow_transaction=false;
		return $this;
	}

	/**
	 * povoli provadeni transakci
	 * @return \TreeTraversal
	 */
	public function transactionEnable(){
		$this->allow_transaction=true;
		return $this;
	}

	/* ************************************************************************ */
	/* private methods */
	/* ************************************************************************ */


	private function setRoot(){
		$tree=$this->n()->setRange(1)->getTree(true);
		if(!empty($tree)){
			$this->root=reset($tree);
		}
		return $this;
	}

	/**
	 * kontroluje jestli strom neni v nekonzistentnim stavu
	 * provadi se automaticky po kazde zmene stromu
	 * a nedovoli commit narusenych dat do db
	 * @return \TreeTraversal
	 * @throws Exception
	 */
	private function controlTree(){
		$this->setRoot();
		$message='Inconsistent state of tree traversal table. ';
		if(!$this->controlTree1()){
			throw new Exception($message.'Control error 1.');
		}
		elseif(!$this->controlTree2()){
			throw new Exception($message. 'Control error 2.');
		}
		elseif(!$this->controlTree3()){
			throw new Exception($message. 'Control error 3.');
		}
		elseif(!$this->controlTree4()){
			throw new Exception($message. 'Control error 4.');
		}
		elseif(!$this->controlTree5()){
			throw new Exception($message. 'Control error 5.');
		}
		return $this;
	}

	/**
	 * @return boolean
	 */
	private function controlTree1(){
		$return=true;
		if(empty($this->root)){
			$query="SELECT COUNT(*) cnt FROM {".$this->table."}".($this->base_string ? " WHERE ".$this->base_string : false);
			if($this->db->get_record_sql($query)->cnt){
				$return=false;
			}
		}
		return $return;
	}

	/**
	 * @return boolean
	 */
	private function controlTree2(){
		$return=true;
		if(!empty($this->root)){
			try{
				$query="SELECT COUNT(*) cnt FROM {".$this->table."} WHERE (lft<=".$this->root->lft." OR lft>=".$this->root->rgt." OR rgt<=".$this->root->lft." OR rgt>=".$this->root->rgt.")".($this->base_string ? " AND ".$this->base_string : false);
				$return=($this->db->get_record_sql($query)->cnt>1 ? false : true);
			}
			catch(Exception $exc){
				throw new Exception('Unknown error during control tree 2. ('.$exc->getMessage().')');
			}
		}
		return $return;
	}

	/**
	 * @return boolean
	 */
	private function controlTree3(){
		$return=true;
		try{
			$query="
				SELECT
					id,
					(SELECT COUNT(*) FROM {".$this->table."} t1 WHERE t1.lft={".$this->table."}.lft".($this->base_string ? " AND t1.".$this->base_column."=".$this->base_value : false).") cnt1,
					(SELECT COUNT(*) FROM {".$this->table."} t2 WHERE t2.lft={".$this->table."}.rgt".($this->base_string ? " AND t2.".$this->base_column."=".$this->base_value : false).") cnt2,
					(SELECT COUNT(*) FROM {".$this->table."} t3 WHERE t3.rgt={".$this->table."}.rgt".($this->base_string ? " AND t3.".$this->base_column."=".$this->base_value : false).") cnt3,
					(SELECT COUNT(*) FROM {".$this->table."} t4 WHERE t4.rgt={".$this->table."}.lft".($this->base_string ? " AND t4.".$this->base_column."=".$this->base_value : false).") cnt4
				FROM {".$this->table."}
				".($this->base_string ? "WHERE ".$this->base_string : false);
			foreach($this->db->get_records_sql($query) as $record){
				if($record->cnt1!=1 || $record->cnt2!=0 || $record->cnt3!=1 || $record->cnt4!=0){
					$return=false;
				}
			}
		}
		catch(Exception $exc){
			throw new Exception('Unknown error during control tree 3. ('.$exc->getMessage().')');
		}
		return $return;
	}

	/**
	 * konroluje unikatnost kazdeho uzlu a jeho hodnot
	 * @return boolean
	 */
	private function controlTree4(){
		$return=true;
		$query="SELECT COUNT(*) cnt, MAX(lft) ml, MAX(rgt) mr FROM {".$this->table."}".($this->base_string ? " WHERE ".$this->base_string : false);
		$record=$this->db->get_record_sql($query);
		if(($record->cnt*2)!=$record->mr){
			$this->debug($record,'vysledek dotazu kontroly 4');
			$this->debug($this->data,'this');
			exit('<p style="font-size: 75%; color: white; background: red; padding: 1em;"><b>file: </b>'.__FILE__.'<br><b>line: </b>'.__LINE__.'<br><b>function: </b>exit()</p>');

			$return=false;
		}
		return $return;
	}

	/**
	 * kontroluje zanoreni
	 * @return boolean
	 */
	private function controlTree5(){
		$return=true;
		$query="
			SELECT * FROM {".$this->table."} dc1
			WHERE ".($this->base_string ? 'dc1.'.$this->base_column."=".$this->base_value." AND " : false)." dc1.depth>0 AND dc1.depth-1!=
			(
			SELECT depth
			FROM {".$this->table."} dc2
			WHERE dc2.lft<dc1.lft and dc2.rgt>dc1.rgt".($this->base_column ? " AND dc2.".$this->base_column."=dc1.".$this->base_column : false)."
			ORDER BY lft DESC LIMIT 1
			);";
		$records=$this->db->get_records_sql($query);
		if(!empty($records)){
			$return=false;
		}
		return $return;
	}

	/**
	 * nacteni db zaznamu do parametru aktualniho objektu
	 * @param stdClass $record
	 * @return \TreeTraversal
	 */
	private function softLoadRecord(stdClass $record){
		try{
			$this->id=$record->id;
			$this->lft=$record->lft;
			$this->rgt=$record->rgt;
			$this->depth=$record->depth;
			$this->timecreated=$record->timecreated;
			$this->timemodified=$record->timemodified;
			$this->all_childrens_count=($this->rgt-$this->lft-1)/2;
			$this->data=$record;
		}
		catch(Exception $exc){
			throw new Exception('Error during soft loading record. ('.$exc->getMessage().')');
		}

		return $this;
	}

	/**
	 * jednotlive prvky pole vrati jako objekty TreeTraversal
	 * @param array $records
	 * @return array
	 * @throws Exception
	 */
	private function softLoadRecords($records){
		$return=null;
		if(is_array($records)){
			$return=array();
			foreach($records as $index => $record){
				$return[$index]=$this->n()->softLoadRecord($record);
			}
		}
		else{
			throw new Exception('Given param must be array.');
		}
		return $return;
	}

	/**
	 * presun pred konkretni uzel
	 * @param TreeTraversal $node
	 * @return \TreeTraversal
	 */
	private function _moveBefore(TreeTraversal $node){
		$return=$this;
		// sam pred sebe se uzel nepresouva
		if(!$this->isSamePosition($node)){
			$this
							->setMoveParams($node->lft, $node->n()->loadParent())
							->openLine()
							->_move()
							->closeLine();
		}
		return $return;
	}

	/**
	 * presune
	 * @throws Exception
	 * @return \TreeTraversal
	 */
	private function _move(){
		if($this->move_new_lft){
			// pred timto krokem melo dojit k otevreni prostoru pro presun
			// takze potrebuji aktualni data
			$this->move_node_after_open_line=$this->n()->load(null,$this->data->id);
			try{
				// presun
				$query1="UPDATE {".$this->table."} SET lft = lft ".($this->move_tree_distance_jump>=0 ? '+' : false).$this->move_tree_distance_jump.", rgt = rgt ".($this->move_tree_distance_jump>=0 ? '+' : false).$this->move_tree_distance_jump.", depth=depth".($this->move_tree_depth_jump>=0 ? '+' : false).$this->move_tree_depth_jump.",".$this->getTimemodifiedString()." WHERE ".($this->base_string ? $this->base_string.' AND ' : false)." lft >= ".$this->move_node_after_open_line->lft." AND rgt<=".$this->move_node_after_open_line->rgt;
				$this->debug($query1, '_move')->db->execute($query1);
			}
			catch(Exception $exc){
				throw new Exception('Error during moving node. ('.$exc->getMessage().'; file: '.$exc->getFile().'; line: '.$exc->getLine().')');
			}
		}
		else{
			throw new Exception('No lft set during move item at tree traversal.');
		}
		return $this;
	}

	/**
	 * vypisuje debug retezec dodane promenne, pokud je zapnuto debugovani
	 * @param mixed $debug
	 * @param string $name
	 * @return \TreeTraversal
	 */
	private function debug($debug, $name=null){
		$name=($name ? $name : 'debug');
		if($this->debug){
			// vytiskne report
			echo '<div style="background: red; color: #ffc; font-weight: bold; padding: .3em 1em; margin: 1em 0 0 0; font-size: 130%; font-family: Courier, monospace;">'.$name.'</div><div style="border: 1px solid red; background: #ffc; padding: 1em; margin: 0 0 1em 0; overflow: auto; font-family: Courier, monospace;"><pre>';
			var_export($debug);
			echo '</pre><p style="font-size: 75%; color: red;">';
			foreach(debug_backtrace() as $values){
				echo '<em># <b>file:</b> '.$values['file'].'; <b>line:</b> '.$values['line'].'; <b>function:</b> '.$values['class'].'::'.$values['function'].'</em><br>';
			}
			echo '<br><b>file: </b>'.__FILE__.'<br><b>line: </b>'.__LINE__.'</p></div>';
		}
		return $this;
	}

	/**
	 * @param \TreeTraversal $new_parent
	 * @todo obcas vyhazuje chybu
	 */
	private function _moveToAsFirstChildren(TreeTraversal $new_parent){
		$this
						->setMoveParams($new_parent->lft+1, $new_parent)
						->openLine()
						->_move()
						->closeLine();
	}

	/**
	 * @param TreeTraversal $test
	 * @return boolean
	 */
	private function isSamePosition(TreeTraversal $test){
		return (
						($test->lft!==$this->lft && ($test->lft-1)!==$this->rgt)
						? false
						: true);
	}

	/**
	 * presun za konkretni uzel
	 * @param stdClass $node
	 * @return \TreeTraversal
	 */
	private function _moveAfter(TreeTraversal $node){
		// sam pred sebe se uzel nepresouva
		if(!$this->isSamePosition($node)){
			$this
							->setMoveParams($node->rgt+1, $node->n()->loadParent())
							->openLine()
							->_move()
							->closeLine();
		}
		return $this;
	}

	/**
	 * nastavi parametry nezbytne pro presun uzlu
	 * @param integer $lft
	 * @param TreeTraversal $parent
	 * @return \TreeTraversal
	 */
	private function setMoveParams($lft, TreeTraversal $parent){
		$this->move_new_lft=$lft;
		$this->move_new_parent=$parent;
		$this->move_immediate_parent=($this->move_new_parent ? $this->getImmediateParent($this->move_new_parent) : false);
		$this->move_actual_parent=$this->n()->loadParent();
		$this->move_tree_size_jump=$this->rgt-$this->lft+1;
		$this->move_tree_distance_jump=$lft - $this->lft - ($lft<$this->lft ? $this->move_tree_size_jump : 0);
		$this->move_tree_depth_jump=$this->move_new_parent->depth - $this->move_actual_parent->depth;
		return $this;
	}

	/**
	 * uvolni misto pro uzel
	 * @return \TreeTraversal
	 * @throws Exception
	 */
	private function openLine(){
		if($this->move_new_lft){
			try{
				$query="UPDATE {".$this->table."} SET lft = lft + IF(lft>=".$this->move_new_lft.", ".$this->move_tree_size_jump.",0), rgt = rgt + ".$this->move_tree_size_jump.",".$this->getTimemodifiedString()." WHERE ".($this->base_string ? $this->base_string.' AND ' : false)." rgt>=".$this->move_new_lft;
//				$query="UPDATE {".$this->table."} SET lft = lft + ".$open_jump.", rgt = rgt + ".$open_jump.",".$this->getTimemodifiedString()." WHERE ".($this->base_string ? $this->base_string.' AND ' : false)." lft >".(!$this->move_new_parent->all_childrens_count ? '=' : false)." ".$this->move_new_lft." AND rgt>".$this->move_new_lft;
				$this->debug($query,'open line')->db->execute($query);
			}
			catch(Exception $exc){
				throw new Exception('Error (1) during open tree for move. ('.$exc->getMessage().'; file: '.$exc->getFile().'; line: '.$exc->getLine().')');
			}
		}
		else{
			throw new Exception('No lft set during during open line at tree traversal.');
		}
		return $this;
	}

	/**
	 * zavre prostor po presunu
	 * @return \TreeTraversal
	 */
	private function closeLine(){
		$query1="
			UPDATE {".$this->table."}
			SET
				lft = lft - IF(lft>".$this->move_node_after_open_line->rgt.",".$this->move_tree_size_jump.", 0),
				rgt = rgt -".$this->move_tree_size_jump.",
				".$this->getTimemodifiedString()."
			WHERE
				".($this->base_string ? $this->base_string.' AND ' : false)."
				rgt> ".$this->move_node_after_open_line->rgt;
//		$query1="
//			UPDATE {".$this->table."}
//			SET
//				rgt = rgt ".$close_jump_str.",
//				lft = lft ".$close_jump_str.",
//				".$this->getTimemodifiedString()."
//			WHERE
//				".($this->base_string ? $this->base_string.' AND ' : false)."
//				rgt>= ".$this->move_node_after_open_line->rgt." AND
//				lft>=".$this->move_node_after_open_line->rgt;

		$this
						->debug($query1, 'close line')
						->db->execute($query1);

//		if($this->move_new_parent->id!==$this->move_actual_parent->id){
//			$query2="
//				UPDATE {".$this->table."}
//				SET
//					rgt = rgt ".$close_jump_str.",
//					".$this->getTimemodifiedString()."
//				WHERE
//					".($this->base_string ? $this->base_string.' AND ' : false)."
//					rgt>=".$this->move_node_after_open_line->rgt." AND
//					lft<=".$this->move_node_after_open_line->lft;
//
//			if($this->debug){
//				echo '<div style="background: red; color: #ffc; font-weight: bold; padding: .3em 1em; margin: 1em 0 0 0; font-size: 130%; font-family: Courier, monospace;">close 2</div><div style="border: 1px solid red; background: #ffc; padding: 1em; margin: 0 0 1em 0; overflow: auto; font-family: Courier, monospace;"><pre>';
//				var_export($query2);
//				echo '</pre><p style="font-size: 75%; color: red;"><b>file: </b>'.__FILE__.'<br><b>line: </b>'.__LINE__.'</p></div>';
//			}
//
//			$this->db->execute($query2);
//		}

//		$parent=$this->n()->loadParent();
//
//		if(!empty($this->move_new_parent) && $parent->id!==$this->move_new_parent->id){
//			$query3="
//				UPDATE {".$this->table."}
//				SET
//					rgt = rgt ".($close_jump>=0 ? '+' : false).$close_jump.",
//					".$this->getTimemodifiedString()."
//				WHERE
//					".($this->base_string ? $this->base_string.' AND ' : false)."
//					lft > ".$this->move_immediate_parent->lft." AND
//					rgt < ".$this->move_immediate_parent->rgt." AND
//					lft<".$this->lft." AND
//					rgt>".$this->rgt;
//
//			echo '<div style="background: red; color: #ffc; font-weight: bold; padding: .3em 1em; margin: 1em 0 0 0; font-size: 130%; font-family: Courier, monospace;">close 3</div><div style="border: 1px solid red; background: #ffc; padding: 1em; margin: 0 0 1em 0; overflow: auto; font-family: Courier, monospace;"><pre>';
//			var_export($query3);
//			echo '</pre><p style="font-size: 75%; color: red;"><b>file: </b>'.__FILE__.'<br><b>line: </b>'.__LINE__.'</p></div>';
//
//			$this->db->execute($query3);
//		}
		return $this->controlTree();
	}



	/**
	 * @return \TreeTraversal
	 */
	private function reset(){
		foreach($this->base_required_columns as $property){
			$this->$property=null;
		}
		foreach($this->other_properties_for_reset as $property){
			$this->$property=null;
		}
		return $this;
	}

	/**
	 * pridani noveho potomka za vsechny stavajici
	 * @param stdClass $data
	 * @return stdClass
	 * @throws Exception
	 */
	private function _addChildren(stdClass $data){
		$return=null;
		if(!empty($this->data)){
			try{
				$query1="UPDATE {".$this->table."} SET lft = lft + 2,".$this->getTimemodifiedString()." WHERE ".($this->base_string ? $this->base_string.' AND ' : false)." lft > ".$this->rgt;
				$query2="UPDATE {".$this->table."} SET rgt = rgt + 2,".$this->getTimemodifiedString()." WHERE ".($this->base_string ? $this->base_string.' AND ' : false)." rgt >= ".$this->rgt;
				$this->db->execute($query1);
				$this->db->execute($query2);
				$this->debug($query1, 'add children query 1')->debug($query2, 'add children query 2');
				$return=$this->_addItem($data);
			}
			catch(Exception $exc){
				throw new Exception('Cant add children! ('.$exc->getMessage().')');
			}
		}
		return $return;
	}

	/**
	 * odebrani uzlu
	 * @return \TreeTraversal
	 */
	private function _removeItem(){
		$this->db->execute("DELETE FROM {".$this->table."} WHERE ".($this->base_string ? $this->base_string.' AND ' : false)." lft >= ".$this->lft." AND rgt <= ".$this->rgt);
		$diff = $this->rgt - $this->lft + 1;
		$this->db->execute("UPDATE {".$this->table."} SET lft = lft - ".$diff.",".$this->getTimemodifiedString()." WHERE ".($this->base_string ? $this->base_string.' AND ' : false)." lft > ".$this->rgt);
		$this->db->execute("UPDATE {".$this->table."} SET rgt = rgt - ".$diff.",".$this->getTimemodifiedString()." WHERE ".($this->base_string ? $this->base_string.' AND ' : false)." rgt > ".$this->rgt);
		return $this->controlTree();
	}

	/**
	 * retezec pro update timemodified
	 * @return string
	 */
	private function getTimemodifiedString(){
		return "timemodified='".date('Y-m-d H:i:s',time())."'";
	}

	/**
	 * pridani uzlu
	 * @param stdClass $data
	 * @param boolean $last_item
	 * @return stdClass objekt vytvoreneho zaznamu
	 */
	private function _addItem(stdClass $data,$last_item=false){
		$prepared_data=$this->getPreparedColumnsValues($data,$last_item,true);
		try{
			$query="
				INSERT INTO {".$this->table."}
					(".implode(',',$prepared_data['columns']).")
				".($last_item ? "SELECT" : "VALUES(")."
					".implode(',',$prepared_data['values'])."
				".($last_item ? "FROM {".$this->table."}".($this->base_string ? ' WHERE '.$this->base_string : false) : ")")."
					";
			$this->db->execute($query,(array)$data);
			$return=$this->getLastItem();
			if(!empty($return)){
			}
		}
		catch(Exception $exc){
			throw new Exception('Error during saving new node. ('.$exc->getMessage().', '.$this->db->get_last_error().')');
		}
		return $return->controlTree();
	}

	/**
	 * get data for insert new item
	 * @param array $data
	 * @return array (columns,values)
	 */
	private function getPreparedColumnsValues(stdClass $data,$last_item=false,$insert_new=false){
		$data=(array)$data;
		$columns=$this->base_required_columns;
		if($last_item){
			$values=array('IFNULL(MAX(rgt), 0) + 1','IFNULL(MAX(rgt), 0) + 2','0');
		}
		else{
			$values=array($this->rgt,$this->rgt+1,$this->depth+1);
		}
		// timecreated, timemodified
		if($insert_new){
			$values[]="'".date('Y-m-d H:i:s',time())."'";
			$values[]="NULL";
		}
		else{
			$values[]="timecreated";
			$values[]="'".date('Y-m-d H:i:s',time())."'";
		}
		// base values
		if($this->base_string){
			array_push($columns, $this->base_column);
			array_push($values, $this->base_value);
		}
		if(!empty($data)){
			foreach($data as $column => $value){
				if(!in_array($column, $this->required_columns)){
					$value=$value;
					array_push($columns, $column);
					array_push($values, '?');
				}
			}
		}
		return array('columns'=>$columns,'values'=>$values);
	}

	/**
	 * @param array $data
	 * @return array (string,values)
	 */
	private function getPreparedKeysValues($data, $glue=' AND '){
		$return=false;
		if(!empty($data)){
			$string=array();
			$values=array();
			foreach($data as $key => $value){
				$string[]=$key.($value===null ? ' IS ' : ' = ').'?';
				$values[]=$value;
			}
			$return=array('string'=>implode($glue, $string),'values'=>$values);
		}
		return $return;
	}

	/**
	 * pokud je v tabulce vice stromu
	 * pak pro tuto instanci lze nastavit zakladni podminku pro jejich odliseni
	 * @param string $base_column nazev sloupce
	 * @param string $base_value hodnota
	 * @return \TreeTraversal
	 */
	private function setBase($base_column,$base_value){
		if($base_column && $base_value){
			$this->base_column=$base_column;
			$this->base_value=$base_value;
			$this->base_string="{".$this->table."}.".$base_column."='".$base_value."'";
		}
		elseif($base_column || $base_value){
			throw new Exception('Base value and base key must be set both!');
		}
		return $this;
	}

	/**
	 * @return \TreeTraversal
	 */
	private function setRequiredColumns(){
		$this->required_columns=$this->base_required_columns;
		$this->required_columns[]=$this->base_column;
		return $this;
	}

	/**
	 * @param type $table
	 * @return \TreeTraversal
	 */
	private function setTable($table){
		if(!$this->isTableExists($table)){
			throw new Exception('Unexisting table "'.$table.'"!');
		}
		elseif(!$this->hasTableTraversalStructure($table)){
			throw new Exception('Unexisting required columns at table "'.$table.'"! ('.implode(', ', $this->required_columns).')');
		}
		else{
			$this->table=$table;
		}
		return $this;
	}

	/**
	 * @param string $table
	 * @return boolean
	 */
	private function isTableExists($table){
		global $CFG;
		$query="SHOW TABLES LIKE ?";
		return ($this->db->get_record_sql($query,array($CFG->prefix.$table)) ? true : false);
	}

	/**
	 * @param string $table
	 * @return boolean
	 */
	private function hasTableTraversalStructure($table){
		global $CFG;
		$query="SHOW COLUMNS FROM ".$CFG->prefix.$table;
		$count=0;
		foreach($this->db->get_records_sql($query) as $info){
			if(in_array($info->field, $this->required_columns)){
				$count++;
			}
		}
		return (count($this->required_columns)!==$count ? false : true);
	}

	/**
	 * @return \TreeTraversal
	 */
	protected function transactionStart(){
		if($this->allow_transaction){
			$this->transaction=$this->db->start_delegated_transaction();
		}
		return $this;
	}

	/**
	 * @return \TreeTraversal
	 */
	protected function transactionAllowCommit(){
		if($this->allow_transaction){
			$this->transaction->allow_commit();
		}
		return $this;
	}
}
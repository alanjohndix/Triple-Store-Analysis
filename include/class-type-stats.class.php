<?php

require_once 'triple-func.inc.php';

class ClassTypeStats {
	var $type_id_map = false;
	var $pred_id_map = false;
	
	var $cc_stats;
	var $c_counts;
	var $sorted_keys;
	var $relations;

	var $key_sort_order = array( 't', '1', '+', 's', '-', 'p', '2', 'c' );
	var $key_sort;
	var $key_kinds = array( '+'=>array('pred','subj:'), 's'=>array('pred','subj:'),
	                        '-'=>array('pred','obj:'),  'p'=>array('pred','obj:'),
							't'=>array('type','type:'),
							'c' => array('gen','gen:'), '2' => array('gen','gen2:'), '1' => array('gen','gen1:')
							 );
	var $kind_map = array();
	
	function __construct($count_file,$type_id_file=false,$pred_id_file=false) {
		if ( $type_id_file ) {
			$this->read_type_names($type_id_file);
		}
		if ( $pred_id_file ) {
			$this->read_pred_names($pred_id_file);
		}
		$this->init_keys();
		$this->read_counts($count_file);
		//print_r($this->cc_stats); echo "\n\n\n";
		$this->gen_single_class_counts();
		$this->sort_keys();
	}
	function init_keys() {
		$this->key_sort = array();
		foreach ( $this->key_sort_order as $index=>$dir ) {
			$this->key_sort[$dir] = $index;
		}
		foreach ( $this->key_sort_order as $index=>$dir ) {
			$this->key_sort[$dir] = $index;
		}
	//	$this->kind_map = array( 'pred'=>&$this->pred_id_map, 'type'=>&$this->type_id_map );
	}
	function read_type_names($type_id_file) {
		$this->type_id_map = read_id_map($type_id_file);
		$this->kind_map['type'] = &$this->type_id_map;
	}
	function read_pred_names($pred_id_file) {
		$this->pred_id_map = read_id_map($pred_id_file);
		$this->kind_map['pred'] = &$this->pred_id_map;
	}
	function read_counts($count_file) {
		$this->cc_stats = read_class_type_counts_as_stats($count_file);
	}
	function gen_single_class_counts() {
		$this->c_counts = array();
		foreach ( $this->cc_stats as $c_key => $entry ) {
			$ct = $entry[$c_key]['ct'];
			$this->c_counts[$c_key] = $ct;
			//echo "count for $c_key is $ct \n";
		}
	}
	function sort_keys() {
		$this->sorted_keys = array_keys($this->cc_stats);
		usort($this->sorted_keys , array($this,'compare_keys'));
		//echo "\n\n=============  sorted keys\n\n";
		//print_r($this->sorted_keys);
		//echo "\n\n";
		//exit;
	}
	function compare_keys($key1,$key2) {
		list($id1,$dir1) = explode('-',$key1,2);
		list($id2,$dir2) = explode('-',$key2,2);
		//if ( $dir1 != $dir2 ) return $dir1=='t' ? -1:1;
		if ( $dir1 != $dir2 ) return $this->key_sort[$dir1] - $this->key_sort[$dir2];
		else return $id1 - $id2;
	}
	function get_name($key) {
		list($id,$kind) = explode('-',$key,2);
		if ( array_key_exists($kind,$this->key_kinds) ) {
			list( $key_kind, $protocol ) = $this->key_kinds[$kind];
			switch ( $key_kind ) {
				case 'pred':
						if ( array_key_exists($id,$this->pred_id_map) ) {
							return $this->pred_id_map[$id];
						}
						break;
				case 'type':
						if ( array_key_exists($id,$this->type_id_map) ) {
							return $this->type_id_map[$id];
						}
						break;
			}
			return $protocol .':'. $id;
		} else {
			return 'unknown:'. $id;
		}
		
	/*	
		if ( $this->type_id_map && $kind=='t' && array_key_exists($id,$this->type_id_map) ){
			return $this->type_id_map[$id];
		} else {
			$protocol = $kind=='c' ? "gen" : "type";
			return $protocol .':'. $id;
		}
		*/
	}
	function calculate_stats() {
		foreach( $this->cc_stats as $c1 => &$c1_stats ) {
			foreach( $c1_stats as $c2 => &$stats ) {
				//if ( $debug ) exit;
				//if ( $p1 == 1764429 && $p2 == 1522696 ) {
				//	$debug = true;
				//} else {
					$debug = false;
				//}
				$name1 = $this->get_name($c1);
				$name2 = $this->get_name($c2);
				//echo "\n [".$p1."]" . $name1 . "\t[".$p2."]" . $name2 ."\n";
				$ct1 = $this->c_counts[$c1];
				$ct2 = $this->c_counts[$c2];
				$ct = $stats['ct'];
				
				$equality = $ct / ($ct1+$ct2-$ct);
				$subset = $ct / $ct1;
				$superset = $ct / $ct2;
				$stats['equality'] = $equality;
				$stats['subset'] = $subset;
				$stats['superset'] = $superset;
				//exit;
			}
		}
	}
	function calc_relations($limits=false) {
		if ( ! $limits ) $limits = array( 'equality'=>0.9, 'subset'=>0.8, 'overlap'=>0.1 );
		$this->relations = array();
		//foreach( $this->cc_stats as $c1 => &$c1_stats ) {
		foreach( $this->sorted_keys as $c1 ) {
			$c1_stats = &$this->cc_stats[$c1];
			$c1_name = $this->get_name($c1);
			$this->relations[$c1]['name'] = $c1_name;
			$this->relations[$c1]['ct'] = $this->c_counts[$c1];
			//foreach( $c1_stats as $c2 => &$stats ) {
			foreach( $this->sorted_keys as $c2 ) {
				$stats = &$c1_stats[$c2];
				if ( $c1 == $c2 ) continue;
				$c2_name = $this->get_name($c2);
				$rels = array();
				if ( $stats['equality'] >=  $limits['equality'] ) {
					$rels[] = 'sameAs';
				}
				if ( $stats['subset'] >=  $limits['subset'] ) {
					$rels[] = 'superType';
				}
				if ( $stats['superset'] >=  $limits['subset'] ) {
					$rels[] = 'subType';
				}
				if ( !$rels ) {
					if ( $stats['subset'] >=  $limits['overlap'] ) {
						$rels[] = 'overlap';
					}
					if ( $stats['superset'] >=  $limits['overlap'] ) {
						$rels[] = 'overlap';
					}
				}
				$rels = array_unique($rels);
				foreach ( $rels as $rel ) {
					$this->relations[$c1][$rel][] = array( 'id'=>$c2, 'name'=>$c2_name, 'ct'=>$stats['ct']);
				}
			}
		}
	}
	function format_relations($reln_file,$relations) {
		$fh = fopen($stats_file,"w");
		foreach ( $relations as $cid => $cluster ) {
			foreach( $cluster['items'] as $item ) {
				fwrite( $fh, $cid . "\t" . $item['p'] . "\t" . $item['dir'] . "\t" . $item['name'] . "\n" );
			}
			fwrite( $fh,  "\n" ); // blank line to separate clusters
		}
		fclose($fh);
	}
	
}

?>
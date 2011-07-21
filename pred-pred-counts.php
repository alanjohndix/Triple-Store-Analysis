<?php

require "config.inc.php";
require "include/entity-processor.class.php";

//define( 'ASPIRE_DIR', '/Users/alandix/Documents/Talis/data/aspire/' );
//define( 'META_DIR', ASPIRE_DIR . '20110401040135/metabox/' );
//define( 'P_DIR', ASPIRE_DIR . 'processed/' );

//$pso_file = P_DIR . 'pso-sorted.txt';
//$ep_file = P_DIR . 'ep-dir.txt';

$p_id_file = P_DIR . 'p-ids.txt';
$ep_file = P_DIR . 'ep-dir.txt';
$pp_ct_file = P_DIR . 'pp-ct.txt';

$ep_fh = fopen($ep_file,"r");

$predicates = file($p_id_file);
$pred_ids = array();

foreach( $predicates as $line ) {
	$line = trim($line);
	list($id,$p) = explode("\t",$line);
	$pred_ids[] = $id;
	if ( $id == 3 ) {
		echo "found 3 in predicates\n";
		exit;
	}
	
}
/*
class PredPredCounter {
	var $ep_fh;
	var $counts;
	function __construct( $ep_fh, $pred_ids=false ) {
		$this->ep_fh = $ep_fh;
		$this->counts = array();
		if ( $pred_ids ) {
			foreach ( $pred_ids as $p1 ) {
				foreach ( $pred_ids as $p2 ) {
					$this->make_cts($p1,$p2);
				}
			}
		}
	}
	function make_cts($p1,$p2) {
		if ( ! array_key_exists($p1,$this->counts) ) {
			$this->counts[$p1] = array();
		}
		if ( ! array_key_exists($p2,$this->counts[$p1]) ) {
			$this->counts[$p1][$p2] = array('ss'=>0,'so'=>0,'os'=>0,'oo'=>0);
		}
	}
			
	var $sample_delta = 20000;
	//var $sample_delta = 1;
	var $start_time = false;
	var $line_ct;

	function count() {
		$this->process_start();
		$ct = 0;
		$last_sample = 0;
		$start_time = time();
		while ( $line = fgets( $this->ep_fh ) ) {
			$this->line_ct++;
			if ( $this->line_ct >= $last_sample + $this->sample_delta ) {
				$last_sample = $this->line_ct;
				echo "@".(time()-$start_time)." ".$this->line_ct." triples \n";
			}
			$line = trim($line);
			list( $e, $p, $dir ) = explode("\t",$line);
			$this->process_line($e, $p, $dir);
		}
		$this->process_end();
	}

	var $current_entity;
	var $predicates;
	function process_start() {
		$this->current_predicate == false;
		$this->reset_entity_counts();
	}
	function process_end() {
		$this->end_entity();
	}
	function process_line($e, $p, $dir) {
		if ( $e !== $this->current_entity ) {
			$this->end_entity();
			$this->current_entity = $e;
		}
		$this->predicates[] = array($p,$dir);
		//echo "process_triple($p,$s,$o): p_ct=".$this->p_ct." ps[s]=".$this->ps[$s]." po[o]=".$this->po[$o]." \n";
	}
	function reset_entity_counts() {
		$this->p_ct = 0;
		$this->predicates = array();
	}
	function end_entity() {
		if ( $this->current_entity ) {
			$e = $this->current_entity;
			foreach ( $this->predicates as $p1_info ) {
				list($p1,$p1_dir) = $p1_info;
				foreach ( $this->predicates as $p2_info ) {
					list($p2,$p2_dir) = $p2_info;
					$this->make_cts($p1,$p2);
					switch ( $p1_dir.$p2_dir ) {
						case '++':
							$this->counts[$p1][$p2]['ss']++;
							break;
						case '+-':
							$this->counts[$p1][$p2]['so']++;
							break;
						case '-+':
							$this->counts[$p1][$p2]['os']++;
							break;
						case '--':
							$this->counts[$p1][$p2]['oo']++;
							break;
					}
				}
			}
		}
		$this->reset_entity_counts();
	}
	
	function save($pp_ct_fh) {
		foreach ( $this->counts as $p1 => $p1_cts ) {
			foreach ( $p1_cts as $p2 => $cts ) {
				fwrite( $pp_ct_fh, $p1 . "\t" . $p2 . "\t" . $cts['ss'] . "\t" . $cts['so'] . "\t" . $cts['os'] . "\t" . $cts['oo'] . "\n" );
			}
		}
	}
}
*/
class PredPredCounterProcessor {
	var $counts;
	function __construct( $pred_ids=false ) {
		$this->counts = array();
		if ( $pred_ids ) {
			$this->init_pred_ids($pred_ids);
		}
	}
	function init_pred_ids($pred_ids) {
		foreach ( $pred_ids as $p1 ) {
			foreach ( $pred_ids as $p2 ) {
				$this->make_cts($p1,$p2);
			}
		}
	}
	function make_cts($p1,$p2) {
		if ( ! array_key_exists($p1,$this->counts) ) {
			$this->counts[$p1] = array();
		}
		if ( ! array_key_exists($p2,$this->counts[$p1]) ) {
			$this->counts[$p1][$p2] = array('ss'=>0,'so'=>0,'os'=>0,'oo'=>0);
		}
	}
	function process_start() {
	}
	function process_end() {
	}
	function process_entity($e,$predicates) {
		foreach ( $predicates as $p1_info ) {
			list($p1,$p1_dir) = $p1_info;
			if ( $p1 == 11522634 ) {
				echo "****************\n  predicate 11522634 for $e \n****************\n";
			}
			foreach ( $predicates as $p2_info ) {
				list($p2,$p2_dir) = $p2_info;
				$this->make_cts($p1,$p2);
				switch ( $p1_dir.$p2_dir ) {
					case '++':
						$this->counts[$p1][$p2]['ss']++;
						break;
					case '+-':
						$this->counts[$p1][$p2]['so']++;
						break;
					case '-+':
						$this->counts[$p1][$p2]['os']++;
						break;
					case '--':
						$this->counts[$p1][$p2]['oo']++;
						break;
				}
			}
		}
	}
	
	function save($pp_ct_fh) {
		foreach ( $this->counts as $p1 => $p1_cts ) {
			foreach ( $p1_cts as $p2 => $cts ) {
				fwrite( $pp_ct_fh, $p1 . "\t" . $p2 . "\t" . $cts['ss'] . "\t" . $cts['so'] . "\t" . $cts['os'] . "\t" . $cts['oo'] . "\n" );
			}
		}
	}
	
}

$pp_ct_fh = fopen($pp_ct_file,"w");


$start = time();

$ppctr = new PredPredCounterProcessor( $pred_ids );
$ep = new EntityProcessor( $ep_fh, $ppctr );
$ep->process();
$ppctr->save($pp_ct_fh);

echo "predicate-predicate counts took ".(time()-$start)."\n";

?>
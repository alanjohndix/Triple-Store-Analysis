<?php

require "config.inc.php";
require_once 'include/triple-func.inc.php';
require "include/entity-processor.class.php";

//define( 'ASPIRE_DIR', '/Users/alandix/Documents/Talis/data/aspire/' );
//define( 'META_DIR', ASPIRE_DIR . '20110401040135/metabox/' );
//define( 'P_DIR', ASPIRE_DIR . 'processed/' );

$p_id_file = P_DIR . 'p-ids.txt';
$ect_file = P_DIR . 'ect-code.txt';
$ct_ct_file = P_DIR . 'class-type-ct.txt';

$ect_fh = fopen($ect_file,"r");

class ClassTypeCounterProcessor {
	var $counts;
	function __construct( ) {
		$this->counts = array();
	}
	function process_start() {
	}
	function process_end() {
	}
	function process_entity($e,$class_or_type) {
		foreach ( $class_or_type as $c1_info ) {
			list($c1,$c1_kind) = $c1_info;
			$c1_key = $c1.'-'.$c1_kind;
			foreach ( $class_or_type as $c2_info ) {
				list($c2,$c2_kind) = $c2_info;
				$c2_key = $c2.'-'.$c2_kind;
				//echo " add count for [$c1_key][$c2_key] \n";
				$this->counts[$c1_key][$c2_key]++;
			}
		}
	}
	
	function save($ct_ct_file) {
		output_class_type_counts($ct_ct_file,$this->counts);
		/*
		$ct_ct_fh = fopen($ct_ct_file);
		foreach ( $this->counts as $c1_key => $c1_cts ) {
			foreach ( $c1_cts as $c2_key => $ct ) {
				list($c1,$c1_dir) = explode('-',$c1_key,2);
				list($c2,$c2_dir) = explode('-',$c2_key,2);
				fwrite( $ct_ct_fh, $c1 . "\t" . $c1_dir . "\t" . $c2 . "\t" . $c2_dir . "\t" . $ct . "\n" );
			}
		}
		fclose($ct_ct_fh);
		*/
	}
	
}

//$ct_ct_fh = fopen($ct_ct_file,"w");


$start = time();

$ct = new ClassTypeCounterProcessor( $pred_ids );
$ep = new EntityProcessor( $ect_fh, $ct );
$ep->sample_delta = 100000;
$ep->process();
$ct->save($ct_ct_file);

echo "class-type counts took ".(time()-$start)."\n";

?>
<?php

require "config.inc.php";

//define( 'ASPIRE_DIR', '/Users/alandix/Documents/Talis/data/aspire/' );
//define( 'META_DIR', ASPIRE_DIR . '20110401040135/metabox/' );
//define( 'P_DIR', ASPIRE_DIR . 'processed/' );

$pso_file = P_DIR . 'pso-sorted.txt';
//$ep_file = P_DIR . 'ep-dir.txt';

$p_ct_file = P_DIR . 'p-ct.txt';
$ps_ct_file = P_DIR . 'ps-ct.txt';
$po_ct_file = P_DIR . 'po-ct.txt';

$pso_fh = fopen($pso_file,"r");
//$ep_fh = fopen($ep_file,"r");

class PredCounter {
	var $pso_fh;
	var $p_ct_fh;
	var $ps_ct_fh;
	var $po_ct_fh;
	var $counts;
	function __construct( $pso_fh, $p_ct_fh=false, $ps_ct_fh=false, $po_ct_fh=false ) {
		$this->pso_fh = $pso_fh;
		$this->p_ct_fh = $p_ct_fh;
		$this->ps_ct_fh = $ps_ct_fh;
		$this->po_ct_fh = $po_ct_fh;
		$counts = array();
	}
	function count() {
		$this->process_start();
		while ( $line = fgets( $this->pso_fh ) ) {
			$line = trim($line);
			list( $p, $s, $o ) = explode("\t",$line);
			$this->process_triple($p,$s,$o);
		}
		$this->process_end();
	}
	var $current_predicate;
	var $p_ct;
	var $ps;
	var $po;
	function process_start() {
		$this->current_predicate == false;
		$this->reset_predicate_counts();
	}
	function process_end() {
		$this->end_predicate();
	}
	function process_triple($p,$s,$o) {
		if ( $p !== $this->current_predicate ) {
			$this->end_predicate();
			$this->current_predicate = $p;
		}
		$this->p_ct++;
		$this->ps[$s]++;
		$this->po[$o]++;
		//echo "process_triple($p,$s,$o): p_ct=".$this->p_ct." ps[s]=".$this->ps[$s]." po[o]=".$this->po[$o]." \n";
	}
	function reset_predicate_counts() {
		$this->p_ct = 0;
		$this->ps = array();
		$this->po = array();
	}
	function end_predicate() {
		if ( $this->current_predicate ) {
			$p = $this->current_predicate;
			$ps_ct = count($this->ps);
			$po_ct = count($this->po);
			$this->counts[$p] = array('total'=>$this->p_ct, 'subjects'=> $ps_ct, 'objects'=> $po_ct);
			if ( $this->p_ct_fh ) {
				fwrite( $this->p_ct_fh, $p . "\t" . $this->p_ct . "\t" . $ps_ct . "\t" . $po_ct . "\n" );
			}
			if ( $this->ps_ct_fh ) {
				asort($this->ps);
				$ps = array_reverse($this->ps,true);
				foreach ( $ps as $s => $ct ) {
					fwrite( $this->ps_ct_fh, $p . "\t" . $s . "\t" . $ct . "\n" );
				}
			}
			if ( $this->po_ct_fh ) {
				asort($this->po);
				$po = array_reverse($this->po,true);
				foreach ( $po as $o => $ct ) {
					fwrite( $this->po_ct_fh, $p . "\t" . $o . "\t" . $ct . "\n" );
				}
			}
		}
		$this->reset_predicate_counts();
	}
	
	function save_counts($p_ct_fh) {
		foreach ( $this->counts as $p => $p_cts ) {
			fwrite( $this->p_ct_fh, $p . "\t" . $p_cts['total'] . "\t" . $p_cts['subjects'] . "\t" . $p_cts['objects'] . "\n" );
		}
	}
}


$p_ct_fh = fopen($p_ct_file,"w");
$ps_ct_fh = fopen($ps_ct_file,"w");
$po_ct_fh = fopen($po_ct_file,"w");


$start = time();

$pctr = new PredCounter( $pso_fh, $p_ct_fh, $ps_ct_fh, $po_ct_fh );
$pctr->count();

echo "predicate counts took ".(time()-$start)."\n";

?>
<?php

class EntityProcessor {
	var $ep_fh;
	var $processor;
	function __construct( $ep_fh, $processor=false ) {
		$this->ep_fh = $ep_fh;
		$this->counts = array();
		if ( $pred_ids ) {
			$this->init_pred_ids($pred_ids);
		}
		$this->processor = $processor;
	}
	function init_pred_ids($pred_ids) {
		foreach ( $pred_ids as $p1 ) {
			foreach ( $pred_ids as $p2 ) {
				$this->make_cts($p1,$p2);
			}
		}
	}
	function set_processor($processor) {
		$this->processor = $processor;
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

	function process() {
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
		if ( $this->processor ) {
			$this->processor->process_start();
		}
	}
	function process_end() {
		$this->end_entity();
		if ( $this->processor ) {
			$this->processor->process_end();
		}
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
			if ( $this->processor ) {
				$this->processor->process_entity( $this->current_entity, $this->predicates );
			}
		}
		$this->reset_entity_counts();
	}
	
}


?>
<?php

require "config.inc.php";  // uses  URI_BASE_ID LITERAL_BASE_ID
require 'include/parse-ntriples.class.php';

//define( 'ASPIRE_DIR', '/Users/alandix/Documents/Talis/data/aspire/' );
//define( 'META_DIR', ASPIRE_DIR . '20110401040135/metabox/' );
//define( 'P_DIR', ASPIRE_DIR . 'processed/' );

$meta_file = META_DIR . 'meta';

$uri_id_file = P_DIR . 'uri-ids.txt';
$literal_id_file = P_DIR . 'literal-ids.txt';

$spo_file = P_DIR . 'spo.txt';
$sop_file = P_DIR . 'sop.txt';
$ops_file = P_DIR . 'ops.txt';
$pso_file = P_DIR . 'pso.txt';

class EntryToId {
	var $mapping;
	function __construct() {
		$this->mapping = array();
	}
	function lookup($entry) {
		return $this->mapping[$entry];
	}
	function read_id_file($id_file) {
		$id_lines = file($id_file);
		foreach ( $id_lines as $id_line ) {
			$id_line = trim($id_line);
			list($id,$entry) = explode("\t",$id_line);
			$this->mapping[$entry] = $id;
		}
		unset($id_lines);
	}
}

function format_literal($literal,$datatype,$lang) {
	strtr( $literal, array("\t"=>"\\t","\n"=>"\\n","\r"=>"\\r"));
	$literal_full = '"' .$literal. '"';
	if ( $lang ) {
		$literal_full .= "@" . $lang;
	} else if ( $datatype ) {
		$literal_full .= "^^" . $datatype;
	}
	return $literal_full;
}

class TripleToIdProcessor {
	var $spo_fh;
	var $sop_fh;
	var $pso_fh;
	var $ops_fh;
	var $uri_to_id;
	var $literal_to_id;
	function __construct( $uri_to_id, $literal_to_id, $spo_fh, $sop_fh, $pso_fh, $ops_fh ) {
		$this->uri_to_id = $uri_to_id;
		$this->literal_to_id = $literal_to_id;
		$this->spo_fh = $spo_fh;
		$this->sop_fh = $sop_fh;
		$this->ops_fh = $ops_fh;
		$this->pso_fh = $pso_fh;
	}
	function process_start() {
	}
	
	function process_end() {
	}
	
	function process_triple($triple,$triple_nos) {
		$s_id = $this->uri_to_id->lookup($triple['s']);
		if ( !$s_id ) {
			echo "subject uri " . $triple['s'] . " not found \n";
			return;
		}
		$p_id = $this->uri_to_id->lookup($triple['p']);
		if ( !$p_id ) {
			echo "predicate uri " . $triple['p'] . " not found \n";
			return;
		}
		if ( $triple['o_type']=='uri' ) {
			$o_id = $this->uri_to_id->lookup($triple['o']);
			if ( !$o_id ) {
				echo "predicate uri " . $triple['o'] . " not found \n";
				return;
			}
		} else {
			$literal = format_literal($triple['o'],$triple['o_datatype'],$triple['o_lang']);
			$o_id = $this->literal_to_id->lookup($literal);
			if ( !$o_id ) {
				echo "predicate literal " . $literal . " not found \n";
				return;
			}
		}
		if ( $this->spo_fh ) {
			fwrite($this->spo_fh, $s_id . "\t" . $p_id . "\t" . $o_id . "\n");
		}
		if ( $this->sop_fh ) {
			fwrite($this->sop_fh, $s_id . "\t" . $o_id . "\t" . $p_id . "\n");
		}
		if ( $this->pso_fh ) {
			fwrite($this->pso_fh, $p_id . "\t" . $s_id . "\t" . $o_id . "\n");
		}
		if ( $this->ops_fh ) {
			fwrite($this->ops_fh, $o_id . "\t" . $p_id . "\t" . $s_id . "\n");
		}
	}
}



$start = time();
$uri_to_id = new EntryToId();
$uri_to_id->read_id_file($uri_id_file);
echo "reading uri ids took ".(time()-$start)."\n";

$start = time();
$literal_to_id = new EntryToId();
$literal_to_id->read_id_file($literal_id_file);
echo "reading literal ids took ".(time()-$start)."\n";

$meta_fh = fopen( $meta_file, 'r' );
$spo_fh = fopen( $spo_file, 'w' );
$sop_fh = fopen( $sop_file, 'w' );
$pso_fh = fopen( $pso_file, 'w' );
$ops_fh = fopen( $ops_file, 'w' );

$tp = new TripleToIdProcessor($uri_to_id,$literal_to_id, $spo_fh, $sop_fh, $pso_fh, $ops_fh );
$pn = new ParseNtriples($meta_fh,$tp);
//$pn->start_triple = 1;
//$pn->max_triples = 10;
//$pn->sample_delta = 1;

$pn->parse_ntriples();

?>
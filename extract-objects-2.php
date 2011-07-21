<?php

require 'include/parse-ntriples.class.php';

define( 'ASPIRE_DIR', '/Users/alandix/Documents/Talis/data/aspire/' );
define( 'META_DIR', ASPIRE_DIR . '20110401040135/metabox/' );
define( 'P_DIR', ASPIRE_DIR . 'processed/' );

define( 'BLOCK_SIZE', 8192 );

define( 'URI_PAT', '/<()[^> ])>/');
define( 'PLAIN_LITERAL_PAT', '/"(([^\\"]*\\.)*[^\\"]*)"/');

$meta_file = META_DIR . 'meta';

$uri_subjects_file = P_DIR . 'uri-subjects.txt';
$uri_predicates_file = P_DIR . 'uri-predicates.txt';
$uri_objects_file = P_DIR . 'uri-objects.txt';
$literal_objects_file = P_DIR . 'literal-objects.txt';

class SplitProcessor {
	var $s_fh, $p_fh, $o_uri_fh, $o_lit_fh;
	function __construct( $s_fh, $p_fh, $o_uri_fh, $o_lit_fh ) {
		$this->s_fh = $s_fh;
		$this->p_fh = $p_fh;
		$this->o_uri_fh = $o_uri_fh;
		$this->o_lit_fh = $o_lit_fh;
	}
	function process_start() {
	}
	
	function process_end() {
	}
	
	function process_triple($triple,$triple_nos) {
		if ( $this->s_fh ) {
			fwrite( $this->s_fh, $triple['s'] ."\n");
		}
		if ( $this->p_fh ) {
			fwrite( $this->p_fh, $triple['p'] ."\n");
		}
		if ( $this->o_uri_fh && ($triple['o_type']=='uri') ) {
			fwrite( $this->o_uri_fh, $triple['o'] ."\n");
		}
		if ( $this->o_lit_fh && ($triple['o_type']=='literal') ) {
			$literal = $triple['o'];
			strtr( $literal, array("\n"=>"\\n","\r"=>"\\r"));
			$literal_full = '"' .$literal. '"';
			if ( $triple['o_lang'] ) {
				$literal_full .= "@" . $triple['o_lang'];
			} else if ( $triple['o_datatype'] ) {
				$literal_full .= "^^" . $triple['o_datatype'];
			}
			fwrite( $this->o_lit_fh, $literal_full ."\n");
		}
	}
}

$meta_fh = fopen( $meta_file, 'r' );

$uri_sub_fh = fopen( $uri_subjects_file, 'w' );
$uri_pred_fh = fopen( $uri_predicates_file, 'w' );

$uri_fh = fopen( $uri_objects_file, 'w' );
$literal_fh = fopen( $literal_objects_file, 'w' );

$sp = new SplitProcessor($uri_sub_fh,$uri_pred_fh,$uri_fh,$literal_fh);
$pn = new ParseNtriples($meta_fh,$sp);
$pn->parse_ntriples();

fclose($uri_sub_fh);
fclose($uri_pred_fh);
fclose($uri_fh);
fclose($literal_fh);

?>
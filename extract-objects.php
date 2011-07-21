<?php

define( 'ASPIRE_DIR', '/Users/alandix/Documents/Talis/data/aspire/' );
define( 'META_DIR', ASPIRE_DIR . '20110401040135/metabox/' );
define( 'P_DIR', ASPIRE_DIR . 'processed/' );

define( 'BLOCK_SIZE', 8192 );

$meta_file = META_DIR . 'meta';

$uri_objects_file = P_DIR . 'uri_objects.txt';
$literal_objects_file = P_DIR . 'literal_objects.txt';

$meta_fh = fopen( $meta_file, 'r' );
//$uri_fh = fopen( $uri_objects_file, 'w' );
//$literal_fh = fopen( $literal_objects_file, 'w' );

class ParseNtriples {
	
	var $meta_fh = false;
	
	//var $start_triple = 1240;
	//var $max_triples = 10;

	var $start_triple = 1;
	var $max_triples = 0;

	function __construct($meta_fh) {
		$this->meta_fh = $meta_fh;
	}
	
	var $buff = false;
	var $blen = 0;
	var $bp = 0;
	var $peeked=false;
	var $EOF = false;
	var $skip_linefeed = false;
	var $convert_linefeed = true;
	
	var $current = false;
	
	var $at_linebreak = true;  // line_nos starts at 1 on first line
	var $line_nos = 0;
	var $triple_nos = 0;
	
	function getc_0() {
		if ( $this->buff && ( $this->blen > $this->bp ) ) {
			$res = $this->buff[$this->bp];
			$this->bp++;
		} elseif ( $this->meta_fh && ( $this->buff = fread( $this->meta_fh, BLOCK_SIZE ) ) ) {
			$this->blen = strlen($this->buff);
			$res = $this->buff{0};
			$this->bp=1;
		} else {
			$this->EOF = true;
			$res = false;
		}
		//echo "getc_0 returns " . $res ."\n";
		return $res;
	}
	
	function getc_1() {
		$res = $this->getc_0();
		if ( $this->convert_linefeed ) {
			if ( $res == "\r" ) {
				$res = "\n";
				$this->skip_linefeed = true;
			} elseif ( $res == "\n" && $this->skip_linefeed ) {
				$this->skip_linefeed = false;
				$res = $this->getc_0();
			} else {
				$this->skip_linefeed = false;
			}
		}
		//echo "getc_1 returns " . $res ."\n";
		return $res;
	}
	
	function peek() {
		if ( $this->peeked === false) {
			$this->peeked = $this->getc_1();
			//echo "peek fetched '" . $this->peeked ."'\n";
		}
		return $this->peeked;
	}
	
	function peek_spaces() {
		while ( ($c=$this->peek())!==false && ctype_space( $c ) && $c != "\n" ) {
			$this->getc();
		}
		return $c;
	}
	
	function getc_2() {
		$res = $this->peek();
		$this->peeked = false;
		return $res;
	}
	
	function getc() {
		if ( $this->at_linebreak ) {
			$this->line_nos++;
		}
		$res = $this->getc_2();
		if ( $res == "\r" || $res == "\n" ) {
			$this->at_linebreak = true;
		} else {
			$this->at_linebreak = false;
		}
		$this->current = $res;
		//echo "getc returns " . $res ."\n";
		return $res;
	}
	
	function parse_ntriples() {
		
		if ( $this->max_triples ) {
			$last_triple = $this->start_triple + $this->max_triples -1;
		} else {
			$last_triple = -1;
		}
		
		$this->process_start();
		while ( ( $last_triple<0 || $last_triple>$this->triple_nos ) && $triple = $this->parse_triple() ) {
			$this->triple_nos++;
			if ( $this->triple_nos < $this->start_triple ) {
				// skip
			} else {
				$this->process_triple($triple);
			}
		}
		$this->process_end();
	}
	
	function parse_triple() {
		$c = $this->peek_spaces();
		while ( $c == "\n" ) { // skip blank lines
			$c = $this->peek_spaces();
		}
		if ( $c === false ) {
			return false;
		}
			
		//echo "parse_triple \n";
		$subject = $this->parse_subject();
		if ( ! $subject ) return false;
		
		$predicate = $this->parse_predicate();
		if ( ! $predicate ) return false;
		
		$object = $this->parse_object();
		if ( ! $object ) return false;
		
		$c = $this->peek_spaces();
		if ( $c != '.' ) {
			$this->parse_error("missing '.' at end of triple ");
			return false;
		}
		$this->getc();
		
		$c = $this->peek_spaces();
		if ( $c != "\n" ) {
			$this->parse_error("extra stuff on line after the triple");
			return false;
		}
		$this->getc();
		
		return array( 's_type' => $subject[0], 's' => $subject[1],
                      'p_type' => $predicate[0], 'p' => $predicate[1],
                      'o_type' => $object[0], 'o' => $object[1],
					  'o_datatype' => $object[2], 'o_lang' => $object[3]
		 			);
	}
	
	function parse_error($mess) {
		echo "Parse error in triple ".($this->triple_nos+1)." on line ".$this->line_nos.": ".$mess."\n";
	}
	
	function parse_subject() {
		//echo "parse_subject \n";
		return array( 'uri', $this->parse_uri("subject") );
	}
	
	function parse_predicate() {
		return array( 'uri', $this->parse_uri("predicate") );
	}
	
	function parse_object() {
		$this->getc();
		$c = $this->peek_spaces();
		switch ( $c ) {
			case "<":
					$uri = $this->parse_uri();
					if ( $uri ) {
						return array( 'uri', $uri, false, false );
					} else {
						return false;
					}
			case "\"":
			 	 	$literal = $this->parse_literal();
					if ( $literal ) {
						return array( 'literal', $literal['0'], $literal['1'], $literal['2'] );
					} else {
						return false;
					}
			default:
					$this->parse_error("can't parse object" );
					return false;
		}
	}
	
	function parse_uri($kind="") {  // kind subject, predicate, object
		//echo "parse_uri($kind) \n";
		$c = $this->peek_spaces();
		//echo "peeked spaces to '".$c."' \n";
		if ( $c != '<' ) {
			$this->parse_error("missing < (got '".$c."') at start of URI " . $kind );
			return false;
		}
		$this->getc();
		$uri = $this->parse_uri_after_start();
		//echo "parse_uri($kind) returns ".$uri."\n";
		return $uri;
	}
	
	function parse_uri_after_start() {
		//echo "parse_uri_after_start() \n";
		$uri = "";
		while ( ( $c=$this->getc() )!==false && ! ctype_space( $c ) ) {
			if ( $c=='>' ) {
				//echo "got > ($c) at end of uri \n";
				return $uri;
			} else {
				//echo "add '".$c."' uri \n";
				$uri .= $c;
			}
		}
		$this->parse_error("got space instead of end of uri");
		return false;
	}
	
	function parse_literal() {
		$c = $this->getc();
		if ( $c != '"' ) {
			$this->parse_error("missing quote at start of literal");
			return false;
		}
		$literal = "";
		$lang = "";
		$datatype = "";
		while ( ($c=$this->getc())!==false && $c != '"' ) {
			if ( $c == '\\' ) {
				$c=$this->getc();
				if ( $c ) $literal .= '\\' . $c;
			} else {
				$literal .= $c;
			}
		}
		$c = $this->peek();
		if ( $c == '@' ) {
			$this->getc();
			$lang = "";
			for ( $c=$this->peek(); ctype_alnum($c); $c=$this->peek() ) {
				$lang .= $c;
				$this->getc();
			}
		} elseif ( $c == '^' ) {
			$this->getc();
			$c = $this->peek();
			if ( $c != '^' ) {
				$this->parse_error("only one '^' before literal datatype");
				return false;
			}
			$this->parse_error("sorry literal datatype parsing not yet implemented");
			return false;
		}
		return array( $literal, $datatype, $lang );
	}
	
	function skip_spaces() {
		$this->peek_spaces();
		$this->getc();
	}
	
	var $sample_delta = 10000;
	var $last_sample = 0;
	
	var $start_time = false;
	
	function process_start() {
		$this->start_time = time();
	}
	
	function process_end() {
		$this->start_time = time();
	}
	
	function process_triple($triple) {
		if ( $this->triple_nos >= $this->last_sample+$this->sample_delta ) {
			echo "@".(time()-$this->start_time)." triple " . $this->triple_nos . ": " .print_r($triple,1). "\n";
			$this->last_sample = $this->triple_nos;
		}
	}

} 

$pn = new ParseNtriples($meta_fh);
$pn->parse_ntriples();


?>
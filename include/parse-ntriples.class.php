<?php

class ParseNtriples {
	
	var $meta_fh = false;
	var $processor;
	
	var $start_triple = 1;
	var $max_triples = 0;

	//var $start_triple = 1;
	//var $max_triples = 10;

	function __construct($meta_fh, $processor=false) {
		$this->meta_fh = $meta_fh;
		$this->processor = $processor;
	}
	
	function set_processor($processor) {
		$this->processor = $processor;
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
	
	function getline() {
		$line = fgets( $this->meta_fh );
		if ( $line !== false ) {
			$this->line_nos++;
		}
		return $line;
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
	
	var $triple_start_line;
	
	function parse_triple() {
		$line = $this->getline();
		$this->triple_start_line = $this->line_nos;
		
		if ( ! $line  ) return false;
		
		$line = ltrim($line);
		
		$triple_parts = preg_split('/\\s+/',$line,3);
		
		//echo "triple_parts: ".print_r($triple_parts,1)."\n";
		
		$subject_str = trim($triple_parts[0]);
		$pred_str = trim($triple_parts[1]);
		$object_str = trim($triple_parts[2]);
		
		$subject = $this->parse_subject($subject_str);
		if ( ! $subject ) return false;
		
		$predicate = $this->parse_predicate($pred_str);
		if ( ! $predicate ) return false;
		
		$object = $this->parse_object($object_str);
		if ( ! $object ) return false;
		
		return array( 's_type' => $subject[0], 's' => $subject[1],
                      'p_type' => $predicate[0], 'p' => $predicate[1],
                      'o_type' => $object[0], 'o' => $object[1],
					  'o_datatype' => $object[2], 'o_lang' => $object[3]
		 			);
	}
	
	function parse_error($mess) {
		$prefix_mess = "Parse error in triple ".($this->triple_nos+1)." on line ".$this->line_nos;
		if  ( $this->triple_start_line != $this->line_nos ) {
			$prefix_mess .= " (started line ".$this->triple_start_line.")";
		}
		echo $prefix_mess .": ".$mess."\n";
	}
	
	function parse_subject($subject_str) {
		//echo "parse_subject \n";
		return array( 'uri', $this->parse_uri($subject_str,"subject") );
	}
	
	function parse_predicate($pred_str) {
		return array( 'uri', $this->parse_uri($pred_str,"predicate") );
	}
	
	function parse_object($object_str) {
		$o_len = strlen($object_str);
		$last = $object_str{$o_len-1};
		$last_is_dot = ( $last == '.' );
		$o_without_last = trim(substr($object_str,0,$o_len-1));
		
		switch ( $object_str{0} ) {
			case "<":
					if ( ! $last_is_dot ) {
						$this->parse_error("missing '.' at end of triple ");
						return false;
					}
					$uri = $this->parse_uri($o_without_last,"object");
					if ( $uri ) {
						return array( 'uri', $uri, false, false );
					} else {
						return false;
					}
			case "\"":
					if ( substr($object_str,0,3)=='"""' ) { // lomg literal
						$literal = $this->parse_long_literal($object_str);
					} else {
						$literal = $this->parse_literal($object_str);
					}
					if ( $literal ) {
						return array( 'literal', $literal['0'], $literal['1'], $literal['2'] );
					} else {
						return false;
					}
			default:
					$this->parse_error("can't parse object: " . $object_str );
					return false;
		}
	}
	
	function parse_uri($uri_str,$kind="") {  // kind subject, predicate, object
		//echo "parse_uri($kind) \n";
		$len = strlen($uri_str);
		if ( $uri_str{0} != '<' ) {
			$this->parse_error("missing < at start of URI " . $kind . ": " . $uri_str );
			return false;
		}
		if ( $uri_str{$len-1} != '>' ) {
			$this->parse_error("missing > (got ".$uri_str{$len-1}.") at end of URI " . $kind . ": " . $uri_str  );
			return false;
		}
		return substr($uri_str,1,$len-2);
	}
	
	function parse_long_literal($literal_str) {
		$literal =  "";
		for( $end_str = substr($literal_str,3); $end_str; $end_str = $this->getline() ) {
			if ( preg_match( '/^(.*[^\\\\])?"""(.*)$/', $end_str, $match ) ) {
				$lit_end = $match[1];
				$rest = $match[2];
				$literal .= $lit_end;
				$end = $this->parse_literal_end($rest);
				if ( ! $end ) return false;
				list($datatype,$lang) = $end;
				return array( $literal, $datatype, $lang );
			} else {
				$literal .= $end_str;
			}
		}
		$this->parse_error("unterminated long literal" );
		return false;
	}
	
	function parse_literal($literal_str) {
		if ( preg_match( '/^"(.*[^\\\\])?"(.*)$/', $literal_str, $match ) ) {
			$literal = $match[1];
			$rest = $match[2];
			$end = $this->parse_literal_end($rest);
			if ( ! $end ) return false;
			list($datatype,$lang) = $end;
			return array( $literal, $datatype, $lang );
		} else {
			$this->parse_error("can't parse object literal: '" . $literal_str ."'" );
			return false;
		}
	}

	function parse_literal_end($end_str) {
		$datatype = "";
		$lang = "";
		$c = $end_str{0};
		if ( preg_match('/^@(\w+)\s*(\.)?/', $end_str, $match ) ) {
			$lang = $match[1];
			$dot = $match[2];
		} else	if ( preg_match('/^\^\^(\S+)\s*(\.)?/', $end_str, $match ) ) {
			//$this->parse_error("sorry literal datatype parsing not yet implemented");
			$datatype = $match[1];
			$dot = $match[2];
		} else	if ( preg_match('/^\s*(\.)?/', $end_str, $match ) ) {
			$dot = $match[1];
		} else {
			$this->parse_error("can't parse literal: '" . $literal_str ."'");
			return false;
		}
		
		if ( $dot != '.' ) {
			$this->parse_error("missing '.' at end of literal triple ");
			return false;
		}
		return array( $literal, $datatype, $lang );
	}
	
	
	var $sample_delta = 20000;
	//var $sample_delta = 1;
	var $last_sample = 0;
	
	var $start_time = false;
	
	function process_start() {
		$this->start_time = time();
		if ( $this->processor ) {
			$this->processor->process_start();
		}
	}
	
	function process_end() {
		$this->start_time = time();
		if ( $this->processor ) {
			$this->processor->process_end();
		}
	}
	
	function process_triple($triple) {
		if ( $this->triple_nos >= $this->last_sample+$this->sample_delta ) {
			//echo "@".(time()-$this->start_time)." triple " . $this->triple_nos . ": " .print_r($triple,1). "\n";
			echo "@".(time()-$this->start_time)." triple " . $this->triple_nos . "\n";
			$this->last_sample = $this->triple_nos;
		}
		if ( $this->processor ) {
			$this->processor->process_triple($triple,$this->triple_nos);
		}
	}

}

?>
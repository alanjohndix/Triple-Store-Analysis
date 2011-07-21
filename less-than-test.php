<?php

define( 'ASPIRE_DIR', '/Users/alandix/Documents/Talis/data/aspire/' );
define( 'META_DIR', ASPIRE_DIR . '20110401040135/metabox/' );
define( 'P_DIR', ASPIRE_DIR . 'processed/' );

$literal_id_file = P_DIR . 'literal-ids.txt';

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



$start = time();
$literal_to_id = new EntryToId();
$literal_to_id->read_id_file($literal_id_file);
echo "reading literal ids took ".(time()-$start)."\n";

$literal = format_literal("1","","");
$id = $literal_to_id->lookup($literal);

echo "$id \t $literal \n";

?>
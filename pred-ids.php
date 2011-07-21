<?php

require "config.inc.php";

//define( 'ASPIRE_DIR', '/Users/alandix/Documents/Talis/data/aspire/' );
//define( 'META_DIR', ASPIRE_DIR . '20110401040135/metabox/' );
//define( 'P_DIR', ASPIRE_DIR . 'processed/' );

$p_file = P_DIR . 'uri-predicates-unique.txt';
$uri_id_file = P_DIR . 'uri-ids.txt';
$p_id_file = P_DIR . 'p-ids.txt';
//$ep_file = P_DIR . 'ep-dir.txt';

$uri_id_fh = fopen($uri_id_file,"r");
$p_id_fh = fopen($p_id_file,"w");

$predicates = file($p_file);

$predicates = array_map('trim',$predicates);

$start = time();

while ( $line = fgets( $uri_id_fh ) ) {
	$line = trim($line);
	list( $id, $uri ) = explode("\t",$line);
	if ( in_array($uri,$predicates) ) {
		fwrite($p_id_fh,$id."\t".$uri."\n");
	}
}

echo "predicate ids took ".(time()-$start)."\n";

?>
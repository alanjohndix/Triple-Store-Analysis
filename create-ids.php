<?php

//define( 'ASPIRE_DIR', '/Users/alandix/Documents/Talis/data/aspire/' );
//define( 'META_DIR', ASPIRE_DIR . '20110401040135/metabox/' );
//define( 'P_DIR', ASPIRE_DIR . 'processed/' );

require "config.inc.php";  // uses  URI_BASE_ID LITERAL_BASE_ID

// $meta_file = META_DIR . 'meta';

$uri_file = P_DIR . 'uri-all-unique.txt';
$literal_file = P_DIR . 'literal-all-unique.txt';

$uri_id_file = P_DIR . 'uri-ids.txt';
$literal_id_file = P_DIR . 'literal-ids.txt';

$uri_ids = array();
$literal_ids = array();

$start = time();
$all_uris = file($uri_file);

echo "reading uris took ".(time()-$start)."\n";

$start = time();

$uri_id_fh = fopen($uri_id_file,"w");

$id = URI_BASE_ID;
foreach( $all_uris as $uri ) {
	$uri = trim($uri);
	$id++;
	$uri_ids[$uri] = $id;
	fwrite($uri_id_fh, $id . "\t" . $uri . "\n");
}

fclose($uri_id_fh);

echo "hashing uris took ".(time()-$start)."\n";
echo "last id was $id \n";

unset($all_uris);

$start = time();
$all_literals = file($literal_file);

echo "reading literals took ".(time()-$start)."\n";

$start = time();

$literal_id_fh = fopen($literal_id_file,"w");

$id = LITERAL_BASE_ID;
foreach( $all_literals as $literal ) {
	$literal = trim($literal);
	$id++;
	$literal_ids[$literal] = $id;
		fwrite($literal_id_fh, $id . "\t" . $literal . "\n");
	}

fclose($literal_id_fh);
echo "hashing literals took ".(time()-$start)."\n";
echo "last id was $id \n";

unset($all_literals);


?>
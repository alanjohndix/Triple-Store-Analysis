<?php

require "config.inc.php";
require_once 'include/triple-func.inc.php';
require "include/entity-processor.class.php";
require "include/classify_on_pred_threshold.class.php";

//define( 'ASPIRE_DIR', '/Users/alandix/Documents/Talis/data/aspire/' );
//define( 'META_DIR', ASPIRE_DIR . '20110401040135/metabox/' );
//define( 'P_DIR', ASPIRE_DIR . 'processed/' );

//$pso_file = P_DIR . 'pso-sorted.txt';
//$ep_file = P_DIR . 'ep-dir.txt';

if ( $argc > 1 ) {
	$threshold = $argv[1];
} else {
	$threshold = 2;
}
if ( $argc > 2 ) {
	$code = $argv[2];
} else {
	$code = 'c';
}
if ( $argc > 3 ) {
	$classified_file = $argv[3];
} else {
	$classified_file = P_DIR . 'ec-code.txt';
}

echo "args: threshold=$threshold code=$code classified_file=$classified_file \n";
//exit(1);

$p_id_file = P_DIR . 'p-ids.txt';
$ep_file = P_DIR . 'ep-dir.txt';
$cluster_file = P_DIR . 'clusters.txt';



$ep_fh = fopen($ep_file,"r");

$classifed_fh = fopen($classified_file,"w");

$clusters = read_clusters($cluster_file);

//print_r($clusters);
//echo "\n\n\n";

$start = time();

$cpp = new ClassifyOnPredThreshold( $classifed_fh, $clusters, $threshold, $code );
$ep = new EntityProcessor( $ep_fh, $cpp );
$ep->sample_delta = 100000;
$ep->process();
fclose($classifed_fh);

echo "classification took ".(time()-$start)."\n";

?>
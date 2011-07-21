<?php

require "config.inc.php";  // uses CLUSTER_BASE_ID
require_once 'include/triple-func.inc.php';
require_once "include/pred-counts.class.php";

//define( 'ASPIRE_DIR', '/Users/alandix/Documents/Talis/data/aspire/' );
//define( 'META_DIR', ASPIRE_DIR . '20110401040135/metabox/' );
//define( 'P_DIR', ASPIRE_DIR . 'processed/' );

//$pso_file = P_DIR . 'pso-sorted.txt';
//$ep_file = P_DIR . 'ep-dir.txt';

$s_ct_file = P_DIR . 'simple-counts.txt';
$p_id_file = P_DIR . 'p-ids.txt';
$p_ct_file = P_DIR . 'p-ct.txt';
$pp_ct_file = P_DIR . 'pp-ct.txt';

$cluster_file = P_DIR . 'clusters.txt';

$simple_counts = read_prop_file($s_ct_file);

print_r($simple_counts);
echo "\n\n\n";
//exit;

$triple_ct = $simple_counts['triples'];
$entity_ct = $simple_counts['entities'];
$subject_ct = $simple_counts['subjects'];
$object_ct = $simple_counts['objects'];

//$triple_ct = 8756763;
//$entity_ct = 1764334;
//$subject_ct = 913392;
//$object_ct = 1749964;


$pc = new PredCounts($triple_ct,$entity_ct,$subject_ct,$object_ct,$p_id_file,$p_ct_file,$pp_ct_file);
$pc->calculate_stats();
$pc->calculate_scores();

//$ordered = $pc->flatten_and_sort();
//print_r($ordered);

//$p_clusters = $pc->make_p_clusters();
//print_r($p_clusters);



///$dendogram = $pc->dendogram(0.8);
//print_r($dendogram);

//echo "\n\n\n=================================\n\n\n";

$pc->set_cluster_base( CLUSTER_BASE_ID );
$clusters = $pc->cluster(0.8);
output_clusters($cluster_file,$clusters)
//print_r($clusters);

/*
foreach( $clusters as $cid => $cluster ) {
	echo "cluster $cid : ct:".$cluster['ct']." \n";
	foreach( $cluster['items'] as $item ) {
		echo "    " . $item['p'] . "\t" . $item['dir'] . "\t" . $item['name'] . "\n";
	}
}
*/


?>
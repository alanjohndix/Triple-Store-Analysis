<?php

require "config.inc.php";
require_once 'include/triple-func.inc.php';
require_once "include/class-type-stats.class.php";

//define( 'ASPIRE_DIR', '/Users/alandix/Documents/Talis/data/aspire/' );
//define( 'META_DIR', ASPIRE_DIR . '20110401040135/metabox/' );
//define( 'P_DIR', ASPIRE_DIR . 'processed/' );

$type_id_file = P_DIR . 'type-ids.txt';
$pred_id_file = P_DIR . 'p-ids.txt';
$count_file = P_DIR . 'class-type-ct.txt';

$cts = new ClassTypeStats($count_file,$type_id_file,$pred_id_file);
$cts->calculate_stats();
$cts->calc_relations();

//print_r($cts->relations);

//echo "\n\n\n====================\n\n";

function display_relations($relations) {
	foreach( $relations as $c1_key => $c1_relns ) {
		echo "{$c1_key} {$c1_relns['ct']} {$c1_relns['name']} \n";
		$names = array( 'sameAs', 'superType', 'subType', 'overlap' );
		foreach ( $names as $setname )  {
			format_relation_set($setname,$c1_relns[$setname],$c1_relns['ct']);
		}
	}
}
function display_sameas($relations) {
	foreach( $relations as $c1_key => $c1_relns ) {
		echo "{$c1_key} {$c1_relns['ct']} {$c1_relns['name']} - ";
		$sameAs = @$c1_relns['sameAs'];
		if ( $sameAs ) echo format_entry($sameAs[0],$c1_relns['ct']);
		else echo "none";
		echo "\n";
	}
}
function format_entry($entry,$ct=0) {
	$formatted = "{$entry['id']} {$entry['ct']}";
	if ( $ct ) {
		$percentage = floor ( 10000 * $entry['ct'] / $ct ) / 100.0;  // 2 digits
		$formatted .= ' ' . $percentage;
	}
	$formatted .= ' ' . $entry['name'];
	return $formatted;
}
function format_relation_set($setname,$set,$ct=0) {
	if ( ! $set ) return;
	echo "    $setname \n";
	$percentage = "";
	foreach( $set as $entry ) {
		echo "        ". format_entry($entry,$ct) ."\n";
		/*
		if ( $ct ) {
			$percentage = floor ( 10000 * $entry['ct'] / $ct ) / 100.0;  // 2 digits
		}
		echo "        {$entry['id']} {$entry['ct']} {$percentage} {$entry['name']} \n";
		*/
	}
}

display_relations($cts->relations);

echo "\n\n\n===================\n\n\n";

display_sameas($cts->relations) ;

echo "\n\n";

?>
<?php

function output_clusters($cluster_file,$clusters) {
	$fh = fopen($cluster_file,"w");
	foreach ( $clusters as $cid => $cluster ) {
		foreach( $cluster['items'] as $item ) {
			fwrite( $fh, $cid . "\t" . $cluster['ct'] . "\t" . $item['p'] . "\t" . $item['dir'] . "\t" . $item['name'] . "\n" );
		}
		fwrite( $fh,  "\n" ); // blank line to separate clusters
	}
	fclose($fh);
}

function read_clusters($cluster_file) {
	$lines = file($cluster_file);
	$clusters = array();
	foreach ( $lines as $line ) {
		$line = trim($line);
		if ( $line == "" ) continue;  // skip blank lines
		list( $cid, $ct, $p, $dir, $name ) = explode("\t",$line);
		$clusters[$cid]['ct'] = $ct;
		$clusters[$cid]['items'][] = array('p'=>$p,'dir'=>$dir,'name'=>$name);
	}
	return $clusters;
}

function output_class_type_counts($ct_file,$counts) {
	//echo "output_class_type_counts($ct_file)\n";
	$ct_ct_fh = fopen($ct_file,"w");
	if ( !$ct_ct_fh ) {
		echo "failed to open $ct_file \n";
		return;
	}
	foreach ( $counts as $c1_key => $c1_cts ) {
		foreach ( $c1_cts as $c2_key => $ct ) {
			list($c1,$c1_dir) = explode('-',$c1_key,2);
			list($c2,$c2_dir) = explode('-',$c2_key,2);
			fwrite( $ct_ct_fh, $c1 . "\t" . $c1_dir . "\t" . $c2 . "\t" . $c2_dir . "\t" . $ct . "\n" );
		}
	}
	fclose($ct_ct_fh);
}

function read_class_type_counts_as_stats($ct_file) {
	$lines = file($ct_file);
	$stats = array();
	foreach ( $lines as $line ) {
		$line = trim($line);
		if ( $line == "" ) continue;  // skip blank lines
		list( $c1, $c1_dir, $c2, $c2_dir, $ct ) = explode("\t",$line);
		$c1_key = $c1.'-'.$c1_dir;
		$c2_key = $c2.'-'.$c2_dir;
		$stats[$c1_key][$c2_key] = array( 'ct'=>$ct, 'c1'=>$c1, 'c1_dir'=>$c1_dir, 'c2'=>$c2, 'c2_dir'=>$c2_dir );
	}
	return $stats;
}

function read_id_map($id_file) {
	$lines = file($id_file);
	$map = array();
	foreach ( $lines as $line ) {
		$line = trim($line);
		list( $id, $value ) = explode("\t",$line);
		$map[$id] = $value;
		//echo "map[$id] = $value \n";
	}
	return $map;
}

function is_key_kind($key,$kind) {
	list($id,$key_kind) = explode('-',$key);
	return $key_kind==$kind;
}

function is_key_cluster($key) {
	return is_key_kind($key,'c');
}

function is_key_type($key) {
	return is_key_kind($key,'t');
}

function is_key_object($key) {
	return is_key_kind($key,'-') ||  is_key_kind($key,'o');
}

function is_key_subject($key) {
	return is_key_kind($key,'+') ||  is_key_kind($key,'s');
}

function read_prop_file($p_file) {
	$lines = file($p_file);
	$props = array();
	foreach ( $lines as $line ) {
		$line = trim($line);
		if ( preg_match( '/^(\\w+)[:\\s](.*)$/', $line, $match ) ) {
			$props[$match[1]] = trim($match[2]);
		}
	}
	return $props;
}

?>
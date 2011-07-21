<?php

class ClassifyOnPredThreshold {
	var $classifed_fh;
	var $clusters;
	var $threshold;
	var $class_code;
	var $p_to_cluster;
	function __construct( $classifed_fh, $clusters, $threshold=2, $class_code='c' ) {
		$this->classifed_fh = $classifed_fh;
		$this->threshold = $threshold;
		$this->class_code = $class_code;
		$this->init_clusters($clusters);
	}
	function init_clusters($clusters) {
		$this->clusters = $clusters;
		$this->p_to_cluster = array();
		foreach ( $clusters as $cid => $cluster ) {
			foreach( $cluster['items'] as $item ) {
				if ( $item['dir'] == 's' ) $dir = '+';
				else $dir = '-';
				$this->p_to_cluster[$item['p'].'-'.$dir][] = $cid;
			}
		}
		//print_r($this->p_to_cluster); echo "\n\n";
	}
	function process_start() {
	}
	function process_end() {
	}
	function process_entity($e,$predicates) {
		//if ( $e == 10164053 ) {
		//	$debug = true;
		//} else {
			$debug = false;
		//}
		//if ( $debug ) {
	//		echo "checking in ...\n";
		//	print_r($this->p_to_cluster);
		//	echo "\n";
		//}
		$clusters_for_e = array();
		foreach ( $predicates as $p_info ) {
			list($p,$p_dir) = $p_info;
			$key = $p.'-'.$p_dir;
			if ( $debug ) echo "check $key ...\n";
			if ( array_key_exists( $key, $this->p_to_cluster ) ) {
				$clusters_for_p = $this->p_to_cluster[$key];
				if ( $debug ) echo "p_to_cluster[{$key}] = ".print_r($clusters_for_p,1)."\n";
				foreach ( $clusters_for_p as $cid ) {
					$clusters_for_e[$cid]++;
				}
			} else {
				if ( $debug ) echo "   ... not in cluster\n";
			}
		}
		foreach ( $clusters_for_e as $cid => $ct ) {
			if ( $ct >= $this->threshold ) {
				if ( $debug ) echo  $e . "\t" . $cid . "\t". $ct . "\t". $this->class_code ."\n";
				fwrite($this->classifed_fh, $e . "\t" . $cid . "\t". $this->class_code ."\n");
			}
		}
	}
	
}

?>
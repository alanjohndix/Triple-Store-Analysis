<?php

require_once 'triple-func.inc.php';

class PredCounts {
	var $triple_ct,$entity_ct,$subject_ct,$object_ct;
	var $entity_cts;
	var $p_names;
	var $p_ct;
	var $pp_ct;
	var $cluster_base = 30000000;
	function __construct($triple_ct,$entity_ct,$subject_ct,$object_ct,$p_id_file=false,$p_ct_file=false,$pp_ct_file=false) {
		$this->triple_ct = $triple_ct;
		$this->entity_ct = $entity_ct;
		$this->triple_ct = $subject_ct;
		$this->triple_ct = $object_ct;
		$this->entity_cts = array('subjects'=>$subject_ct,'objects'=>$object_ct);
		if ( $p_id_file ) {
			$this->read_names($p_id_file);
		}
		if ( $p_ct_file ) {
			$this->read_p_counts($p_ct_file);
		}
		if ( $pp_ct_file ) {
			$this->read_pp_counts($pp_ct_file);
		}
	}
	function set_cluster_base($base) {
		$this->cluster_base = $base;
	}
	function read_names($p_id_file) {
		$this->p_names = read_id_map($p_id_file);
		/*
		$lines = file($p_id_file);
		foreach ( $lines as $line ) {
			$line = trim($line);
			list( $id, $uri ) = explode("\t",$line);
			$this->p_names[$id] = $uri;
		}
		*/
	}
	function read_p_counts($p_ct_file) {
		$lines = file($p_ct_file);
		foreach ( $lines as $line ) {
			$line = trim($line);
			list( $p, $total, $subjects, $objects ) = explode("\t",$line);
			$this->p_ct[$p] = array('total'=>$total, 'subjects'=> $subjects, 'objects'=> $objects);
		}
	}
	function read_pp_counts($pp_ct_file) {
		$lines = file($pp_ct_file);
		foreach ( $lines as $line ) {
			$line = trim($line);
			list( $p1, $p2, $ss, $so, $os, $oo ) = explode("\t",$line);
			$this->pp_ct[$p1][$p2] = array('ss'=>array('ct'=>$ss), 'so'=>array('ct'=>$so),
										   'os'=>array('ct'=>$os), 'oo'=>array('ct'=>$oo) );
		}
	}
	function get_name($id) {
		if ( array_key_exists($id,$this->p_names) ){
			return $this->p_names[$id];
		} else {
			return $id;
		}
	}
	var $count_fields = array ( 'ss'=>array('subjects','subjects'), 'so'=>array('subjects','objects'),
						        'os'=>array('objects','subjects'),  'oo'=>array('objects','objects') );
	function calculate_stats() {
		foreach( $this->pp_ct as $p1 => &$p1_ct ) {
			foreach( $p1_ct as $p2 => &$p1_p2_ct ) {
				//if ( $debug ) exit;
				//if ( $p1 == 1764429 && $p2 == 1522696 ) {
				//	$debug = true;
				//} else {
					$debug = false;
				//}
				$name1 = $this->get_name($p1);
				$name2 = $this->get_name($p2);
				//echo "\n [".$p1."]" . $name1 . "\t[".$p2."]" . $name2 ."\n";
				foreach ( $p1_p2_ct as $dir => &$stats ) {
					list($f1,$f2) = $this->count_fields[$dir];
					$dir1 = $dir{0};
					$dir2 = $dir{1};
					$tot1 = $this->p_ct[$p1]['total'];
					$ct1 = $this->p_ct[$p1][$f1];
					$all1 = $this->entity_cts[$f1];
					$tot2 = $this->p_ct[$p2]['total'];
					$ct2 = $this->p_ct[$p2][$f2];
					$all2 = $this->entity_cts[$f2];
					$ct = $stats['ct'];
					
					if ( $ct1 == 0 ) {
						echo "[".$p1."]" . $name1 . "\t[".$p2."]" . $name2 ."\n";
						echo "  count_fields[$dir] = ($f1,$f2)\n";
					}
					
					$cont['+']['+'] = $ct;
					$cont['+']['-'] = $ct2-$ct;
					$cont['-']['+'] = $ct1-$ct;
					$cont['-']['-'] = $this->entity_ct-$ct1-$ct2+$ct;
					$sum[1]['+'] = $ct1; $sum[1]['-'] = $this->entity_ct - $ct1;
					$sum[2]['+'] = $ct2; $sum[2]['-'] = $this->entity_ct - $ct2;
					
					if ( $debug ) echo "sum = " . print_r($sum,1) . "\n";
					if ( $debug ) echo "cont = " . print_r($cont,1) . "\n";
					
					$chi2 = 0.0;
					foreach( $cont as $c1 => $cont1 ) {
						foreach( $cont1 as $c2 => $observed ) {
							$expected = ( $sum[1][$c1] * $sum[2][$c2] ) / $this->entity_ct;
							$diff = $observed - $expected;
							if ( $expected == 0 ) {
								echo "    ".$dir." expected[$c1][$c2] == 0 :  \n";
								echo "          sum[1][$c1] = {$sum[1][$c1]}\n";
								echo "          sum[1][$c2] = {$sum[2][$c2]}\n";
								$chidelta = 0.0;
							} else {
								$chidelta = $diff*$diff / $expected;
							}
							$chi2 += $chidelta;
							if ( $debug ) echo "$c1 $c2 observed=".$observed." expected=".$expected." diff=".$diff.
							     				      " chidelta=".$chidelta." chi2=".$chi2." \n";
						}
					}
					$equality = $ct / ($ct1+$ct2-$ct);
					$overlap = $ct / $ct1;
					$size = sqrt( $ct / $this->entity_ct );
					// echo "    ".$dir."\t".$chi2."\t".$equality."\t".$overlap."\n";
					$stats['chi2'] = $chi2;
					$stats['equality'] = $equality;
					$stats['overlap'] = $overlap;
					$stats['size'] = $size;
				}
				//exit;
			}
		}
	}
	function calculate_scores($weights=false,$scorename='score') {
		if ( ! $weights ) $weights = array( 'chi2' => 0, 'equality' => 1, 'overlap' => 0, 'size' => 0.1);
		foreach( $this->pp_ct as $p1 => &$p1_ct ) {
			foreach( $p1_ct as $p2 => &$p1_p2_ct ) {
				foreach ( $p1_p2_ct as $dir => &$stats ) {
					//echo "@ $p1 $p2 $dir: ".print_r($stats,1)."\n";
					$score = 0.0;
					foreach ( $weights as $name=>$weight ) {
						$score += $weight * $stats[$name];
						//echo "  add $weight * ".$stats[$name]." = stats[$name] \n";
					}
					$stats[$scorename] = $score;
					//echo "  set $p1 $p2 $dir: [$scorename] = $score \n";
				}
			}
		}
		
	}
	function flatten() {
		$flatten = array();
		foreach( $this->pp_ct as $p1 => $p1_ct ) {
			foreach( $p1_ct as $p2 => $p1_p2_ct ) {
				foreach ( $p1_p2_ct as $dir => &$stats ) {
					$dir1 = $dir{0};
					$dir2 = $dir{1};
					$key = $p1 . '-' . $dir1 . '&' . $p2 . '-' . $dir2;
					$flatten[$key] = $stats;
				}
			}
		}
		return $flatten;
	}
	function flatten_and_sort($scorename='score') {
		$flatten = array();
		foreach( $this->pp_ct as $p1 => $p1_ct ) {
			foreach( $p1_ct as $p2 => $p1_p2_ct ) {
				foreach ( $p1_p2_ct as $dir => &$stats ) {
					$dir1 = $dir{0};
					$dir2 = $dir{1};
					$key = $p1 . '-' . $dir1 . '&' . $p2 . '-' . $dir2;
					$flatten[$key] = $stats[$scorename];
				}
			}
		}
		asort($flatten);
		return array_reverse($flatten);
	}
	function make_p_clusters($min_score=0,$scorename='score') {
		$flattened = $this->flatten_and_sort($scorename);
		$flat_stats = $this->flatten();
		$cluster_ct = $this->cluster_base;
		$p_to_cluster = array();
		$cluster_info = array();
		$pids = array_keys( $this->p_ct );
		// start with each predicate set in its own cluster
		foreach ( $pids as $p ) {
			$cluster_ct++;
			$p_to_cluster[$p.'-'.'s'] = array($cluster_ct);
			$cluster_info[$cluster_ct] = array( 'ct'=>0 );
			$cluster_ct++;
			$p_to_cluster[$p.'-'.'o'] = array($cluster_ct);
			$cluster_info[$cluster_ct] = array( 'ct'=>0 );
		}
		
		
		foreach ( $flattened as $key=>$score ) {
			if ( $score < $min_score) {
				break;
			}
			$stats = $flat_stats[$key];
			list($pd1,$pd2) = explode('&',$key);
			$c1 = $p_to_cluster[$pd1][0];
			$c2 = $p_to_cluster[$pd2][0];
			//echo "merge: $pd1 @ $c1 with $pd2 @ $c2 \n";
			if ( $c1 == $c2 ) {
				//echo "  in same cluster \n";
				continue;  // already in same cluster
			}
			// merge $c1 and $c2
			$cluster_ct++;
			$new_cluster = $cluster_ct;
			$cluster_info[$new_cluster] = array( 'ct'=>$stats['ct'] );
			//echo "  merge $c1 and $c2 into $new_cluster \n";
			$ct = 0;
			foreach( $p_to_cluster as $pd => &$clusters ) {
				if ( $clusters[0] == $c1 || $clusters[0] == $c2 ) {
					array_unshift($clusters,$new_cluster);
					$ct++;
				}
				if ( $ct >= count($p_to_cluster) ) {
					break; // one big cluster
				}
			}
		}
		return array($p_to_cluster,$cluster_info);
	}
	function dendogram($min_score=0,$scorename='score') {
		list($p_to_cluster,$cluster_info) = $this->make_p_clusters($min_score,$scorename);
		$dendogram = $this->build_dendogram_from_p_clusters2($p_to_cluster,$cluster_info);
		$filtered = array_filter( $dendogram, array($this,'multi_item_branch') );
		return $filtered;
	}
	function multi_item_branch($dendogram) {
		return is_array($dendogram['branches']);
	}
	function build_dendogram_from_p_clusters($p_to_cluster,$cluster_info) {
		foreach( $p_to_cluster as $pdir => &$clusters ) {
			array_shift($clusters); // lose top cluster
		}
		return $this->build_dendogram_from_p_clusters2($p_to_cluster,$cluster_info);
	}
	
	function build_dendogram_from_p_clusters2($p_to_cluster,$cluster_info) {
		// assumes single top level cluster
		if ( count( $p_to_cluster ) == 1 ) {
			$pdirs = array_keys($p_to_cluster);
			return $pdirs[0];  // cluster is single predicate-dir
		} else {
			$cluster_sets = array();
			foreach ( $p_to_cluster as $pdir => $clusters) {
				$cluster = array_shift($clusters);
				$cluster_sets[$cluster][$pdir] = $clusters;
			}
		}
		$dendogram = array();
		foreach ( $cluster_sets as $cluster => $p_set ) {
			$dendogram[$cluster] = array( 'ct'=>$cluster_info[$cluster]['ct'],
			                              'branches'=>$this->build_dendogram_from_p_clusters2($p_set,$cluster_info) );
		}
		return $dendogram;
	}
	function cluster($min_score=0,$scorename='score') {
		$dendograms = $this->dendogram($min_score,$scorename);
		//$filtered = array_filter( $dendogram, array($this,'multi_item_branch') );
		return array_map( array($this,'flatten_dendogram'), $dendograms );
	}
	function flatten_dendogram($dendogram) {
		$ct_max = $dendogram['ct'];
		$branches = $dendogram['branches'];
		$flattened = array();
		if ( is_array($branches) ) {
			foreach( $branches as $cluster=>$item ) {
				//echo "flatten cluster $cluster \n";
				$flatened_item = $this->flatten_dendogram($item);
				$ct = $flatened_item['ct'];
				$flattened = array_merge($flattened,$flatened_item['items']);
				if ( $ct > $ct_max ) $ct_max = $ct;
			}
		} else {
			list($p,$dir) = explode('-',$branches);
			$flattened[] = array( 'p'=>$p, 'dir'=>$dir, 'name'=> $this->get_name($p) ) ;
		}

		return array( 'ct'=>$ct_max, 'items'=>$flattened );
	}
	function output_cluster($cluster_file,$clusters) {
		output_clusters($cluster_file,$clusters);
		/*
		$fh = fopen($cluster_file,"w");
		foreach ( $clusters as $cid => $cluster ) {
			foreach( $cluster['items'] as $item ) {
				fwrite( $fh, $cid . "\t" . $item['p'] . "\t" . $item['dir'] . "\t" . $item['name'] . "\n" );
			}
			fwrite( $fh,  "\n" ); // blank line to separate clusters
		}
		fclose($fh);
		*/
	}
	
}

?>
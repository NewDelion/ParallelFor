<?php
require_once 'ParallelFor.php';

$executor = function($data, $opt){
	$result = [];
	foreach($data as $value){
		$result[] = $value;

		$wait = 100000;
		usleep($wait);
	}
	return $result;
};

$data = [];
for($i = 0; $i < 50; ++$i){
	$data[] = $i;
}

# SINGLE
echo "running. please wait...\n";
$begin = microtime(true);
$executor($data, null);
echo "single: " . (microtime(true) - $begin) . " sec\n";

# PARALLEL
echo "running. please wait...\n";
$begin = microtime(true);

$p = new ParallelFor();
$p->setNumChilds(8);
$result = $p->run($data, $executor);

echo "parallel: " . (microtime(true) - $begin) . " sec\n";

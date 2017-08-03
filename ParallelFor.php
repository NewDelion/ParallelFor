<?php

# 参考
# https://github.com/hironobu-s/parallel-for

class ParallelFor{
	private $num_child = 4;
	private $aggregator = null;

	public function __construct(){
		$func = function(&$result, $data) {
			if(!is_array($result)) {
				$result = array();
			}
			$result = array_merge($result, $data);
		};
		$this->setAggregator($func);
	}

	public function setNumChilds($num){
		if(!is_numeric($num)){
			throw new InvalidArgumentException('Argument #1($num) must be integer.');
		}
		$this->num_child = $num;
	}

	public function setAggregator($func){
		if(!$func instanceof Closure){
			throw new InvalidArgumentException('Argument #2($callback) must be closure.');
		}
		$this->aggregator = $func;
	}

	public function run(array $data, Closure $executor, $opt = null){
		$num_data = count($data);
		$count_per_child = ceil($num_data / $this->num_child);

		$worker_queue = [];
		for($i = 0; $i < $this->num_child; $i++){
			$offset = $i * $count_per_child;
			if($offset + $count_per_child >= $num_data){
				$limit = $num_data - $offset;
			}
			else{
				$limit = $count_per_child;
			}
			$child_data = array_slice($data, $offset, $limit);
			$worker_queue[] = new ParallelForWorker($executor, $child_data, $opt);
		}
		foreach($worker_queue as $worker){
			$worker->start();
		}

		$result = null;
		foreach($worker_queue as $worker){
			$worker->join();

			$method = $this->aggregator;
			$method($result, $worker->getResult());
		}
		return $result;
	}
}

class ParallelForWorker extends Thread{
	private $executor = null;

	private $target = null;
	private $opt = null;

	private $result;

	public function getResult(){
		return unserialize($this->result);
	}

	public function __construct(Closure $executor, array $data, $opt = null){
		$this->executor = $executor;
		$this->target = $data;
		$this->opt = $opt;
	}

	public function run(){
		$this->result = serialize(($this->executor)($this->target, $this->opt));
	}
}

<?php

namespace Sowe\Parallel;

class Pool
{
    protected $index;
    protected $size;
    protected $workers;

    public function __construct($size, $bootstrap = null){
        $this->index = 0;
        $this->size = $size;
        $this->workers = [];
        for ($i = 0; $i < $size; $i++) {
            $this->workers[] = new Worker($bootstrap);
        }
    }

    public function runAll(callable $task, $arguments = [])
    {
        foreach($this->workers as $worker){
            $worker->run($task, $arguments);
        }
    }

    public function run(callable $task, $arguments = [])
    {
        return $this->workers[$this->index++ % $this->size]->run($task, $arguments);
    }

    public function stop(): void
    {
        foreach($this->workers as $worker){
            $worker->stop();
        }
    }

    public function kill(): void
    {
        foreach($this->workers as $worker){
            $worker->kill();
        }
    }
}

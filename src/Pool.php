<?php

namespace Sowe\Parallel;

class Pool
{
    protected $size;
    protected $index;
    protected $workers;
    
    public function __construct($size, $workerClass, $outputChannel, $bootstrap = AbstractWorker::BOOTSTRAP)
    {
        $this->size = $size;
        $this->index = 0;
        $this->workers = [];

        if (is_null($bootstrap)) {
            for($i = 0; $i < $size; $i++){
                $inputChannel = Channel::make();
                $this->workers[$i] = new $workerClass($inputChannel, $outputChannel);
            }
        } else {
            for($i = 0; $i < $size; $i++){
                $inputChannel = Channel::make();
                $this->workers[$i] = new $workerClass($inputChannel, $outputChannel, $bootstrap);
            }
        }
    }

    public function start($workerArguments){
        foreach($this->workers as $i => $worker){
            $worker->start($workerArguments);
        }
    }

    public function stop(){
        foreach($this->workers as $i => $worker){
            $worker->stop();
        }
    }

    public function dispatch($task){
        $this->workers[$this->index % $this->size]->getInputChannel()->send($task);
        $this->index++;
    }
}

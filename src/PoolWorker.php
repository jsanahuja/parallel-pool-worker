<?php

namespace Sowe\Parallel;

use parallel\Event as ParallelEvent;

class PoolWorker extends Worker
{
    protected $size;
    protected $workerClass;
    protected $bootstrap;

    public function __construct(
        int $size, $workerClass,
        Channel $inputChannel, Channel $outputChannel,
        string $bootstrap = Worker::BOOTSTRAP
    ){
        parent::__construct($inputChannel, $outputChannel, $bootstrap);

        $this->size = $size;
        $this->workerClass = $workerClass;
        $this->bootstrap = $bootstrap;
    }

    public function start(...$arguments): void
    {
        parent::start($this->size, $this->workerClass, $this->bootstrap, $arguments);
    }

    public static function onStart(Channel $outputChannel, int $size, $workerClass, string $bootstrap, $workerArguments = []): array
    {
        $pool = new Pool($size, $workerClass, $outputChannel, $bootstrap);
        $pool->start($workerArguments);
        return [$pool];
    }

    public static function onRead(Channel $outputChannel, ParallelEvent $event, Pool $pool)
    {
        $pool->dispatch($event);
    }
}
<?php

namespace Sowe\Parallel;

use parallel\Event as ParallelEvent;

abstract class PoolWorker extends AbstractWorker
{
    protected $size;
    protected $classWorker;

    public function __construct(
        int $size, $workerClass,
        Channel $inputChannel, Channel $outputChannel,
        string $bootstrap = AbstractWorker::BOOTSTRAP
    ){
        parent::__construct($inputChannel, $outputChannel, $bootstrap);

        $this->size = $size;
        $this->classWorker = $classWorker;
    }

    public function start(): void
    {
        parent::start($this->size, $this->classWorker);
    }

    public static function onStart(Channel $outputChannel, int $size, $classWorker, $workerArguments = []): array
    {
        $pool = new Pool($size, $classWorker, $outputChannel);
        $pool->start($workerArguments);
        return [$pool];
    }

    public static function onRead(Channel $outputChannel, ParallelEvent $event, Pool $pool)
    {
        $pool->dispatch($event);
    }
}
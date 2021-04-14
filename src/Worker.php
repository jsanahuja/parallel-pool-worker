<?php

namespace Sowe\Parallel;

use \parallel\Runtime;
use \parallel\Future;

class Worker
{
    protected $id;
    protected $runtime;

    public function __construct($bootstrap = null)
    {
        $this->id = md5(uniqid());
        if (is_null($bootstrap)) {
            $this->runtime = new Runtime();
        } else {
            $this->runtime = new Runtime($bootstrap);
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function run(callable $closure, ...$arguments)
    {
        return $this->runtime->run($closure, array_merge(
            [$this->id], $arguments
        ));
    }

    public function stop(): void
    {
        $this->runtime->close();
    }

    public function kill(): void
    {
        $this->runtime->kill();
    }
}

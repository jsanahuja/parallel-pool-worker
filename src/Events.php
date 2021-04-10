<?php

namespace Sowe\Parallel;

use parallel\Events as ParallelEvents;
use parallel\Input as ParallelInput;
use parallel\Future as ParallelFuture;

class Events
{
    protected $events;

    public function __construct(){
        $this->$events = new ParallelEvents();
    }

    public function setInput(ParallelInput $input): void
    {
        $this->events->setInput($input);
    }

    public function addChannel(Channel $channel): void
    {
        $this->events->addChannel($channel->getChannel());
    }

    public function addFuture(string $name, ParallelFuture $future): void
    {
        $this->events->addFuture($name, $future);
    }

    public function remove(string $target): void
    {
        $this->events->remove($target);
    }

    public function setBlocking(bool $blocking): void
    {
        $this->events->setBlocking($blocking);
    }

    public function setTimeout(int $timeout): void
    {
        $this->events->setTimeout($timeout);
    }

    public function poll(): ?ParallelEvent
    {
        $event = $this->events->poll();
        if (!is_null($event)) {
            $this->events->addChannel($event->object);
        }
        return $event;
    }
}

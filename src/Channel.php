<?php

namespace Sowe\Parallel;

use parallel\Channel as ParallelChannel;

class Channel
{
    protected $id;
    protected $channel;

    protected function __construct($id, ParallelChannel $channel){
        $this->id = $id;
        $this->channel = $channel;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getChannel(): ParallelChannel
    {
        return $this->channel;
    }

    public function make($id = null, $capacity = null): Channel
    {
        if (is_null($id)) {
            $id = uniqid(bin2hex(random_bytes(6)));
            $capacity = ParallelChannel::Infinite;
        } else if (is_null($capacity)) {
            $capacity = $id;
            $id = uniqid(bin2hex(random_bytes(6)));
        }
        return new self(
            $id,
            ParallelChannel::make($id, $capacity)
        );
    }

    public function open($id): Channel
    {
        return new self(
            $id,
            ParallelChannel::open($id)
        );
    }

    public function recv()
    {
        return $this->channel->recv();
    }

    public function send($value) : void
    {
        $this->channel->send($value);
    }

    public function close()
    {
        $this->channel->close();
    }
}

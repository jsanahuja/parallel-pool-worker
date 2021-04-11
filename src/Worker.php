<?php

namespace Sowe\Parallel;

use Sowe\Parallel\Channel;
use Sowe\Parallel\Events;
use parallel\Runtime as ParallelRuntime;

abstract class Worker
{
    const BOOTSTRAP = __DIR__ . "/vendor/autoload.php";

    protected $inputChannel;
    protected $outputChannel;
    protected $running;
    protected $runtime;
    protected $future;

    protected function __construct(Channel $inputChannel, Channel $outputChannel, string $bootstrap = self::BOOTSTRAP)
    {
        if (is_null($bootstrap)) {
            $this->runtime = new ParallelRuntime();
        }else{
            $this->runtime = new ParallelRuntime($bootstrap);
        }
        $this->inputChannel = $inputChannel;
        $this->outputChannel = $outputChannel;
        $this->future = null;
        $this->running = false;
    }

    public function __destruct()
    {
        $this->stop();
    }

    public function start(...$arguments): void
    {
        if ($this->running) {
            throw new \Exception("Worker is already running");
        }
        $this->running = true;

        $this->future = $this->runtime->run(
            function($inputChannelId, $outputChannelId, $className, $args){
                $inputChannel = Channel::open($inputChannelId);
                $outputChannel = Channel::open($outputChannelId);

                $events = call_user_func_array([$className, 'getEvents'], [$inputChannel, $outputChannel]);
                $handlers = call_user_func([$className, 'getHandlers']);

                if ($handlers['start'] !== false) {
                    $arguments = call_user_func_array(
                        [$className, $handlers['start']],
                        array_merge([$outputChannel], $args)
                    );
                } else {
                    $arguments = [];
                }

                while (true) {
                    try {
                        $event = null;
                        do {
                            $event = $events->poll();
                            if ($event) {
                                if ($event->source == $outputChannelId && $event->type == Type::Read) {
                                    $event->type = Type::Write;
                                }
                                if (isset($handlers[$event->type]) && $handlers[$event->type] !== false) {
                                    switch($event->type) {
                                        case Type::Read:
                                            $result = call_user_func_array(
                                                [$className, $handlers[$event->type]],
                                                array_merge([$outputChannel, $event], $arguments)
                                            );
                                        case Type::Write:
                                            $result = call_user_func_array(
                                                [$className, $handlers[$event->type]],
                                                [$inputChannel, $event]
                                            );
                                        default:
                                            $result = call_user_func_array(
                                                [$className, $handlers[$event->type]],
                                                [$event]
                                            );
                                    }
                                    if(!$result){
                                        return;
                                    }
                                }
                                
                                switch($event->type){
                                    case Type::Error:
                                    case Type::Close:
                                    case Type::Cancel:
                                    case Type::Kill:
                                        if ($handlers['stop'] !== false) {
                                            call_user_func([$className, $handlers['stop']]);
                                        }
                                        return;
                                }
                            }
                        } while ($event);

                        if ($handlers['loop'] !== false) {
                            $result = call_user_func_array(
                                [$className, $handlers['loop']],
                                array_merge([$outputChannel], $arguments)
                            );
                            if(!$result){
                                return;
                            }
                        }
                    } catch (\Exception $e) {
                        throw $e;
                    }
                }
            },
            [$this->inputChannel->getId(), $this->outputChannel->getId(), static::class, $arguments]
        );
    }

    protected static function getEvents(Channel $inputChannel, Channel $outputChannel): Events
    {
        $events = new Events();
        $events->addChannel($inputChannel);
        $events->addChannel($outputChannel);
        $events->setBlocking(false);
        return $events;
    }

    protected static function getHandlers()
    {
        return [
            Type::Read      => method_exists(static::class, 'onRead')   ? 'onRead'   : false,
            Type::Write     => method_exists(static::class, 'onWrite')  ? 'onWrite'  : false,
            Type::Close     => method_exists(static::class, 'onClose')  ? 'onClose'  : false,
            Type::Cancel    => method_exists(static::class, 'onCancel') ? 'onCancel' : false,
            Type::Kill      => method_exists(static::class, 'onKill')   ? 'onKill'   : false,
            Type::Error     => method_exists(static::class, 'onError')  ? 'onError'  : false,
            'loop'          => method_exists(static::class, 'onLoop')   ? 'onLoop'   : false,
            'start'         => method_exists(static::class, 'onStart')  ? 'onStart'  : false,
            'stop'          => method_exists(static::class, 'onStop')   ? 'onStop'   : false
        ];
    }

    public function stop(): void
    {
        $this->running = false;
        $this->inputChannel->close();
    }

    public function send($data): void
    {
        $this->inputChannel->send($data);
    }

    public function getInputChannel(Channel $channel): Channel
    {
        return $this->inputChannel;
    }

    public function getOutputChannel(Channel $channel): Channel
    {
        return $this->outputChannel;
    }
}

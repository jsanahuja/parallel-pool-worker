<?php

namespace Sowe\Parallel;

use parallel\Runtime as ParallelRuntime;

abstract class AbstractWorker
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

                $events = new Events();
                $events->addChannel($inputChannel);
                $events->setBlocking(false);

                $handlers = [
                    Type::Read      => method_exists($className, 'onRead')   ? 'onRead'   : false,
                    Type::Close     => method_exists($className, 'onClose')  ? 'onClose'  : false,
                    Type::Cancel    => method_exists($className, 'onCancel') ? 'onCancel' : false,
                    Type::Kill      => method_exists($className, 'onKill')   ? 'onKill'   : false,
                    Type::Error     => method_exists($className, 'onError')  ? 'onError'  : false,
                    'loop'          => method_exists($className, 'onLoop')   ? 'onLoop'   : false,
                    'start'         => method_exists($className, 'onStart')  ? 'onStart'  : false,
                    'stop'          => method_exists($className, 'onStop')   ? 'onStop'   : false
                ];

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
                                if (isset($handlers[$event->type]) && $handlers[$event->type] !== false) {
                                    $result = call_user_func_array(
                                        [$className, $handlers[$event->type]],
                                        array_merge([$outputChannel, $event], $arguments)
                                    );
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

    public function stop(): void
    {
        $this->running = false;
        $this->inputChannel->close();
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

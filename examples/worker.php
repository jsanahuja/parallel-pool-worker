<?php

use Sowe\Parallel\Worker;

$bootstrap = dirname(__DIR__) . "/vendor/autoload.php";
include $bootstrap;

$load = function($id){
    // This will make $logger variable to be created thread-context global.
    global $logger;
    $logger = function($id, $msg){
        echo "Thread " . $id . " says: " . $msg . PHP_EOL;
    };
};

$task = function($id, $msg){
    // Getting our global $logger for this task.
    global $logger;
    $logger($id, $msg);
};

$worker = new Worker($bootstrap);
$worker->run($load);
$worker->run($task, "This is the first task");
$worker->run($task, "This is the second task");
$worker->run($task, "This is the third task");
$worker->stop();

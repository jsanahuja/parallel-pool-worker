<?php

use Sowe\Parallel\Pool;

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

// Creating a poll of 3 Workers.
$worker = new Pool(3, $bootstrap);
$worker->runAll($load);
$worker->run($task, "This is the 1st task");
$worker->run($task, "This is the 2nd task");
$worker->run($task, "This is the 3rd task");
$worker->run($task, "This is the 4th task");
$worker->run($task, "This is the 5th task");
$worker->run($task, "This is the 6th task");
$worker->run($task, "This is the 7th task");
$worker->run($task, "This is the 8th task");
$worker->run($task, "This is the 9th task");
$worker->stop();
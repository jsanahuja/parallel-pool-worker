# parallel-pool-worker
PHP ext-parallel pool-worker abstraction

## Requirements
- PHP ^7.2
- Parallel (ext-parallel) ^1

## Install
```
composer require sowe/parallel
```

If your composer doesn't run your zts-php you can also install it running:
```
zts-php /usr/bin/composer require sowe/parallel
```

## Example
```
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
```

Result:
```
Thread 52b95298ad5eaa92e23a7ff00f3dda45 says: This is the 1st task
Thread 920be484d88bab0b3cef633ec4beafe6 says: This is the 2nd task
Thread 735bd40cc4c50243ff17fae2ab730da2 says: This is the 3rd task
Thread 52b95298ad5eaa92e23a7ff00f3dda45 says: This is the 4th task
Thread 920be484d88bab0b3cef633ec4beafe6 says: This is the 5th task
Thread 735bd40cc4c50243ff17fae2ab730da2 says: This is the 6th task
Thread 52b95298ad5eaa92e23a7ff00f3dda45 says: This is the 7th task
Thread 920be484d88bab0b3cef633ec4beafe6 says: This is the 8th task
Thread 735bd40cc4c50243ff17fae2ab730da2 says: This is the 9th task
```

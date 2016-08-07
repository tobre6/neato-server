<?php
/**
 * Created by IntelliJ IDEA.
 * User: tobre
 * Date: 07/08/16
 * Time: 15:33
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . "/../vendor/bluerhinos/phpmqtt/phpMQTT.php";

$config = include 'config/config.php';

$token = false; // Token returned from authorize method

$client = new NeatoBotvacClient($token);
$robots = array();

$auth = $client->authorize($config['neato']['email'], $config['neato']['password']);

if($auth !== false) {
    $result = $client->getRobots();

    if($result !== false) {
        foreach ($result["robots"] as $robot) {
            $robots[] = new NeatoBotvacRobot($robot["serial"], $robot["secret_key"]);
        }
    }
} else {
    echo "Unable to authorize";
}

$mqtt = new phpMQTT($config['mqtt']['server'], $config['mqtt']['port'], "neato-server"); //Change client name to something unique

if(!$mqtt->connect()){
    exit(1);
}

$topics[$config['mqtt']['commandTopic']] = array("qos"=>0, "function"=>"processMessage");
$mqtt->subscribe($topics,0);

$time = time();
$interval = 600;
while($mqtt->proc()) {
    if ((time() - $time) >= $interval) {
        $state = $robots[0]->getState();
        publishStatus($state['state']);
        if ($state['state'] == 2) {
            $interval = 20;
        } else {
            $interval = 600;
        }
        $time = time();
    }
}

$mqtt->close();

function processMessage($topic, $msg) {
    global $robots;
    global $interval;
    if ($msg == 'clean') {
        publishStatus(2);
        $interval = 20;
        $robots[0]->startCleaning();
    }

    if ($msg == 'stop') {
        publishStatus(1);
        $interval = 600;
        $robots[0]->pauseCleaning();
        $robots[0]->sendToBase();
        $robots[0]->resumeCleaning();
    }
}

function publishStatus($status) {
    global $mqtt;
    global $config;
    $mqtt->publish($config['mqtt']['stateTopic'], $status == 1 ? 'stop' : 'clean', 0);
}
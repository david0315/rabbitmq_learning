<?php
/**
 * Created by PhpStorm.
 * User: Ants
 * Date: 2018/11/13 9:14
 */

require_once __DIR__ . '/../../../../vendor/autoload.php';
use \PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('192.168.10.10', 5672, 'guest', 'guest');
$channel = $connection->channel();
$queue_name = 'reliable_queue';
$exchange = 'reliable_exchange';
$routing_key = 'reliable_key';

$channel->exchange_declare($exchange,'topic',false,true,false);

$channel->queue_declare($queue_name,false,true,false,false,false);


$channel->queue_bind($queue_name,$exchange,$routing_key);


$callback = function ($message){
echo '----------------------';
    echo $message->body.PHP_EOL;
    echo "deliver_tag    ". $message->delivery_info['delivery_tag'].PHP_EOL;
    echo "routing_key    ". $message->delivery_info['routing_key'].PHP_EOL;
    echo "exchange   ". $message->delivery_info['exchange'].PHP_EOL;
    echo "consumer_tag    ". $message->delivery_info['consumer_tag'].PHP_EOL;
//    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
};


// 提取callback 到单独的一个类中
$channel->basic_consume($queue_name,'',false,false,false,false,$callback);

while (count($channel->callbacks)){
    $channel->wait();
};




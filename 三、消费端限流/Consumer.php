<?php
/**
 * Created by PhpStorm.
 * User: Ants
 * Date: 2018/11/13 9:14
 */

require_once __DIR__ . '/../../../vendor/autoload.php';
use \PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * 三、消费端限流
 *
 * 假设一个场景，由于我们的消费端突然全部不可用了，导致rabbitMQ服务器上有上万条未处理的消息，
 * 这时候如果没做任何现在，随便开启一个消费端客户端，就会导致巨量的消息瞬间全部推送过来，
 * 但是我们单个客户端无法同时处理这么多的数据，就会导致消费端变得巨卡，
 * 有可能直接崩溃不可用了。所以在实际生产中，限流保护是很重要的
 **/

$connection = new AMQPStreamConnection('192.168.10.10', 5672, 'guest', 'guest');
$channel = $connection->channel();

$queue_name = 'limit_queue';
$exchange = 'limit_exchange';
$routing_key = 'limit.#';

/*
    name: $exchange
    type: direct 、topic 、fanout
    passive: false // don't check if an exchange with the same name exists
    durable: false // the exchange won't survive server restarts
    auto_delete: true //the exchange will be deleted once the channel is closed.
*/
$channel->exchange_declare($exchange,'topic',false,true,false);

/*
    name: $queue
    passive: false // don't check if a queue with the same name exists
    durable: true // the queue will survive server restarts
    exclusive: false // the queue can be accessed in other channels（独占）
    auto_delete: false //the queue won't be deleted once the channel is closed.
*/
$channel->queue_declare($queue_name,false,true,false,false,false);

$channel->queue_bind($queue_name,$exchange,$routing_key);

$callback = function ($message){
    echo '----------------------';
    echo $message->body.PHP_EOL;
    echo "deliver_tag    ". $message->delivery_info['delivery_tag'].PHP_EOL;
    echo "routing_key    ". $message->delivery_info['routing_key'].PHP_EOL;
    echo "exchange   ". $message->delivery_info['exchange'].PHP_EOL;
    echo "consumer_tag    ". $message->delivery_info['consumer_tag'].PHP_EOL;
    // 模拟处理任务 时长
    sleep(1);
    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
};

$channel->basic_qos(0,1,true);

$channel->basic_consume($queue_name,'',false,false,false,false,$callback);

while (count($channel->callbacks)){
    $channel->wait();
};




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
 * confirm消息确认机制
 *
 * 消息的确认是指生产者投递消息后，如果Broker接收到消息，则会给生产者一个应答。
 * 生产者进行接收应答，用来确认这条消息是否正常的发送到Broker，
 * 这种方式也是消息可靠性投递的核心保障
 **/

$connection = new AMQPStreamConnection('192.168.10.10', 5672, 'guest', 'guest');
$channel = $connection->channel();
$queue_name = 'reliable_queue';
$exchange = 'reliable_exchange';
$routing_key = 'reliable_key';

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
    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
};


$channel->basic_consume($queue_name,'',false,false,false,false,$callback);

while (count($channel->callbacks)){
    $channel->wait();
};




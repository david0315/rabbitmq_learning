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
 * 开启多个消费端
 *
 * 任务队列里的数据过多，需要开启多个消费端加速消费
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

    try{
        //业务逻辑处理，模拟时长
        sleep(2);
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);

    }catch (\Exception $e){
        $requeue = true;
        // 那么现在问题又来了，正确的消息被ack 了，那么在消费过程中有异常了怎么办，
        //第一种 不ack 保留在queue里边，
        //第二种 nack 然后根据错误类型 决定是重回队列 还是抛弃改消息.
        // 1. requeue true 这条消息重新放回队列重新消费 ，
        // 2. requeue false 抛弃这条消息
        $message->delivery_info['channel']->basic_nack($message->delivery_info['delivery_tag'], false, $requeue);
    }
};

// 开启2个消费端，同时处理消息
$channel->basic_qos(0,2,null);

$channel->basic_consume($queue_name,'',false,false,false,false,$callback);

while (count($channel->callbacks)){
    $channel->wait();
};




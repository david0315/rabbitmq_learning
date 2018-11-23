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

//1.创建连接
$connection = new AMQPStreamConnection('192.168.10.10', 5672, 'guest', 'guest');

//2.通过connection 创建一个新的channel
$channel = $connection->channel();

$queue_name = 'limit_queue';
$exchange = 'limit_exchange';
$routing_key = 'limit.#';

//3 发送多条消息

for ($i=0;$i<10;$i++){
    $msg = new AMQPMessage("this a message from reliable producer".$i);
    $channel->basic_publish($msg,$exchange,$routing_key,true);
}

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
 * 生产者通过指定一个exchange 和 routingkey  把消息送达到某个队列中去，
 * 然后消费者监听队列，进行消费处理。
 * 但是在某些情况下，如果我们在发送消息时，当前的exchange 不存在或者指定的routingkey路由不到，
 * 这个时候如果要监听这种不可达的消息，
 * 就要使用 return
 **/

//1.创建连接
$connection = new AMQPStreamConnection('192.168.10.10', 5672, 'guest', 'guest');

//2.通过connection 创建一个新的channel
$channel = $connection->channel();

$exchange = 'return_exchange';
$routing_key = 'll';

//3.注册return callback （return 用于处理一些不可路由的消息！）

$channel->set_return_listener(
    function ($replyCode, $replyText, $exchange, $routingKey, AMQPMessage $message) {
        echo "Message returned with content " . $message->body . PHP_EOL;
    }
);

// declare  exchange but don`t bind any queue
$channel->exchange_declare($exchange, 'topic');

//4 发送一条消息
$msg = new AMQPMessage("this a message from return producer");

//mandatory 一定要设置true ，否则这些路由不到的消息就会被broker端自动删除，只有设置成true后监听器会接受到路由不可达的消息 然后后续处理
$channel->basic_publish($msg,$exchange,$routing_key,true);

//channel要wait 才可看到结果
$channel->wait();

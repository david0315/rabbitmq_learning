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
 * 六、消息的ttl与死信路由
 *
 * 消息的TTL是time to live 的简称，顾名思义指的是消息的存活时间。
 *
 * Dead Letter Exchanges。一个消息在满足如下条件下，会进死信路由，记住这里是路由而不是队列，一个路由可以对应很多队列。
 * ①. 一个消息被Consumer拒收了，并且reject方法的参数里requeue是false。也就是说不会被再次放在队列里，被其他消费者使用。
 * ②. 上面的消息的TTL到了，消息过期了。
 * ③. 队列的长度限制满了。排在前面的消息会被丢弃或者扔到死信路由上。
 *
 * Dead Letter Exchange其实就是一种普通的exchange，和创建其他exchange没有两样。
 * 只是在某一个设置Dead Letter Exchange的队列中有消息过期了，会自动触发消息的转发，
 * 发送到Dead Letter Exchange中去。
 */

//1.创建连接
$connection = new AMQPStreamConnection('192.168.10.10', 5672, 'guest', 'guest');

//2.通过connection 创建一个新的channel
$channel = $connection->channel();

//3.先声明一个普通的 交换机以及队列 并绑定
$queue_name = 'dlx_common_queue';
$exchange = 'dlx__common_exchange';
$routing_key = 'dlx.#';

$channel->exchange_declare($exchange, 'topic', false, true, false);
$channel->queue_declare($queue_name, false, true, false, false, false,
    new \PhpAmqpLib\Wire\AMQPTable([
        "x-message-ttl" => 10000, //过期时间 单位毫秒, 10秒钟
        "x-dead-letter-exchange" => "dlx.exchange", // 过期后 发送到的死信队列
//       "x-dead-letter-routing-key", // 过期后 发送到的死信队列 routing_key
//       "x-max-length-bytes" // 最大字节数
//        "x-max-length" => 10, //容量个数
//        "x-expires" => 16000,// 自动过期时间
//        "x-max-priority"  // 权重
    ]));
$channel->queue_bind($queue_name, $exchange, $routing_key);

//4.声明 死信队列、交换机 并绑定
$channel->exchange_declare('dlx.exchange', 'topic', false, true, false);
$channel->queue_declare('dlx.queue', false, true, false, false);
$channel->queue_bind('dlx.queue', 'dlx.exchange', '#');





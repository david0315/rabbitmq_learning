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
 * 七、 延迟队列 （方式二：使用消息的TTL结合DLX）
 * 延迟队列存储的对象肯定是对应的延时消息，所谓”延时消息”是指当消息被发送以后，并不想让消费者立即拿到消息，
 * 而是等待指定时间后，消费者才拿到这个消息进行消费。
 *
 * 使用场景：当我们的系统数据库比较小的时候，我们可以直接数据库定时轮询，查询时间有没有 超出半个小时这样子，
 * 但是一旦数据库比较大，牵扯到分库分表这样的话，定时轮询就是很耗费系统资源的，这时候就是延迟队列的用武之地了。
 *
 * 场景一：在订单系统中，一个用户下单之后通常有30分钟的时间进行支付，如果30分钟之内没有支付成功，那么这个订单将进行一场处理。
 *       这是就可以使用延时队列将订单信息发送到延时队列。
 * 场景二：用户希望通过手机远程遥控家里的智能设备在指定的时间进行工作。这时候就可以将用户指令发送到延时队列，
 *        当指令设定的时间到了再将指令推送到只能设备。
 *
 * 实现方式：
 *  方式一：使用rabbitmq-delayed-message-exchange插件
 *  方式二：使用消息的TTL结合DLX(死信路由)
 */

//1.创建连接
$connection = new AMQPStreamConnection('192.168.10.10', 5672, 'guest', 'guest');

//2.通过connection 创建一个新的channel
$channel = $connection->channel();

//3.先声明一个普通的 交换机以及队列 并绑定
$queue_name = 'delay_queue';
$exchange = 'delay_exchange';
$routing_key = 'delay.#';

$channel->exchange_declare($exchange, 'topic', false, true, false);
$channel->queue_declare($queue_name, false, true, false, false, false,
    new \PhpAmqpLib\Wire\AMQPTable([
        "x-message-ttl" => 10000, //过期时间 单位毫秒, 10秒钟
        "x-dead-letter-exchange" => "delay.exchange", // 过期后 发送到的死信路由
//       "x-dead-letter-routing-key", // 过期后 发送到的死信队列 routing_key
//       "x-max-length-bytes" // 最大字节数
//        "x-max-length" => 10, //容量个数
//        "x-expires" => 16000,// 自动过期时间
//        "x-max-priority"  // 权重
    ]));
$channel->queue_bind($queue_name, $exchange, $routing_key);

//4.声明 延迟队列、交换机 并绑定(其实也就是上章所讲的死信队列 )
$channel->exchange_declare('delay.exchange', 'topic', false, true, false);
$channel->queue_declare('delay.queue', false, true, false, false);
$channel->queue_bind('delay.queue', 'delay.exchange', 'delay.#');


//5.发送消息
$msg = new AMQPMessage("this a message from delay producer2");
$channel->basic_publish($msg, $exchange, $routing_key);

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
 * 七、 延迟队列 （方式一：使用rabbitmq-delayed-message-exchange插件）
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
 *      插件需要手动安装
 * 1.下载地址 http://www.rabbitmq.com/community-plugins/ 找到对应版本，
 *    下载rabbitmq_delayed_message_exchange-0.0.1-rmq3.5.x-9bf265e4.ez
 * 2. 找到rabbitmq plugin插件的位置，复制进去，/usr/lib/rabbitmq/lib/rabbitmq_server-3.5.7/plugins/
 * 3. 启用插件 rabbitmq-plugins list  、rabbitmq-plugins enable rabbitmq_delayed_message_exchange

 *
 *  方式二：使用消息的TTL结合DLX(死信路由)
 *
 */

//1.创建连接
$connection = new AMQPStreamConnection('192.168.10.10', 5672, 'guest', 'guest');

//2.通过connection 创建一个新的channel
$channel = $connection->channel();

$queue_name = 'delayed_queue';
$exchange = 'delayed_exchange';
$routing_key = 'delay.#';

//3.声明交换机、队列并绑定
$channel->exchange_declare($exchange, 'x-delayed-message', false, true,
    false, false, false, new \PhpAmqpLib\Wire\AMQPTable(["x-delayed-type" => "fanout"]));

$channel->queue_declare($queue_name, false, false, false, false, false,
    new \PhpAmqpLib\Wire\AMQPTable(["x-dead-letter-exchange" => "delayed"]));

$channel->queue_bind($queue_name, $exchange);

$headers = new \PhpAmqpLib\Wire\AMQPTable();
//set 方式添加 header 延迟30秒
$headers->set('x-delay', 30000);

$msg = new AMQPMessage('this a message from delay producer', [
    //消息持久化
    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
    //自定义消息头
    'application_headers' => $headers
]);

//4.发送消息
$channel->basic_publish($msg, $exchange);

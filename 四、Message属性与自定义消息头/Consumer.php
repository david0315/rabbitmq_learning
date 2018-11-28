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
 * 四、消费端Ack与重回队列
 *
 * 消费端进行消费的时候，如果由于业务异常导致失败了，返回NACK达到最大重试次数，此时我们可以进行日志的记录，
 * 然后手动ACK回去，最后对这个记录进行补偿。
 * 或者由于服务器宕机等严重问题，导致ACK和NACK都没有，那我们就需要手工进行ACK保障消费端消费成功，再通过补偿机制补偿。
 **/

$connection = new AMQPStreamConnection('192.168.10.10', 5672, 'guest', 'guest');
$channel = $connection->channel();

$queue_name = 'header_queue';
$exchange = 'header_exchange';
$routing_key = 'header.#';

/*
    name: $exchange
    type: direct 、topic 、fanout
    passive: false // don't check if an exchange with the same name exists
    durable: false // the exchange won't survive server restarts
    auto_delete: true //the exchange will be deleted once the channel is closed.
*/
$channel->exchange_declare($exchange, 'topic', false, true, false);

/*
    name: $queue
    passive: false // don't check if a queue with the same name exists
    durable: true // the queue will survive server restarts
    exclusive: false // the queue can be accessed in other channels（独占）
    auto_delete: false //the queue won't be deleted once the channel is closed.
*/
$channel->queue_declare($queue_name, false, true, false, false, false);

$channel->queue_bind($queue_name, $exchange, $routing_key);

$callback = function ($message) {
    echo '----------------------';
    echo $message->body . PHP_EOL;
    echo "deliver_tag    " . $message->delivery_info['delivery_tag'] . PHP_EOL;
    echo "routing_key    " . $message->delivery_info['routing_key'] . PHP_EOL;
    echo "exchange   " . $message->delivery_info['exchange'] . PHP_EOL;
    echo "consumer_tag    " . $message->delivery_info['consumer_tag'] . PHP_EOL;

    // 获取自定义的消息头
    $header = $message->get('application_headers')->getNativeData();
    var_dump($header);

    // 模拟处理任务 时长
    sleep(1);
    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
};

$channel->basic_consume($queue_name, '', false, false, false, false, $callback);

while (count($channel->callbacks)) {
    $channel->wait();
};




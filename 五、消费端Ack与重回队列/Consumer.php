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
 * 五、消费端Ack与重回队列
 *
 * 消费端进行消费的时候，如果由于业务异常导致失败了，返回NACK达到最大重试次数，此时我们可以进行日志的记录，
 * 然后手动ACK回去，最后对这个记录进行补偿。
 * 或者由于服务器宕机等严重问题，导致ACK和NACK都没有，那我们就需要手工进行ACK保障消费端消费成功，再通过补偿机制补偿。
 **/

$connection = new AMQPStreamConnection('192.168.10.10', 5672, 'guest', 'guest');
$channel = $connection->channel();

$queue_name = 'confirm_queue';
$exchange = 'confirm_exchange';
$routing_key = 'confirm.#';

$channel->exchange_declare($exchange, 'topic', false, true, false);

$channel->queue_declare($queue_name, false, true, false, false, false);

$channel->queue_bind($queue_name, $exchange, $routing_key);

$callback = function ($message) {
    echo '----------------------';
    echo $message->body . PHP_EOL;
    echo "deliver_tag    " . $message->delivery_info['delivery_tag'] . PHP_EOL;
    echo "routing_key    " . $message->delivery_info['routing_key'] . PHP_EOL;
    echo "exchange   " . $message->delivery_info['exchange'] . PHP_EOL;
    echo "consumer_tag    " . $message->delivery_info['consumer_tag'] . PHP_EOL;

    $header = $message->get('application_headers')->getNativeData();
    // 模拟处理时长
    sleep(2);

    if ($header['num'] % 2 == 0) {
        echo "ack" . PHP_EOL;
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
    } else {
        echo "nack" . PHP_EOL;
        $requeue = true;
        // 那么现在问题又来了，正确的消息被ack 了，那么在消费过程中有异常了怎么办，
        //第一种 不ack 保留在queue里边，
        //第二种 nack 然后根据错误类型 决定是重回队列 还是抛弃改消息.
        // 1. requeue true 这条消息重新放回队列重新消费 ，
        // 2. requeue false 抛弃这条消息
        $message->delivery_info['channel']->basic_nack($message->delivery_info['delivery_tag'], false, $requeue);
    }
};

$channel->basic_consume($queue_name, '', false, false, false, false, $callback);

while (count($channel->callbacks)) {
    $channel->wait();
};




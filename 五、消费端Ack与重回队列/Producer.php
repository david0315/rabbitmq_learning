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

//1.创建连接
$connection = new AMQPStreamConnection('192.168.10.10', 5672, 'guest', 'guest');

//2.通过connection 创建一个新的channel
$channel = $connection->channel();

$queue_name = 'confirm_queue';
$exchange = 'confirm_exchange';
$routing_key = 'confirm.#';

//3 发送多条消息
for ($i=0;$i<10;$i++){

    // 创建头消息,各种数据格式
    $headers = new \PhpAmqpLib\Wire\AMQPTable();
    $headers->set('num',$i);
    $msg = new AMQPMessage('this a message from confirm producer'.$i,[
        //消息持久化
        'delivery_mode'=> AMQPMessage::DELIVERY_MODE_PERSISTENT,

        //自定义消息头
        'application_headers'=> $headers
    ]);

    $channel->basic_publish($msg,$exchange,$routing_key);
}

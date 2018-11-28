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

//1.创建连接
$connection = new AMQPStreamConnection('192.168.10.10', 5672, 'guest', 'guest');

//2.通过connection 创建一个新的channel
$channel = $connection->channel();

$queue_name = 'header_queue';
$exchange = 'header_exchange';
$routing_key = 'header.#';

//3 发送多条消息
for ($i=0;$i<10;$i++){

    // 创建头消息,各种数据格式
    $headers = new \PhpAmqpLib\Wire\AMQPTable([
        'x-foo'=>'bar',
//        'table'=> array('figuf', 'ghf'=>5, 5=>675),
//        'num1' => -4294967295,
//        'true' => true,
//        'void' => null,
//        'date' => new DateTime(),
//        'array' => array(null, 'foo', 'bar', 5, 5674625, 'ttt', array(5, 8, 2)),
//        '64bitint' => 9223372036854775807,
//        '64bit_uint' => '18446744073709600000',
    ]);

    //set 方式添加 header
    $headers->set('shortshort', -5, \PhpAmqpLib\Wire\AMQPTable::T_INT_SHORTSHORT);
    $headers->set('short', -1024, \PhpAmqpLib\Wire\AMQPTable::T_INT_SHORT);
    $headers->set('num',$i);

    var_dump($headers->getNativeData());
    echo PHP_EOL;

//    $message->set('application_headers', $headers);

//         message 属性
//        'content_type' => 'shortstr',
//        'content_encoding' => 'shortstr',
//        'application_headers' => 'table_object',
//        'delivery_mode' => 'octet',
//        'priority' => 'octet',
//        'correlation_id' => 'shortstr',
//        'reply_to' => 'shortstr',
//        'expiration' => 'shortstr',
//        'message_id' => 'shortstr',
//        'timestamp' => 'timestamp',
//        'type' => 'shortstr',
//        'user_id' => 'shortstr',
//        'app_id' => 'shortstr',
//        'cluster_id' => 'shortstr',

    $msg = new AMQPMessage('this a message from header producer'.$i,[
        //消息持久化
        'delivery_mode'=> AMQPMessage::DELIVERY_MODE_PERSISTENT,

        //自定义消息头
        'application_headers'=> $headers
    ]);


    $channel->basic_publish($msg,$exchange,$routing_key);
}


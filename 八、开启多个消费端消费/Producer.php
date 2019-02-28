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

//1.创建连接
$connection = new AMQPStreamConnection('192.168.10.10', 5672, 'guest', 'guest');

//2.通过connection 创建一个新的channel
$channel = $connection->channel();

//设置ack成功回调函数
$channel->set_ack_handler(
    function (AMQPMessage $message){
        echo "ack ---- ". PHP_EOL;
    }
);
//设置ack失败回调函数
$channel->set_nack_handler(
    function (AMQPMessage $message){
        echo " no ack ----". PHP_EOL;
    }
);

//3 确定消息的投递模式 ：消息确认模式
$channel->confirm_select();

$queue_name = 'reliable_queue';
$exchange = 'reliable_exchange';
$routing_key = 'reliable_key';

//声明交换机exchange （非必要，如果存在就可不声明,或者在consumer消费端声明）
//$channel->exchange_declare($exchange,'topic',false,true,false);

//4 发送多条消息
for ($i=0;$i<1000;$i++){
    $msg = new AMQPMessage("this a message from reliable producer,and msg id is $i");
    $channel->basic_publish($msg,$exchange,$routing_key);
}

//5 等待ack 回调
$channel->wait_for_pending_acks_returns();


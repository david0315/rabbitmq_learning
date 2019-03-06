<?php
/**
 * Created by PhpStorm.
 * User: Ants
 * Date: 2019/3/7 9:53
 */

require_once __DIR__ . '/../../../vendor/autoload.php';
use \PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;


class RpcClient {

    private $connection;

    private $channel;

    private $callback_queue;

    private $response;

    private $corr_id;

    public function __construct()
    {
        $this->connection = new AMQPStreamConnection('192.168.10.10', 5672, 'guest', 'guest');

        $this->channel = $this->connection->channel();

        list($this->callback_queue,,) = $this->channel->queue_declare("",false,false,true,false);

        $this->channel->basic_consume(
            $this->callback_queue, '', false, false, false, false,
            array($this, 'on_response'));

    }

    public function on_response($rep)
    {
        if($rep->get('correlation_id') == $this->corr_id) {
            $this->response = $rep->body;
        }
    }

    public function call($n) {
        $this->response = null;
        $this->corr_id = uniqid();

        $msg = new AMQPMessage(
            (string) $n,
            array('correlation_id' => $this->corr_id,
                'reply_to' => $this->callback_queue)
        );
        $this->channel->basic_publish($msg, '', 'rpc_queue');
        while(!$this->response) {
            $this->channel->wait();
        }
        return intval($this->response);
    }

}

$fibonacci_rpc = new RpcClient();
for($i= 1;$i<1000;$i++){
    $response = $fibonacci_rpc->call(30);
    echo " [.] call 30 Got ", $response, PHP_EOL;
}

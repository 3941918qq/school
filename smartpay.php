<?php
$serv = new Swoole\Server("0.0.0.0", 45108);
$serv->set(array(
	'worker_num' =>10 , //工作进程数量
	'daemonize' => true, //是否作为守护进程
    	'backlog' => 128,
    	'log_file' => '/data/logs/smartpayt.log',

));

$serv->on('workerstart', function($serv, $id) {
	$conn_args = array(
                'host' => '127.0.0.1', //rabbitmq 服务器host
                'port' => 5672, //rabbitmq 服务器端口
                'login' => 'guest', //登录用户
                'password' => 'hnzf55030687', //登录密码
                'vhost' => '/', //虚拟主机
        );
        $e_name = 'smartpay';
        $q_name = 'smartpay';
        $k_route = 'smartpay';
        //$msg = "helloworld";

        $conn = new AMQPConnection($conn_args);
        if (!$conn->connect()) {
                die('Cannot connect to the broker');
        }
        $channel = new AMQPChannel($conn);

        $ex = new AMQPExchange($channel);
        $ex->setName($e_name);
        $ex->setType(AMQP_EX_TYPE_DIRECT);
        $ex->setFlags(AMQP_DURABLE);
        $status = $ex->declareExchange(); //声明一个新交换机，如果这个交换机已经存在了，就不需要再调用declareExchange()方法了.
       /* $q = new AMQPQueue($channel);
        $q->setName($q_name);
        $status = $q->declareQueue(); //同理如果该队列已经存在不用再调用这个方法了。
        $q->bind($e_name,$k_route);*/
	$serv->exchange = $ex;

});


$serv->on('connect', function ($serv, $fd) {
	echo date("Y-m-d H:i:s  ")."Client:Connect.\n";
});
$serv->on('receive', function ($serv, $fd, $from_id, $data)  {
	//$exchange = getExchange();
	echo date("Y-m-d H:i:s  ").$data."\n";
	$serv->exchange->publish($data, $k_route);
	$serv->send($fd, 'OK');
	//$serv->close($fd);
});
$serv->on('close', function ($serv, $fd) {
	echo date("Y-m-d H:i:s  ")."Client: Close.\n";
});
$serv->start();



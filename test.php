<?php

$conn_args = array(
        'host'=>'127.0.0.1',  //rabbitmq 服务器host
        'port'=>5672,         //rabbitmq 服务器端口
        'login'=>'guest',     //登录用户
        'password'=>'hnzf55030687',   //登录密码
        'vhost'=>'/'         //虚拟主机
    );
$e_name = 'weixin';
$q_name = 'weixin';
$k_route = 'weixin';
//$msg = "ABAB00480566500101e200001673140208232029a2201709011801251111BABA";
//$msg = "E200001673140149237024A1";
////////E200517B9946050817506373
//$msg = "helloworld";

$conn = new AMQPConnection($conn_args);
if(!$conn->connect()){
    die('Cannot connect to the broker');
}
$channel = new AMQPChannel($conn);

$ex = new AMQPExchange($channel);
$ex->setName($e_name);
$ex->setType(AMQP_EX_TYPE_DIRECT);
$ex->setFlags(AMQP_DURABLE);
var_dump($ex);
$status = $ex->declareExchange();  //声明一个新交换机，如果这个交换机已经存在了，就不需要再调用declareExchange()方法了.
$q = new AMQPQueue($channel);
$q->setName($q_name);
$q->setFlags(AMQP_DURABLE | AMQP_AUTODELETE );
var_dump($q);
$status = $q->declareQueue(); //同理如果该队列已经存在不用再调用这个方法了。
$q->bind($e_name,$k_route);
var_dump($status);
$status = $ex->publish($msg, $k_route);
var_dump($status);


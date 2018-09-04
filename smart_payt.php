<?php
require_once __DIR__ . "/sendSmartWeiXin.class.php";

$conn_args = array(
  'host' => '127.0.0.1',
  'port' => 5672,
  'login' => 'guest',
  'password' => 'hnzf55030687',
  'vhost' => '/',
);
$e_name = 'smartpay';
$q_name = 'smartpay';
$k_route = 'smartpay';

$conn = new AMQPConnection($conn_args);
if (!$conn->connect()) {
  die('Cannot connect to the broker');
}
$channel = new AMQPChannel($conn);
$channel->qos(0, 1);
$ex = new AMQPExchange($channel);
$ex->setName($e_name);
$ex->setType(AMQP_EX_TYPE_DIRECT);
$ex->setFlags(AMQP_DURABLE);

$q = new AMQPQueue($channel);
$q->setName($q_name);
$q->setFlags(AMQP_DURABLE); //持久化 
$q->declareQueue();
$q->bind($e_name, $k_route);

while (true) {
  $q->consume('callback');
  //$channel->qos(0, 1);
}
$conn->disconnect();
function callback($envelope, $queue)
{
        //$res = $q->ack($arr->getDeliveryTag());
        //$info = $arr->getBody();
    $info = $envelope->getBody();
      preg_match_all("/^PAPA[0-9A-Za-z-]*APAP$/", $info, $matches);
      foreach ($matches[0] as $row) 
        {
          global $schoolid;
      $schoolid = substr($row,4,5);
      $openid = substr($row,9,-4);
      if($schoolid==56650){
              $random_num =1;
            }else{
              $random_num =2;
            }
            $tempId = sendWeiXin::getTempid($random_num);

        $data['title'] = "来自正梵智慧校园的信息！";
        $data['content'] = "尊敬的家长：您好！智慧校园在个人中心新增加了投诉建议，欢迎您给我们提出宝贵的建议。为了能更好的为您提供服务，请您根据自愿的原则缴费。具体细节详见智慧校园-我要支付-功能支付。若有疑问，请咨询人工客服或037155030687，非常感谢您对我们工作的支持！【正梵智慧校园】";
        $data['url'] = "";
        $msg = sendWeiXin::createTempMsg($openid,$tempId,$data['title'],$data['content'],$data['url']);
        $sendUrl = sendWeiXin::getUrl('mb');
        $result = sendWeiXin::singlePostMsg($sendUrl, $msg);
        var_dump($info.time());
        var_dump($result);
        }
    $queue->ack($envelope->getDeliveryTag());
}

<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-06-17
 * Time: 17:01
 */
// require_once "zhihuixiaoyuanmb.php";
/*实例化redis*/
require_once "db.php";
require_once __DIR__ . "/sendSmartWeiXin.class.php";
$db = DatabaseUtils::getDatabase();

function getOpid(){
   global $db;
    $row = $db->prepare("SELECT openid,s.sid FROM wp_ischool_pastudent p LEFT JOIN wp_ischool_student s on p.stu_id=s.id WHERE openid IS not NULL AND openid!='' and enddateqq < UNIX_TIMESTAMP(NOW()) and enddatejx < UNIX_TIMESTAMP(NOW()) and enddateck < UNIX_TIMESTAMP(NOW()) and IF(s.sid != 56758,enddatepa < UNIX_TIMESTAMP(NOW()),enddatepa is not null) and s.sid = 56748 GROUP BY openid ORDER BY sid DESC ");
    $row->execute();
    $row = $row->fetchAll(PDO::FETCH_ASSOC);
    foreach ($row as $key => $value) {
        global $schoolid;
        $schoolid = $value['sid'];
        broadMsgToManyUsers($value['openid'],$schoolid);
    }
}

static $COM_PIC_URL = "/upload/syspic/msg.jpg";

function push2Queue($data) {
    $conn_args = array(
        'host' => '127.0.0.1', //rabbitmq 服务器host
        'port' => 5672, //rabbitmq 服务器端口
        'login' => 'guest', //登录用户
        'password' => 'hnzf55030687', //登录密码
        'vhost' => '/', //虚拟主机
    );
    $e_name = 'smartpay';
    $q_name = 'smartpay';

    $conn = new \AMQPConnection($conn_args);
    if (!$conn->connect()) {
        die('Cannot connect to the broker');
    }
    $channel = new \AMQPChannel($conn);

    $ex = new \AMQPExchange($channel);
    $ex->setName($e_name);
    $ex->setType(AMQP_EX_TYPE_DIRECT);
    $ex->setFlags(AMQP_DURABLE);
    $status = $ex->declareExchange(); //声明一个新交换机，如果这个交换机已经存在了，就不需要再调用declareExchange()方法了.
    $q = new \AMQPQueue($channel);
    $q->setName($q_name);
    $q->setFlags(AMQP_DURABLE  );
    $status = $q->declareQueue(); //同理如果该队列已经存在不用再调用这个方法了。
    $q->bind($e_name,$e_name);
    $ex->publish($data, $q_name);
}
/**
 * @param $openid
 * @param $title
 * @param $content
 * @param $url
 * @param $picurl
 * @return string
 * 创建模版 消息的消息实体
 */
// "template_id":"XST91dXgs5EKFpLHvtgN_u40KKmm0ZhNuf4QtfD4wEk", 三高
// "template_id":"cBWROP8P_fDOKhz0BjD1zU-r_tdNGSiXW8KYBTZXeFw", 智慧校园 
    /**
     * @param $tos
     * @param $data
     * @return mixed
     * 学校公告等大批量信息发送
     */
    function broadMsgToManyUsers($tos,$schoolid){
             $msg = "PAPA".$schoolid.$tos."APAP";
             push2Queue($msg);
                return true;
    }

getOpid();

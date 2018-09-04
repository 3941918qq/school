<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-06-17
 * Time: 17:01
 */
//require_once __DIR__ . "/zhihuixiaoyuanmb.php";
/*实例化redis*/
function getRedis(){
    $redis = new \redis();
    $redis->pconnect("127.0.0.1", 6379, 5);//连接redis
    $redis->select(2);		//2库
    return $redis;
}
function querysid(){
		$formdata = [
            "056759"=>"柘城县学苑中学",
            "056758"=>"舞钢市第一高级中学",
            "056757"=>"获嘉县第一中学",        //170821
//			"056756"=>"新乡优行教育分校",
//			"056755"=>"新乡优行教育分校",
	    "056744"=>"正梵高级中学",
	    "056742"=>"临颍县窝城镇中心小学",
	    "056741"=>"漯河市邓襄镇第一初级中学",
            "056740"=>"许昌新区实验学校",
            "056739"=>"商水县新世纪学校",
            "056738"=>"商水县第一高级中学",
            "056736"=>"许昌市榆林乡柏庄学校",
            "056732"=>"临颍县王孟中心小学",
//            "056731"=>"临颍县陈留中心小学",  
            "056707"=>"西平县人和育才小学",
            "056698"=>"王孟镇范庙小学",
            "056689"=>"漯河市第五高级中学",
//            "056685"=>"石桥乡中心小学",
	    "056684"=>"台陈一中",
//            "056683"=>"河南省临颍县职业教育中心",
            "056682"=>"窝城二中",
            "056681"=>"王岗二中",
            "056675"=>"漯河市艺术学校",
            "056673"=>"王洛中心社区小学",
            "056670"=>"襄城县玉成学校",
            "056666"=>"石桥一中",
            "056665"=>"窝城一中",
            "056664"=>"王孟一中",
            "056654"=>"马庙小学",
            "056653"=>"巨陵二中",
            "056652"=>"巨陵一中",
            "056650"=>"许昌市建安区第三高级中学",
            "056649"=>"许昌市大同街小学",
            "056623"=>"许昌市建安区实验中学",
	    "056762"=>"柘城县老王集中心中学"
			];

            $redis = getRedis();
            try {
                $redis->ping();
            } catch (Exception $e) {
                $redis = getRedis();
            }
            $redis->select(2);
            $rediskey = $redis->keys('*');
            var_dump($rediskey);
            foreach ($formdata as $key => $value) {
                if (in_array($key, $rediskey)) {
                    $arr['zc'][$key] = $value;
//                    if (!empty($status[$key]) && $status[$key] ==1) {
//                        unset($status[$key]);
//                    }
                }else{
                    $arr['bzc'][$key] = $value;
                }
            }
//            var_dump(json_encode($arr['bzc']));
    $arr_array = implode(",",$arr['bzc']);
//    var_dump($arr_array);exit();
            sendmsgt($arr_array);

//    if (!empty($arr['bzc'])) {
//        foreach ($arr['bzc'] as $key => $value) {
//            // $this->sendmsgt($value);
//            if (empty($status[$key])) {
//                sendmsgt($value);
//                $status[$key] =1;
//            }
//        }
//    }
}

function sendmsgt($value){
    // oUMeDwHY58TN7eGHRMYabhEzOvAg 张豪openid
    // $tos = ["oUMeDwLBklMzOqyGuxhuA-Pmzsu0","oUMeDwC5bsoGmgX6mC8qk3gzPnu8"];
    $tos = ["oUMeDwKrzFj4FePyVvHKzj9JFOig",'oUMeDwLBklMzOqyGuxhuA-Pmzsu0'];
    $data['title'] = "来自正梵智慧校园的信息！";
    $data['content'] = $value."设备运行不正常，请您及时查看解决！";
    $data['url'] = "";
    $result = broadMsgToManyUsers($tos,$data);
    // var_dump($result);
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
    $e_name = 'smart';
    $q_name = 'smart';

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
    $q->setFlags(AMQP_DURABLE | AMQP_AUTODELETE );
    //$status = $q->declareQueue(); //同理如果该队列已经存在不用再调用这个方法了。
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
function createTempMsg($openid,$tempid,$title,$content,$url=""){
		return '{
                       "touser":"'.$openid.'",
                       "template_id":"cBWROP8P_fDOKhz0BjD1zU-r_tdNGSiXW8KYBTZXeFw",
                       "url":"'.$url.'",
                       "topcolor":"#FF6666",
                       "data":{
                           "first":{
                               "value":"'.$title.'\n",
                               "color":"#000000"
                           },
                            "keyword1":{
                               "value":"'.$content.'\n",
                               "color":"#000000"
                           },
                            "keyword2":{
                               "value":"系统管理员\n",
                               "color":"#000000"
                           },
                            "keyword3":{
                               "value":"'.date("Y年m月d日H时i分s秒").'\n",
                               "color":"#000000"
                           },
                          "remark":{
                               "value":"",
                               "color":"#000000"
                           }
                       }
              }' ;
	}


    /**
     * @param $tos
     * @param $data
     * @return mixed
     * 学校公告等大批量信息发送
     */
    function broadMsgToManyUsers($tos,$data){
        $title = $data['title'];
        $content = $data['content'];
        $url = $data['url'];
        $pic_url = "";
        foreach($tos as $v){
            $msg = createTempMsg($v,"",$title,$content,$pic_url);
            push2Queue($msg);
        }
        return true;
    }

querysid();

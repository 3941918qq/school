<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-06-17
 * Time: 17:01
 */
//require_once __DIR__ . "/zhihuixiaoyuanmb.php";
/*实例化redis*/
require_once "db.php";
    $db = DatabaseUtils::getDatabase();
function getRedis(){
    $redis = new \redis();
    $redis->pconnect("127.0.0.1", 6379, 5);//连接redis
    $redis->select(2);		//2库
    return $redis;
}  

function snameConfig($sid){
    global $db;
    $row = $db->prepare("select name from wp_ischool_school WHERE is_deleted=0 and id= ?");
    $row->execute([$sid]);
    $row = $row->fetchAll(PDO::FETCH_ASSOC);
    $sname = isset($row[0]['name'])?$row[0]['name']:"暂时没该学校";
    return $sname;
}

function qqdh(){
   global $db;
    $row = $db->prepare("select DISTINCT(sid) FROM wp_ischool_telephone");
    $row->execute();
    $row = $row->fetchAll(PDO::FETCH_NUM);
    $row2 = $db->prepare("select Device_id,AddressInfo,sid from wp_ischool_telephone ORDER BY sid,Device_id");
    $row2->execute();
    $row2 = $row2->fetchAll(PDO::FETCH_ASSOC);
    $redis = getRedis();
    try {
        $redis->ping();
    } catch (Exception $e) {
        $redis = getRedis();
    }
    $redis->select(14);
    $rediskey = $redis->keys('*');
 $i=0;

    foreach ($row as $key => $value) {
        foreach ($rediskey as $k => $v) {
            if(substr($v,6,5) == $value[0]){
                $new['zc'][$value[0]]['sid'] = $value[0];
                $new['zc'][$value[0]]['sname'] = snameConfig($value[0]);
                $new['zc'][$value[0]]['pingan_id'][$i] = intval(substr($v,11,4));
                unset($row[$key]);
            }
            $i++;
        }
    }
     if(isset($row2)){
        foreach($row2 as $k => $v){
            $newrow[$v['sid']]['id'] = $v['sid'];
            $newrow[$v['sid']]['pinganid'][$k] = intval(substr($v['Device_id'],11,4));
        }
    }
    if(isset($new['zc'])){
        foreach ($new['zc'] as $k => $v) {
            foreach ($newrow as $key => $value) {
                $num = count($value["pinganid"],true);
                if (intval($value['id']) == $k) {
                    if($num != count($v["pingan_id"])){
                        $new['bfzc'][$k] = $new['zc'][$k];
                        unset($new['zc'][$k]);
                        $bfbzc = array_diff($value["pinganid"],$v["pingan_id"]); //部分正常不正常部分
                        $bfzcs = array_intersect($value["pinganid"],$v["pingan_id"]); //部分正常正常部分
                        $new['bfzc'][$k]['pingan_bzcid'] = $bfbzc;
                        $new['bfzc'][$k]['pingan_zcid'] = $bfzcs;
//                       var_dump($new['bfzc'][$k]['pingan_zcid']);
//                      exit();
                    }
                }
            }
        }
    }
    if(isset($row)){
        foreach($row as $k=>$v){
            $bzc[$v[0]] = snameConfig($v[0]);
        }
    }

     if(isset($new['bfzc'])){
            foreach ($new['bfzc'] as $key => $value) {
                $bfzc[$key] = $value['sname'];
            }
        }

    $arr_bfzc = isset($bfzc)?implode(",",$bfzc):"";     //部分正常
    $arr_bzc = isset($bzc)?implode(",",$bzc):"";     //不正常
    sendmsgts($arr_bfzc,$arr_bzc);
}

/**
 * 平安通知设备
 */
function querysid(){
    global $db;
    $stmt = $db->prepare("select id,name,pinganid from wp_ischool_school where is_deleted = 0 and pinganid is not null and id not in(56731,56683)");
    $stmt->execute();
    $formdata2 = $formdata = $stmt->fetchAll();
    // var_dump($formdata);exit();
            $redis = getRedis();
            try {
                $redis->ping();
            } catch (Exception $e) {
                $redis = getRedis();
            }
            $redis->select(15);
            $rediskey = $redis->keys('*');

            $i=0;
                foreach ($formdata as $key => $value) {
                    foreach ($rediskey as $k => $v) {
                        if(substr($v,0,5) == $value['id']){
                            if(strlen($v) == 5){                //暂未位置信息
                                $new['zcmwz'][$value['id']]['sid'] = $value['id'];
                                $new['zcmwz'][$value['id']]['sname'] = $value['name'];
        //                      $new['zcmwz'][$value['id']]['pingan_id'][$i] = substr($v,5,2);
                            }else{
                                $new['zc'][$value['id']]['sid'] = $value['id'];
                                $new['zc'][$value['id']]['sname'] = $value['name'];
                                $new['zc'][$value['id']]['pingan_id'][$i] = substr($v,5,2);
                            }
                            unset($formdata[$key]);
                        }
                        $i++;
                    }
                }
        if(isset($new['zc'])){
            foreach ($new['zc'] as $k => $v) {
                foreach ($formdata2 as $key => $value) {
                    $num = count(json_decode($value["pinganid"],true));
                    if (intval($value['id']) == $k) {
                        if($num != count($v["pingan_id"])){
                            $new['bfzc'][$k] = $new['zc' ][$k];
                            unset($new['zc'][$k]);
                        }
                    }
                }
            } 
        }
             //$new['zc']    //正常
            // var_dump($new['zcmwz']); //正常没位置
            // var_dump($new['bfzc']); //部分正常
            // var_dump($formdata); //全不正常
     if(isset($new['bfzc'])){
            foreach ($new['bfzc'] as $key => $value) {
                $bfzc[$key] = $value['sname'];
            }
        }
    if(isset($formdata)){
            foreach ($formdata as $key => $value) {
                $bzc[$value['id']] = $value['name'];
            }
     }
            
    $arr_bfzc = isset($bfzc)?implode(",",$bfzc):"";     //部分正常
    $arr_bzc = isset($bzc)?implode(",",$bzc):"";     //不正常
   // var_dump($arr_bzc);
   // exit();
            sendmsgt($arr_bfzc,$arr_bzc);
} 

function sendmsgts($bfzc,$bzc){
    // oUMeDwHY58TN7eGHRMYabhEzOvAg 张豪openid  oUMeDwLBklMzOqyGuxhuA-Pmzsu0 杨茫
    // $tos = ["oUMeDwLBklMzOqyGuxhuA-Pmzsu0","oUMeDwC5bsoGmgX6mC8qk3gzPnu8"];
    $tos = ["oUMeDwLBklMzOqyGuxhuA-Pmzsu0"];
    $data['title'] = "来自正梵智慧校园的亲情电话设备信息！";
    $data['content'] = !empty($bfzc)?$bfzc."设备部分运行不正常,":"";
    $data['content'].= !empty($bzc)?$bzc."设备运行不正常,":"";
    $data['content'] = !empty($data['content'])?$data['content']."请您及时查看解决！":"设备全部正常运行！";
    $data['url'] = "";
    $result = broadMsgToManyUsers($tos,$data);
    // var_dump($result);
}

function sendmsgt($bfzc,$bzc){
    // oUMeDwHY58TN7eGHRMYabhEzOvAg 张豪openid  oUMeDwLBklMzOqyGuxhuA-Pmzsu0 杨茫
    $tos = ["oUMeDwLBklMzOqyGuxhuA-Pmzsu0"];
    $data['title'] = "来自正梵智慧校园的设备信息！";
    $data['content'] = !empty($bfzc)?$bfzc."设备部分运行不正常,":"";
    $data['content'].= !empty($bzc)?$bzc."设备运行不正常,":"";
    $data['content'] = !empty($data['content'])?$data['content']."请您及时查看解决！":"设备全部正常运行！";
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
                               "value":"' . date("Y年m月d日H时i分s秒") . '\n",
                               "color":"#000000"
                           },
                          "remark":{
                               "value":"正梵智慧校园感谢您的支持。",
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
qqdh();
querysid();

<?php
require_once __DIR__ . "/sendSmartWeiXin.class.php";
function getMsg($position, $status, $sid, $db) {

	$stmt = $db->prepare("select * from wp_ischool_school  where  id=?");
	$stmt->execute([$sid]);
	$ret = $stmt->fetch();
	//return $ret;
	$temp = $ret['name'];
	if ($sid == 56650) {
		if ($position == "03" || $position =='05' || $position =='08') {
			$temp .= "东校区南门";
		} else if($position == "04") 
		{
			$temp .= "东校区西门";
		}else if($position=="11" || $position=="12" || $position=="13" || $position=="16" || $position=="17"){
		  	$temp .= "东校区";
		}
		else
		{
			$temp .= "西校区";
		}

	}
	if (substr($position,0,1) == "0") {
		if ($status == "01") {
			$temp .= "进校";
		} else if ($status == "02") {
			$temp .= "出校";
		}
	}
	if (substr($position,0,1) == "1") {
		if ($status == "01") {
			$temp .= "进宿舍";
		} else if ($status == "02") {
			$temp .= "出宿舍";
		}
	}
	if (substr($position,0,1) == "2") {
		if ($status == "01") {
			$temp .= "进餐厅";
		} else if ($status == "02") {
			$temp .= "出餐厅";
		}
	}

	/*
		if ($position == "01" || $position == "02") {
			$temp .= "学校大门";
		} else if ($position == "11") {
			$temp .= "学校宿舍";
	*/

	return $temp;
}
function getStudentInfo($db, $sid, $cardid) {
	if($sid == 56770){
                $stmts = $db->prepare("SELECT stuno FROM wp_ischool_epc WHERE epc0 = :epc or epc1 = :epc or epc2 = :epc or epc3 = :epc or epc4 = :epc or epc5 = :epc");
                $stmts->execute([':epc'=>$cardid]);
        	$rets = $stmts->fetch();
                $stmt = $db->prepare("select id,name,school,enddatepa,cid from wp_ischool_student where stuno2=? ");
                $stmt->execute([ $rets['stuno']]);
                $ret = $stmt->fetch();
	        return $ret;
        }else
        {
		$stmt = $db->prepare("select id,name,school,enddatepa,cid from wp_ischool_student where  cardid=? ");
		$stmt->execute([$cardid]);
		$ret = $stmt->fetch();
		return $ret;
	}
}

/**
 * 班主任openid
 */
function getTopenByCid($db, $cid) {
	if (empty($cid)) {
		return 0;
	}
	$stmt = $db->prepare("select distinct openid from wp_ischool_teaclass where cid = ?");
	$stmt->execute([$cid]);
	$ret = $stmt->fetchAll();
	return $ret;
}

function getParOpenidByStuid($db, $stuid) {
	$stmt = $db->prepare("select distinct openid from wp_ischool_pastudent where stu_id= ?");
	$stmt->execute([$stuid]);
	$ret = $stmt->fetchAll();
	return $ret;
}

function getDb() {
	$pdo = new PDO("mysql:host=127.0.0.1;dbname=ischool", "root", 'hnzf123456', array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
	return $pdo;
}
function getSchoolPic($db, $sid) {
	$stmt = $db->prepare("select toppic from wp_ischool_picschool where schoolid = ?");
	$stmt->execute([$sid]);
	$ret = $stmt->fetch();
	if ($ret && $ret['toppic']) {
		return $ret['toppic'];
	} else {
		return "http://www.henanzhengfan.com/ischool/upload/syspic/msg.jpg";
	}
}
function addSource($source, $db) {
	$stmt = $db->prepare("insert into wp_safecard_source (source,created) values (?,?)");
	$stmt->execute([$source, time()]);

}
// msg reprents info
function addSafeMsgIntoDb($stuid, $time, $msg, $db) {
	$stmt = $db->prepare("select * from wp_ischool_safecard where stuid = ? and info = ? and ctime = ?");
	$stmt->execute([$stuid, $msg, $time]);
	$ret = $stmt->fetch();
	if (empty($ret)) {
		$stmt1 = $db->prepare("insert into wp_ischool_safecard (stuid,info,ctime,yearmonth,yearweek,weekday,receivetime)values(
			:stuid,:info,:ctime,:yearmonth,:yearweek,:weekday,:receivetime)");
		$stmt1->execute([
			":stuid" => $stuid,
			":info" => $msg,
			":ctime" => $time,
			":yearmonth" => date("ym"),
			":yearweek" => date("yW"),
			":weekday" => date("w"),
			":receivetime" => time(),
		]);
	}
}
// main($sid, $position, $status, $epc, $time);

function main($sid, $position, $status, $cardid, $timeinfo, $db) {
	//$sid = 56651;
	//$cardid = "E2005120370F02352020444E";
	/*
	        $explict_card_arr = [
	                "E20051203711021816606B26",
			"E200001673140149237024A1",
			"E2000016731402152320299A",
			"E200001673140208232029A2",
			"E20051203711022016606B27"
*/

	//$db = getDb();
	$info = getMsg($position, $status, $sid, $db);
	$stuinfo = getStudentInfo($db, $sid, $cardid);
	$cid = $stuinfo['cid'];
	$stuid = $stuinfo['id'];
	$stuname = $stuinfo['name'];
	$enddate = $stuinfo['enddatepa'];
	$explict_arr = [23, 0, 1, 2, 3, 4];
	$hour_explict = date("H");
	$stu_hook_time = strtotime($timeinfo);
	
	//$info = getMsg($position,$status);
	if ($info && $enddate) {
		if ($sid == 56683 || $sid == 56686 || $sid == 56687) {
			$parOpenid = getTopenByCid($db, $cid); //抓班主任openid，发信息
		} else {
			$parOpenids = getParOpenidByStuid($db, $stuid); //抓家长openid，发信息
		}
       	    	if(time() < $enddate && time() < $stu_hook_time+10800  && !in_array($hour_explict, $explict_arr)){
			foreach ($parOpenids as $row) {
				$picurl = getSchoolPic($db, $sid);
				$retMsg = sendWeiXin::sendSafe($row['openid'], $stuname, $info, strtotime($timeinfo), $picurl); //发信息
			//Jpush::push($row['openid'],$info);
				var_dump($retMsg);
				$res = $retMsg->errcode;
				if ($res != 0) {
					$logmsg = '返回码-' . $res . '返回值-' . $retMsg->errmsg;
				//$this->addErrorLog($stuid, $epctime, $info, $logmsg);
				}
		    	}
		}
		addSafeMsgIntoDb($stuid, strtotime($timeinfo), $info, $db);

	}
}

function getRedis() {
	$redis = new \redis();
	$redis->pconnect("127.0.0.1", 6379, 5); //连接redis
	//$redis->select(2); //2库
	return $redis;
}

//function fetchQueue() {
$conn_args = array(
	'host' => '127.0.0.1',
	'port' => 5672,
	'login' => 'guest',
	'password' => 'hnzf55030687',
	'vhost' => '/',
);
$e_name = 'smart';
$q_name = 'smart';
$k_route = 'smart';

$conn = new AMQPConnection($conn_args);
if (!$conn->connect()) {
	die('Cannot connect to the broker');
}
$channel = new AMQPChannel($conn);
$ex = new AMQPExchange($channel);
$ex->setName($e_name);
$ex->setType(AMQP_EX_TYPE_DIRECT);
$ex->setFlags(AMQP_DURABLE);

$q = new AMQPQueue($channel);
$q->setName($q_name);
$q->setFlags(AMQP_DURABLE); //持久化 
$q->declareQueue();
$q->bind($e_name, $k_route);
$db = getDb();

while (true) {
	$q->consume('callback');
	$channel->qos(0, 1);
}
$conn->disconnect();

function callback($envelope, $queue)
{
		global $db;
                //$res = $q->ack($arr->getDeliveryTag());
                //$info = $arr->getBody();
		$info = $envelope->getBody();
/*        if (strlen(intval($info)) == 5 || strlen(intval($info)) ==7) 
        {
        //      echo $info;
                if(strlen(intval($info)) ==7)
                {
                        $info = substr($info,0,6);
                }
                $redis = getRedis();
                try {
                        $redis->ping();
                } catch (Exception $e) {
                        $redis = getRedis();
                }
				$redis->select(2); //2库老设备监控用
                if ($redis->exists($info)) {
                        $redis->delete($info);
                }
                $redis->set($info, 6, 300); //将学校ID存入redis中 生存周期300秒
        }*/
		
		if (strlen(intval($info)) == 5 || strlen(intval($info)) ==7) 
		{                       
			$info = intval($info);
			$redis = getRedis();
                try {
                        $redis->ping();
                } catch (Exception $e) {
                        $redis = getRedis();
                }
                $redis->select(15); //15库新平安通知设备监控用
                if ($redis->exists($info)) {
                        $redis->delete($info);
                }
                $redis->set($info, 6, 300); //将学校ID存入redis中 生存周期300秒
        }

        preg_match_all("/ABAB[0-9A-Za-z]{56}BABA/", $info, $matches);
        foreach ($matches[0] as $row) 
        {
            addSource($row, $db);
            $sid = substr($row, 8, 6);

            $redis = getRedis();
            try {
                    $redis->ping();
            } catch (Exception $e) {
                    $redis = getRedis();
            }
			// $redis->select(2); //2老库设备监控用
   //          if ($redis->exists($sid)) {
   //                  $redis->delete($sid);
   //          }
   //          $redis->set($sid, 6, 300); //将学校ID存入redis中 生存周期300秒 跟心跳包一起判断设备状态是否正常


            $position = substr($row, 14, 2);
            $status = substr($row, 16, 2);
            $epc = substr($row, 18, 24);
            $time = substr($row, 42, 14);
            global $schoolid;
            $schoolid = substr($sid, 1, 5);

			$redis ->select(3);	//3库，存放EPC在两分钟之内的推送一条信息
			$rediskey = $redis->keys('*');
			$istwo = substr($row, 16, 26);//位置状态EPC号
			if(!in_array($istwo,$rediskey)){
			 	 main($sid, $position, $status, $epc, $time, $db);
			}else{
                 $redis->delete($istwo);
			}
			$redis->set($istwo, 8, 30);
        }
		
		foreach ($matches[0] as $row) 
		{
			$sid = substr($row, 9,5);
            $sid2 = substr($row, 9,7);
            $redis = getRedis();
            try {
                    $redis->ping();
            } catch (Exception $e) {
                    $redis = getRedis();
            }
            $redis->select(15); //15库新设备监控用
            if ($redis->exists($sid2)) 
            {
               	 $redis->delete($sid2);
		 		 $redis->set($sid2, 6, 300); //将学校ID存入redis中,生存周期300秒，跟心跳包一起判断设备状态是否正常
            }elseif ($redis->exists($sid)) 
            {
            	$redis->delete($sid);
            	$redis->set($sid, 6, 300);
            }else{
            	$redis->set($sid2, 6, 300);
            }
 	    }


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


        if (empty($matches[0])) {
                // echo $info; 
                $sendUrl = sendWeiXin::getUrl('mb');
                // var_dump($sendUrl);
                $result = sendWeiXin::singlePostMsg($sendUrl, $info);
                // var_dump($result);
        }
		$queue->ack($envelope->getDeliveryTag());
}

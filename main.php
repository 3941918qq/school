<?php
//
require_once __DIR__ . "/sendWeiXin.class.php";
function getMsg($position, $status) {
	$temp = "";
	if ($position == "01" || $position == "02") {
		if ($status == "01") {
			$temp .= "进校";
		} else if($status == "02"){
			$temp .= "出校";
		}
	}
	if ($position == "11") {
                if ($status == "01") {
                        $temp .= "进宿舍";
                } else if($status == "02") {
                        $temp .= "出宿舍";
                }
        }
	
	/*
	if ($position == "01" || $position == "02") {
		$temp .= "学校大门";
	} else if ($position == "11") {
		$temp .= "学校宿舍";
	} */

	return $temp;
}
function getStudentInfo($db, $sid, $cardid) {
	if($sid == 56683 ) 
	{
		//56683,56686,56687
		$stmt = $db->prepare("select id,name,school,enddatepa from wp_ischool_student where sid in (56683,56686,56687) and cardid=?");
       		$stmt->execute([ $cardid]);
        	$ret = $stmt->fetch();
        	return $ret;
	}elseif($sid == 56770){
		$stmts = $db->prepare("SELECT stuno FROM wp_ischool_epc WHERE epc0 = :epc or epc1 = :epc or epc2 = :epc or epc3 = :epc or epc4 = :epc or epc5 = :epc");
   		$stmts->execute([':epc'=>$cardid]);
    	$rets = $stmts->fetch();
		$stmt = $db->prepare("select id,name,school,enddatepa from wp_ischool_student where stuno2=?");
		$stmt->execute([ $rets['stuno']]);
		$ret = $stmt->fetch();
    	return $ret;
	}else
	{
	        $stmt = $db->prepare("select id,name,school,enddatepa from wp_ischool_student where sid=? and cardid=?");
	        $stmt->execute([$sid, $cardid]);
        	$ret = $stmt->fetch();
        	return $ret;

	}
}

function getParOpenidByStuid($db, $stuid) {
	$stmt = $db->prepare("select distinct openid from wp_ischool_pastudent where stu_id= ?");
	$stmt->execute([$stuid]);
	$ret = $stmt->fetchAll();
	return $ret;
}

function getDb() {
	$pdo = new PDO("mysql:host=122.114.51.145;dbname=ischool", "ischool", '!@#$ASDvgrrz7*9jqB',array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
	$pdo->setAttribute( PDO::ATTR_ERRMODE , PDO::ERRMODE_EXCEPTION );
        $pdo->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE , PDO::FETCH_ASSOC);
	return $pdo;
}
function getSchoolPic($db, $sid) {
	$stmt = $db->prepare("select toppic from wp_ischool_picschool where schoolid = ?");
	$stmt->execute([$sid]);
	$ret = $stmt->fetch();
	if ($ret && $ret['toppic']) {
		return $ret['toppic'];
	} else {
		return  "http://www.henanzhengfan.com/ischool/upload/syspic/msg.jpg";
	}
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
		echo date("Y-m-d H:i:s")." ".$stuid.$msg."\n";
	}
}
// main($sid, $position, $status, $epc, $time);

function main($sid,$position,$status,$cardid,$timeinfo,$db) {
	//$sid = 56651;
	//$cardid = "E2005120370F02352020444E";
        $explict_card_arr = [
                "E20051203711021816606B26",
		"E200001673140149237024A1",
		"E2000016731402152320299A",
		"E200001673140208232029A2",
		"E20051203711022016606B27"
        ];

	//$db = getDb();
	if(!$db) $db = getDb();
	$info = getMsg($position,$status);
	if(in_array(strtoupper($cardid),$explict_card_arr )) 
	{
		 $stuinfo = getStudentInfo($db,56651 , $cardid);
		 $info = $sid.$info;
	}
	else {
		$stuinfo = getStudentInfo($db, $sid, $cardid);
		
	}
	$stuid = $stuinfo['id'];
	$stuname = $stuinfo['name'];
	$enddate = $stuinfo['enddatepa'];
	//$info = getMsg($position,$status);
	if ($info && $enddate && time() < $enddate) {
		$parOpenids = getParOpenidByStuid($db, $stuid); //抓家长openid，发信息
		//var_dump($parOpenids);
		foreach ($parOpenids as $row) {
			$picurl = getSchoolPic($db,$sid);
			$retMsg = sendWeiXin::sendSafe($row['openid'], $stuname, $info, strtotime($timeinfo), $picurl); //发信息
			$res = $retMsg->errcode;
			if ($res != 0) {
				$logmsg = '返回码-' . $res . '返回值-' . $retMsg->errmsg;
				//$this->addErrorLog($stuid, $epctime, $info, $logmsg);
			}
		}
	}
	if($stuid)addSafeMsgIntoDb($stuid, strtotime($timeinfo), $info, $db);
}

function getRedis(){
	$redis = new \redis();
	$redis->pconnect("127.0.0.1", 6379, 5);//连接redis
	$redis->select(2);		//2库
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
	$e_name = 'weixin';
	$q_name = 'weixin';
	$k_route = 'weixin';

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
	$q->bind($e_name, $k_route);
	$db = getDb();
while(true) {
$arr = $q->get();
	if($arr) {
		if(!$db) $db = getDb();
		$res = $q->ack($arr->getDeliveryTag());
		$info = $arr->getBody();
                if(strlen(intval($info)) == 5){
                        $redis = getRedis();
                        try {
                                $redis->ping();
                        } catch (Exception $e) {
                                $redis = getRedis();
                        }

                        if($redis->exists($info)){
                                $redis->delete($info);
                        }
                        $redis->set($info,6,300);                       //将学校ID存入redis中 生存周期300秒
                }

		preg_match_all("/ABAB[0-9A-Za-z]{56}BABA/", $info, $matches);
		foreach ($matches[0] as $row) {
			$sid = substr($row, 8, 6);
			$position = substr($row, 14, 2);
			$status = substr($row, 16, 2);
			$epc = substr($row, 18, 24);
			$time = substr($row, 42, 14);
			main($sid, $position, $status, $epc, $time,$db);
		}
	}
}
//}
$conn->disconnect();



















	

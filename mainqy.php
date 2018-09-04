<?php
//
require_once __DIR__ . "/sendQiYe.class.php";
function getMsg($position, $status) {
	$temp = "";
	if ($position == "01" || $position == "02") {
		if ($status == "01") {
			$temp .= "进厂";
		} else if($status == "02"){
			$temp .= "出厂";
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
	$explict_card_arr = [
		"E20051203711021816606B26"
	];
	if(in_array(strtoupper($cardid),$explict_card_arr ))
	{
		$stmt = $db->prepare("select id,name,school from wp_ischool_student where sid=? and cardid=?");
		$stmt->execute([56651, $cardid]);
		$ret = $stmt->fetch();
		return $ret;
	}else 
	{
                $stmt = $db->prepare("select id,name,school from wp_ischool_student where sid=? and cardid=?");
                $stmt->execute([$sid, $cardid]);
                $ret = $stmt->fetch();
                return $ret;

	}
}

function getParOpenidByStuid($db, $stuid) {
	$stmt = $db->prepare("select distinct openid from wp_ischool_user where last_stuid= ?");
	$stmt->execute([$stuid]);
	$ret = $stmt->fetchAll();
	return $ret;
}

function getDb() {
	$pdo = new PDO("mysql:host=122.114.51.145;dbname=qiyeischool", "ischool", '!@#$ASDvgrrz7*9jqB',array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
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
		return  "http://qiye.henanzhengfan.com/img/msg.jpg";
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
	}
}
// main($sid, $position, $status, $epc, $time);

function main($sid,$position,$status,$cardid,$timeinfo) {
	//$sid = 56651;
	//$cardid = "E2005120370F02352020444E";
	$db = getDb();
	$stuinfo = getStudentInfo($db, $sid, $cardid);
	$stuid = $stuinfo['id'];
	$stuname = $stuinfo['name'];
//	$enddate = $stuinfo['enddatepa'];
	//$timeinfo = "20170526151412";
	$info = getMsg($position,$status);
	if ($info) {
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
		addSafeMsgIntoDb($stuid, strtotime($timeinfo), $info, $db);
	}
}

//function fetchQueue() {
	$conn_args = array(
		'host' => '127.0.0.1',
		'port' => 5672,
		'login' => 'guest',
		'password' => 'hnzf55030687',
		'vhost' => '/',
	);
	$e_name = 'qiye';
	$q_name = 'qiye';
	$k_route = 'qiye';

	$conn = new AMQPConnection($conn_args);
	if (!$conn->connect()) {
		die('Cannot connect to the broker');
	}
	$channel = new AMQPChannel($conn);
	$ex = new AMQPExchange($channel);
	$ex->setName($e_name);
	$ex->setType(AMQP_EX_TYPE_DIRECT);
	$ex->setFlags(AMQP_DURABLE );
	$ex->declareExchange();

	$q = new AMQPQueue($channel);
	$q->setName($q_name);
	$q->setFlags(AMQP_DURABLE | AMQP_AUTODELETE );  
	$q->declareQueue();
	$q->bind($e_name, $k_route);

while(true) {
	$arr = $q->get();
	if ($arr) {
		$res = $q->ack($arr->getDeliveryTag());
		$info = $arr->getBody();
		preg_match_all("/ABAB[0-9A-Za-z]{56}BABA/", $info, $matches);
		
		
		foreach ($matches[0] as $row) {
			$sid = substr($row, 8, 6);
			$position = substr($row, 14, 2);
			$status = substr($row, 16, 2);
			$epc = substr($row, 18, 24);
			$time = substr($row, 42, 14);
			main($sid, $position, $status, $epc, $time);
		}
	}
}
//}
$conn->disconnect();



















	

<?php
//
require_once __DIR__ . "/sendWeiXin.class.php";
function getMsg($position, $status) {
	$temp = "";
	if ($status == "01") {
		$temp .= "进入";
	} else {
		$temp .= "外出";
	}

	if ($position == "01") {
		$temp .= "大门";
	} else if ($position == "02") {
		$temp .= "小门";
	} else {
		$temp .= "宿舍";
	}

	return $temp;
}
function getStudentInfo($db, $sid, $cardid) {
	$stmt = $db->prepare("select id,name,school,enddatepa from wp_ischool_student where sid=? and cardid=? order by id desc");
	$stmt->execute([$sid, $cardid]);
	$ret = $stmt->fetch();
	return $ret;
}

function getParOpenidByStuid($db, $stuid) {
	var_dump($stuid);
	$stmt = $db->prepare("select distinct openid from wp_ischool_pastudent where stu_id= ?");
	$stmt->execute([$stuid]);
	$ret = $stmt->fetchAll();
	return $ret;
}

function getDb() {
	$pdo = new PDO("mysql:host=127.0.0.1;dbname=ischool", "root", 'hnzf123456',array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
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
	}
}
// main($sid, $position, $status, $epc, $time);

function main($sid,$position,$status,$cardid,$timeinfo) {
	//$sid = 56651;
	//$cardid = "E2005120370F02352020444E";
	$db = getDb();
	$stuinfo = getStudentInfo($db, $sid, $cardid);
	var_dump($stuinfo);
	$stuid = $stuinfo['id'];
	$stuname = $stuinfo['name'];
	$enddate = $stuinfo['enddatepa'];
	echo "///////////////";
	echo $enddate;
	echo "//////////////";
	//$timeinfo = "20170526151412";
	$info = getMsg($position,$status);
	
	if ($enddate && time() < $enddate) {
		$parOpenids = getParOpenidByStuid($db, $stuid); //抓家长openid，发信息
		var_dump($parOpenids);
		/*
		foreach ($parOpenids as $row) {
			$picurl = getSchoolPic($db,$sid);
			$retMsg = sendWeiXin::sendSafe($row['openid'], $stuname, $info, strtotime($timeinfo), $picurl); //发信息
			$res = $retMsg->errcode;
			var_dump($retMsg);
			if ($res != 0) {
				$logmsg = '返回码-' . $res . '返回值-' . $retMsg->errmsg;
				//$this->addErrorLog($stuid, $epctime, $info, $logmsg);
			}
		}*/
		//addSafeMsgIntoDb($stuid, strtotime($timeinfo), $info, $db);
	}
}

//function fetchQueue() {
	/*$conn_args = array(
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

	$arr = $q->get();
	if ($arr) {
		$res = $q->ack($arr->getDeliveryTag());
		$info = $arr->getBody();
		preg_match_all("/ABBA[0-9A-Z]{56}BABA/", $info, $matches);
		
		foreach ($matches[0] as $row) {
			$sid = substr($row, 8, 6);
			$position = substr($row, 14, 2);
			$status = substr($row, 16, 2);
			$epc = substr($row, 18, 24);
			$time = substr($row, 42, 14);
			main($sid, $position, $status, $epc, $time);
		}
	}
//}
$conn->disconnect();
*/
		$info = "ABAB00480567440102e20000167314011923102807201706191125331111BABA";
		//$info = "ABAB00480567400101e20051799806010817506373201704181257521111BABA";
                preg_match_all("/ABAB[0-9A-Za-z]{56}BABA/", $info, $matches);
                
		var_dump($matches);
                foreach ($matches[0] as $row) {
                        $sid = substr($row, 8, 6);
                        $position = substr($row, 14, 2);
                        $status = substr($row, 16, 2);
                        $epc = substr($row, 18, 24);
                        $time = substr($row, 42, 14);
                        main($sid, $position, $status, $epc, $time);
                }





























	

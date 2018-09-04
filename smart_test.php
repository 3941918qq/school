<?php
require_once __DIR__ . "/sendSmartWeiXinclass.php";
function getMsg($position, $status, $sid, $db) {

	$stmt = $db->prepare("select * from wp_ischool_school  where  id=?");
	$stmt->execute([$sid]);
	$ret = $stmt->fetch();
	//return $ret;
	$temp = $ret['name'];
	if ($sid == 56650) {
		if ($position == "03") {
			$temp .= "东校区";
		} else if($position == "04") 
		{
			$temp .= "东校区西门";
		}
		else
		{
			$temp .= "西校区";
		}

	}
	if ($position == "01" || $position == "02" || $position == "03" || $position == "04") {
		if ($status == "01") {
			$temp .= "进校";
		} else if ($status == "02") {
			$temp .= "出校";
		}
	}
	if ($position == "11") {
		if ($status == "01") {
			$temp .= "进宿舍";
		} else if ($status == "02") {
			$temp .= "出宿舍";
		}
	}
	if ($position == "21") {
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
	$stmt = $db->prepare("select id,name,school,enddatepa,cid from wp_ischool_student where  cardid=? ");
	$stmt->execute([$cardid]);
	$ret = $stmt->fetch();
	return $ret;
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
	//$info = getMsg($position,$status);
	if ($info && $enddate && time() < $enddate) {
		if ($sid == 56683 || $sid == 56686 || $sid == 56687) {
			$parOpenid = getTopenByCid($db, $cid); //抓班主任openid，发信息
		} else {
			$parOpenids = getParOpenidByStuid($db, $stuid); //抓家长openid，发信息
		}
		foreach ($parOpenids as $row) {
			$picurl = getSchoolPic($db, $sid);
			$retMsg = sendWeiXin::sendSafe($row['openid'], $stuname, $info, strtotime($timeinfo), $picurl); //发信息
			$res = $retMsg->errcode;
			var_dump($retMsg);
			if ($res != 0) {
				$logmsg = '返回码-' . $res . '返回值-' . $retMsg->errmsg;
				//$this->addErrorLog($stuid, $epctime, $info, $logmsg);
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
/*
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
*/
$db = getDb();
$info = "ABAB00480566500402e2000015800901140700d197201710201813021111BABAABAB00480566500401e2000015800901140700d197201710201816511111BABA";

                //$res = $q->ack($arr->getDeliveryTag());
                preg_match_all("/ABAB[0-9A-Za-z]{56}BABA/", $info, $matches);
		var_dump($matches);
                foreach ($matches[0] as $row) {

                        $sid = substr($row, 8, 6);
                        $position = substr($row, 14, 2);
                        $status = substr($row, 16, 2);
                        $epc = substr($row, 18, 24);
                        $time = substr($row, 42, 14);
                        $schoolid = substr($sid, 1, 5);
			main($sid, $position, $status, $epc, $time, $db);
                }

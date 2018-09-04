<?php
require_once __DIR__ . "/sendSmartWeiXin.class.php";
function getMsg($position, $status,$sid,$db) {
	
	$stmt = $db->prepare("select * from wp_ischool_school  where  id=?");
        $stmt->execute([$sid]);
        $ret = $stmt->fetch();
        //return $ret;
	$temp = $ret['name'];
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
	$stmt = $db->prepare("select id,name,school,enddatepa from wp_ischool_student where  cardid=? ");
     	$stmt->execute([ $cardid]);
        $ret = $stmt->fetch();
        return $ret;
}
	

function getParOpenidByStuid($db, $stuid) {
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
function addSource($source,$db)
{
	$stmt = $db->prepare("insert into wp_safecard_source (source,created) values (?,?)");
	$stmt ->execute([$source,time()]);
	
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

function main($sid,$position,$status,$cardid,$timeinfo,$db) {
	//$sid = 56651;
	//$cardid = "E2005120370F02352020444E";
	/*
        $explict_card_arr = [
                "E20051203711021816606B26",
		"E200001673140149237024A1",
		"E2000016731402152320299A",
		"E200001673140208232029A2",
		"E20051203711022016606B27"
        ];*/

	//$db = getDb();
	$info = getMsg($position,$status, $sid, $db);
	$stuinfo = getStudentInfo($db, $sid, $cardid);
	var_dump($info);
	var_dump($stuinfo);
		
	$stuid = $stuinfo['id'];
	$stuname = $stuinfo['name'];
	$enddate = $stuinfo['enddatepa'];
	//$info = getMsg($position,$status);
	if ($info && $enddate && time() < $enddate) {
		$parOpenids = getParOpenidByStuid($db, $stuid); //抓家长openid，发信息
		var_dump($parOpenids);
		foreach ($parOpenids as $row) {
			$picurl = getSchoolPic($db,$sid);
			//$retMsg = sendWeiXin::sendSafe($row['openid'], $stuname, $info, strtotime($timeinfo), $picurl); //发信息
			//$res = $retMsg->errcode;
			//if ($res != 0) {
			//	$logmsg = '返回码-' . $res . '返回值-' . $retMsg->errmsg;
				//$this->addErrorLog($stuid, $epctime, $info, $logmsg);
			//}
		}
		//addSafeMsgIntoDb($stuid, strtotime($timeinfo), $info, $db);
	}
}
		
		$info = "ABAB00480566690101E200001673140149237024A1201704060918501111BABA";
		echo $info;
		$db = getDb();
		preg_match_all("/ABAB[0-9A-Za-z]{56}BABA/", $info, $matches);
		foreach ($matches[0] as $row) {
			echo "worked\n";
			$sid = substr($row, 8, 6);
			$position = substr($row, 14, 2);
			$status = substr($row, 16, 2);
			$epc = substr($row, 18, 24);
			$time = substr($row, 42, 14);
			main($sid, $position, $status, $epc, $time,$db);
		}
		if (empty($matches[0])) {
			//var_dump($info);
			$sendUrl = sendWeiXin::getUrl('mb');
			$result = sendWeiXin::singlePostMsg($sendUrl, $info);
			var_dump($result);
		}
		




















	

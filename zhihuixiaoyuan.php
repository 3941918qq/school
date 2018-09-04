<?php
//require_once "/data/lib/push.php";
require_once "/data/lib/push.php";
function getUrl($type = "kf",$sid) {
	$access_token = getAccessToken($sid);
	$COM_KF_URL = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=";
	$COM_MB_RUL = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=";
	if ($type == 'kf') {
		$url = $COM_KF_URL . $access_token;
	} else {
		$url = $COM_MB_RUL . $access_token;
	}
	return $url;
}
function getRedis() {
	$redis = new \redis();
	$redis->pconnect("127.0.0.1", 6379, 5);
	return $redis;
}
function getAccessToken($sid) {
    	//global $sid;
	if($sid=='56650'){
	  $appId = "wxc5c7e311f8d5d759";
          $appSecret = "e6ccb6b6817cfe5e9c58bc360b0a05b7";
	}else{
	  $appId = "wx8c6755d40004036d";
          $appSecret = "22f68f4da5b36641ed492c596406b75f";
	}	   
   	var_dump($appId );
	$token_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appId . "&secret=" . $appSecret;

	$redis = getRedis();
	if ($redis) {
		if($sid=='56650'){
			$acc_token = $redis->get("my_access_token_one");
			if (!$acc_token) {
				$json = file_get_contents($token_url);
				$result = json_decode($json);
				$acc_token = $result->access_token;
				$redis->set("my_access_token_one",$acc_token, 7100);
			}

		}else{
			$acc_token = $redis->get("my_access_token");
			if (!$acc_token) {
				$json = file_get_contents($token_url);
				$result = json_decode($json);
				$acc_token = $result->access_token;
				$redis->set("my_access_token", $acc_token, 7100);
			}
		}
		
		
	} else {
		$json = file_get_contents($token_url);
		$result = json_decode($json);
		$acc_token = $result->access_token;
	}

	return $acc_token;
}
function singlePostMsg($url, $data) {
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HEADER, FALSE);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
	curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
	$result = curl_exec($curl);
	if (curl_errno($curl)) {
		return array("errcode" => -1, "errmsg" => '发送错误号' . curl_errno($curl) . '错误信息' . curl_error($curl));
	}
	curl_close($curl);
	return $result;
}

$conn_args = array(
	'host' => '127.0.0.1',
	'port' => 5672,
	'login' => 'guest',
	'password' => 'hnzf55030687',
	'vhost' => '/',
);
$e_name = 'school';
$q_name = 'school';
$k_route = 'school';

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

while (true) {
	$arr = $q->get();
	if ($arr) {
		var_dump($arr);
		$res = $q->ack($arr->getDeliveryTag());
		$info = $arr->getBody();
		
	    	$info1 =json_decode($info,true);
	    	$openid = $info1['touser'];
		Jpush::push($openid,"您有一条新消息，请注意查收。");
		$fives = substr($openid,0,5);
		if($fives == 'okr7G'){
		  $sid = '56650';
		}
		else $sid = 56744;
		// $url=trim($info1['news']['articles'][0]['url']);
		// $url=explode('&',$url);
		$sendUrl = getUrl('kf',$sid);
		if(isset($info1['template_id'])){
			$sendUrl = getUrl('tp',$sid);
		}
		//global $sid;
		//$sid=substr($url[1],4,5);
		//$sendUrl = getUrl('kf',$sid);
		
		$result = singlePostMsg($sendUrl,$info);
		var_dump($result);
	}
}
$conn->disconnect();

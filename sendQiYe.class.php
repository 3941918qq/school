<?php
class sendWeiXin {

	static $COM_KF_URL = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=";
	static $COM_MB_RUL = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=";
	static $COM_PIC_URL = "/upload/syspic/msg.jpg";
	static $temp_id_0 = "4QUptgSBYncUlS1kOA4Hx6Q5bso4leyfS6ZblLEN3rM";
	static $temp_id_1 = "DW69CLCSwKZNgerIjvQduoOiDzehc20fvOODR59UxO4";
	static function getUrl($type) {
		$access_token = self::getAccessToken();
		if ($type == 'kf') {
			$url = self::$COM_KF_URL . $access_token;
		} else {
			$url = self::$COM_MB_RUL . $access_token;
		}
		return $url;
	}
	static function getAccessToken() {
		$appId = "wx5c4d151389b92441";
		$appSecret = "0593bafd8d82f824a6958f140a25507a";
		$token_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appId . "&secret=" . $appSecret;

		$redis = new redis();
		$result = $redis->connect('127.0.0.1', 6379);
		if ($result) {
			$acc_token = $redis->get("my_access_token");
			if (!$acc_token) {
				$json = file_get_contents($token_url);
				$result = json_decode($json);
				$acc_token = $result->access_token;
				$redis->set("my_access_token", $acc_token, 7100);
			}

		} else {
			$json = file_get_contents($token_url);
			$result = json_decode($json);
			$acc_token = $result->access_token;
		}

		return $acc_token;
	}
	static function createNewsMsg($openid, $title, $content, $url, $picurl) {
		if (empty($picurl) || $picurl == "") {
			$picurl = C("URL_PATH") . self::$COM_PIC_URL;
		}
		return '{
            "touser":"' . $openid . '",
            "msgtype":"news",
            "news":
                {
                  "articles":[
                    {
                      "title":"' . $title . '",
                      "description":"' . $content . '",
                      "url":"' . $url . '",
                      "picurl":"' . $picurl . '"
                    }
                  ]
                }
            }';
	}
	static function getTempid($index) {
		if ($index % 2 == 0) {
			return self::$temp_id_0;
		} else {
			return self::$temp_id_1;
		}

	}
	static function singlePostMsg($url, $data) {

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
	static public function sendSafe($to, $stuname, $msg, $timeinfo, $picurl) {
		$result = json_decode('{"errcode":0}');
		if (!empty($to)) {
			$inoutDate = date("Y年m月d日H时i分s秒", $timeinfo);
			$title = "推送消息";
			$content = "您于" . $inoutDate . "有一条" . $msg . "信息。\\n\\t\\n点击【首页】->【平安通知】查看进出信息";
			$url = self::getUrl('mb');
			$random_num = random_int(1, 2);
			$tempId = self::getTempid($random_num);
			$data = self::createTempMsg($to, $tempId, $title, $content);
			$result = json_decode(self::singlePostMsg($url, $data));
			var_dump($result);
			//self::resetTempNum($tempId, 1);
		}

		return $result;
	}

	static function createTempMsg($openid, $tempid, $title, $content, $url = "") {
		return '{
                       "touser":"' . $openid . '",
                       "template_id":"' . $tempid . '",
                       "url":"' . $url . '",
                       "topcolor":"#FF6666",
                       "data":{
                           "first":{
                               "value":"' . $title . '\n",
                               "color":"#000000"
                           },
                            "keyword1":{
                               "value":"' . $content . '\n",
                               "color":"#000000"
                           },
                            "keyword2":{
                               "value":"系统管理员\n",
                               "color":"#000000"
                           },
                            "keyword3":{
                               "value":"' . date("Y年m月d日H时i分s秒") . '\n",
                               "color":"#000000"
                           },
                          "remark":{
                               "value":"",
                               "color":"#000000"
                           }
                       }
              }';
	}
}

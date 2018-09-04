<?php

function getDb() {
        $pdo = new PDO("mysql:host=127.0.0.1;dbname=ischool", "root", 'hnzf123456',array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
        $pdo->setAttribute( PDO::ATTR_ERRMODE , PDO::ERRMODE_EXCEPTION );
        $pdo->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE , PDO::FETCH_ASSOC);
        return $pdo;
}

//$db = getDb();
$serv = new Swoole\Websocket\Server("0.0.0.0", 9527);

$serv->on('workerstart', function($server, $id) {
	$pdo = new PDO("mysql:host=127.0.0.1;dbname=ischool", "root", 'hnzf123456',array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
        $pdo->setAttribute( PDO::ATTR_ERRMODE , PDO::ERRMODE_EXCEPTION );
        $pdo->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE , PDO::FETCH_ASSOC);
        //return $pdo;
	$server->db = $pdo;

});

$serv->on('Open', function($server, $req) {
    $server_info = $req->server;
    $token_string = $server_info['query_string'];
    echo $token_string;
    if($token_string != "token=a9abc4b26ad47da0d5b569f4bcc5713d")
    {
	$server->close($req->fd);
    }
    else {
	echo "client connect";
    }
});

$serv->on('Message', function($server, $frame) {
	if($frame->data == "start")
	{
		$limit = 10;
		$offset = 0;
		$time = time();
	        while(true){    
			$sql = "select id,source,FROM_UNIXTIME(created,'%Y-%m-%d %H:%i:%S') as time from wp_safecard_source where created > $time order by id asc  limit $offset,$limit";
			echo $sql."\n";
			$stmt = $server->db->prepare($sql);
			$stmt ->execute();
			$result = $stmt->fetchAll();
			$offset += count($result);
                        $ret = $server->push($frame->fd, json_encode($result));
			if(!$ret) {$server->close($frame->fd);break;}
			//$offset += count($result);
                        sleep(10);
	        }

	}
});

$serv->on('Close', function($server, $fd) {
    echo "connection close: ".$fd;
});

$serv->start();

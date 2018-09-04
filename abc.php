<?php
require_once "db.php";
header("Content-type: text/html; charset=uft8");
$file = fopen('./student2.csv','r');
$db = DatabaseUtils::getDatabase();
while ($data = fgetcsv($file)) { 
//每次读取CSV里面的一行内容
//print_r($data); //此为一个数组，要获得每一个数据，访问数组下标即可
//	$data = eval('return '.iconv('GB18030','utf-8',var_export($data,true)).';');
	$goods_list[] = $data;
}
//print_r($goods_list);

 foreach ($goods_list as $arr){
    if ($arr[0]!=""){
    	$stmt1 = $db->prepare("update wp_ischool_student set upendtimeqq = ? where id = ?");
        $stmt1->execute(array($arr[1],$arr[0]));
        echo $arr[1]."<br>";
    }
}
//echo iconv('gbk','utf-8',$goods_list[2][0]);
// echo $goods_list[1][0];
fclose($file);
?>
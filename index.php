<?php

$key = "*************";  //酷推key
$pwd = "*****"; //服务密码
$path = dirname(__FILE__).'/'; //账号信息和日志存放的路径，可自定义

error_reporting(0);
date_default_timezone_set("Etc/GMT-8");

// 导入乐心和小米模块
require($path . "lexin.php");
require($path . "xiaomi.php");

$apis = [new Lexin, new Xiaomi];
$fileList = [$path."Laccount.json", $path."Xaccount.json"];

if(php_sapi_name() == 'cli') { //判断是否以脚本方式运行
	$time=date('Y-m-d H:i:s',time());
	
	//乐心
	$apis[0]->key = $key;
	$Lresult = $apis[0]->mul_start($fileList[0]);

	//小米
	$apis[1]->key = $key;
	$Xresult = $apis[1]->mul_start($fileList[1]);
	
	//写入日志文件(倒序,前30天)
	$logFile = $path.'info.log';
	$log = file_get_contents($logFile);
	$endTag = strpos($log,date('Y-m-d',strtotime('-1 month')));
	if($endTag) $log = substr($log, 0 , $endTag);
	file_put_contents($logFile,$time."\n\n乐心：\n".$Lresult."\n\n小米：\n".$Xresult."\n\n\n\n".$log);

	exit;
} else if(isset($_REQUEST['flag'])) {

	$flag = $_REQUEST['flag'];
	$phone = $_REQUEST['phone'];
	$password = $_REQUEST['pwd'];
	$step = $_REQUEST['step'];
	$switch = (int)$_REQUEST['switch'];

	if($flag == '1') { // 自动，将账号信息写入文件
		$sevpwd=$_REQUEST['sevpwd'];
		if($sevpwd != $pwd){
			echo "{\"code\":\"404\",\"msg\":\"服务器密码错误\"}";
			exit;
		}
		$file = file_get_contents($fileList[$switch]);
		$data = json_decode($file, true);
		$data[$phone] = ['user'=>'user', 'phone'=>$phone, 'password'=>$password, 'step'=>$step];
		$account = json_encode($data);
		$result=file_put_contents($fileList[$switch],$account);

		$text = $result == False ? "{\"code\":\"405\",\"msg\":\"文件写入失败\"}" : "{\"code\":\"508\",\"msg\":\"账号添加成功\"}";
		echo $text;

	} else { //手动，直接刷步
		$result = $apis[$switch]->one_start($phone, $password, $step);
		echo $result;
	}

	exit;
 }
?>

<!doctype html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"> 
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>乐心刷步</title>
	<link rel="stylesheet" type="text/css" href="css/styles.css">
	<!--弹窗样式-->
	<link rel="stylesheet" type="text/css" href="css/xtiper.css">
</head>
<body>
<div class="htmleaf-container">
	<div class="wrapper">
		<div class="container">
			<h2 id="Dtitle">乐心刷步</h1>
			
			<form class="form" action="./" method="post" id='port_form'>
				<input type="text" placeholder="Phone" name="phone" id="phone">
				<input type="password" placeholder="Password" name="pwd" id="pwd">
				<input type="text" placeholder="step" name="step" id="step">
				<input type="hidden" name="flag" id='flag' value='0' id="flag">

				<button type='button' id="mul-button" style="width:130px">手动</button>
				<button type='button' id="auto-button" style="width:130px">自动</button>
			</form>
			<form class="form">
			<p>&nbsp;</p>
				<button type='button' id="switch" style="width:240px" value='0'>切换到小米刷步</button>
			</form>
		</div>
		<ul class="bg-bubbles">
			<li></li>
			<li></li>
			<li></li>
			<li></li>
			<li></li>
			<li></li>
			<li></li>
			<li></li>
			<li></li>
			<li></li>
		</ul>
	</div>
</div>
	<script src="https://cdn.bootcss.com/jquery/1.11.3/jquery.min.js"></script> 
	<script src="js/xtiper.min.js" type="text/javascript"></script>
	<script src="js/login.js"></script>
</body>
</html>

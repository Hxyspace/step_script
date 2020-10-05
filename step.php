#!/usr/bin/php
<?php

//error_reporting(0);
date_default_timezone_set("Etc/GMT-8");
//获取加秒数的时间戳
function getMillisecond() {
	list($t1, $t2) = explode(' ', microtime());
	return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
}
//post函数
function curl($url, $data='', $method='POST',$header){   
    $curl = curl_init(); // 启动一个CURL会话  
	$UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.121 Safari/537.36"; //浏览器UA
    curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址  
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查  
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在  
    curl_setopt($curl, CURLOPT_USERAGENT, $UA); // 模拟用户使用的浏览器  
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转  
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer  
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

    if($method=='POST'){  
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求  
        if ($data != ''){  
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包  
        }  
    }  
	curl_setopt($curl, CURLOPT_ENCODING ,'gzip');
    curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环  
    curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容  
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回  
    $tmpInfo = curl_exec($curl); // 执行操作  
    curl_close($curl); // 关闭CURL会话  
    return $tmpInfo; // 返回数据  
}  

function start($phone, $password, $setStep) {
	//登录的post，取cookie，Content-Type必须加上
	$header = [
		'Content-Type: application/json; charset=utf-8',
	];
	$url="sports.lifesense.com/sessions_service/login?systemType=2&version=4.6.7";
	$data = '{"appType":6,"clientId":"88888","loginName":"'.$phone.'","password":"'.md5($password).'","roleType":0}';
	$html = curl($url,$data,$method='POST',$header);
	$html=json_decode($html,true);
	//这两个参数下面的post需要
	$accessToken= $html['data']['accessToken'];
	$userId=$html['data']['userId'];
	$step = $setStep;

	//取到参数添加到刷步数的post里
	$url="sports.lifesense.com/sport_service/sport/sport/uploadMobileStepV2?version=4.5&systemType=2";
	$header = [
		'Cookie: accessToken='.$accessToken,
		'Content-Type: application/json; charset=utf-8',
	];
	//步数距离时间戳都要加上才能成功
	$data = '{"list":[{"DataSource":2,"active":1,"calories":"'.intval($step/4).'","dataSource":2,"deviceId":"M_NULL","distance":'.intval($step/3).',"exerciseTime":0,"isUpload":0,"measurementTime":"'.date("Y-m-d H:i:s",time()).'","priority":0,"step":'.$step.',"type":2,"updated":'.getMillisecond().',"userId":'.$userId.'}]}';
	$html = curl($url,$data,$method='POST',$header);
	$html=json_decode($html,true);
	$steps = $html['data']['pedometerRecordHourlyList'][0]['step'];
	if(!empty($steps)){
		return "{\"user\":$phone,\"code\":\"200\",\"step\":\"". $steps ."\"}";
	}else if(empty($json[resultObj])){
		return "{\"user\":$phone,\"code\":\"407\",\"msg\":\"刷步数失败了，请检查账号密码和步数\"}";
	}
}

$time=date('y-m-d h:i:s',time());
file_put_contents('info.log',$time."\n\n",FILE_APPEND);
$file = file_get_contents("account.json");
$account = json_decode($file);
foreach($account as $user) {
	$phone = $user->phone;
	$password = $user->password;
	$step = $user->step;
	
	$result = start($phone, $password, $step);
	file_put_contents('info.log',"\t".$result."\n\n",FILE_APPEND);

	sleep(1);
}
file_put_contents('info.log',"\n\n",FILE_APPEND);

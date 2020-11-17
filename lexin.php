<?php

class Lexin {
	var $key;
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

	function push_robot($desp){
		if(empty($this->key)){
			return false;
		}
		$content = '@json={"app":"com.tencent.qqpay.qqmp.groupmsg","desc":"","view":"groupPushView","ver":"1.0.0.7","prompt":"刷步推送","meta":{"groupPushData":{"bannerImg":"https://bkimg.cdn.bcebos.com/pic/d8f9d72a6059252ddc356a72339b033b5bb5b9a1","bannerTxt":"乐心运动修改步数结果","summaryTxt":"'. $desp .'","time":""}},"config":{"forward":0,"showSender":1}}@';

		$url = "https://push.xuthus.cc/group/" . $this->key;
		$header = [
			'Content-Type: application/json; charset=utf-8',
		];
		$response = $this->curl($url,$content,'POST',$header);
		$res=json_decode($response,true);
		if($res['code'] == 200){
			echo "发送成功\n";
		}else{
			echo $response;
		}
	}

	// @return login_token
	function login($phone, $password) {
		//登录的post，取cookie，Content-Type必须加上
		$header = [
			'Content-Type: application/json; charset=utf-8',
		];
		$url="sports.lifesense.com/sessions_service/login?systemType=2&version=4.6.7";
		$data = '{"appType":6,"clientId":"88888","loginName":"'.$phone.'","password":"'.md5($password).'","roleType":0}';
		$html = $this->curl($url,$data,$method='POST',$header);
		$html=json_decode($html,true);
		//这两个参数下面的post需要
		if(empty($html['data'])){
			return false;
		}
		$login_token['accessToken']= $html['data']['accessToken'];
		$login_token['userId']=$html['data']['userId'];
		return $login_token;
	}

	function change_step($login_token, $step){
		if(empty($login_token)){
			return false;
		}
		$accessToken = $login_token['accessToken'];
		$userId = $login_token['userId'];
		//取到参数添加到刷步数的post里
		$url="sports.lifesense.com/sport_service/sport/sport/uploadMobileStepV2?version=4.5&systemType=2";
		$header = [
			'Cookie: accessToken='.$accessToken,
			'Content-Type: application/json; charset=utf-8',
		];
		//步数距离时间戳都要加上才能成功
		$data = '{"list":[{"DataSource":2,"active":1,"calories":"'.intval($step/4).'","dataSource":2,"deviceId":"M_NULL","distance":'.intval($step/3).',"exerciseTime":0,"isUpload":0,"measurementTime":"'.date("Y-m-d H:i:s",time()).'","priority":0,"step":'.$step.',"type":2,"updated":'.$this->getMillisecond().',"userId":'.$userId.'}]}';
		$html = $this->curl($url,$data,$method='POST',$header);
		$html=json_decode($html,true);
		$steps = $html['data']['pedometerRecordHourlyList'][0]['step'];
		if(!empty($steps)){
			return true;
		}else{
			return false;
		}
	}
	function one_start($phone, $password, $step){ //单账号
		$login_token = $this->login($phone, $password);
		$result = $this->change_step($login_token, $step);
		if($result) {
			return '{"code":"200","msg":"修改成功"}';
		} else {
			return '{"code":"406","msg":"登录失败"}';
		}
	}
	function mul_start($account_file){ // 多账号
		$desp = "";
		$file = file_get_contents($account_file);
		$accounts = json_decode($file, true);
		$aflag = false; //是否需要修改account文件的标志
		
		foreach($accounts as &$user){
			$desp = $desp . $user['user']. ": ";
			$step = $user['step'] + mt_rand(0, 10000);
			$result = $this->change_step($user['login_token'], $step);

			if($result == false){
				$desp = $desp . "token失效,重新获取... ";
				$login_token = $this->login($user['phone'], $user['password']);
				if($login_token == false){
					$desp = $desp . "登录失败\\n";
					sleep(5);
					continue;
				} else {
					$user['login_token'] = $login_token;
					$aflag = true;
					$result = $this->change_step($login_token, $step);
				}
			}

			if($result){
				$desp = $desp . $step . "\\n";
			} else {
				$desp = $desp . "修改失败\\n";
			}
			sleep(5);
		}
		$this->push_robot($desp);
		if($aflag){
			$accounts = json_encode($accounts);
			file_put_contents($account_file, $accounts);
		}
		$desp = str_replace("\\n","\n",$desp);
		return $desp;
	}
}
?>

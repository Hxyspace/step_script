<?php

class Xiaomi {
	var $key; // 酷推key
	function get_code($location){ //获取用户链接码
		preg_match('/(?<=access=).*?(?=&)/', $location, $code);
		if(!empty($code[0])) {
			return $code[0];
		} else {
			return false;
		}
	}

	function get_Timestamp(){ //获取13位时间戳
		list($s1, $s2) = explode(' ', microtime());
		return (float)sprintf('%.0f',(floatval($s1) + floatval($s2)) * 1000);
	}

	function curl($url, $data='', $method='POST', $header, $location_flag){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查  
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
		if($location_flag){
			curl_setopt($curl, CURLOPT_NOBODY, true);
		} else {
			curl_setopt($curl, CURLOPT_AUTOREFERER, true);
		}
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		if($method=='POST'){  
		   curl_setopt($curl, CURLOPT_POST, true); // 发送一个常规的Post请求  
		   if ($data != ''){  
				curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包  
			}  
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_ENCODING ,'gzip');
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		$html = curl_exec($curl);
		$location = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
		curl_close($curl);
		if($location_flag) {
			return $location;
		}
		return $html;
	}

	function push_robot($desp){
		if(empty($this->key)){
			return false;
		}
		$content = '@json={"app":"com.tencent.qqpay.qqmp.groupmsg","desc":"","view":"groupPushView","ver":"1.0.0.7","prompt":"刷步推送","meta":{"groupPushData":{"bannerImg":"https://bkimg.cdn.bcebos.com/pic/91529822720e0cf3d7cab034560ce51fbe096a637ce3?x-bce-process=image/resize,m_lfit,w_268,limit_1/format,f_jpg","bannerTxt":"小米运动修改步数结果","summaryTxt":"'. $desp .'","time":""}},"config":{"forward":0,"showSender":1}}@';
		$url = "https://push.xuthus.cc/group/" . $this->key;
		$header = [
			'Content-Type: application/json; charset=utf-8',
		];
		$response = $this->curl($url,$content,'POST',$header, false);
		$res=json_decode($response,true);
		if($res['code'] == 200){
			echo "发送成功\n";
		}else{
			echo $response;
		}
	}


	// @return login_token
	function login($phone, $password){ //登录
		if(strpos($phone,'@') == false) { //非邮箱登录，需要加区号
			$phone = "+86" . $phone;
		}
		$url = "https://api-user.huami.com/registrations/".$phone."/tokens";
		$header = [
			'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
			'User-Agent: Dalvik/2.1.0 (Linux; U; Android 9; MI 6 MIUI/20.6.18)'
		];
		$data = array(
			"client_id"=>"HuaMi",
			"password"=>$password,
			"redirect_uri"=>"https://s3-us-west-2.amazonaws.com/hm-registration/successsignin.html",
			"token"=>"access"
		);

		// 获取重定向后的链接
		$location = $this->curl($url,http_build_query($data),$method='POST',$header,true);
		$code=$this->get_code($location); // 获取用户链接码
		if($code == false){
			return false;
		}

		$url = "https://account.huami.com/v2/client/login";
		$data = array(
			"app_name"=>"com.xiaomi.hm.health",
			"app_version"=>"4.6.5",
			"code"=>$code,
			"country_code"=>"852",
			"device_id"=>"02:00:00:00:00:00",
			"device_model"=>"android_phone",
			"grant_type"=>"access_token",
			"third_name"=>"huami"
		);

		// 获取login_token
		$html = $this->curl($url,http_build_query($data),$method='POST',$header,false);
		$result = json_decode($html,true);
		$login_token = $result["token_info"]["login_token"];
		return $login_token;
	}

	// @return token_info
	function get_app_token($login_token){ //获取app_token
		if(empty($login_token)){
			return false;
		}
		$url = 'https://account-cn.huami.com/v1/client/app_tokens?app_name=com.xiaomi.hm.health&dn=api-user.huami.com%2Capi-mifit.huami.com%2Capp-analytics.huami.com&login_token=' . $login_token . '&os_version=4.1.0';
		$header = [
			'User-Agent: Dalvik/2.1.0 (Linux; U; Android 9; MI 6 MIUI/20.6.18)'
		];
		
		$response = $this->curl($url, $data='', $method='GET', $header, false);
		if(strpos($response,'token_info') != false) {
			$token_info = json_decode($response,true)['token_info'];
			return $token_info;
		} else {
			return false;
		}
	}

	// @return 1 | 0
	function change_step($token_info, $step) {
		$url = "https://api-mifit-cn2.huami.com/v1/data/band_data.json?&t=" . $this->get_Timestamp();
		$app_token = $token_info['app_token'];
		$userid = $token_info['user_id'];
		$header = [
			'apptoken: ' . $app_token ,
			'Content-Type: application/x-www-form-urlencoded'
		];

		$data_file = dirname(__FILE__).'/data'; //data文件路径,可自定义，默认与脚本位置相同
		$data_json = file_get_contents($data_file); //读取data文件
		//替换data中的时间和需要刷的步数
		/*
		preg_match('/.*?date%22%3A%22(.*?)%22%2C%22data.*?/', $data_json, $code);
		$data_json = str_replace($code[1],date('Y-m-d',time()),$data_json);
		preg_match('/.*?ttl%5C%22%3A(.*?)%2C%5C%22dis.*?/', $data_json, $code);
		$data_json = str_replace($code[1],$step,$data_json);
		 */
		$data_json = str_replace('__date__',date('Y-m-d',time()),$data_json);
		$data_json = str_replace('__ttl__',$step,$data_json);

		$data = 'userid=' . $userid . '&last_sync_data_time=1597306380&device_type=0&last_deviceid=DA932FFFFE8816E7&data_json=' . $data_json;

		$res = $this->curl($url,$data,$method='POST',$header,false);
		$res = json_decode($res,true);
		return $res['code'];
	}

	function one_start($phone, $password, $step){ //单个账号
		$login_token = $this->login($phone, $password);
		if($login_token == false){
			echo '{"code":"405","msg":"登录失败"}';
			return false;
		}
		$token_info = $this->get_app_token($login_token);
		$result = $this->change_step($token_info, $step);
		if($result == '1'){
			return '{"code":"200","msg":"修改成功"}';
		} else {
			return '{"code":"406","msg":"修改失败"}';
		}
	}

	function mul_start($account_file){ //多账号
		$desp = "";
		$file = file_get_contents($account_file);
		$accounts = json_decode($file,true);
		$aflag = false; //是否需要修改account文件的标志


		foreach($accounts as &$user){
			$desp = $desp . $user['user']. ": ";
			$step = $user['step'] + mt_rand(0, 10000);
			$token_info = $this->get_app_token($user['login_token']);

			if($token_info == false) {
				$desp = $desp . "token失效,重新获取... ";
				$login_token = $this->login($user['phone'], $user['password']);
				if($login_token == false){
					$desp = $desp . "登录失败\\n";
					sleep(5);
					continue;
				} else {
					$user['login_token'] = $login_token;
					$aflag = true;
					$token_info = $this->get_app_token($login_token);
				}
			}

			$result = $this->change_step($token_info, $step);
			if($result == 1){
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

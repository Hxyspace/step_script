<?php

class Robot_feishu {
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

	function push($desp){
		$content = '{"msg_type": "text","content": {"text": "' . $desp .'"}}';
		$url = "https://open.feishu.cn/open-apis/bot/v2/hook/*************************";
		$header = [
			'Content-Type: application/json; charset=utf-8',
		];
		$response = $this->curl($url,$content,'POST',$header, false);
		$res=json_decode($response,true);
		if($res['StatusCode'] == 0){
			echo "发送成功\n";
		}else{
			echo $response;
		}
	}
}
?>

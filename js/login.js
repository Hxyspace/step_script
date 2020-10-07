var xiha={
	postData: function(url, parameter, callback, dataType, ajaxType) {
		if(!dataType) dataType='json';
		$.ajax({
			type: "POST",
			url: url,
			async: true,
			dataType: dataType,
			json: "callback",
			data: parameter,
			success: function(data) {
				if (callback == null) {
					return;
				} 
				callback(data);
			},
			error: function(error) {
				xtip.closeAll();
				xtip.alert('网络错误',"e");
			}
		});
	}
}
function trim(str){ //去掉头尾空格
	return str.replace(/(^\s*)|(\s*$)/g, "");
}

function shiyongClick() {
	xtip.win({
		type: "confirm",
		btn: ["知道了"],
		tip: '放心使用，不会记录你的任何信息(手动模式)，白给我都不要，原理就是伪装浏览器请求了乐心的步数接口：<br><br>1. 基于乐心健康app，请载乐心健康app：官方下载地址：<a href="http://www.lifesense.com/app/" target="_blank">http://www.lifesense.com/app/</a>，手机号注册账号</br>2. 登录之后，点击我的->设置->账号与安全->设置密码(修改密码)，设置你自己记得住的密码</br> 3. 回到App首页，点击我的->数据共享，绑定你想同步数据的项目, 注：同步微信运动请按照要求关注【乐心运动】公众号</br>4. 回到网站，填写信息后开始刷步即可同步至你绑定的所有平台</br></br>',
		icon: "success",
		title: "食用方法",
		min: true,
		width: /(iPhone|iOS|Android)/i.test(navigator.userAgent)?'100%':'600px',
		shade: false,
		shadeClose: false,
		lock: false,
		zindex: 99999,
	})
}

function init(){
	$('#mul-button').click(function(){
		login(0);
	});
	$('#auto-button').click(function(){
		login(1);
	});
}

function isEmpty(str){
	return str.length == 0;
}

function login(flag){

	var phone=trim($('#phone').val()),
		pwd=trim($('#pwd').val()),
		step=trim($('#step').val());
	xtip.closeAll();
	if(isEmpty(phone)||isEmpty(pwd)||isEmpty(step)){
		xtip.alert("不能留空，请重新输入！", "e");
		return ;
	}
	if(flag == 0) {
		var url = "?flag=0";
	} else if(flag == 1) {
		var sevpwd=window.prompt('此功能需要提供密码，联系站长获取：', '密码');
		var url = "?flag=1&sevpwd="+sevpwd;
	}
	xiha.postData(url,"phone="+phone+"&pwd="+pwd+"&step="+step, function(d) {
		if(d.code ==200){
			var stepMax = Math.max.apply(Number,d.step.split(",").map(Number));
			xtip.alert('刷步成功<br>当前最大步数：' + stepMax, "s");
		} else if(d.code == 508){
			xtip.alert(d.msg, "s");
		}else{
			xtip.alert(d.code+'<br>'+d.msg, "e");

		}
	});
}
	
$(document).ready(function(){
	init();
});

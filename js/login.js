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

function init(){
	$('#mul-button').click(function(){
		login(0);
	});
	$('#auto-button').click(function(){
		login(1);
	});
	$('#switch').click(function(){
		var value = Number(trim($('#switch').val()));
		value ^= 1;
		$('#switch').val(value);
		if(value == 0){
			$('#Dtitle').html("乐心刷步");
			$('#switch').html("切换到小米刷步");
		}else if(value == 1) {
			$('#Dtitle').html("小米刷步");
			$('#switch').html("切换到乐心刷步");
		}
	});
}

function isEmpty(str){
	return str.length == 0;
}

function login(flag){

	var phone=trim($('#phone').val()),
		pwd=trim($('#pwd').val()),
		step=trim($('#step').val()),
		lswitch=trim($('#switch').val());
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
	xiha.postData(url,"phone="+phone+"&pwd="+pwd+"&step="+step+"&switch="+lswitch, function(d) {
		if(d.code ==200){
			//var stepMax = Math.max.apply(Number,d.step.split(",").map(Number));
			//xtip.alert('刷步成功<br>当前最大步数：' + stepMax, "s");
			xtip.alert(d.msg, "s");
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
$("input").on('focus',function(){
	window.addEventListener('resize',function(){
		document.activeElement.scrollIntoViewIfNeeded();
	})
});

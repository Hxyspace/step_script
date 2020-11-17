### 说明

	由乐心刷步服务器版本略作修改，多用户刷步的php脚本。可配合定时任务(如Linux下的crontab、android下的tasker)实现全自动刷步。

	Laccount.json文件存放乐心健康账号密码和要刷的步数。
	Xaccount.json文件存放小米运动账号密码和要刷的步数。
	data文件存放小米运动需要提交的数据。
	info.log文件存放自动刷步的日志。

	使用前自行修改index.php中第三行酷推推送的key和第四行自动方式的服务密码。

	注: 若出现文件写入错误，请检查文件权限

#### 更新
	2020-11-17:
	增加小米运动刷步;
	增加酷推推送;
	加入保存cookie、token功能，避免多次使用账号密码登录;
	小米运动、乐心健康刷步独立成两个模块.

	2020-10-07:
	添加前端页面，为适应前端，脚本文件删除了shebang，脚本方式运行：php index.php

	

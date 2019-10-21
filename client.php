<?php
	require_once './config.php';
	require __DIR__ . '/vendor/autoload.php';
	$uuid = uniqid('', true);
	$client = new MongoDB\Client($dbConnectionStr);
    $databaseManager = $client->admama;
    $qrUUid = $databaseManager->qr_uuid;
    $qrUUid->insertOne(['uuid' => $uuid, 'isLogin' => 0]);

	$imageUrl = $qrCodeImage . '?uuid=' . $uuid;
?>

<html>
	<head>
		<meta charset="UTF-8">
		<title></title>
	</head>
	<body>
		<div id="box">
            <img src="<?= $imageUrl?>" />
			<input type="text" id="message">
			<button id="send" onclick="sendText()">验证登录</button>            
		</div>
	</body>
</html>
<script type="text/javascript">
	var ws = new WebSocket("ws://127.0.0.1:9999");
    var uuid = "<?= $uuid ?>";
	ws.onopen = function(evt) {  //绑定连接事件
	　　console.log("Connection open ...");
	};

	ws.onmessage = function(evt) {//绑定收到消息事件
	　　var result = JSON.parse(evt.data);
		console.log(result);
		if(result.code == 200){
			document.getElementById('message').value = '登陆成功';
		}
	};

	function sendText() {
	  var msg = {
	    uuid: uuid,	    
	  };
	  ws.send(JSON.stringify(msg));
	}

	setInterval("sendText()",1000)
        
</script>
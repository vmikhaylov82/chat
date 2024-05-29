<?php

set_time_limit(0);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$link = mysqli_connect("localhost", "vladimir", "royal");
if ($link == false){
	print(mysqli_connect_error());
	exit;
} else {
	//print("Соединение установлено успешно");
}

mysqli_select_db($link, "chat");
mysqli_query($link, "SET NAMES 'UTF8'");

session_start();

if (isset($_POST['type']) && $_POST['type'] == 'form') {
	
	$sql = "SELECT id,name,password FROM admins WHERE name='".$_POST['login']."'";
	$result = mysqli_query($link, $sql);
	if (!$result) {
		print(mysqli_error($link));
	}
	$access = 0;
	while ($row = mysqli_fetch_array($result)) {
		if ($row['name'] == $_POST['login'] && $row['password'] == $_POST['password']) {
			$access = 1;
			break;
		}
	}
	if ($access == 1) {
		$_SESSION['admin_id'] = $row['id'];
	}

	Header("Location: ?");
	
} else if (isset($_SESSION['admin_id'])) {

?>

<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<title>admin</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<style>

		body {
			width: 100%;
			font-family: "Lucida Sans Unicode", "Lucida Grande", Sans-Serif;
			font-size: 14px;
			background: #fff;
			caret-color: transparent;
		}
		.center {
			margin: 0 auto;
			overflow: hidden;			
			border: 1px solid #ccc;
			max-width: 1300px;
			border-radius: 10px;
		}			
		.hidden {
			display: none;
		}		
		.visible {
			display: block;
		}		
		.selected {
			background-color: #3e5dd1;
			border-radius: 10px;
		}
		
		/* HEADER ///////////////////////////////////////////////////////////////////////////////// */

		.header {
			height: 80px;
			background: #f7f7f8;
			display: flex;
			border-bottom: 1px solid #ccc;
		}
		.conversationCount {
			width: 300px;
			background: #f0f4fa; 
			justify-content: center;
			display: flex;
			align-items: center;
			border-right: 1px solid #ccc;
		}
		.header__ul {
			display: flex;
			margin-left: auto;
			margin-right: 30px;
			gap: 60px;
			list-style-type: none;
			align-items: center;
		}
		.header__list {
			display: flex;
			align-items: center;
			gap: 20px;
		}
		
		/* SECTION РАЗГОВОРЫ ///////////////////////////////////////////////////////////////////////////////// */

		.section {
			display: flex;
		}
		.section__conversations {
			width: 300px;
			height: 800px;
			background: #032d7c; 
			display: flex;
			color: #fff;
			flex-direction: column;
			font-size: 13px;
		}
		.conversations__header {
			display: flex;
			justify-content: center;
			gap: 30px;
			padding: 20px;
			/* border-bottom: 1px dashed #3b5ccf; */
		}
		#sectionConversation, #sectionHistory {
			padding: 5px 20px;
			cursor: pointer;
		}
		#conversations {
			padding: 10px;
			padding-top: 0px; 
		}
		.conversations__list {
			border-radius: 10px;
			padding: 7px 7px 7px 15px;
			margin-bottom: 3px;
			cursor: pointer;
		}
		.section__unreaded {
			background-image: url("assets/img/inactive.png");
			display: inline-block;
			width: 20px;
			height: 20px;
			background-size: cover;
			background-repeat: no-repeat;
			text-align: center;
		}
		
		/* SECTION СООБЩЕНИЯ ///////////////////////////////////////////////////////////////////////////////// */
		
		.section__messages {
			display: flex;
			flex: 2;
			background: #fff;
			flex-direction: column;
		}
		#scroll {
			overflow-y: scroll;
			padding: 0px 50px 50px 50px;
			flex-grow: 1;
			height: 400px;
		}	
		.message__header {
			font-size: 16px;
			font-weight: bold;
			height: 60px;
			position: sticky; 
			top: 0; 
			background: #fff;
			display: flex;
			align-items: center;
		}
		.message__outer {
			display: flex;
		}		
		.message__inner {
			flex: 1;
			display: flex;
			padding-bottom: 7px;
		}		
		.message__bubble {
			min-width: 200px;
			min-height: 30px;
			border-radius: 10px;
			max-width: calc(100% - 67px);
			overflow-wrap: anywhere;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 5px 15px;
		}		
		.message__ts {
			min-width: 130px;
			min-height: 30px;
			display: flex;
			align-items: center;
			justify-content: center;	
		}		
		.message__avatar {
			min-height: 30px;
			display: flex;
			align-items: center;
			justify-content: center;	
			padding: 0 0 0 15px;
		}	
		.widget_emoji {
			width: 30px;
		}

		/* ФОРМА ///////////////////////////////////////////////////////////////////////////////// */		
		
		.section__admin {
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 30px;
			gap: 20px;
			border-top: 1px solid #ccc;
			height: 80px;
			background: #f7f7f8;
		}
		.vertborder {
			border-left: 1px solid #ccc;
			height: 40px;
			width: 1px;
			margin: 0 10px;
		}
		textarea {
		  width: 70%;
		  height: 60px;		  
		  box-sizing: border-box;
		  border: 2px solid #ccc;
		  border-radius: 8px;
		  background-color: #fff;
		  resize: none;
		  margin-right: 10px;
		  padding: 10px;
		  align-content: left;
		  cols: 33;
		}	
		textarea:focus {
			border: 2px solid #ccc;
			caret-color: auto;
		}
		input[type=text] {
		  background-color: #fff;
		  border: 2px solid #ccc;
		  border-radius: 4px;
		  width: 300px;
		}
		input[type=text]:focus {
		  caret-color: auto;
		}		
		input[type=button] {
		  background-color: #3b5bcf;
		  border: none;
		  color: white;
		  padding: 16px 32px;
		  text-decoration: none;
		  margin: 4px 2px;
		  cursor: pointer;
		  border-radius: 8px;
		}	
		input[type=button].button2 {
		  background-color: #f7f7f8;
		  color: #000;
		  cursor: pointer;
		  border: 1px solid #ccc;
		  font-size: 11px;
		  margin-left: 14px;
		  padding: 5px;
		}

		/* media ///////////////////////////////////////////////////////////////////////////////// */
		
		@media(max-width: 920px) {
			
			.conversationCount {
				display: none;
			}
			.header__ul {
				padding: 0px;
				gap: 80px;
				justify-content: start;
			}
			.header__list {
				width: 60px;
				gap: 10px;
			}
			.header__list input[type=button], .section__admin input[type=button] {
				width: 100px;
				height: 50px;
				padding: 10px;
			}
			.section__admin input[type=text] {
				width: 150px;
			}
			.section__conversations {
				width: 120px;
			}
			.conversations__header {
				flex-direction: column;
				gap: 10px;
				padding: 10px;
			}
			.section__messages {
				width: 200px;
				display: flex;
				background: #fff;
			}
			#scroll {
				padding: 10px;
			}
			.message__bubble {
				width: 100%;
				padding: 0px;
			}
			.section__admin {
				flex-direction: column;
				padding: 0px;
			}
			.vertborder {
				display: none;
			}
			textarea {
			  width: 70%;
			  height: 150px;		  
			}
		}

		/* СКРОЛЛ ///////////////////////////////////////////////////////////////////////////////// */

		#scrollConversations {
			overflow-y: scroll;
			height: 730px; 
		}	
		#scrollConversations::-webkit-scrollbar {
			width: 7px;
			background-color: #032d7c;
		}		
		#scrollConversations::-webkit-scrollbar-thumb {
			background-color: #3e5dd1;
		}		
		#scroll::-webkit-scrollbar {
			width: 7px;
			background-color: #f9f9fd;
		}		
		#scroll::-webkit-scrollbar-thumb {
			background-color: #ccc;
		}
		
	</style>
</head>
<body>

	<div class="center">
		<div class="header">
			<div class="conversationCount">
				Количество разговоров: &nbsp; <span id="conversationCount"></span>
			</div>
			
			<input type='hidden' id='conversationStatus' value=''>
			<input type='hidden' id='adminId' value='<?php echo $_SESSION['admin_id']; ?>'>
			<input type='hidden' id='conversationId' value=''>
			<input type='hidden' id='siteId' value=''>
			
			<ul>
				<li class="header__list">

				</li>
			</ul>
			
			<ul class="header__ul">
				<li class="header__list">
					<?php
					$sql = "SELECT name,status FROM admins WHERE id=".$_SESSION['admin_id'];
					$result = mysqli_query($link, $sql);
					if (!$result) {
						print(mysqli_error($link));
					}
					
					$row = mysqli_fetch_array($result);
					print "<img src='assets/operators/".$row['name'].".png' width='30px' height='40px'> ".$row['name']." <img id='adminStatusImg' src='assets/img/".$row['status'].".png' width='20px' height='20px'>";
					?>					
					
				</li>
				<li class="header__list"><input type="button" value="Сменить статус" onclick='changeStatus()'></li>
				<li class="header__list"><input type="button" value="Выйти" onclick='logout()'></li>

			</ul>
		</div>	
			
		<div class="section">
			<div class="section__conversations">
				<div class="conversations__header">
					<div class="selected" id="sectionConversation" onclick='selectConversation()'>Разговоры</div>
					<div class="" id="sectionHistory" onclick='selectHistory()'>История</div>
				</div>

				<div id="scrollConversations">
					<div id='conversations'></div>
				</div>
			</div>
			<div class="section__messages">
				<div id="scroll">
					<div id='messages'></div>
				</div>
				
				<div id="sendMes" class="hidden">	
					<div class="section__admin">
						<textarea id='adminMessage'></textarea>
						<input type='button' value='Отправить' onclick='sendAdminMes()'>	
						
					</div>
				</div>

				<div id="manage" class="hidden">
					<div class="section__admin">
						<input type='text' name='theme' id="theme" value=''> Переименовать тему
						<input type='button' value='Переименовать' onclick='renameTheme()'>
						<div class="vertborder"></div>
						<input type='button' value='В архив' onclick='toHistory()'>
					</div>
				</div>
				
				<div id="searchHistory" class="hidden">
					<div class="section__admin">
						<input type='text' name='theme' id="searchHistoryValue" value=''> 
						<input type='button' value='Найти' onclick='searchHistory()'>
						<div class="vertborder"></div>
						<input type='button' id='fromHistory' value='Из архива' onclick='fromHistory()' class="hidden">
					</div>
				</div>
				
			</div>
		</div>
	</div>

	<script>
		window.onload = startGetConversations('active');
		var last_count_unreaded;
		
		var intervalGetConversations;
		
		function startGetConversations(status) {
			getConversations(status);
			
			if (status == 'active') {
				intervalGetConversations = setInterval(getConversations, 10000, status);
			}
			
			document.getElementById("conversationStatus").value = status;
		}
		
		function getConversations(status) {
			
			fetch("adminRequests.php?action=getConversations&status="+status+"&adminId="+document.getElementById("adminId").value)
				.then(res => res.json())
				
				.then(function(res) {
					if (res['status'] == 'ok') {
						var html = "";
						for (var i=0; i<res['data'].length; i++) {
							
							let count_unreaded = res['data'][i]['count_unreaded'] > 0 ? "<span class='section__unreaded'>"+res['data'][i]['count_unreaded']+"</span>" : "";
							let closed_by = status == 'history' ? "<span style='float:right'><img src='assets/img/"+res['data'][i]['closed_by']+".png' width='25px' height='25px'></span>" : "";
							let delete_message = status == 'history' && document.getElementById("adminId").value == 4 ? "<span style='float:right' onclick='deleteMessage(" + res['data'][i]['id'] + ")'><img src='assets/img/delete_message.png' width='20px' height='20px'></span>" : "";
							let site_id = res['data'][i]['site_id'] == 'royal' ? "<img src='assets/img/royal.png' width='14px' height='16px' style='height: auto'>" : "";
							
							html += "<div class='conversations__list' style='word-wrap:break-word; background-color:"+ (res['data'][i]['id'] == document.getElementById('conversationId').value ? '#3e5dd1' : '') +"' id='conversation"+res['data'][i]['id']+"' onclick='startGetMessages(" + res['data'][i]['id'] + ", \"" + res['data'][i]['site_id'] + "\")'>" + site_id + res['data'][i]['theme'] + " " + count_unreaded + delete_message + closed_by + "</div>"; 
						}
						
						document.getElementById("conversationCount").innerHTML = res['data'].length;
						
					} else {
						var html = "";
						document.getElementById("conversationCount").innerHTML = 0;
					}
					document.getElementById("conversations").innerHTML = html;
				})
				.catch(err => console.error(err));
		}
		
		var intervalGetMessages;
		function startGetMessages(conversationId, siteId) {
				
			document.getElementById('conversationId').value = conversationId; //для отправки ответа оператора
			document.getElementById('manage').className = document.getElementById("conversationStatus").value == 'active' ? "visible" : "hidden";
			document.getElementById('sendMes').className = document.getElementById("conversationStatus").value == 'active' ? "visible" : "hidden";
			
			document.getElementById('fromHistory').className = document.getElementById("conversationStatus").value == 'active' ? "hidden" : "visible";
			
			clearInterval(intervalGetMessages);
			getMessages(conversationId);
			
			if (document.getElementById("conversationStatus").value == 'active') {
				intervalGetMessages = setInterval(getMessages, 10000, conversationId);
			}
			
			Array.from(document.getElementsByClassName('conversations__list')).forEach((element, index) => element.style.removeProperty('background-color'));
			
			document.getElementById('conversation'+conversationId).style.background = '#3e5dd1';
			
			document.getElementById('siteId').value = siteId;
		}
		
		function getMessages(conversationId) {
		
			fetch("adminRequests.php?action=getMessages&id="+conversationId)
				.then(res => res.json())
				
				.then(function(res) {
					
					if (res['status'] == 'ok') {
						
						var html = "";
						html += "<div class='message__header'>";
							html += "<span id='uid' style='color:"+ (res['userId'].indexOf(".") >= 0 ? "red" : "") +"'>"+res['userId']+"</span>";
							html += "<span class='"+ (res['userId'].indexOf(".") >= 0 ? "hidden" : "") +"'>";
							html += "<input type='button' class='button2' value='Wallet' onclick='openWallet()'>";
							html += "<input type='button' class='button2' value='Profile' onclick='openProfile()'>";
							html += "<input type='button' class='button2' value='Transactions' onclick='openTransactions()'>";
							html += "<input type='button' class='button2' value='Game process' onclick='openProcess()'>";
							html += "</span>";
						html += "</div>";
						
						for (var i=0; i<res['data'].length; i++) {
							
							var direction = "";
							if (res['data'][i]['sender'] == 'admin') {
								direction = "flex-direction: row-reverse";
								bgcolor = "background-color: #2e89e6; color: white;";
								admin = res['data'][i]['name'];
							} else {
								direction = "";
								bgcolor = "background-color: #e2e2e2; color: black";
								admin = "";
							}
							
							var message = replacePath(res['data'][i]['message']);
							
							html += "<div class='message__outer'>";
							html += "	<div class='message__inner' style='"+direction+"'>";
							html += "		<div class='message__avatar'>"+ admin +"</div>";
							html += "		<div class='message__bubble' style='"+bgcolor+"'>" +message+ "</div>";
							html += "		<div class='message__ts'>"+ res['data'][i]['ts'] +"</div>";
							html += "	</div>";
							html += "</div>";
						}

						if (res['conversationStatus'] == 'history' && document.getElementById("conversationStatus").value == 'active') {
							html += "<div class='message__header' style='color: red'>Разговор перенесен в историю пользователем.</div>";
							
							document.getElementById('manage').className = "hidden";
							document.getElementById('sendMes').className = "hidden";						
							clearInterval(intervalGetMessages);
						}
						//console.log(html);
					}
					document.getElementById("messages").innerHTML = html;
				})
				.catch(err => console.error(err));
		}
		
		var intervalDelaySendAdminMes;
		function sendAdminMes() {
		
			let conversationId = document.getElementById('conversationId').value;
			let adminMessage = document.getElementById('adminMessage').value;
			
			document.getElementById('adminMessage').value = '';
			
			fetch("adminRequests.php?action=sendAdminMes&id="+conversationId+"&adminId="+document.getElementById("adminId").value+"&adminMessage="+encodeURIComponent(adminMessage))
				.then(res => res.text())
				.then(function(res) {
					//обновление окна сообщений через 1 сек для обновления после отправки сообщения оператором, тк обновление происходит каждые 10 секунд
					intervalDelaySendAdminMes = setInterval(delaySendAdminMes, 1000, conversationId);
				})
				.catch(err => console.error(err));
		}
		
		function delaySendAdminMes(conversationId) {
		
			clearInterval(intervalDelaySendAdminMes);
			getMessages(conversationId);
		}
		
		function renameTheme() {
		
			let theme = document.getElementById('theme').value;
			if (theme != '') {
				let conversationId = document.getElementById('conversationId').value;
			
				fetch("adminRequests.php?action=renameTheme&id="+conversationId+"&theme="+theme)
					.then(function(res) {
						clearInterval(intervalGetConversations);						
						getConversations();
					})
					.catch(err => console.error(err));
			}
		}
		
		function toHistory() {
		
			let conversationId = document.getElementById('conversationId').value;
			
			document.getElementById("conversations").innerHTML = "";
			clearInterval(intervalGetConversations);
			
			fetch("adminRequests.php?action=toHistory&id="+conversationId+"&adminId="+document.getElementById("adminId").value)
				.then(res => getConversations(document.getElementById("conversationStatus").value))
				.catch(err => console.error(err));
				
			document.getElementById('manage').className = "hidden";
			
			document.getElementById("messages").innerHTML = ""; 
			clearInterval(intervalGetMessages);
			
			document.getElementById('sendMes').className = "hidden";
		}
		
		function selectConversation() {

			let section1 = document.getElementById('sectionConversation');
			section1.classList.add("selected");

			let section2 = document.getElementById('sectionHistory');
			section2.classList.remove("selected");
			
			clearInterval(intervalGetMessages);
			clearInterval(intervalGetConversations);
			
			startGetConversations('active');
			
			document.getElementById("messages").innerHTML = "";
			document.getElementById("conversations").innerHTML = "";
			
			document.getElementById("conversationStatus").value = "active";
			
			document.getElementById('manage').className = "hidden";
			document.getElementById('searchHistory').className = "hidden";
			
			document.getElementById('conversationId').value = '';
			Array.from(document.getElementsByClassName('conversations__list')).forEach((element, index) => element.style.removeProperty('background-color'));
		} 

		function selectHistory() {

			let section1 = document.getElementById('sectionHistory');
			section1.classList.add("selected");

			let section2 = document.getElementById('sectionConversation');
			section2.classList.remove("selected");
			
			clearInterval(intervalGetMessages);
			clearInterval(intervalGetConversations);
			
			startGetConversations('history');
			
			document.getElementById("messages").innerHTML = "";
			document.getElementById("conversations").innerHTML = "";
			
			document.getElementById("conversationStatus").value = "history";
			
			document.getElementById('manage').className = "hidden";
			document.getElementById('searchHistory').className = "visible";
			document.getElementById('sendMes').className = "hidden";
			
			document.getElementById('fromHistory').className = "hidden";
			
			document.getElementById('searchHistoryValue').value = "";
			document.getElementById('conversationId').value = '';
		}
	
		function searchHistory() {
		
			let searchHistoryValue = document.getElementById('searchHistoryValue').value.trim();
			
			fetch("adminRequests.php?action=searchHistory&searchHistoryValue="+searchHistoryValue)
				.then(res => res.json())
				
				.then(function(res) {

					clearInterval(intervalGetMessages);
					clearInterval(intervalGetConversations);
					
					document.getElementById("messages").innerHTML = "";
					document.getElementById("conversations").innerHTML = "";
						
					if (res['status'] == 'ok') {
						var html = "";
						for (var i=0; i<res['data'].length; i++) {
							html += "<div class='conversations__list' style='' id='conversation"+res['data'][i]['id']+"' onclick='startGetMessages(" + res['data'][i]['id'] + ")'>" + res['data'][i]['theme'] + "</div>"; 
						}
						
						document.getElementById("conversationCount").innerHTML = res['data'].length;
						
					} else {
						var html = "";
						document.getElementById("conversationCount").innerHTML = 0;
					}
					document.getElementById("conversations").innerHTML = html;
				})
				.catch(err => console.error(err));
		}
		
		function changeStatus() {
			
			fetch("adminRequests.php?action=changeStatus&adminId="+document.getElementById("adminId").value)
				.then(res => res.json())
				
				.then(function(res) {
					if (res['status'] == 'ok') {
						document.getElementById("adminStatusImg").src = "assets/img/"+res['data']+".png";

						if (res['data'] == 'inactive') {
							
							clearInterval(intervalGetMessages);
							clearInterval(intervalGetConversations);
							
							document.getElementById("messages").innerHTML = "";
							document.getElementById("conversations").innerHTML = "";

							document.getElementById("conversationCount").innerHTML = 0;	
						
						} else {
							checkNullAdminConversations();
					
							clearInterval(intervalGetMessages);
							clearInterval(intervalGetConversations);
							
							getConversations('active');
							intervalGetConversations = setInterval(getConversations, 10000, 'active');
						}
					}
			})
			.catch(err => console.error(err));
		}
		
		function logout() {
			
			fetch("adminRequests.php?action=logout&adminId="+document.getElementById("adminId").value)
				.then(res => res.json())
				
				.then(function(res) {
					if (res['status'] == 'ok') {
						window.location.reload();
					}
			})
			.catch(err => console.error(err));
		}
		
		//проверка таблицы conversations на наличие строк с adminId=null, созданное при: 1. смена статуса на inactive оператора с открытыми разговорами, 2. разговоры созданы когда все операторы были inactive
		function checkNullAdminConversations() {
			
			fetch("adminRequests.php?action=checkNullAdminConversations&adminId="+document.getElementById("adminId").value)
			.then(res => res.text())
			.catch(err => console.error(err));			
		}
		
		function copyToClipboard() {
			navigator.clipboard.writeText(document.getElementById("uid").innerHTML);
		}
		
		function openWallet() {
			window.open("operatorWallet.php?uid="+document.getElementById("uid").innerHTML+"&site_id="+document.getElementById("siteId").value, 'mywin', 'width=500,height=500');
		}
		
		function openProfile() {
			window.open("operatorProfile.php?uid="+document.getElementById("uid").innerHTML+"&site_id="+document.getElementById("siteId").value, 'mywin', 'width=500,height=500');
		}
			
		function openTransactions() {
			window.open("operatorTransactions.php?uid="+document.getElementById("uid").innerHTML+"&site_id="+document.getElementById("siteId").value, 'mywin', 'width=500,height=500');
		}
		
		function openProcess() {
			window.open("operatorProcess.php?uid="+document.getElementById("uid").innerHTML+"&site_id="+document.getElementById("siteId").value, 'mywin', 'width=500,height=500');
		}
		
		function fromHistory() {
		
			let conversationId = document.getElementById('conversationId').value;
			
			document.getElementById("conversations").innerHTML = "";
			clearInterval(intervalGetConversations);
			
			fetch("adminRequests.php?action=fromHistory&id="+conversationId+"&adminId="+document.getElementById("adminId").value)
				.then(res => getConversations(document.getElementById("conversationStatus").value))
				.catch(err => console.error(err));
				
			document.getElementById("messages").innerHTML = ""; 
			clearInterval(intervalGetMessages);
			
			document.getElementById('fromHistory').className = "hidden";
		}
		
		function deleteMessage(conversationId) {
		
			fetch("adminRequests.php?action=deleteMessage&id="+conversationId)
				.then(res => res.json())	
				
				.then(function(res) {	
					if (res['status'] == 'ok') {
						clearInterval(intervalGetMessages);
						clearInterval(intervalGetConversations);
						
						document.getElementById("messages").innerHTML = "";
						document.getElementById("conversations").innerHTML = "";					
						
						startGetConversations('history');
					}
				});
		}
		
	</script>
</body>
</html>

<?php

} else {

?>

<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<title>admin</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<style>

		html {
		display: table;
		width: 100%;
		}

		.table {
			margin: 0 auto;
			border-spacing: 0;
			overflow: hidden;			
		}

	</style>
</head>
<body>
			
	<table border="0" width="15%" class="table">
	<tr>
		<td height="80px">
	<tr>
		<td height="80px" align="center">
		<form action="?" method="POST">
		Логин<br>
		<input type="text" name="login"><br><br>
		Пароль<br>
		<input type="password" name="password"><br><br>
		<input type="hidden" name="type" value="form"><br><br>
		<input type="submit" value="OK">
		</form>
	</table>

</body>
</html>	

<?php
	
}
?>
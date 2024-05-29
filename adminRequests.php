<?php

set_time_limit(0);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

date_default_timezone_set('Asia/Vladivostok');

$link = mysqli_connect("localhost", "vladimir", "royal");
if ($link == false){
	print("Ошибка: Невозможно подключиться к MySQL " . mysqli_connect_error());
	exit;
} else {
	//print("Соединение установлено успешно");
}

mysqli_select_db($link, "chat");
mysqli_query($link, "SET NAMES 'UTF8'");

////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
//ПОЛУЧЕНИЕ РАЗГОВОРОВ
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////

if ($_GET['action'] == 'getConversations') {

	$limit = ($_GET['status'] == "history") ? " LIMIT 50" : "";
	$adminId = ($_GET['status'] == "history") ? "" : "AND conversations.admin_id=".$_GET['adminId']."";
	$sql = "SELECT conversations.id,theme,SUM(IF(messages.readed='0', 1,0)) AS count_unreaded,closedBy,site_id FROM conversations LEFT JOIN messages ON conversations.id=messages.conversation_id WHERE status='".$_GET['status']."' ".$adminId." GROUP BY conversations.id ORDER BY conversations.id DESC".$limit;
	$result = mysqli_query($link, $sql);
	if (!$result) {
		print(mysqli_error($link));
	} else {
	
		$res = Array();
		if (mysqli_num_rows($result) > 0) {
			$res['status'] = 'ok';
			
			while ($row = mysqli_fetch_array($result)) { 
				$res['data'][] = ['id' => $row['id'], 'theme' => $row['theme'], 'count_unreaded' => $row['count_unreaded'], 'closed_by' => $row['closedBy'], 'site_id' => $row['site_id']];
			}
  			
		} else {
			$res['status'] = 'fail';
			
		}
		
		print json_encode($res, JSON_UNESCAPED_UNICODE);
	}
	 
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
//ПОЛУЧЕНИЕ СООБЩЕНИЙ
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////

} else if ($_GET['action'] == 'getMessages') {

	$sql = "SELECT message,sender,DATE_FORMAT(messages.ts, '%d.%m.%y %H:%i') AS ts,name,ip,conversations.status FROM messages LEFT JOIN admins ON messages.admin_id=admins.id LEFT JOIN conversations ON messages.conversation_id=conversations.id WHERE conversation_id='".$_GET['id']."' ORDER BY messages.id";
	//print($sql)."<br>";
	$result = mysqli_query($link, $sql);
	if (!$result) {
		print(mysqli_error($link));
	}
	
	$res = Array();
	$res['status'] = 'ok';
	
	while ($row = mysqli_fetch_array($result)) { 
		$res['data'][] = ['message' => $row['message'], 'ts' => $row['ts'], 'sender' => $row['sender'], 'name' => $row['name']];
		$res['userId'] = $row['ip'];
		$res['conversationStatus'] = $row['status'];
	}

	print json_encode($res, JSON_UNESCAPED_UNICODE);
	
	$sql = "UPDATE messages SET readed='1' WHERE conversation_id = '".$_GET['id']."' ORDER BY ts";
	$result = mysqli_query($link, $sql);
	if (!$result) {
		print(mysqli_error($link));
	}
	
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
//ВСТАВКА В БД СООБЩЕНИЕ ОПЕРАТОРА
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////

} else if ($_GET['action'] == 'sendAdminMes') {
	
	//удаление возможной табуляции
	$message = trim(preg_replace('/\t+/', '', $_GET['adminMessage']));
	$message = mysqli_real_escape_string($link, $message);		

	$sql = "INSERT INTO messages (message, conversation_id, sender, admin_id, ts) VALUES ('".$message."', ".$_GET['id'].", 'admin', '".$_GET['adminId']."', '".date("Y-m-d H:i:s")."')";
	$result = mysqli_query($link, $sql);
	if ($result == false) {
		print("Произошла ошибка при выполнении запроса ") . mysqli_error($link) . "<br>";
	} 	
	
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
//ПЕРЕИМЕНОВАНИЕ ТЕМЫ
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////

} else if ($_GET['action'] == 'renameTheme') {	

	$sql = "UPDATE conversations SET theme='".$_GET['theme']."' WHERE id = ".$_GET['id'];
	print $sql;
	$result = mysqli_query($link, $sql);
	if (!$result) {
		print(mysqli_error($link));
	}	

////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
//ОТПРАВКА В ИСТОРИЮ
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////

} else if ($_GET['action'] == 'toHistory') {	

	$sql = "UPDATE conversations SET status='history',closedBy='operator' WHERE id = ".$_GET['id'];
	$result = mysqli_query($link, $sql);
	if (!$result) {
		print(mysqli_error($link));
	}	

	$sql = "UPDATE admins SET countConversations=countConversations-1 WHERE id = ".$_GET['adminId'];
	$result = mysqli_query($link, $sql);
	if (!$result) {
		print(mysqli_error($link));
	}	
	
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
//ВОЗВРАТ ИЗ ИСТОРИИ
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////

} else if ($_GET['action'] == 'fromHistory') {	

	$sql = "UPDATE conversations SET status='active',admin_id=".$_GET['adminId']." WHERE id = ".$_GET['id'];
	$result = mysqli_query($link, $sql);
	if (!$result) {
		print(mysqli_error($link));
	}	

	$sql = "UPDATE admins SET countConversations=countConversations+1 WHERE id = ".$_GET['adminId'];
	$result = mysqli_query($link, $sql);
	if (!$result) {
		print(mysqli_error($link));
	}

////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
//ПОИСК СООБЩЕНИЯ В АРХИВЕ
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////

} else if ($_GET['action'] == 'searchHistory') {	

	//file_put_contents(__DIR__.'/output.log', file_get_contents('php://input'));
	
	$sql = "SELECT id,theme FROM conversations WHERE theme LIKE '%".$_GET['searchHistoryValue']."%' OR ip='".$_GET['searchHistoryValue']."' ORDER BY ts";
	//print($sql)."<br>";
	$result = mysqli_query($link, $sql);
	if (!$result) {
		print(mysqli_error($link));
	} else {
	
		$res = Array();

		if (mysqli_num_rows($result) > 0) {
			
			$res['status'] = 'ok';
		
			while ($row = mysqli_fetch_array($result)) { 
				$res['data'][] = ['id' => $row['id'], 'theme' => $row['theme']];
			}

		} else {
			
			$res['status'] = 'error';
		}

		print json_encode($res, JSON_UNESCAPED_UNICODE);	
	}

////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
//СМЕНА СТАТУСА
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////

} else if ($_GET['action'] == 'changeStatus') {	

	$sql = "SELECT status FROM admins WHERE id=".$_GET['adminId'];
	//print($sql)."<br>";
	$result = mysqli_query($link, $sql);
	if (!$result) {
		print(mysqli_error($link));
	}
	$row = mysqli_fetch_array($result);
	$status = $row['status'] == 'active' ? 'inactive' : 'active';
	
	$sql = "UPDATE admins SET status='".$status."' WHERE id=".$_GET['adminId'];
	//print($sql)."<br>";
	$result = mysqli_query($link, $sql);
	if (!$result) {
		print(mysqli_error($link));
	}
	
	//при переключении на статус inactive
	if ($status == 'inactive') {
		updateConversations($link);
	}
	
	$res = Array();
	$res['status'] = 'ok';
	$res['data'] = $status;

	print json_encode($res);

////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
//LOGOUT
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////

} else if ($_GET['action'] == 'logout') {	

	session_start();
	session_destroy();
	
	$sql = "UPDATE admins SET status='inactive' WHERE id=".$_GET['adminId'];
	//print($sql)."<br>";
	$result = mysqli_query($link, $sql);
	if (!$result) {
		print(mysqli_error($link));
	}
	
	$res = Array();
	$res['status'] = 'ok';
	
	updateConversations($link);

	print json_encode($res);	

////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
//ПРОВЕРКА ТАБЛИЦЫ conversations НА НАЛИЧИЕ СТРОК С adminId=null, СОЗДАННОЕ ПРИ: 1. СМЕНА СТАТУСА НА inactive ОПЕРАТОРА С ОТКРЫТЫМИ РАЗГОВОРАМИ, 2. РАЗГОВОРЫ СОЗДАНЫ КОГДА ВСЕ ОПЕРАТОРЫ БЫЛИ inactive
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////

} else if ($_GET['action'] == 'checkNullAdminConversations') {	

	$sql = "SELECT id FROM conversations WHERE admin_id IS NULL AND status='active'";
	//print($sql)."<br>";
	$result = mysqli_query($link, $sql);
	if (!$result) {
		print(mysqli_error($link));
	}
	
	if (mysqli_num_rows($result) != 0) {
		$num_rows = mysqli_num_rows($result);
		
		$sql = "UPDATE conversations SET admin_id=".$_GET['adminId']." WHERE admin_id IS NULL AND status='active'";
		//print($sql)."<br>";
		$result = mysqli_query($link, $sql);
		if (!$result) {
			print(mysqli_error($link));
		}
		
		$sql = "UPDATE admins SET countConversations=countConversations+".$num_rows." WHERE id=".$_GET['adminId']." AND status='active'";
		//print($sql)."<br>";
		$result = mysqli_query($link, $sql);
		if (!$result) {
			print(mysqli_error($link));
		}
	}
	
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
//УДАЛЕНИЕ СООБЩЕНИЯ В ИСТОРИИ
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////

} else if ($_GET['action'] == 'deleteMessage') {	

	if (empty($_GET['id']) || $_GET['id'] == '') {
		
		$res = Array();
		$res['status'] = 'error';
		print json_encode($res);
		return;		
	}
	
	$sql = "DELETE FROM conversations WHERE id=".$_GET['id'];
	//print($sql)."<br>";
	$result = mysqli_query($link, $sql);
	if (!$result) {
		print(mysqli_error($link));
	}
	
	$res = Array();
	$res['status'] = 'ok';
	print json_encode($res);
}

////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
//ПРИ ПЕРЕКЛЮЧЕНИИ НА СТАТУС INACTIVE ИЛИ РАЗЛОГИРОВАНИИ, ПЕРЕНАЗНАЧЕНИЕ conversation НА ДРУГОГО АКТИВНОГО ОПЕРАТОРА ИЛИ ДЛЯ conversations УСТАНОВКА adminId=null
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////

function updateConversations($link) {

	//обнуление количества разговоров оператора
	$sql = "UPDATE admins SET countConversations=0 WHERE id=".$_GET['adminId'];
	//print($sql)."<br>";
	$result = mysqli_query($link, $sql);
	if ($result == false) {
		print("Произошла ошибка при выполнении запроса ") . mysqli_error($link) . "<br>";
	}

	$sql = "SELECT id FROM conversations WHERE admin_id=".$_GET['adminId']." AND status='active'";
	//print($sql)."<br>";
	$result = mysqli_query($link, $sql);
	if (!$result) {
		print(mysqli_error($link));
	}
	
	if (mysqli_num_rows($result) != 0) {
		$num_rows = mysqli_num_rows($result);
	
		//проверка наличия активных операторов
		$sql = "SELECT id FROM admins WHERE status='active' ORDER BY RAND() LIMIT 1";
		//print($sql)."<br>";
		$result = mysqli_query($link, $sql);
		if (!$result) {
			print(mysqli_error($link));
		}

		//если есть активные операторы, то переназначение разговоров, иначе заявки остаются без оператора до авторизации любого оператора
		if (mysqli_num_rows($result) != 0) {
			$row = mysqli_fetch_array($result);
			$newAdminId = $row['id'];

		} else {
			$newAdminId = 'NULL';
		}
		
		$sql = "UPDATE conversations SET admin_id=".$newAdminId." WHERE admin_id=".$_GET['adminId']." AND status='active'";
		//print($sql)."<br>";
		$result = mysqli_query($link, $sql);
		if (!$result) {
			print(mysqli_error($link));
		}
		
		if ($newAdminId != 'NULL') {
			$sql = "UPDATE admins SET countConversations=countConversations+".$num_rows." WHERE id=".$newAdminId." AND status='active'";
			//print($sql)."<br>";
			$result = mysqli_query($link, $sql);
			if (!$result) {
				print(mysqli_error($link));
			}
		}
	}
}
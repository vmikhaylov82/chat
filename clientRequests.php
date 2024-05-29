<?php

set_time_limit(0);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
date_default_timezone_set('Asia/Vladivostok');

require_once __DIR__.'/assets/libs/sms.ru.php';

$link = mysqli_connect("localhost", "vladimir", "royal");
if ($link == false){
	print("Ошибка: Невозможно подключиться к MySQL " . mysqli_connect_error());
	exit;
} 

mysqli_select_db($link, "chat");
mysqli_query($link, "SET NAMES 'UTF8'");

////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
//ВСТАВКА СООБЩЕНИЯ
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////

//file_put_contents(__DIR__.'/output.log', urldecode(file_get_contents('php://input')).PHP_EOL, FILE_APPEND);

if (isset($_POST['type']) && $_POST['type'] == 'request') {
	
	//ПРОВЕРКА ИМЕЮЩЕГОСЯ СООБЩЕНИЯ

	//если не авторизованный - переменная authorized существует и равна 0,
	//если авторизованный - переменная authorized не существует
	if (isset($_POST['authorized']) && !isset($_POST['chat_id'])) {
		return;
	}
	
	$chat_id = isset($_POST['authorized']) ? " AND chat_id='".$_POST['chat_id']."'" : "";
	
	$sql = "SELECT id, admin_id FROM conversations WHERE status='active' AND ip='".$_POST['user']."'".$chat_id;
	//print($sql)."<br>";
	$result = mysqli_query($link, $sql);
	if ($result == false) {
		print("Произошла ошибка при выполнении запроса ") . mysqli_error($link) . "<br>";
	} 	
	
	$message = urldecode($_POST['message']);
	$message = trim(preg_replace('/\t+/', '', $message));
	$message = mysqli_real_escape_string($link, $message);
			
	if (mysqli_num_rows($result) == 0) {
		
		//СОЗДАНИЕ НОВОГО СООБЩЕНИЯ
		
		$theme = substr($message, 0, 35);
		$theme = strip_tags(htmlspecialchars_decode($theme));
		
		//СОЗДАНИЕ CONVERSATION
		$chat_id = isset($_POST['authorized']) ? $_POST['chat_id'] : "";
		
		$site_id = !isset($_POST['site_id']) ? "v" : $_POST['site_id'];
		$sql = "INSERT INTO conversations (theme, ip, chat_id, site_id) VALUES ('".$theme."', '".$_POST['user']."', '".$chat_id."', '".$site_id."')";
		//print $sql."<br>";
		$result = mysqli_query($link, $sql);
		if ($result == false) {
			print("Произошла ошибка при выполнении запроса ") . mysqli_error($link) . "<br>";
		}
		$last_conversation_id = mysqli_insert_id($link);

		//СОЗДАНИЕ MESSAGES
		$sql = "INSERT INTO messages (message, conversation_id, sender, user_id, ts) VALUES ('".$message."', ".$last_conversation_id.", 'user', '".$_POST['user']."', '".date("Y-m-d H:i:s")."')";
		//print $sql."<br>";
		$result = mysqli_query($link, $sql);
		if ($result == false) {
			print("Произошла ошибка при выполнении запроса ") . mysqli_error($link) . "<br>";
		}
		
		//ВЫБОР ADMIN
		$sql = "SELECT id FROM admins WHERE countConversations = (SELECT MIN(countConversations) FROM admins WHERE status='active') AND status='active' ORDER BY RAND() LIMIT 1";
		$result2 = mysqli_query($link, $sql);
		if ($result2 == false) {
			print("Произошла ошибка при выполнении запроса ") . mysqli_error($link) . "<br>";
		}
		
		//если есть активный оператор, иначе поле adminId остается NULL в conversations
		if (mysqli_num_rows($result2) != 0) {
			$row = mysqli_fetch_array($result2);
			
			$sql = "UPDATE conversations SET admin_id='".$row['id']."' WHERE id='".$last_conversation_id."'";
			$result = mysqli_query($link, $sql);
			if ($result == false) {
				print("Произошла ошибка при выполнении запроса ") . mysqli_error($link) . "<br>";
			}
			
			$sql = "UPDATE admins SET countConversations=countConversations+1 WHERE id='".$row['id']."'";
			$result = mysqli_query($link, $sql);
			if ($result == false) {
				print("Произошла ошибка при выполнении запроса ") . mysqli_error($link) . "<br>";
			} 
			
			//сопоставление оператора и отправка смс
			$phone = "";
			if ($row['id'] == 1) {
				if (in_array(date('H'), array('00','01','02','03','04','05','06','07','08')) && in_array(date('N'), array('1','2','3','4','5','6','7'))) {
					//Екатерина
					$phone = "79242356755";
				}
			} else if ($row['id'] == 2) {		
				if (in_array(date('H'), array('14','15','16','17','18','19','20','21','22','23')) && in_array(date('N'), array('1','2','3','4','5'))) {
					//Анастасия
					//$phone = "79965618188";
				}
			}
			if ($phone != "") {
				$smsru = new SMSRU('14329855-7DF2-221E-4701-7769B90D2CD9'); 
				$data = new stdClass();
	
				$data->to = $phone;
				$data->text = "Получено сообщение в чат.";
				$sms = $smsru->send_one($data); 
			}			
		
		} else {
			
			$smsru = new SMSRU('14329855-7DF2-221E-4701-7769B90D2CD9'); 
			$data = new stdClass();

			//активных операторов нет - отправка смс 
			$data->to = "79143262697";
			$data->text = "Получено сообщение в чат.";
			$sms = $smsru->send_one($data); 
			
			$data->to = "79143215166";
			$data->text = "Получено сообщение в чат.";
			$sms = $smsru->send_one($data); 
		}
			
	} else {
		
		//ДОБАВЛЕНИЕ СООБЩЕНИЯ В ИМЕЮЩИЙСЯ РАЗГОВОР
		$row = mysqli_fetch_array($result);

		$sql = "INSERT INTO messages (message, conversation_id, sender, user_id, ts) VALUES ('".$message."', ".$row['id'].", 'user', '".$_POST['user']."', '".date("Y-m-d H:i:s")."')";
		//print $sql."<br>";
		$result = mysqli_query($link, $sql);
		if ($result == false) {
			print("Произошла ошибка при выполнении запроса ") . mysqli_error($link) . "<br>";
		} 	
		
		//сопоставление оператора и отправка смс
		$phone = "";
		if ($row['admin_id'] == 1) {
			if (in_array(date('H'), array('00','01','02','03','04','05','06','07','08')) && in_array(date('N'), array('1','2','3','4','5','6','7'))) {
				//Екатерина
				$phone = "79242356755";
			}
		} else if ($row['admin_id'] == 2) {
			if (in_array(date('H'), array('14','15','16','17','18','19','20','21','22','23')) && in_array(date('N'), array('1','2','3','4','5'))) {
				//Анастасия
				//$phone = "79965618188";
			}
		}
		if ($phone != "") {
			$smsru = new SMSRU('14329855-7DF2-221E-4701-7769B90D2CD9'); 
			$data = new stdClass();

			$data->to = $phone;
			//file_put_contents(__DIR__."/sms.log", "Отправка на номер ".$phone." (".date("d.m.Y H:i").")".PHP_EOL, FILE_APPEND);
			$data->text = "Получено сообщение в чат.";
			$sms = $smsru->send_one($data); 
		} 
	}
	
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
//ВЫБОРКА СООБЩЕНИЙ
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////

} else if (isset($_POST['type']) && $_POST['type'] == 'check') {
	
	if (isset($_POST['authorized']) && !isset($_POST['chat_id'])) {
		return;
	}
	
	if ($_POST['status'] == 'active') {
		$chat_id = isset($_POST['authorized']) ? " AND chat_id='".$_POST['chat_id']."'" : "";
		$sql = "SELECT messages.message, admins.name, messages.sender, DATE_FORMAT(messages.ts, '%H:%i') AS ts, conversations.status FROM conversations INNER JOIN messages ON conversations.id=messages.conversation_id LEFT JOIN admins ON messages.admin_id=admins.id WHERE conversations.status='active' AND conversations.ip='".$_POST['user']."' ".$chat_id." ORDER BY messages.id";
	} else {
		$sql = "SELECT messages.message, admins.name, messages.sender, DATE_FORMAT(messages.ts, '%H:%i') AS ts, conversations.status FROM conversations INNER JOIN messages ON conversations.id=messages.conversation_id LEFT JOIN admins ON messages.admin_id=admins.id WHERE conversations.id=".$_POST['idConversation']." ORDER BY messages.id";
	}
	//print $sql."<br>";
	$result = mysqli_query($link, $sql);
	if ($result == false) {
		print("Произошла ошибка при выполнении запроса ") . mysqli_error($link) . "<br>";
	} 	

	$res = Array();
	while ($row = mysqli_fetch_array($result)) {
		$res[] = array("message" => $row['message'], "sender" => $row['sender'], "name" => $row['name'], "ts" => $row['ts'], "status" => $row['status']);
	}
	
	print json_encode($res, JSON_UNESCAPED_UNICODE);
	
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
//ВЫБОРКА ТЕМ ИСТОРИИ
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////

} else if (isset($_POST['type']) && $_POST['type'] == 'history') {
	
	if (isset($_POST['authorized']) && !isset($_POST['chat_id'])) {
		$res[] = array();
		print json_encode($res, JSON_UNESCAPED_UNICODE);
	}
	
	$chat_id = isset($_POST['authorized']) ? " AND chat_id='".$_POST['chat_id']."'" : "";
	
	$sql = "SELECT id, theme, DATE_FORMAT(ts, '%d.%m.%Y') AS ts FROM conversations WHERE conversations.status='history' AND conversations.ip='".$_POST['user']."' ".$chat_id." ORDER BY ts DESC LIMIT 10";
	
	$result = mysqli_query($link, $sql);
	if ($result == false) {
		print("Произошла ошибка при выполнении запроса ") . mysqli_error($link) . "<br>";
	} 	

	$res = Array();
	while ($row = mysqli_fetch_array($result)) {
		$res[] = array("id" => $row['id'], "theme" => $row['theme'], "ts" => $row['ts']);
	}
	
	print json_encode($res, JSON_UNESCAPED_UNICODE);

////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
//ОТПРАВКА В ИСТОРИЮ ПОЛЬЗОВАТЕЛЕМ
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////

} else if (isset($_POST['type']) && $_POST['type'] == 'toHistory') {
	
	if (isset($_POST['authorized']) && !isset($_POST['chat_id'])) {
		return;
	}
	
	$chat_id = isset($_POST['authorized']) ? " AND chat_id='".$_POST['chat_id']."'" : "";
	
	$sql = "SELECT admin_id FROM conversations WHERE ip='".$_POST['user']."' AND status='active'";
	$result = mysqli_query($link, $sql);
	if (!$result) {
		print(mysqli_error($link));
	}	
	
	$row = mysqli_fetch_array($result);
	
	$sql = "UPDATE conversations SET status='history',closedBy='client' WHERE ip='".$_POST['user']."' ".$chat_id." AND status='active'";
	$result = mysqli_query($link, $sql);
	if (!$result) {
		print(mysqli_error($link));
	}	

	$sql = "UPDATE admins SET countConversations=countConversations-1 WHERE id=".$row['admin_id'];
	$result = mysqli_query($link, $sql);
	if (!$result) {
		print(mysqli_error($link));
	}

////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
//ОТПРАВКА ФАЙЛА
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////

} else if (isset($_POST['type']) && $_POST['type'] == 'upload') {
	
	switch ($_FILES['file']['type']) {
		case 'image/jpeg':
			$type = ".jpeg";
			break;
		case 'image/png':
			$type = ".png";
			break;
		case 'image/webp':
			$type = ".webp";
			break;
		case 'image/jpg':
			$type = ".jpg";
			break;
	}
		
	$newFile = substr(hash('sha256', rand(111111, 999999)), 0, 32);
	
	if (move_uploaded_file($_FILES['file']['tmp_name'], __DIR__."/assets/upload/".$newFile.$type)) {
		print json_encode(array('status' => 'true', 'path' => 'https://v-world.site/chat/assets/upload/'.$newFile.$type));
	}
}

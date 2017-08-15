<?if(!defined("CMS_BASTION") || CMS_BASTION!==true) {
	header('HTTP/1.1 404 Not Found');
	header("Status: 404 Not Found");
	die();
}?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" type="text/css" href="/css/adm/style.css" />
		<link href="http://fonts.googleapis.com/css?family=Play" rel="stylesheet" type="text/css">
		<title>Авторизоватся - Bastion CMS</title>
	</head>
	<body style="background: #ecf0f5;">
		<div class="login">
			<div class="headlogin">
				<div class="headtitle">
					<p><b>Bastion</b> CMS</p>
				</div>
			</div>
			<div class="bodylogin">
				<div class="leftbody">
					<p>Введите существующие логин и пароль для доступа к панели управления</p>
					<p>Обратите внимание - строчные и прописные буквы различаются</p>
				</div>
				<div class="rightbody">
					<div align="right">
					<span class="error"><?=$data['error']?></span>
					
					<form action="/adm/login/" method="post">
					<input type="hidden" name="action" value="login" >
					<input type='text' name='login' id="login" value='' class="inputlogin" required placeholder="Введите логин">
					<br>
					<input type='password' name='pwd' id="pwd" class="pass" value='' size='40' required placeholder="Введите пароль">
					<br>
					<input type="submit" class="enter" value="Войти">
					</form>
					</div>
				</div>
			</div>
			<div class="footerlogin">
				&copy; Сайт работает на <a href="http://bastiondesign.ru/cms-services.html">Bastion CMS v3.0</a><br>
				Данный продук защищен авторскими правами. Тех. поддержка: <a href="mailto:info@bastiondesign.ru">info@bastiondesign.ru</a>
			</div>
		</div>
	</body>
</html>
<?if(!defined("CMS_BASTION") || CMS_BASTION!==true) {
	header('HTTP/1.1 404 Not Found');
	header("Status: 404 Not Found");
	die();
}?>
<div class="nav"><? echo $data['nav']; ?></div>
<h1><?=$data['title']?></h1>
<?=$data['text']?>


<?if (isset($data["form_send"]))
{?>
	<h2>Обратная связь</h2>
	<?$form = $data["form_send"];
	echo $form["message"]; //сообщение об отправки письма
	?>	
	<form action='' method='post' enctype='multipart/form-data'>
		<div class="lableform">Введите ваше имя:<span class='error'>*</span></div>
		<input type='text' name='fio' class="form" value='<?=$form["form_fio"]?>' placeholder="Представьтесь" required pattern="[а-яА-Яa-zA-Z0-9_- ]{3,255}">
		<span class='error'><?=$form["error_fio"]?></span><br/>
		<div class="lableform">E-mail адрес:<span class='error'>*</span></div>
		<input type='email' name='email' class="form" value='<?=$form["form_email"]?>' placeholder="Как с Вами связаться?"  required pattern="^([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$" />
		<span class='error'><?=$form["error_email"]?></span><br/>
		<div class="lableform">Тема сообщения:<span class='error'>*</span></div>
		<input type='text' name='subject' class="form" value='<?=$form["form_subject"]?>' placeholder="Кратко суть вопроса (передложения)" required>
		<span class='error'><?=$form["error_subject"]?></span><br/>
		<!--<input type="file" name="filename" size="30">-->
		<div class="lableform">Введите текст вашего сообщения:<span class='error'>*</span></div><span class='error'><?=$form["error_mes_content"]?></span>	
		<textarea name='mes_content' rows='6' cols='30' class="form" placeholder="Подробно что Вы хотели?" required><?=$form["form_mes_content"]?></textarea><br/>
		<input type='checkbox' name='copy' value='1' <?=$form["copy"]?>/>  - Отправить копию этого сообщения на ваш адрес<br/>
		<span class='error'><?=$form["error_captcha"]?></span>
		<div class="g-recaptcha" data-sitekey="<?=reCAPTCHA_KEY?>"></div>
		<div class="lableform"><span class='error'>*</span> - поля обязательные для заполнения</div>
					
		<input type='submit' name='btn' class="cbutton" value='Отправить' />
		<input type='hidden' name='action' value='<?=$form["action"]?>' />
	</form>
	<p> </p>
<?
}?>
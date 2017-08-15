<?if(!defined("CMS_BASTION") || CMS_BASTION!==true) {
	header('HTTP/1.1 404 Not Found');
	header("Status: 404 Not Found");
	die();
}?>
<h1><?echo $data['main_title'];?></h1>
<form action="" method="post" enctype="multipart/form-data">
<div class="section">
	<ul class="tabs">
		<li class="current">Общая информация</li>
		<li>SEO</li>
		<li>Натройки</li>
	</ul>
	<div class="box visible">
		<table cellspacing="12" cellpadding="12" >
			<tbody>
				<tr>
					<td class="lable">
						Дата:
					</td>
					<td>
						<input name="news_date" type="datetime" value="<? echo date("d.m.Y H:i:s", $data['news_date'])?>" class="datepickerTimeField">
					</td>
				</tr>
				<tr>
					<td class="lable">
						Название:
					</td>
					<td>
						<input type="text" name="name" value="<?=$data['name']?>" required autofocus> <span class="error"><?=$data["name_error"]?></span>
					</td>
				</tr>
				<tr>
					<td class="lable">
						Адрес (url):
					</td>
					<td>
						<input type="text" name="url" value="<?=$data['url']?>"> <span class="error"><?=$data["url_error"]?></span>
						<div class="help-tip"><i class="fa fa-question-circle-o"></i>
							<p>Название ссылки в адресной строке (по умолчанию транслит названия)</p>
						</div>
					</td>
				</tr>
				<tr>
					<td class="lable">
						Раздел:
					</td>
					<td>
						<?=$data['parents']?>
					</td>
				</tr>			
				<tr>
					<td colspan="2" class="lable">
						Подробное описание:
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<textarea name="text"><?=$data['text']?></textarea>
						<?=$data["editor"]?>
					</td>
				</tr>
			</tbody>
		</table>	
	</div>
	<div class="box">
		<table cellspacing="12" cellpadding="12">
			<tbody>
				<tr>
					<td class="lable">
						Заголовок(&#60;h1&#62;):
					</td>
					<td>
						<input type="text" name="title" value="<?=$data['title']?>">
					</td>
				</tr>
				<tr>
					<td class="lable">
						Заголовок(&#60;title&#62;):
					</td>
					<td>
						<input type="text" id="head_title" name="head_title" value="<?=$data['head_title']?>">
						<br><span id="charlimitinfotitle"></span> 
					</td>
				</tr>					
				<tr>
					<td class="lable">
						Ключевые слова:<br>
						(keywords)
					</td>
					<td>
						<textarea name="keywords"><?=$data['keywords']?></textarea>
					</td>
				</tr>
				<tr>
					<td class="lable">
						Описание:<br>
						(description)
					</td>
					<td>
						<textarea name="description"  id="description"><?=$data['description']?></textarea>
						<br /><span id="charlimitinfo"></span>
					</td>
				</tr>
				</tbody>
		</table>
	</div>
	<div class="box">
		<table cellspacing="12" cellpadding="12">
			<tbody>
				<tr>
					<td class="lable">
						Выводить в меню сайта:
					</td>
					<td>
						<label>
							<input class="checkbox" type="checkbox" id="is_menu" name="is_menu" value="1" <?echo ($data["is_menu"] == 0)?"":"checked";?>>
							<label for="is_menu"></label>						
						</label>
					</td>
				</tr>
				<tr>
					<td class="lable">
						Открывать в новом окне:
					</td>
					<td>
						<label>
							<input type="checkbox" class="checkbox" id="target" name="target" value="1" <?echo ($data["target"] == 0)?"":"checked";?>>
							<label for="target"></label>						
						</label>
					</td>
				</tr>
				<tr>
					<td class="lable">
						Шаблон сайта:
					</td>
					<td>
						<label>
							<?=$data['tamplatemain']?>				
						</label>
					</td>
				</tr>
				<tr>
					<td class="lable">
						Шаблон содержимого:
					</td>
					<td>
						<label>
							<?=$data['tamplateview']?>				
						</label>
					</td>
				</tr>
			<tbody>
		</table>
		
	</div>
</div>
<table cellspacing="12" cellpadding="12" >
	<tbody>		
		<tr>			
			<td colspan="2">
				<button type="submit" class="savenew">
					<i class="fa fa-floppy-o"></i> Сохранить
				</button>
				<button type="button" class="cancel" onClick="window.location.href='/adm/menus/'">
					<i class="fa fa-ban"></i> Отмена
				</button>
				<input type="hidden" name="action" value="<?=$data["action"]?>" />
				<input type="hidden" name="token" value="<?=$data["token"]?>" />
			</td>			
		</tr>
		<?if (isset ($data["update"])) {?>
		<tr>
			<td class="lable">
				Дата редактирования:
			</td>
			<td>
				<?=$data["update"]["update_date"]?>
			</td>
		</tr>
		<tr>
			<td class="lable">
				Пользователь:
			</td>
			<td>
				<?=$data["update"]["update_user"]?>
			</td>
		</tr>
		<?}?>
	</tbody>
</table>
</form>
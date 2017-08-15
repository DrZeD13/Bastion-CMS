<?if(!defined("CMS_BASTION") || CMS_BASTION!==true) {
	header('HTTP/1.1 404 Not Found');
	header("Status: 404 Not Found");
	die();
}?>
<h1><?echo $data['main_title'];?></h1>
<form action="" method="post" enctype="multipart/form-data">
<table cellspacing="12" cellpadding="12" >
	<tbody>
		<tr>
			<td class="lable">
				Название:
			</td>
			<td>
				<input type="text" name="title" value="<?=$data['title']?>" required> <span class="error"><?=$data["title_error"]?></span>
			</td>
		</tr>
		<tr>
			<td class="lable">
				Дата:
			</td>
			<td>
				<?=$data['news_date']?>
			</td>
		</tr>	
		<tr>
			<td class="lable">
				Тип (type):
			</td>
			<td>
				<?=$data['type']?>
			</td>
		</tr>
		<tr>
			<td class="lable">
				Название (name):
			</td>
			<td>
				<input type="text" name="name" value="<?=$data['name']?>"> 
			</td>
		</tr>
		<tr>
			<td class="lable">
				Значения (value):
			</td>
			<td>
				<textarea name="value"><?=$data['value']?></textarea>
			</td>
		</tr>
		<tr>
			<td class="lable">
				Значение по-умолчанию (только для Select):
			</td>
			<td>
				<input type="text" name="def" value="<?=$data['def']?>"> 
			</td>
		</tr>
		<tr>
			<td class="lable">
				Заполнитель (placeholder):
			</td>
			<td>
				<input type="text" name="placeholder" value="<?=$data['placeholder']?>"> 
			</td>
		</tr>
		<tr>
			<td class="lable">
				Метка (label):
			</td>
			<td>
				<input type="text" name="label" value="<?=$data['label']?>"> 
			</td>
		</tr>
		<tr>
			<td class="lable">
				Класс (class):
			</td>
			<td>
				<input type="text" name="class" value="<?=$data['class']?>"> 
			</td>
		</tr>
		<tr>
			<td class="lable">
				Обязательное (required):
			</td>
			<td>
				<?=$data['required']?>
			</td>
		</tr>
		<tr>			
			<td colspan="2">				
				<button type="submit" class="savenew">
					<i class="fa fa-floppy-o"></i> Сохранить
				</button>
				<button type="button" class="cancel" onClick="window.location.href='/adm/form_item/'">
					<i class="fa fa-ban"></i> Отмена
				</button>								
				<input type="hidden" name="action" value="<?=$data["action"]?>" />
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

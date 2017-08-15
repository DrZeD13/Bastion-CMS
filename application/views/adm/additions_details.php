<h1><?echo $data['main_title'];?></h1>
<form action="" method="post" enctype="multipart/form-data">
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
				Модуль:
			</td>
			<td>
				<?=$data['module']?>
			</td>
		</tr>
		<tr>
			<td class="lable">
				Цена:
			</td>
			<td>
				<input type="text" name="price" value="<?=$data['price']?>">
			</td>
		</tr>
	</tbody>
</table>	
<table cellspacing="12" cellpadding="12" >
	<tbody>	
		<tr>			
			<td colspan="2">
				<button type="submit" class="savenew">
					<i class="fa fa-floppy-o"></i> Сохранить
				</button>
				<button type="button" class="cancel" onClick="window.location.href='/adm/additions/'">
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
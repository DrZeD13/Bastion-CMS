<?if(!defined("CMS_BASTION") || CMS_BASTION!==true) {
	header('HTTP/1.1 404 Not Found');
	header("Status: 404 Not Found");
	die();
}?>
<section class="content-header">
    <h1><?echo $data['main_title'];?></h1>
</section>
<section class="content">
	<div class="row">
		<div class="col-md-12">
			<div class="main-content">
			<div class="tab-content">
				<form action="" method="post" enctype="multipart/form-data">
				<table class="table-main">
				<tbody>
					<tr>
						<td class="lable">
							Название:
						</td>
						<td>
							<input type="text" name="title" value="<?=$data['title']?>" required autofocus> <span class="error"><?=$data["title_error"]?></span>
						</td>
					</tr>
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
							Текст на кнопке:
						</td>
						<td>
							<input type="text" name="button" value="<?=$data['button']?>">
						</td>
					</tr>
					<tr>
						<td class="lable">
							Ссылка:
						</td>
						<td>
							<input type="text" name="url" value="<?=$data['url']?>">
						</td>
					</tr>
					<tr>
						<td class="lable">
							Картинка:
						</td>
						<td>
							<?if (isset($data["filename"]))
							{?>
								[<a href="<?=$data["filename"]["link"]?>" onClick="return confirm ('Вы действительно хотите удалить это изображение?');">Удалить изображение</a>]
								<img src='<?=$data["filename"]["img"]?>' alt='Изображение' />
							<?}
							else
							{?>
								<input type='file' name='filename' size='30' />
							<?}?>
						</td>
					</tr>
					<tr>
						<td class="lable">
							Краткое описание:
						</td>
						<td>
							<textarea name="short_text"><?=$data['short_text']?></textarea>
						</td>
					</tr>
					<tr>			
						<td colspan="2">
							<button type="submit" class="savenew">
								<i class="fa fa-floppy-o"></i> Сохранить
							</button>
							<button type="button" class="cancel" onClick="window.location.href='/adm/<?=$data["table_name"]?>/'">
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
			</div>
			</div>
		</div>
	</div>
</section>
<h1><?echo $data['main_title'];?></h1>
<form action="" method="post" enctype="multipart/form-data">
<div class="section">
	<ul class="tabs">
		<li class="current">Общая информация</li>
		<li>SEO</li>
	</ul>
	<div class="box visible">
		<table cellspacing="12" cellpadding="12" >
			<tbody>
				<tr>
					<td class="lable">
						Дата:
					</td>
					<td>
						<input name="news_date" type="text" value="<? echo date("d.m.Y H:i:s", $data['news_date'])?>" class="datepickerTimeField">
					</td>
				</tr>
				<tr>
					<td class="lable">
						Название:
					</td>
					<td>
						<input type="text" name="name" value='<?=$data['name']?>' required> <span class="error"><?=$data["name_error"]?></span>								
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
					<td class="lable">
						Категория:
					</td>
					<td>
						<?=$data['category']?>
					</td>
				</tr>
				<tr>
					<td class="lable">
						Теги:
					</td>
					<td>
						<?=$data['tags']?>
					</td>
				</tr>
				<tr>
					<td class="lable">
						Дополнительные опции:
					</td>
					<td>
						<?=$data['options']?>
					</td>
				</tr>
				<tr>
					<td class="lable">
						Цена:
					</td>
					<td>
						<input type="text" name="price" value='<?=$data['price']?>'>								
					</td>
				</tr>
				<tr>
					<td class="lable">
						Рекомендуем:
					</td>
					<td>
						<input type="checkbox" class="checkbox" id="recomend" name="recomend" value="1" <?echo ($data['recomend'] == 0)?"":"checked";?>>
						<label for="recomend"></label>
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
						<input type="text" name="title" value='<?=$data['title']?>'>								
					</td>
				</tr>
				<tr>
					<td class="lable">
						Заголовок(&#60;title&#62;):
					</td>
					<td>
						<input type="text" id="head_title" name="head_title" value='<?=$data['head_title']?>'>
						<br><span id="charlimitinfotitle"></span> 
					</td>
				</tr>
				<tr>
					<td class="lable">
						url:
					</td>
					<td>
						<input type="text" name="url" value="<?=$data['url']?>"> <span class="error"><?=$data["url_error"]?></span>
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
</div>
<table cellspacing="12" cellpadding="12" >
	<tbody>
		<tr>			
			<td colspan="2">
				<button type="submit" class="savenew">
					<i class="fa fa-floppy-o"></i> Сохранить
				</button>
				<button type="button" class="cancel" onClick="window.location.href='/adm/shop/'">
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

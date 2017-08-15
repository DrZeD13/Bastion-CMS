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
				<form action="" method="post" enctype="multipart/form-data">
				<div class="nav-tabs-custom">
					<ul class="nav nav-tabs">
						<li class="active"><a href="#info" data-toggle="tab" aria-expanded="true">Общая информация</a></li>
						<li><a href="#seo" data-toggle="tab" aria-expanded="false">SEO</a></li>
					</ul>
					<div class="tab-content">
					<div class="tab-pane active" id="info">
						<table class="table-main">
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
				<div class="tab-pane" id="seo">
					<table class="table-main">
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
									<span id="charlimitinfotitle"></span> 
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
			</div>
			<div class="main-content-pad">
				<table class="table-main">
					<tbody>
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
			</div>
			</form>
			</div>
		</div>
	</div>
</section>
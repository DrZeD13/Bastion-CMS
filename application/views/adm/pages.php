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
				<form action="" method="POST">
				<table class="table-main">
				<tbody>
					<tr>
						<td>
							<span class="lable lablemargin">Выберите страницу</span>
							<?=$data['page_select']?>
						</td>
					</tr>
				</tbody>
				</table>
				</form>
				<form action="" method="POST">
				<table class="table-main">
				<tbody>
					<tr>
						<td>
							<span class="lable lablemargin">Заголовок</span>
							<input type="text" name="title" value="<?=$data['t_title']?>" required> <span class="error"><?=$data['title_error']?></span>
						</td>
					</tr>
					<tr>
						<td>
							<span class="lable lablemargin">Подробное описание</span>
							<textarea name="text"><?=$data['text']?></textarea>
							<?=$data['editor']?>
						</td>
					</tr>
					<tr>
						<td>
							<button type="submit" class="savenew">
								<i class="fa fa-floppy-o"></i> Сохранить
							</button>
							<input type="hidden" name="action" value="<?=$data["action"]?>" />
							<input type="hidden" name="page_id" value="<?=$data["id"]?>" />
							<input type="hidden" name="token" value="<?=$data["token"]?>" />
						</td>
					</tr>
				</tbody>
				</table>
				</form>

				<!--input id="xFilePath" name="FilePath" type="text" size="60" />
				<input type="button" class="btn btn-flat" value="Выбрать на сервере" onclick="BrowseServer('xFilePath');" />
						
						
				<script type="text/javascript">

				function BrowseServer(elementId)
				{
					CKFinder.popup(
						{
							basePath : '/ckfinder/',
							width : '80%',
							height: '70%',
							selectActionFunction : function (fileUrl) {
								document.getElementById(elementId).value = fileUrl;
							},
						}
					);
				}
				</script-->
			</div>
			</div>
		</div>
	</div>
</section>
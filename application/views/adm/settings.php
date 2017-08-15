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
					<?foreach($data['row'] as $row) 
					{?>
					<tr>
						<td class="lable">
							<?=$row['title']?>: 
						</td>
						<td>
							<?switch ($row['type'])
							{
								case "email":?>
									<input type="email" name="<?=$row['keyname']?>" value='<?=$row['value']?>' required pattern="^([a-z0-9_-]+\.)*[a-z0-9_-]+@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,6}$"> <span class="error"><?=$row['error']?></span>
								<?break;
								case "numeric":?>
									<input type="number" min="50" max="1024" name="<?=$row['keyname']?>" value='<?=$row['value']?>' required> <span class="error"><?=$row['error']?></span>
								<?break;
								case "textarea":?>
									<textarea name="<?=$row['keyname']?>" <?=($row['keyname'] == "Description")?"id=\"description\"":""?> required><?=$row['value']?></textarea> <span class="error"><?=$row['error']?></span>
									<? if ($row['keyname'] == "Description"):?>
									<span id="charlimitinfo"></span>
									<?endif;?>
								<?break;
								case "filename":?>
									<input id="<?=$row['keyname']?>" name="<?=$row['keyname']?>" type="text" size="60" value="<?=$row['value']?>" /> <input type="button" value="Выбрать" onclick="BrowseServer('<?=$row['keyname']?>');" />	<span class="error"><?=$row['error']?></span>
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
									</script>
								<?break;
								case "checkbox":?>
									<?$checked = ($row['value'] == 1)?"checked":""?>
									<input type="checkbox" class="checkbox" id="<?=$row['keyname']?>" name="<?=$row['keyname']?>" <?=$checked?>  value="1">
									<label for="<?=$row['keyname']?>"></label>	
								<?break;
								default:?>
									<input type="text" name="<?=$row['keyname']?>" value='<?=$row['value']?>' <?=($row['keyname'] == "SiteTitle")?"id=\"head_title\"":""?> required> <span class="error"><?=$row['error']?></span>
									<? if ($row['keyname'] == "SiteTitle"):?>
									<span id="charlimitinfotitle"></span>
									<?endif;?>
							<?}?>
						</td>
					</tr>
					<?}?>
					<tr>
						<td>
						</td>
						<td>
							<button type="submit" class="savenew">
								<i class="fa fa-floppy-o"></i> Сохранить
							</button>
							<input type="hidden" name="action" value="save" />
							<input type="hidden" name="token" value="<?=$data["token"]?>" />
						</td>
					</tr>
				</tbody>
				</table>
				</form>
			</div>
			</div>
		</div>
	</div>
</section>
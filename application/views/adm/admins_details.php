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
							Логин:
						</td>
						<td>
							<input type="text" name="login" value="<?=$data['login']?>" required> <span class="error"><?=$data["login_error"]?></span>
						</td>
					</tr>
					<tr>
						<td class="lable">
							Пароль:
						</td>
						<td>
							<input type="text" name="pass" value="<?=$data['pass']?>" <?=$data["required"]?>> <span class="error"><?=$data["pass_error"]?></span>
						</td>
					</tr>
					<tr>
						<td class="lable" valign="top">
							Доступ:
						</td>
						<td>
							<span id="checkbox_all">Выбрать все</span><br>
						</td>
					</tr>
					<? foreach($data['pages'] as $row) 
					{?>		
					<tr>
						<td class="lable admin_space" valign="top">
							<?=$row['title']?>:
						</td>
						<td class="admin_space">
							<input type="checkbox" class="checkbox" id="<?=$row['keyname']?>" name="<?=$row['keyname']?>" value="1" <?=$row['check']?>><label for="<?=$row['keyname']?>"></label> 
						</td>
					</tr>
					<?}?>			
					<tr>
						<td></td>
						<td>
							<button type="submit" class="savenew">
								<i class="fa fa-floppy-o"></i> Сохранить
							</button>
							<button type="button" class="cancel" onClick="window.location.href='/adm/<?=$data["table_name"]?>/'">
								<i class="fa fa-ban"></i> Отмена
							</button>
							<input type="hidden" name="action" value="<?=$data["action"]?>" />
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

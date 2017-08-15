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
						<td>
							<textarea name="<?=$row['counter_id']?>"><?=$row['code']?></textarea>
						</td>
					</tr>
					<?}?>
					<tr>
						<td>
							<button type="submit" class="savenew">
								<i class="fa fa-floppy-o"></i> Сохранить
							</button>
							<input type="hidden" name="action" value="save" />
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
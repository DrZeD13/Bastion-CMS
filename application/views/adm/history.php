<?if(!defined("CMS_BASTION") || CMS_BASTION!==true) {
	header('HTTP/1.1 404 Not Found');
	header("Status: 404 Not Found");
	die();
}?>
<section class="content-header">
      <h1><? echo $data['title']; ?></h1>
</section>
<section class="content">
<div class="row">
	<div class="col-md-12">
		<div class="main-content">	
			<div class="row head-search">
				<div class="col-sm-12">
					<div class="col-md-4">						
						<form action="/adm/<?=$data["table_name"]?>/" method="GET">				
							<span class="lable">Поиск:</span>
							<div class="input-group">
								<input type='text' name='search' class="search" value="<?=$data['search']?>" placeholder="Что ищем?">
								<div class="input-group-addon">
								  <button type="submit" class="btn btn-primary btn-flat">
										<i class="fa fa-search"></i>
									</button>
								</div>
							  </div>				
						</form>
					</div>
					<div class="col-md-3">
					</div>
					<div class="col-md-3">
						
					</div>
					<div class="col-md-2 text-right">
						<br>
						<a class="btn btn-flat btn-danger" href="/adm/history/clear" onClick="return confirm ('Вы действительно хотите удалить все записи старше одного месяца?');"><i class="fa fa-minus-circle"></i> Очистить историю</a>
					</div>
				</div>
			</div>
			<div class="table-responsive">														
				<table class="table">
				<thead>
					<td width="5%"><?=$data['id']?></td>
				   <td><?=$data['admin_id']?></td>
				   <td width="180px"><?=$data['date']?></td>
				   <td><?=$data['ip']?></td>	   
				   <td width="200px"><?=$data['status']?></td>
				   <td width="180px"><?=$data['admin_pages']?></td>	   
				   <td><?=$data['item_id']?></td>
				</thead>
				<tbody>
				<?
				if (isset($data['row']))
				{
					foreach($data['row'] as $row) 
					{?>
					<tr class="">
						<td>
							<?=$row['id']?>
						</td>
						<td>
							<?=$row['admin_id']?>
						</td>
						<td>
							<?=$row['date']?>
						</td>
						<td>
							<?=$row['ip']?>
						</td>
						<td>
							<?=$row['status']?>
						</td>
						<td>
							<a href="/adm/<?=$row['link_admin_pages']?>/"><?=$row['admin_pages']?></a>
						</td>
						<td>
							<?if (!empty($row['item_id'])){
								if (is_numeric($row['item_id'])) {?>
							<a href="/adm/<?=$row['link_admin_pages']?>/edit?id=<?=$row['item_id']?>"><?=$row['item_id']?></a>
							<?}
							else echo $row['item_id']; }?>
						</td>
					</tr>
					<?}
				}
				else
				{?>
					<tr><td colspan="6" align="center"><?=$data['empty_row']?></td></tr>
				<?}?>
			</tbody>
			</table>
			</div>
		</div>
	</div>
</div>
</section>
<? 
if (isset($data["pages"])) 
{?>
<div class="content pages">
<div class="main-content">
<div class="row">
	<?=$data["pages"]?>
</div>
</div>
</div>
<?}
?>
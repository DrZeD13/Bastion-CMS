<h1><? echo $data['title']; ?></h1>
<p><br></p>
<table border="0" width="100%">
	<tr>
		<td>
			<form action="/adm/shop/" method="GET">
				<input type='text' name='search' class="search" value="<?=$data['search']?>" autofocus placeholder="Что ищем?">
				<?=$data['field']?>
				<button type="submit" class="searchbtn">
					<i class="fa fa-search"></i>
				</button>
			</form>			
		</td>
		<td>
			<?=$data['catalogtree']?>		
		</td>
		<td align="right">
			<a href="/adm/shop/add" class="add"><i class="fa fa-plus-square"></i> Добавить</a>
		</td>
	</tr>
	<tr>
	<td><p><br></p></td>
	</tr>
	<tr>
		<td>
			<?=$data['navigation']?>
		</td>
	</tr>
</table>
<table width="100%" class="table" cellspacing="0" cellpadding="0">
	<thead>
		<td width="5%"><?=$data['id']?></td>
	   <td><?=$data['t_title']?></td>
	   <td width="90px"><?=$data['date']?></td>
	   <td><?=$data['link']?></td>
	   <td><?=$data['tags']?></td>
	   
	   <td width="15">&nbsp;</td>
	   <td width="15"><?=$data['order_index']?></td>
	   <td width="15"><?=$data['is_active']?></td>
	   <td width="15">&nbsp;</td>
	   <td width="15">&nbsp;</td>
	</thead>
	<tbody>
	<?
	if (isset($data['article_row']))
	{
		foreach($data['article_row'] as $row) 
		{?>
		<tr class="<?=$row['status']?>">
			<td>
				<?=$row['id']?>
			</td>
			<td>
				<a href="<?=$row['edit']?>"><?=$row['title']?></a>
			</td>
			<td>
				<?=$row['date']?>
			</td>
			<td>
				<a href="<?=$row['link']?>" target="_blank"><?=$row['linktitle']?></a>
			</td>
			<td>
				<?=$row['tags']?>
			</td>
			<td>
				<a href="/adm/indigrienty/?parent_id=<?=$row['id']?>" title="Ингредиенты"><i class="fa fa-cart-arrow-down"></i></a>
			</td>
			<td>
				<?=$row['order_index']?>
			</td>
			<td>
				<a class="<?=$row['active_img']?>" href="<?=$row['active']?>" title="Изменить статус"><i class="fa fa-<?=$row['active_img']?>"></i></a>
			</td>
			<td>
				<a class="edit" href="<?=$row['edit']?>" title="Редактировать"><i class="fa fa-pencil-square-o"></i></a>
			</td>
			<td>
				<? if (!empty($row['del'])) {?>
				<a class="delete" href="<?=$row['del']?>" onClick="return confirm ('Вы действительно хотите удалить данную запись?');" title="Удалить"><i class="fa fa-minus-circle"></i></a>
				<?}?>
			</td>
		</tr>
		<?}
	}
	else
	{?>
		<tr><td colspan="8" align="center"><?=$data['empty_row']?></td></tr>
	<?}?>
</tbody>
</table>
<? 
if (isset($data["pages"])) 
{
	echo $data["pages"];
}
?>
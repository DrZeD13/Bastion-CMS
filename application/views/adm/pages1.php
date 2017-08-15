<?if(!defined("CMS_BASTION") || CMS_BASTION!==true) {
	header('HTTP/1.1 404 Not Found');
	header("Status: 404 Not Found");
	die();
}?>
<h1><? echo $data['title']; ?></h1>
<p><br></p>
<form action="" method="POST">
<table cellspacing="12">
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
<table cellspacing="12">
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
		</td>
	</tr>
</tbody>
</table>


<input id="xFilePath" name="FilePath" type="text" size="60" />
<input type="button" value="Browse Server" onclick="BrowseServer();" />
		
		
<script type="text/javascript">

function BrowseServer()
{
	// You can use the "CKFinder" class to render CKFinder in a page:
	var finder = new CKFinder();
	finder.basePath = '../Images';	// The path for the installation of CKFinder (default = "/ckfinder/").
	finder.selectActionFunction = SetFileField;
	finder.popup();

	// It can also be done in a single line, calling the "static"
	// popup( basePath, width, height, selectFunction ) function:
	// CKFinder.popup( '../', null, null, SetFileField ) ;
	//
	// The "popup" function can also accept an object as the only argument.
	// CKFinder.popup( { basePath : '../', selectActionFunction : SetFileField } ) ;
}

// This is a sample function which is called when a file is selected in CKFinder.
function SetFileField( fileUrl )
{
	document.getElementById( 'xFilePath' ).value = fileUrl;
}

	</script>
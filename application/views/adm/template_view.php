<?if(!defined("CMS_BASTION") || CMS_BASTION!==true) {
	header('HTTP/1.1 404 Not Found');
	header("Status: 404 Not Found");
	die();
}?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" /> 
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?=strip_tags($data['main_title'])?></title>	
	<link rel="stylesheet" type="text/css" href="/css/adm/bootstrap.css">
	<link rel="stylesheet" type="text/css" href="/css/adm/font-awesome.css">
	<link rel="stylesheet" type="text/css" href="/css/adm/style.css">
	<link rel="stylesheet" type="text/css" href="/css/adm/chosen.css">
	<link rel="stylesheet" type="text/css" href="/js/adm/latest.css">
	<script type="text/javascript" src="/ckeditor/ckeditor.js"></script>
	<script type="text/javascript" src="/ckfinder/ckfinder.js"></script>
</head>
<body class="sidebar-mini">
	<header class="main-header">
		<div class="logo">
			<span class="logo-mini"><b>B</b></span>
			<span class="logo-lg"><b>Bastion</b> CMS</span>
		</div>	
		<nav class="navbar navbar-static-top">
			<a href="#" class="sidebar-toggle" id="menutoggle"><i class="fa fa-reorder"></i></a>
			<div class="navbar-custom-menu">
				<ul class="nav navbar-nav">
					<li class="dropdown user user-menu">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown">
						<span><i class="fa fa-user"></i> Admin</span>
						</a>
						<ul class="dropdown-menu">
							<li>               
								<a href="/adm/login/logout" class="btn btn-danger btn-flat"><i class="fa fa-power-off"></i> Выход</a>
							</li>
						</ul>
					</li>
				</ul>
			</div>
    </nav>
	</header>
	<aside class="main-sidebar">
		<section class="sidebar">
			<?=$head['header']?>
		</section>
	</aside>

	<div class="content-wrapper">		
			<?php include 'application/views/adm/'.$content_view; ?>			
	</div>
	<footer class="main-footer">
		<div class="pull-right hidden-xs">
		  <b>Version</b> 3.0.0
		</div>
		<strong>&copy; <a href="http://bastiondesign.ru">Bastion CMS</a>, 2017.</strong> Все права защищены.
	</footer>
	<script src="/js/adm/jquery.min.js"></script>
	<script src="/js/adm/bootstrap.min.js"></script>
	<!--Для маски форм-->
	<script src="/js/adm/jquery.inputmask.js"></script>
	<script type="text/javascript" src="/js/adm/latest.js"></script>
	<script type="text/javascript" src="/js/adm/chosen.jquery.min.js"></script>
	<script type="text/javascript" src="/js/adm/script.js"></script>
</body>
</html>
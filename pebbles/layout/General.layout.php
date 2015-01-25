<?php
/** @var core\Site $site */
$site=(empty($site) ? null : $site);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="cs" lang="cs">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta http-equiv="Content-language" content="cs" />
	<meta http-equiv="imagetoolbar" content="no" />
	<meta http-equiv="cache-control" content="cache" />
	<?php echo ($site->getTitle() ? _N_T.'<title>'.$site->getTitle().'</title>' : false); ?>
	<?php
	$header=$site->getHeader();
	if(!empty($header)){
		echo _N_T.implode(_N_T,$header);
	}
	?>
	<style>
	<?php echo $site->getDebuger()->get_css();?>
	</style>
</head>
<body>
	<ul id="menu">
		<?php foreach($site->data['menu'] as $menu_item){
			echo '<li>'.($menu_item['active'] ? false : '<a href="'.$menu_item['url'].'">').$menu_item['text'].($menu_item['active'] ? false : '</a>').'</li>';
		}?>
	</ul>
	<h1><?php echo $site->getTitle() ?></h1>
	<h2><?php echo $site->data['title_h2'] ?></h2>
	<?php
	echo $site->data['report'];
	if(!empty($site->data['person_table'])){
	echo $site->data['person_table'] ?>
	<a href="?action=add_person" class="button add">nový záznam</a>
	<?php } else {
		echo $site->data['add_person_form'];
	} ?>
	<?php echo $site->getDebuger()->get_panel(); ?>
</body>
</html>
<html>
<head>
<title>Toolbar testing page</title>
<link rel="stylesheet" type="text/css" href="default.css">
<script language="Javascript">
function foo(str) {
	if (obj = document.getElementById("output")) {
		obj.value = str;
	}
}
</script>
</head>
<body>
<?
	include_once("include/toolbar.php");
	$t = new Toolbar(array(
		"look"	=>	"toggle",
	));
	$t->add_button(array(
		"caption"	=>	"foo",
		"code"		=>	"foo('foo')",
	));
	$t->add_button(array(
		"caption"	=>	"bar",
		"code"		=>	"foo('bar')",
	));
	$t->add_button(array(
		"caption"	=>	"qux",
		"code"		=>	"foo('qux')",
	));
	$t->add_button(array(
		"caption"	=>	"erf",
		"code"		=>	"foo('erf')",
	));
	$t->render();
?>
<center><input id="output"></center>
<hr>
<?
	$tb = new Toolbar(array(
		"look"	=>	"modern"
	));
	$tb->add_button(array(
		"caption"	=>	"cancel",
		"code"		=>	"alert('cancel');",
		"img"		=>	"cancel.png",
		"imgpos"	=>	"left",
	));
	$tb->add_button(array(
		"caption"	=>	"prev",
		"code"		=>	"alert('prev');",
		"img"		=>	"back.png",
		"imgpos"	=>	"left",
	));
	$tb->add_button(array(
		"caption"	=>	"next",
		"code"		=>	"alert('next');",
		"img"		=>	"forward.png",
		"imgpos"	=>	"right",
	));
	$tb->add_button(array(
		"caption"	=>	"finish",
		"code"		=>	"alert('finish');",
		"img"		=>	"finish.png",
		"imgpos"	=>	"right",
	));
	$tb->render();
?>
<input type="button" onclick="disable_all_toolbar_buttons(true);" 
	value="disable">
<input type="button" onclick="disable_all_toolbar_buttons(false);" 
	value="enable">
	
</body>
</html>

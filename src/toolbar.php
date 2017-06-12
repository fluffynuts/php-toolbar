<?
/*	About the Toolbar class:
	Purpose: to provide an easier and quicker interface to providing
		one or more buttons on an html page, with click code or a url
		to load into the current or a popup pane. Simply stated, this
		class aims to reduce some code writing and increase robustness.
	Product: a class which does the above, in one of three possible
		outcomes: regular buttons, "modern" style buttons (which are
		a little slimmer, and make use of tables for rendering) and
		toggle buttons (which use the "modern" engine, but remain 
		depressed after a click, whilst making sure that other buttons
		belonging to the toolbar are raised); You may have as many 
		toolbars on a page as you wish -- each one is assigned a unique id
		which you can get back either as the return from the render()
		function (if you choose to draw immediately, the default)
		or from the id member variable of your object.
	Usage:
		$tb = new Toolbar(array(
			"look"	=>	"modern"; 
		));
		// you can set any of the following in constructor: 
		//	style (css style / classname), 
		//	align (alignment string, "left", "center" (default) or "right"), 
		//	orient (h for horizontal (default) or "v" for vertical), 
		//	bwidth (button width -- size in px, or "auto" (default)
		//	look (one of "classic" (default), "modern", "toggle");

		// now, add some buttons:
		$tb->add_button(array(
			"caption"		=>	"click me!",
			"url"			=>	"http://www.google.com",
			"popup"			=>	true,	// optional
			// or:
			"code"			=>	"myfunction()",
			"img"			=>	"images/icon.png", // optional, only for modern
													// and toggle looks
			"imgpos"		=> "top" // default, can also be one of:
										// "left", "right", "bottom"
		));
		// now, do a render:
		$tb->render();
		//or, if you want the html output for some other reason:
		$html = $tb->render(false);
*/
if (file_exists("include/log.php")) {
	include_once ("include/log.php");
} elseif (file_exists("log.php")) {
	include_once ("log.php");
}

class Toolbar extends Logger {
	var $set;
	var $buttons;
	
	function Toolbar($set="") {/*<<<*/
		$this->set=array (
			"style"		=>	"padding-left: 20px; padding-right: 20px;"
								."padding-top: 5px; margin: auto;",
			"align"		=> "center",
			"orient"	=> "h",
			"bwidth"	=> "auto",
			"look"		=> "classic",
		);
		if (isset($GLOBALS)) {
			if (is_array($GLOBALS)) {
				if (array_key_exists("toolbar_look", $GLOBALS)) {
					$this->set["look"] = $GLOBALS["toolbar_look"];
				}
			}
		}
		if (is_array($set)) {
			foreach ($set as $idx => $val) {
				$this->set[$idx]=$val;
			}
		}
		// some value validation
		$avail_looks = array("classic", "modern", "toggle");
		if (!in_array($this->set["look"], $avail_looks)) {
			$this->set["look"] = $avail_looks[0];
		}
		$this->set["bwidth"] = strtolower(str_replace(" ", "", 
			$this->set["bwidth"]));
		if (is_numeric($this->set["bwidth"])) {
			$this->set["bwidth"].="px";
		} elseif (strpos("px", $this->set["bwidth"]) === false) {
			$this->set["bwidth"] = "auto";
		}
		$this->set["orient"] = strtolower(substr($this->set["orient"], 0, 1));
		$avail_orients = array("h", "v");
		if (!in_array($this->set["orient"], $avail_orients)) {
			$this->set["orient"] = "h";
		}
	}
/*>>>*/
	function add_button ($set=array()) {/*<<<*/
		if (!is_array($set)) {
			print("Toolbar->addbutton called without array parameter set");
			return;
		}
		/* available settings:
			caption:		title on button;
			code:		direct javasript code for button;
			url:		url for a window.location call: overrides code;
			popup:		if  a url is given, then the url is loaded in a popup
						window.
		*/
		
		$set["disabled"]=$this->array_or_default($set, "disabled", 0);
		if ($set["disabled"]) {
			$set["disabled"] = true;
		} else {
			$set["disabled"] = false;
		}
		$set["caption"]=$this->array_or_default($set, "caption", "label me");
		if ($set["caption"] == "") {
			$set["caption"] = " ";
		}
		$set["title"] = $this->array_or_default($set, "title", $set["caption"]);
		$set["img"] = $this->array_or_default($set, "img", "");
		$set["imgpos"] = $this->array_or_default($set, "imgpos", "top");
		$set["id"] = $this->array_or_default($set, "id", uniqid("btn_"));
		if ($this->button_idx($set["caption"])!=-1) {
			$this->log("ignoring attempt to add existing button: ("
				.$set["caption"].") -- perhaps you meant to call mod_button?");
		} else {
			$this->buttons[]=$set;
		}
		return $set["id"];
	}
	/*>>>*/
	function add_spacer () {/*<<<*/
		$this->buttons[] = array("spacer" => true);
	}
/*>>>*/
	function mod_button($idx_or_caption, $settings) {/*<<<*/
		if (!is_numeric($idx_or_caption)) {
			$foundmatch = false;
			foreach ($this->buttons as $idx => $b) {
				if ($b["caption"] == $idx_or_caption) {
					$idx_or_caption = $idx;
					$foundmatch = true;
					break;
				}
			}
			if (!$foundmatch) {
				$this->log("unable to find button with caption \""
					.$idx_or_caption." -- mod_button aborted");
				return;
			}
		}
		if (!array_key_exists($idx_or_caption, $this->buttons)) {
			$this->log("unable to find button with index \""
				.$idx_or_caption." -- mod_button aborted");
			return;
		}
		foreach ($settings as $idx => $val) {
			$this->log("modifying button $idx_or_caption: setting $idx to $val");
			$this->buttons[$idx_or_caption][$idx] = $val;
		}
	}
/*>>>*/
	function array_or_default(&$array, $idx, $default) {/*<<<*/
		if (!is_array($array)) {
			return $default;
		}
		if (array_key_exists($idx, $array)) {
			return $array[$idx];
		} else {
			return $default;
		}
	}
/*>>>*/
	function render($dodraw = true) {/*<<<*/
		if ($this->set["align"] != "") {
			$align = " align=\"".$this->set["align"]."\"";
		} else {
			$align = "";
		}
		$this->id = uniqid("toolbar_");
		// classic look employs standard button inputs; modern look employs
		//	a hack on styles and td elements. The modern look provides a 
		//	slightly smoother, smaller button and look.
		switch ($this->set["look"]) {
			case "toggle": {
				// like modern, but buttons stay toggled on click
			}
			case "modern": {
				// uses td's instead of buttons -- this idea taken from
				//	phppgadmin.
				//	your included stylesheet should have definitions
				//	for the following:
				//	td.opbutton1
				//	td.opbutton2			-- disabled colors
				//	a.opbutton1:link		(optional)
				//	a.opbutton1:hover		(optional)
				//	a.opbutton1:visited		(optional)
				//	td.opbutton1d			(optional -- sets a depressed look)
				$r="<table border=\"0\" ".$align."><tr>";
				if (!is_array($this->buttons)) {
					$r.="toolbar: render called with no buttons defined.";
					return;
				}
				foreach ($this->buttons as $b) {
					if (array_key_exists("spacer", $b) && $b["spacer"]) {
						$r.="<td nowrap style=\"border: none\">&nbsp;</td>";
					} else {
						if (array_key_exists("id", $b)) {
							$idstr = " id=\"".$b["id"]."\"";
						} else {
							$idstr = "";
						}
						if ($b["disabled"]) {
							$obclass = "opbutton2";
						} else {
							$obclass = "opbutton1";
						}
						if (array_key_exists("on", $b) 
							&& $b["on"]
							&& $this->set["look"] == "toggle") {
							$obclass = "opbutton1d";
							$this->togglebuttons[]=$btnid;
						}
						$arrbtnids[]=$b["id"];
						$btnid = $b["id"];
						$r.="<td nowrap".$idstr
							." class=\"".$obclass."\" title=\"".$b["title"]
							."\" onmouseover=\"window.status='".$b["title"]
							."';\" onmouseout=\"window.status='';\" id=\""
							.$btnid."\" align=\"center\" valign=\"middle\"";
						if ($this->set["bwidth"] != "") {
							$r.=" width=\"".$this->set["bwidth"]."\"";
						}
						$r.=">";
						if (file_exists($b["img"])) {
							$r.="<table border=\"0\""
								."cellpadding=\"0\"><tr><td nowrap>";
						}
						if ($this->set["look"] == "modern") {
							$this->add_event($btnid, "onmousedown", 
								"this.className=\"opbutton1d\"");
							$this->add_event($btnid, "onmouseup",
								"this.className=\"opbutton1\"");
							$this->add_event($btnid, "onmouseout", 
								"this.className=\"opbutton1\"");
						} else {
							$this->add_event($btnid, "onclick",
								"dotoggle(this, '".$this->id."')");
						}
						if (array_key_exists("url", $b)
							&& $b["url"] != "") {
							if (array_key_exists("popup", $b)
								&& $b["popup"]) {
								if (array_key_exists("winopts", $b)
									&& is_array($b["winopts"])) {
									$awinopts = array(
										"status"	=>	"no",
										"toolbar"	=>	"no",
										"menubar"	=>	"no",
										"location"	=>	"no",
										"title"		=>	"",
									);
									foreach ($b["winopts"] as $idx=>$val) {
										$awinopts[$idx] = $val;
									}
									$winopts = "";
									$delim = "";
									foreach ($awinopts as $idx => $val) {
										if ($idx == "title") continue;
										$winopts.=$delim.$idx."=".$val;
										$delim=",";
									}
								} else {
									$winopts = "status=no,toolbar=no,"
										."menubar=no,location=no";
								}
								$this->add_event($btnid, "onclick",
									"window.open('".$b["url"]."', '"
									.$awinopts["title"]."', '"
									.$winopts."')");
							} else {
								$this->add_event($btnid, "onclick",
								"window.location='".$b["url"]."'");
							}
						} else {
							$this->add_event($btnid, "onclick", $b["code"]);
						}
						if (file_exists($b["img"]) 
							&& (($b["imgpos"] == "left") 
								|| ($b["imgpos"] == "top"))) {
							$r.="<img src=\"".$b["img"]."\" title=\""
								.$b["title"]."\"></td><td>";
							if ($b["imgpos"] == "top") {
								$r.="</td></tr><tr><td nowrap>";
							}
						}
						$r.="<span class=\"opbutton1\" id=\"tbbtntext_"
							.$b["id"]."\"";
						if ($b["title"] != "") {
							$r.=" title=\"".$b["title"]
								."\" onmouseover=\""
								."window.status='"
								.$this->jsquote($b["title"])
								."';\" onmouseout=\"window."
								."status='';\"";
						}
						$r.=">";
						$r.=$b["caption"];
						$r.="</span>";
						if (file_exists($b["img"]) 
							&& (($b["imgpos"] == "right") 
								|| ($b["imgpos"] == "bottom"))) {
							if ($b["imgpos"] == "bottom") {
								$r.="</td></tr><td>";
							} else {
								$r.="</td><td>";
							}
							$r.="<img src=\"".$b["img"]."\" title=\""
								.$b["title"]."\">";
						}
						$r.="</td>";
						if (file_exists($b["img"])) {
							$r.="</tr></table></td>";
						}
						if ($this->set["orient"] == "v") {
							$r.="</tr><tr>";
						}
					}
					if ($b["disabled"]) {
						$this->disabled_buttons[] = $b["id"];
					}
				}
				$r.="</tr></table>";
				break;
			}
			case "classic":
			default: {/*<<<*/
				// uses standard button inputs: the look is then defined
				//	by your stylesheet
				$r.="<table border=\"0\"".$align."><tr>";
				foreach ($this->buttons as $b) {
					if (array_key_exists("spacer", $b) && $b["spacer"]) {
						$r.="<td>&nbsp;</td>";
					} else {
						if (array_key_exists("id", $b)) {
							$idstr = " id=\"".$b["id"]."\"";
						} else {
							$idstr = "";
						}
						if (array_key_exists("submit", $b) && $b["submit"]) {
							$typestr = " type=\"submit\"";
							$r.="<td><input".$typestr." value=\""
								.$b["caption"]."\"".$idstr;
						} else {
							$typestr = " type=\"button\"";
							$r.="<td><input".$typestr." value=\""
								.$b["caption"]."\"".$idstr." onclick=\"";
							if (array_key_exists("url", $b)
								&& $b["url"] != "") {
								if (array_key_exists("popup", $b)
									&& $b["popup"]) {
									if (array_key_exists("winopts", $b)
										&& is_array($b["winopts"])) {
										$awinopts = array(
											"status"	=>	"no",
											"toolbar"	=>	"no",
											"menubar"	=>	"no",
											"location"	=>	"no",
											"title"		=>	"",
										);
										foreach ($b["winopts"] as $idx=>$val) {
											$awinopts[$idx] = $val;
										}
										$winopts = "";
										$delim = "";
										foreach ($awinopts as $idx => $val) {
											if ($idx == "title") continue;
											$winopts.=$delim.$idx."=".$val;
											$delim=",";
										}
									} else {
										$winopts = "status=no,toolbar=no,"
											."menubar=no,location=no";
									}
									$this->add_event($btnid, "onclick",
										"window.open('".$b["url"]."', '"
										.$awinopts["title"]."', '"
										.$winopts."')");
								} else {
									$this->add_event($btnid, "onclick",
									"window.location='".$b["url"]."'");
								}
							} else {
								$r.=$b["code"]."\"";
							}
						}
						if ($b["title"] == "") {
							if ($b["title"] != "-") {
								$r.=" title=\"".$b["caption"]."\"";
							}
						} else {
							$r.=" title=\"".$b["title"]."\"";
						}
						if ($this->set["bwidth"] != "auto") {
							$b["style"].="; width:".$this->set["bwidth"];
						}
						if ($b["style"] != "") {
							$r.=" style=\"".$b["style"]."\"";
						}
						$r.="></td>";
						if ($this->set["orient"] == "v") {
							$r.="</tr><tr>";
						}
					}
					if ($b["disabled"]) {
						$this->disabled_buttons[] = $b["id"];
					}
				}
				$r.="</tr></table>";
			}
/*>>>*/
		}
		if ($this->set["center"]) $r.="</div>";
		$r.= "<script language=\"Javascript\">\n"
			."\n// event handler registrations:\n";
		if (isset($arrbtnids)) {
			if (is_array($arrbtnids)) {
				$r.="if (typeof(all_toolbar_buttons['".$this->id."']) == "
					."\"undefined\") all_toolbar_buttons['".$this->id."'] = "
					."new Array();\n";
				foreach ($arrbtnids as $btnid) {
					$r.="all_toolbar_buttons['".$this->id."'].push(\"".$btnid."\");\n";
				}
			}
		}
		if (is_array($this->js_events)) {
			foreach ($this->js_events as $objid => $eventsarray) {
				if (!is_array($eventsarray)) continue;
				foreach ($eventsarray as $ev => $script) {
					if (trim($ev) == "") continue;
					if (is_array($script)) {
						$script = implode(";", $script);
					}
					if (trim($script) == "") continue;
					
					$r.="addEvent(\"".$this->jsquote($objid)."\", \""
						.$this->jsquote($ev)."\", \""
						.$this->jsquote($script)."\");\n";
				}
			}
		}
		if (is_array($this->js_obj_events)) {
			foreach ($this->js_obj_events as $obj => $eventsarray) {
				if (!is_array($eventsarray)) continue;
				foreach ($eventsarray as $ev => $script) {
					if (trim($ev) == "") continue;
					if (is_array($script)) {
						$script = implode(";", $script);
					}
					if (trim($script) == "") continue;
					$r.="addEventToObject(".$this->jsquote($obj).", \""
						.$this->jsquote($ev)."\", \""
						.$this->jsquote($script)."\");\n";
				}
			}
		}
		if (is_array($this->togglebuttons)) {
			$r.="if (typeof(toolbar_toggle_buttons) == \"undefined\") {\n"
				."	var toolbar_toggle_buttons = new Array();\n"
				."}";
			foreach ($this->togglebuttons as $id) {
				$r.="toolbar_toggle_buttons.push(\"".$id."\");\n";
			}
		}
		if (is_array($this->disabled_buttons)) {
			foreach ($this->disabled_buttons as $btnid) {
				$r.="addEventToObject(window, \"onload\","
					."\"disable_toolbar_btn_byid('".$btnid."', true)\");\n";
			}
		}
		$r.="</script>";
		if ($dodraw) {
			print($r);
			return $this->id;
		} else {
			return $r;
		}
	}
/*>>>*/
	function button_idx($caption) {/*<<<*/
		for ($i=0; $i<count($this->buttons); $i++) {
			if (array_key_exists("caption", $this->buttons[$i])) {
				if ($this->buttons[$i]["caption"] == $old_caption) {
					return $i;
				}
			}
		}
		return -1;
	}
/*>>>*/
	function jsquote($str) {/*<<<*/
		return str_replace("\"", "\\\"", $str);
	}
/*>>>*/
	function add_event($objid, $evname, $script) {// <<<2
		if (trim($evname) == "") return;
		$this->js_events[$objid][$evname][] = $script;
	}
	function add_object_event($object, $evname, $script) { // <<<2
		if (trim($evname) == "") return;
		$this->js_obj_events[$object][$evname][]=$script;
	}

}
// >>>2
if (!isset($DONE_TOOLBAR_JS_AND_CSS)) {
?>
<style>
span.opbutton1 {
	color: ButtonText;
    background-color: transparent;
	cursor:	pointer;
	font-family: arial, tahoma, verdana, helvetica, sans-serif, serif;
	font-size: smaller; /*0.8em;*/
	text-align: center;
}
span.opbutton1d {
	color: ButtonText;
    background-color: transparent;
	cursor:	pointer;
	font-family: arial, tahoma, verdana, helvetica, sans-serif, serif;
	font-size: smaller; /*0.8em;*/
	text-align: center;
}
span.opbutton2 {
	color: ThreeDHighlight;
    background-color: transparent;
	cursor:	default;
	font-family: arial, tahoma, verdana, helvetica, sans-serif, serif;
	font-size: smaller; /*0.8em;*/
	text-align: center;
}
td.opbutton1 {
	color: ButtonText;
    background-color: ButtonFace;
	border-top:	1px solid ThreeDHighlight;
	border-right: 1px solid	ThreeDShadow;
	border-bottom: 1px solid ThreeDShadow;
	border-left: 1px solid ThreeDHighlight;
	cursor:	pointer;
	font-family: arial, tahoma, verdana, helvetica, sans-serif, serif;
	font-size: smaller; /*0.8em;*/
	padding-left: 6px;
	padding-right: 6px;
	text-align: center;
}
td.opbutton1d {
	color: ButtonText;
    background-color: ButtonFace;
	border-top:	1px solid ThreeDShadow;
	border-right: 1px solid	ThreeDHighlight;
	border-bottom: 1px solid ThreeDHighlight;
	border-left: 1px solid ThreeDShadow;
	cursor:	pointer;
	font-family: arial, tahoma, verdana, helvetica, sans-serif, serif;
	font-size: smaller; /*0.8em;*/
	padding-left: 6px;
	padding-right: 6px;
	text-align: center;
}
td.opbutton2 {
	color: ThreeDHighlight;
    background-color: ThreeDLightShadow;
	border-top:	1px solid ThreeDHighlight;
	border-right: 1px solid ThreeDShadow;
	border-bottom: 1px solid ThreeDShadow;
	border-left: 1px solid ThreeDHighlight;
	cursor:	default;
	font-family: arial, tahoma, verdana, helvetica, sans-serif, serif;
	font-size: smaller; /*0.8em;*/
	padding-left: 6px;
	padding-right: 6px;
	text-align: center;
}
</style>
<script language="Javascript">
if (typeof(addEvent) == "undefined") {
	function addEvent(objid, evname, funcname) {/*<<<*/
		if (obj = document.getElementById(objid)) {
			addEventToObject(obj, evname, funcname);
		}
	}
}
/*>>>*/
if (typeof(addEventToObject) == "undefined") {
function addEventToObject(obj, evname, funcname) { /*<<<*/
	if (typeof(obj) == "undefined") return;
	if (evname.substr(0,2) != "on") {
		evname = "on"+evname; // allow events to be passed by their names
	}
	if (funcname.indexOf(")")) {
		nfbr = "";
	} else {
		nfbr = "()"; // make sure that the new function is called as a fn
	}
	if (obj) {
		eval ("var oldfunc = obj."+evname);
		if (typeof oldfunc != 'function') {
			str = "obj."+evname+" = function () {"+funcname+nfbr+"}";
			eval(str);
		} else {
			eval("obj."+evname+" = function() {\noldfunc();\n"+funcname+";\n}");
		}
	}
}
}
/*>>>*/
if (typeof(dotoggle) == "undefined") {/*<<<*/
	function dotoggle(btn, toolbarid) {
		if (typeof(all_toolbar_buttons[toolbarid] != "undefined")) {
			for (var idx in all_toolbar_buttons[toolbarid]) {
				if (obj = document.getElementById(all_toolbar_buttons[toolbarid][idx])) {
					obj.className = "opbutton1";
				}
			}
		}
		if (btn.className == "opbutton1") {
			btn.className = "opbutton1d";
		} else {
			btn.className = "opbutton1";
		}
	}
}
/*>>>*/
function disable_all_toolbar_buttons(disable, toolbarid) {/*<<<*/
	if (typeof(disable) == "undefined") disable = true;
	if (typeof(all_toolbar_buttons) == "undefined") {
		return;
	}
	for (var idx in all_toolbar_buttons) {
		if ((typeof(toolbarid) != "undefined") && (toolbarid != idx)) continue;
		for (var idx2 in all_toolbar_buttons[idx]) {
			if (btn = document.getElementById(all_toolbar_buttons[idx][idx2])) {
				disable_toolbar_btn(btn, disable);
			}
		}
	}
}
/*>>>*/
function donothing() {/*<<<*/
// does nothing...
}
/*>>>*/
function in_array(el, arr) {/*<<<*/
	if (typeof(arr) == "undefined") return false;
	for (var idx in arr) {
		if (arr[idx] == el) return true;
	}
	return false;
}
/*>>>*/
function disable_toolbar_btn_byid(btnid, disable) {/*<<<*/
	if (obj = document.getElementById(btnid)) {
		disable_toolbar_btn(obj, disable);
	}
}
/*>>>*/
function disable_toolbar_btn (btn, disable) {/*<<<*/
	if (typeof(disable) == "undefined") {
		disable = true;
	}
	if (disable) {
		disable=true;	// ensure boolean
	} else {
		disable=false;
	}
	if (typeof(toolbar_button_states[btn.id]) != "undefined") {
		if (toolbar_button_states[btn.id] == disable) {
			return;
		}
	}
	if (typeof(btn.disabled) == "undefined") {
		if (typeof(stored_toolbar_scripts[btn.id]) == "function") {
			got_script = true;
		} else {
			got_script = false;
		}
		if (disable) {
			if (!got_script) {
				stored_toolbar_scripts[btn.id] = btn.onclick;
			}
			btn.onclick = donothing;
			btn.onmousedown = donothing;
			btn.onmouseup = donothing;
			btn.className = 'opbutton2';
			if (obj = document.getElementById("tbbtntext_"+btn.id)) {
				obj.className = "opbutton2";
			}
		} else {
			if (got_script) {
				if ((typeof(toolbar_toggle_buttons) == "undefined") || (!in_array(btn.id, toolbar_toggle_buttons))) {
					btn.onmousedown = function () {
						this.className="opbutton1d";
					};
					btn.onmouseup = function () {
						this.className="opbutton1";
					};
				}
				btn.onclick=stored_toolbar_scripts[btn.id];
				btn.className = "opbutton1";
				if (obj = document.getElementById("tbbtntext_"+btn.id)) {
					obj.className = "opbutton1";
				}
			}
		}
	} else {
		btn.disabled = disable;
	}
	toolbar_button_states[btn.id] = disable;
}
/*>>>*/

var all_toolbar_buttons = new Array();
var stored_toolbar_scripts = new Array();
var toolbar_button_states = new Array();
</script>
<?
}
$DONE_TOOLBAR_JS_AND_CSS = true;
?>

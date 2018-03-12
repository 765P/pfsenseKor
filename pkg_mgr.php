<?php
/*
 * pkg_mgr.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2018 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2013 Marcello Coutinho
 * All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/*
2018.03.12
한글화 번역 시작
*/

##|+PRIV
##|*IDENT=page-system-packagemanager
##|*NAME=System: Package Manager
##|*DESCR=Allow access to the 'System: Package Manager' page.
##|*MATCH=pkg_mgr.php*
##|-PRIV

ini_set('max_execution_time', '0');

require_once("globals.inc");
require_once("guiconfig.inc");
require_once("pkg-utils.inc");

// if upgrade in progress, alert user
if (is_subsystem_dirty('packagelock')) {
	$pgtitle = array(gettext("System"), gettext("패키지 매니저"));
	$pglinks = array("", "@self");
	include("head.inc");
	print_info_box("패키지가 백그라운드에서 다시 설치되는 동안 기다려주십시오.");
	include("foot.inc");
	exit;
}

// We are being called only to get the package data, not to display anything
if (($_REQUEST) && ($_REQUEST['ajax'])) {
	print(get_pkg_table());
	exit;
}

// The content for the table of packages is created here and fetched by Ajax. This allows us to draw the page and display
// any required messages while the table is being downloaded/populated. On very small/slow systems, that can take a while
function get_pkg_table() {
	$pkg_info = get_pkg_info('all', true, false);

	if (!$pkg_info) {
		print("error");
		exit;
	}

	$pkgtbl = 	'<table id="pkgtable" class="table table-striped table-hover">' . "\n";
	$pkgtbl .= 		'<thead>' . "\n";
	$pkgtbl .= 			'<tr>' . "\n";
	$pkgtbl .= 				'<th>' . gettext("이름") . "</th>\n";
	$pkgtbl .= 				'<th>' . gettext("버전") . "</th>\n";
	$pkgtbl .= 				'<th>' . gettext("Description") . "</th>\n";
	$pkgtbl .= 				'<th></th>' . "\n";
	$pkgtbl .= 			'</tr>' . "\n";
	$pkgtbl .= 		'</thead>' . "\n";
	$pkgtbl .= 		'<tbody>' . "\n";

	foreach ($pkg_info as $index) {
		if (isset($index['installed'])) {
			continue;
		}

		$pkgtbl .= 	'<tr>' . "\n";
		$pkgtbl .= 	'<td>' . "\n";

		if (($index['www']) && ($index['www'] != "UNKNOWN")) {
			$pkgtbl .= 	'<a title="' . gettext("공식 웹 사이트 방문") . '" target="_blank" href="' . htmlspecialchars($index['www']) . '">' . "\n";
			$pkgtbl .= htmlspecialchars($index['shortname']) . '</a>' . "\n";
		} else {
			$pkgtbl .= htmlspecialchars($index['shortname']);
		}
		$pkgtbl .= 	'</td>' . "\n";
		$pkgtbl .= 	'<td>' . "\n";

		if (!$g['disablepackagehistory']) {
			$pkgtbl .= '<a target="_blank" title="' . gettext("변경로그 보기") . '" href="' . htmlspecialchars($index['changeloglink']) . '">' . "\n";
			$pkgtbl .= htmlspecialchars($index['version']) . '</a>' . "\n";
		} else {
			$pkgtbl .= htmlspecialchars($index['version']);
		}

		$pkgtbl .= 	'</td>' . "\n";
		$pkgtbl .= 	'<td>' . "\n";
		$pkgtbl .= 		$index['desc'];

		if (is_array($index['deps']) && count($index['deps'])) {
			$pkgtbl .= 	'<br /><br />' . gettext("패키지 종속성") . ":<br/>\n";

			foreach ($index['deps'] as $pdep) {
				$pkgtbl .= '<a target="_blank" href="https://freshports.org/' . $pdep['origin'] . '">&nbsp;<i class="fa fa-paperclip"></i> ' . basename($pdep['origin']) . '-' . $pdep['version'] . '</a>&emsp;' . "\n";
			}

			$pkgtbl .= "\n";
		}

		$pkgtbl .= 	'</td>' . "\n";
		$pkgtbl .= '<td>' . "\n";
		$pkgtbl .= '<a title="' . gettext("설치") . '" href="pkg_mgr_install.php?pkg=' . $index['name'] . '" class="btn btn-success btn-sm"><i class="fa fa-plus icon-embed-btn"></i>Install</a>' . "\n";

		if (!$g['disablepackageinfo'] && $index['pkginfolink'] && $index['pkginfolink'] != $index['www']) {
			$pkgtbl .= '<a target="_blank" title="' . gettext("더보기") . '" href="' . htmlspecialchars($index['pkginfolink']) . '" class="btn btn-default btn-sm">info</a>' . "\n";
		}

		$pkgtbl .= 	'</td>' . "\n";
		$pkgtbl .= 	'</tr>' . "\n";
	}

	$pkgtbl .= 	'</tbody>' . "\n";
	$pkgtbl .= '</table>' . "\n";

	return ($pkgtbl);
}

$pgtitle = array(gettext("System"), gettext("패키지 매니저"), gettext("사용 가능한 패키지"));
$pglinks = array("", "pkg_mgr_installed.php", "@self");
include("head.inc");

$tab_array = array();
$tab_array[] = array(gettext("설치된 패키지"), false, "pkg_mgr_installed.php");
$tab_array[] = array(gettext("사용 가능한 패키지"), true, "pkg_mgr.php");
display_top_tabs($tab_array);
?>
<div class="panel panel-default" id="search-panel" style="display: none;">
	<div class="panel-heading">
		<h2 class="panel-title">
			<?=gettext('검색')?>
			<span class="widget-heading-icon pull-right">
				<a data-toggle="collapse" href="#search-panel_panel-body">
					<i class="fa fa-plus-circle"></i>
				</a>
			</span>
		</h2>
	</div>
	<div id="search-panel_panel-body" class="panel-body collapse in">
		<div class="form-group">
			<label class="col-sm-2 control-label">
				<?=gettext("검색어")?>
			</label>
			<div class="col-sm-5"><input class="form-control" name="searchstr" id="searchstr" type="text"/></div>
			<div class="col-sm-2">
				<select id="where" class="form-control">
					<option value="0"><?=gettext("이름")?></option>
					<option value="1"><?=gettext("Description")?></option>
					<option value="2" selected><?=gettext("모두")?></option>
				</select>
			</div>
			<div class="col-sm-3">
				<a id="btnsearch" title="<?=gettext("검색")?>" class="btn btn-primary btn-sm"><i class="fa fa-search icon-embed-btn"></i><?=gettext("검색")?></a>
				<a id="btnclear" title="<?=gettext("클리어")?>" class="btn btn-info btn-sm"><i class="fa fa-undo icon-embed-btn"></i><?=gettext("클리어")?></a>
			</div>
			<div class="col-sm-10 col-sm-offset-2">
				<span class="help-block"><?=gettext('패키지 이름 및 설명을 검색하려면 검색 문자열 또는 *nix 정규식을 입력하십시오.')?></span>
			</div>
		</div>
	</div>
</div>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext('패키지')?></h2></div>
	<div id="pkgtbl" class="panel-body table-responsive">
		<div id="waitmsg">
			<?php print_info_box(gettext("패키지 목록을 가져 와서 포맷하는 동안 잠시 기다려주십시오.") . '&nbsp;<i class="fa fa-cog fa-spin"></i>'); ?>
		</div>

		<div id="errmsg" style="display: none;">
			<?php print_info_box("<ul><li>" . gettext("패키지 정보를 검색 할 수 없습니다.") . "</li></ul>", 'danger'); ?>
		</div>
	</div>
</div>

<script type="text/javascript">
//<![CDATA[

events.push(function() {

	// Initial state & toggle icons of collapsed panel
	$('.panel-heading a[data-toggle="collapse"]').each(function (idx, el) {
		var body = $(el).parents('.panel').children('.panel-body')
		var isOpen = body.hasClass('in');

		$(el).children('i').toggleClass('fa-plus-circle', !isOpen);
		$(el).children('i').toggleClass('fa-minus-circle', isOpen);

		body.on('shown.bs.collapse', function() {
			$(el).children('i').toggleClass('fa-minus-circle', true);
			$(el).children('i').toggleClass('fa-plus-circle', false);
		});
	});

	// Make these controls plain buttons
	$("#btnsearch").prop('type', 'button');
	$("#btnclear").prop('type', 'button');

	// Search for a term in the package name and/or description
	$("#btnsearch").click(function() {
		var searchstr = $('#searchstr').val().toLowerCase();
		var table = $("table tbody");
		var where = $('#where').val();

		table.find('tr').each(function (i) {
			var $tds = $(this).find('td'),
				shortname = $tds.eq(0).text().trim().toLowerCase(),
				descr = $tds.eq(2).text().trim().toLowerCase();

			regexp = new RegExp(searchstr);
			if (searchstr.length > 0) {
				if (!(regexp.test(shortname) && (where != 1)) && !(regexp.test(descr) && (where != 0))) {
					$(this).hide();
				} else {
					$(this).show();
				}
			} else {
				$(this).show();	// A blank search string shows all
			}
		});
	});

	// Clear the search term and unhide all rows (that were hidden during a previous search)
	$("#btnclear").click(function() {
		var table = $("table tbody");

		$('#searchstr').val("");

		table.find('tr').each(function (i) {
			$(this).show();
		});
	});

	// Hitting the enter key will do the same as clicking the search button
	$("#searchstr").on("keyup", function (event) {
	    if (event.keyCode == 13) {
	        $("#btnsearch").get(0).click();
	    }
	});

	// Retrieve the table formatted package information and display it in the "Packages" panel
	// (Or display an appropriate error message)
	var ajaxRequest;

	$.ajax({
		url: "/pkg_mgr.php",
		type: "post",
		data: { ajax: "ajax"},
		success: function(data) {
			if (data == "error") {
				$('#waitmsg').hide();
				$('#errmsg').show();
			} else {
				$('#pkgtbl').html(data);
				$('#search-panel').show();
			}
		},
		error: function() {
			$('#waitmsg').hide();
			$('#errmsg').show();
		}
	});

});
//]]>
</script>

<?php include("foot.inc");
?>

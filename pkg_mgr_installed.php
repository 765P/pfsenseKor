<?php
/*
 * pkg_mgr_installed.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2018 Rubicon Communications, LLC (Netgate)
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
2018.03.08
한글화 번역 시작
*/

##|+PRIV
##|*IDENT=page-system-packagemanager-installed
##|*NAME=System: Package Manager: Installed
##|*DESCR=Allow access to the 'System: Package Manager: Installed' page.
##|*MATCH=pkg_mgr_installed.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("pkg-utils.inc");

/* if upgrade in progress, alert user */
if (is_subsystem_dirty('packagelock')) {
	$pgtitle = array(gettext("시스템"), gettext("패키지 매니저"));
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

function get_pkg_table() {
	$installed_packages = get_pkg_info('all', false, true);

	if (is_array($input_errors)) {
		print("error");
		exit;
	}

	if (empty($installed_packages)) {
		print ("nopkg");
		exit;
	}

	$pkgtbl = "";
	$pkgtbl .='		<div class="table-responsive">';
	$pkgtbl .='		<table class="table table-striped table-hover table-condensed">';
	$pkgtbl .='			<thead>';
	$pkgtbl .='				<tr>';
	$pkgtbl .='					<th><!-- Status icon --></th>';
	$pkgtbl .='					<th>' . gettext("이름") . '</th>';
	$pkgtbl .='					<th>' . gettext("카테고리") . '</th>';
	$pkgtbl .='					<th>' . gettext("버전") . '</th>';
	$pkgtbl .='					<th>' . gettext("Description") . '</th>';
	$pkgtbl .='					<th>' . gettext("Actions") . '</th>';
	$pkgtbl .='				</tr>';
	$pkgtbl .='			</thead>';
	$pkgtbl .='			<tbody>';

	foreach ($installed_packages as $pkg) {
		if (!$pkg['name']) {
			continue;
		}

		#check package version
		$txtcolor = "";
		$upgradeavail = false;
		$missing = false;
		$vergetstr = "";

		if (isset($pkg['broken'])) {
			// package is configured, but does not exist in the system
			$txtcolor = "text-danger";
			$missing = true;
			$status = gettext('패키지가 구성되었지만 설치되지 않았습니다!');
		} else if (isset($pkg['obsolete'])) {
			// package is configured, but does not exist in the system
			$txtcolor = "text-danger";
			$missing = true;
			$status = gettext('패키지가 설치되었지만 원격 저장소에서 사용할 수 없습니다!');
		} else if (isset($pkg['installed_version']) && isset($pkg['version'])) {
			$version_compare = pkg_version_compare($pkg['installed_version'], $pkg['version']);

			if ($version_compare == '>') {
				// we're running a newer version of the package
				$status = sprintf(gettext('사용 가능한 크기(%s)보다 큼'), $pkg['version']);
			} else if ($version_compare == '<') {
				// we're running an older version of the package
				$status = sprintf(gettext('%s에서 사용 가능한 업그레이드'), $pkg['version']);
				$txtcolor = "text-warning";
				$upgradeavail = true;
				$vergetstr = '&amp;from=' . $pkg['installed_version'] . '&amp;to=' . $pkg['version'];
			} else if ($version_compare == '=') {
				// we're running the current version
				$status = gettext('Up-to-date');
			} else {
				$status = gettext('버전을 비교하던 중 오류 발생');
			}
		} else {
			// unknown available package version
			$status = gettext('Unknown');
			$statusicon = 'question';
		}

		$pkgtbl .='				<tr>';
		$pkgtbl .='					<td>';

		if ($upgradeavail) {
			$pkgtbl .='						<a title="' . $status . '" href="pkg_mgr_install.php?mode=reinstallpkg&amp;pkg=' . $pkg['name'] . $vergetstr . '" class="fa fa-refresh"></a>';
		} elseif ($missing) {
			$pkgtbl .='						<span class="text-danger"><i title="' . $status . '" class="fa fa-exclamation"></i></span>';
		} else {
			$pkgtbl .='						<i title="' . $status . '" class="fa fa-check"></i>';
		}
		$pkgtbl .='					</td>';
		$pkgtbl .='					<td>';
		$pkgtbl .='						<span class="' . $txtcolor . '">' . $pkg['shortname'] . '</span>';
		$pkgtbl .='					</td>';
		$pkgtbl .='					<td>';
		$pkgtbl .='						' . implode(" ", $pkg['categories']);
		$pkgtbl .='					</td>';
		$pkgtbl .='					<td>';

		if (!$g['disablepackagehistory']) {
			$pkgtbl .='						<a target="_blank" title="' . gettext("변경내역 보기") . '" href="' . htmlspecialchars($pkg['changeloglink']) . '">' .
		    htmlspecialchars($pkg['installed_version']) . '</a>';
		} else {
			$pkgtbl .='						' . htmlspecialchars($pkg['installed_version']);
		}

		$pkgtbl .='					</td>';
		$pkgtbl .='					<td>';
		$pkgtbl .='						' . $pkg['desc'];

		if (is_array($pkg['deps']) && count($pkg['deps'])) {
			$pkgtbl .='						<br /><br />' . gettext("패키지 종속성") . ':<br/>';
			foreach ($pkg['deps'] as $pdep) {
				$pkgtbl .='						<a target="_blank" href="https://freshports.org/' . $pdep['origin'] . '">&nbsp;' .
				    '<i class="fa fa-paperclip"></i> ' . basename($pdep['origin']) . '-' . $pdep['version'] . '</a>&emsp;';
			}
		}
		$pkgtbl .='					</td>';
		$pkgtbl .='					<td>';
		$pkgtbl .='							<a title="' . sprintf(gettext("%s 패키지 삭제"), $pkg['name']) .
		    '" href="pkg_mgr_install.php?mode=delete&amp;pkg=' . $pkg['name'] . '" class="fa fa-trash"></a>';

		if ($upgradeavail) {
			$pkgtbl .='						<a title="' . sprintf(gettext("%s 패키지 업데이트"), $pkg['name']) .
			    '" href="pkg_mgr_install.php?mode=reinstallpkg&amp;pkg=' . $pkg['name'] . $vergetstr . '" class="fa fa-refresh"></a>';
		} else if (!isset($pkg['obsolete'])) {
			$pkgtbl .='						<a title="' . sprintf(gettext("%s 패키지 재설치"), $pkg['name']) .
			    '" href="pkg_mgr_install.php?mode=reinstallpkg&amp;pkg=' . $pkg['name'] . '" class="fa fa-retweet"></a>';
		}

		if (!isset($g['disablepackageinfo']) && $pkg['www'] != 'UNKNOWN') {
			$pkgtbl .='						<a target="_blank" title="' . gettext("더보기") . '" href="' .
			    htmlspecialchars($pkg['www']) . '" class="fa fa-info"></a>';
		}
		$pkgtbl .='					</td>';
		$pkgtbl .='				</tr>';
	}

	$pkgtbl .='			</tbody>';
	$pkgtbl .='		</table>';
	$pkgtbl .='		</div>';
	$pkgtbl .='	</div>';

	return $pkgtbl;
}

$pgtitle = array(gettext("시스템"), gettext("패키지 매니저"), gettext("설치된 패키지"));
$pglinks = array("", "@self", "@self");
include("head.inc");

$tab_array = array();
$tab_array[] = array(gettext("설치된 패키지"), true, "pkg_mgr_installed.php");
$tab_array[] = array(gettext("사용 가능한 패키지"), false, "pkg_mgr.php");
display_top_tabs($tab_array);

?>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext('설치된 패키지')?></h2></div>
	<div id="pkgtbl" class="panel-body">
		<div id="waitmsg">
			<?php print_info_box(gettext("패키지 목록을 가져 와서 포맷하는 동안 잠시 기다려주십시오.") . '&nbsp;<i class="fa fa-cog fa-spin"></i>'); ?>
		</div>

		<div id="errmsg" style="display: none;">
			<?php print_info_box("<ul><li>" . gettext("패키지 정보를 검색 할 수 없습니다.") . "</li></ul>", 'danger'); ?>
		</div>

		<div id="nopkg" style="display: none;">
			<?php print_info_box(gettext("현재 설치된 패키지가 없습니다."), 'warning', false); ?>
		</div>
	</div>

	<div id="legend" class="alert-info text-center">
		<p>
		<i class="fa fa-refresh"></i> = <?=gettext('업데이트')?>  &nbsp;
		<i class="fa fa-check"></i> = <?=gettext('Current')?> &nbsp;
		</p>
		<p>
		<i class="fa fa-trash"></i> = <?=gettext('제거')?> &nbsp;
		<i class="fa fa-info"></i> = <?=gettext('정보')?> &nbsp;
		<i class="fa fa-retweet"></i> = <?=gettext('재설치')?>
		</p>
		<p>
		<span class="text-warning"><?=gettext("사용 가능한 최신 버전")?></span>
		</p>
		<span class="text-danger"><?=gettext("패키지가 구성되었지만 완전히 설치되지 않았거나 더 이상 사용되지 않습니다.")?></span>
	</div>
</div>

<script type="text/javascript">
//<![CDATA[

events.push(function() {

	// Retrieve the table formatted package information and display it in the "Packages" panel
	// (Or display an appropriate error message)
	var ajaxRequest;

	$('#legend').hide();
	$('#nopkg').hide();

	$.ajax({
		url: "/pkg_mgr_installed.php",
		type: "post",
		data: { ajax: "ajax"},
		success: function(data) {
			if (data == "error") {
				$('#waitmsg').hide();
				$('#errmsg').show();
			} else if (data == "nopkg") {
				$('#waitmsg').hide();
				$('#nopkg').show();
				$('#errmsg').hide();
			} else {
				$('#pkgtbl').html(data);
				$('#legend').show();
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

<?php include("foot.inc")?>

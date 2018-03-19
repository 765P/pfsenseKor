<?php
/*
 * installed_packages.widget.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) Scott Dale
 * Copyright (c) 2004-2005 T. Lechat <dev@lechat.org>
 * Copyright (c) Jonathan Watt <jwatt@jwatt.org>
 * Copyright (c) 2004-2018 Rubicon Communications, LLC (Netgate)
 * All rights reserved.
 *
 * originally part of m0n0wall (http://m0n0.ch/wall)
 * Copyright (c) 2003-2004 Manuel Kasper <mk@neon1.net>.
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

require_once("guiconfig.inc");
require_once("pfsense-utils.inc");
require_once("functions.inc");
require_once("/usr/local/www/widgets/include/installed_packages.inc");
require_once("pkg-utils.inc");

function get_pkg_stats() {
	$package_list = get_pkg_info('all', true, true);
	$installed_packages = array_filter($package_list, function($v) {
		return (isset($v['installed']) || isset($v['broken']));
	});

	if (empty($installed_packages)) {
		print_info_box(gettext("설치된 패키지가 없습니다."), 'warning', false);
		return;
	}

	print("<thead>\n");
	print(	"<tr>\n");
	print(		"<th>" . gettext("이름")     . "</th>\n");
	print(		"<th>" . gettext("버전")  . "</th>\n");
	print(		"<th>" . gettext("Actions")  . "</th>\n");
	print(	"</tr>\n");
	print("</thead>\n");
	print("<tbody>\n");

	foreach ($installed_packages as $pkg) {
		if (!$pkg['name']) {
			continue;
		}

		$txtcolor = "";
		$upgradeavail = false;
		$vergetstr = "";
		$missing = false;

		if (isset($pkg['broken'])) {
			$txtcolor = "text-danger";
			$missing = true;
			$status = gettext('패키지가 구성되었지만 설치되지 않았습니다!');
		} else if (isset($pkg['installed_version']) && isset($pkg['version'])) {
			$version_compare = pkg_version_compare(
			    $pkg['installed_version'], $pkg['version']);
			if ($version_compare == '>') {
				// we're running a newer version of the package
				$status = sprintf(gettext('최신 버전(%s)'), $pkg['version']);
				$statusicon = 'exclamation';
			} else if ($version_compare == '<') {
				// we're running an older version of the package
				$status = sprintf(gettext('%s에 사용 가능한 업그레이드'), $pkg['version']);
				$statusicon = 'plus-circle';
				$txtcolor = "text-warning";
				$upgradeavail = true;
				$vergetstr = '&amp;from=' . $pkg['installed_version'] .
				    '&amp;to=' . $pkg['version'];
			} else if ($version_compare == '=') {
				// we're running the current version
				$status = gettext('예');
				$statusicon = 'check';
			} else {
				$status = gettext('버전 비교 중 오류 발생');
				$statusicon = 'exclamation';
			}
		} else {
			// unknown available package version
			$status = gettext('알수없음');
			$statusicon = 'question';
		}

		print("<tr>\n");
		print(		'<td><span class="' . $txtcolor . '">' . $pkg['shortname'] . "</span></td>\n");
		print(		"<td>\n");
		print(			'<i title="' . $status . '" class="fa fa-' . $statusicon . '"></i> ');

		if (!$g['disablepackagehistory']) {
			print('<a target="_blank" title="' . gettext("변경내역보기") . '" href="' . htmlspecialchars($pkg['changeloglink']) . '">');
		}

		print(			htmlspecialchars($pkg['installed_version']));

		if (!$g['disablepackagehistory']) {
			print("</a>\n");
		}

		print(	"</td>\n");
		print(	"<td>\n");
		print(		'<a title="' . gettext("삭제") . '" href="pkg_mgr_install.php?mode=delete&amp;pkg=' . $pkg['name'] . '"><i class="fa fa-trash"></i></a>'."\n");

		if ($upgradeavail) {
			print(	'<a title="' . gettext("업데이트") . '" href="pkg_mgr_install.php?mode=reinstallpkg&amp;pkg=' . $pkg['name'] . $vergetstr . '"><i class="fa fa-refresh"></i></a>'."\n");
		} else {
			print(	'<a title="' . gettext("재설치") . '" href="pkg_mgr_install.php?mode=reinstallpkg&amp;pkg=' . $pkg['name'] . '"><i class="fa fa-retweet"></i></a>'."\n");
		}

		if (!isset($g['disablepackageinfo']) && $pkg['www'] != 'UNKNOWN') {
			print(	'<a target="_blank" title="' . gettext("더 많은 정보보기") . '" href="' . htmlspecialchars($pkg['www']) . '"><i class="fa fa-info"></i></a>'."\n");
		}

		print(	"</td>\n");
		print("</tr>\n");
	}

	print("</tbody>\n");

}
?>

<div class="table-responsive">
	<table id="pkgtbl" class="table table-striped table-hover table-condensed">
		<?php get_pkg_stats(); ?>
	</table>
</div>

<p class="text-center">
	<?=gettext("여기에 패키지를 추가 및 관리 할 수 있습니다.")?> <a href="pkg_mgr_installed.php"><?=gettext("시스템")?> -&gt; <?=gettext("Packages")?></a>
</p>

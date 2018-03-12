<?php
/*
 * diag_sockets.php
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
2018.03.12
한글화 번역 시작
*/

##|+PRIV
##|*IDENT=page-diagnostics-sockets
##|*NAME=Diagnostics: Sockets
##|*DESCR=Allow access to the 'Diagnostics: Sockets' page.
##|*MATCH=diag_sockets.php*
##|-PRIV

require_once('guiconfig.inc');

$pgtitle = array(gettext("Diagnostics"), gettext("Sockets"));

include('head.inc');

$showAll = isset($_REQUEST['showAll']);
$showAllText = $showAll ? gettext("수신받는 소켓만 표시") : gettext("모든 소켓 표시");
$showAllOption = $showAll ? "" : "?showAll";

?>
<button class="btn btn-info btn-sm" type="button" value="<?=$showAllText?>" onclick="window.location.href='diag_sockets.php<?=$showAllOption?>'">
	<i class="fa fa-<?= ($showAll) ? 'minus-circle' : 'plus-circle' ; ?> icon-embed-btn"></i>
	<?=$showAllText?>
</button>
<br />
<br />

<?php
	if (isset($_REQUEST['showAll'])) {
		$internet4 = shell_exec('sockstat -4');
		$internet6 = shell_exec('sockstat -6');
	} else {
		$internet4 = shell_exec('sockstat -4l');
		$internet6 = shell_exec('sockstat -6l');
	}


	foreach (array(&$internet4, &$internet6) as $tabindex => $table) {
		$elements = ($tabindex == 0 ? 7 : 7);
		$name = ($tabindex == 0 ? 'IPv4' : 'IPv6');
?>
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=$name?> <?=gettext("시스템 소켓 정보")?></h2></div>
	<div class="panel-body">
		<div class="table table-responsive">
			<table class="table table-striped table-hover table-condensed sortable-theme-bootstrap" data-sortable>
				<thead>
<?php
					foreach (explode("\n", $table) as $i => $line) {
						if (trim($line) == "") {
							continue;
						}

						$j = 0;
						print("<tr>\n");
						foreach (explode(' ', $line) as $entry) {
							if ($entry == '' || $entry == "ADDRESS") {
								continue;
							}

							if ($i == 0) {
								print("<th class=\"$class\">$entry</th>\n");
							} else {
								print("<td class=\"$class\">$entry</td>\n");
							}

							$j++;
						}
						print("</tr>\n");
						if ($i == 0) {
							print("</thead>\n");
							print("<tbody>\n");
						}
					}
?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<?php
	}
?>

<div>
<div class="infoblock">
<?php
print_info_box(
	gettext('소켓 정보') .
		'<br /><br />' .
		sprintf(gettext('이 페이지는 기본적으로 모든 청취 소켓을 표시하며 %1$s모든 소켓 표시%2$s를 클릭하면 청취 소켓과 아웃 바운드 연결 소켓을 모두 표시합니다.'), '<strong>', '</strong>') .
		'<br /><br />' .
		gettext('각 소켓에 대해 나열된 정보는 다음과 같습니다.:') .
		'<br /><br />' .
		'<dl class="dl-horizontal responsive">' .
		sprintf(gettext('%1$s사용자%2$s	%3$s소켓을 소유한 사용자.%4$s'), '<dt>', '</dt>', '<dd>', '</dd>') .
		sprintf(gettext('%1$s커멘드%2$s	%3$s소켓을 보유하는 명령.%4$s'), '<dt>', '</dt>', '<dd>', '</dd>') .
		sprintf(gettext('%1$sPID%2$s	%3$s소켓을 보유하고있는 명령의 프로세스 ID.%4$s'), '<dt>', '</dt>', '<dd>', '</dd>') .
		sprintf(gettext('%1$sFD%2$s	%3$s소켓의 파일 기술자 번호.%4$s'), '<dt>', '</dt>', '<dd>', '</dd>') .
		sprintf(gettext('%1$sPROTO%2$s	%3$s소켓과 관련된 전송 프로토콜입니다.%4$s'), '<dt>', '</dt>', '<dd>', '</dd>') .
		sprintf(gettext('%1$s로컬 주소%2$s	%3$s소켓의 로컬 끝이 바인드 된 주소입니다.%4$s'), '<dt>', '</dt>', '<dd>', '</dd>') .
		sprintf(gettext('%1$s외부 주소%2$s	%3$s소켓의 외부 끝이 바인딩 된 주소입니다.%4$s'), '<dt>', '</dt>', '<dd>', '</dd>') .
		'</dl>',
	'info',
	false);
?>
</div>
</div>
<?php

include('foot.inc');



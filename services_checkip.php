<?php
/*
 * services_checkip.php
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
##|*IDENT=page-services-checkipservices
##|*NAME=Services: Check IP Service
##|*DESCR=Allow access to the 'Services: Check IP Service' page.
##|*MATCH=services_checkip.php*
##|-PRIV

require_once("guiconfig.inc");

if (!is_array($config['checkipservices']['checkipservice'])) {
	$config['checkipservices']['checkipservice'] = array();
}

$a_checkipservice = &$config['checkipservices']['checkipservice'];

$dirty = false;
if ($_POST['act'] == "del") {
	unset($a_checkipservice[$_POST['id']]);
	$wc_msg = gettext('Deleted a check IP service.');
	$dirty = true;
} else if ($_POST['act'] == "toggle") {
	if ($a_checkipservice[$_POST['id']]) {
		if (isset($a_checkipservice[$_POST['id']]['enable'])) {
			unset($a_checkipservice[$_POST['id']]['enable']);
			$wc_msg = gettext('IP 검사 서비스를 사용할 수 없습니다.');
		} else {
			$a_checkipservice[$_POST['id']]['enable'] = true;
			$wc_msg = gettext('IP 검사 서비스를 활성화했습니다.');
		}
		$dirty = true;
	} else if ($_POST['id'] == count($a_checkipservice)) {
		if (isset($config['checkipservices']['disable_factory_default'])) {
			unset($config['checkipservices']['disable_factory_default']);
			$wc_msg = gettext('기본 체크 IP 서비스를 활성화했습니다.');
		} else {
			$config['checkipservices']['disable_factory_default'] = true;
			$wc_msg = gettext('기본 체크 IP 서비스를 비활성화했습니다.');
		}
		$dirty = true;
	}
}
if ($dirty) {
	write_config($wc_msg);

	header("Location: services_checkip.php");
	exit;
}

$pgtitle = array(gettext("Services"), gettext("동적 DNS"), gettext("Check IP Services"));
$pglinks = array("", "services_dyndns.php", "@self");
include("head.inc");

$tab_array = array();
$tab_array[] = array(gettext("동적 DNS 클라이언트"), false, "services_dyndns.php");
$tab_array[] = array(gettext("RFC 2136 클라이언트"), false, "services_rfc2136.php");
$tab_array[] = array(gettext("IP 체크 서비스"), true, "services_checkip.php");
display_top_tabs($tab_array);

if ($input_errors) {
	print_input_errors($input_errors);
}
?>

<form action="services_checkip.php" method="post" name="iform" id="iform">
	<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext('IP 체크 서비스')?></h2></div>
		<div class="panel-body">
			<div class="table-responsive">
				<table class="table table-striped table-hover table-condensed">
					<thead>
						<tr>
							<th><?=gettext("이름")?></th>
							<th><?=gettext("URL")?></th>
							<th><?=gettext("SSL 피어 확인")?></th>
							<th><?=gettext("Description")?></th>
							<th><?=gettext("Actions")?></th>
						</tr>
					</thead>
					<tbody>
<?php
// Is the factory default check IP service disabled?
if (isset($config['checkipservices']['disable_factory_default'])) {
	unset($factory_default_checkipservice['enable']);
}

// Append the factory default check IP service to the list.
$a_checkipservice[] = $factory_default_checkipservice;
$factory_default = count($a_checkipservice) - 1;

$i = 0;
foreach ($a_checkipservice as $checkipservice):

	// Hide edit and delete controls on the factory default check IP service entry (last one; id = count-1), and retain layout positioning.
	if ($i == $factory_default) {
		$visibility = 'invisible';
	} else {
		$visibility = 'visible';
	}
?>
						<tr<?=(isset($checkipservice['enable']) ? '' : ' class="disabled"')?>>
						<td>
							<?=htmlspecialchars($checkipservice['name'])?>
						</td>
						<td>
							<?=htmlspecialchars($checkipservice['url'])?>
						</td>
						<td class="text-center">
							<i<?=(isset($checkipservice['verifysslpeer'])) ? ' class="fa fa-check"' : '';?>></i>
						</td>
						<td>
							<?=htmlspecialchars($checkipservice['descr'])?>
						</td>
						<td>
							<a class="fa fa-pencil <?=$visibility?>" title="<?=gettext('서비스 편집')?>" href="services_checkip_edit.php?id=<?=$i?>"></a>
						<?php if (isset($checkipservice['enable'])) {
						?>
							<a	class="fa fa-ban" title="<?=gettext('서비스 비활성화')?>" href="?act=toggle&amp;id=<?=$i?>" usepost></a>
						<?php } else {
						?>
							<a class="fa fa-check-square-o" title="<?=gettext('서비스 활성화')?>" href="?act=toggle&amp;id=<?=$i?>" usepost></a>
						<?php }
						?>
							<a class="fa fa-trash <?=$visibility?>" title="<?=gettext('서비스 삭제')?>" href="services_checkip.php?act=del&amp;id=<?=$i?>" usepost></a>
						</td>
					</tr>
<?php
	$i++;
endforeach; ?>

					</tbody>
				</table>
			</div>
		</div>
	</div>
</form>

<nav class="action-buttons">
	<a href="services_checkip_edit.php" class="btn btn-sm btn-success btn-sm">
		<i class="fa fa-plus icon-embed-btn"></i>
		<?=gettext('추가')?>
	</a>
</nav>

<div class="infoblock">
	<?php print_info_box(gettext('서버는 클라이언트 IP 주소를 다음 형식의 문자열로 반환해야합니다.: ') .
	'<pre>현재 IP 주소: x.x.x.x</pre>' .
	gettext(
	'첫 번째 (목록에서 최상위) 활성화 된 check ip 서비스는 ' .
	'동적 DNS 서비스의 IP 주소를 검사하고 "공용 IP 사용"옵션이 ' .
	'활성화 된 RFC 2136 항목을 확인하는 데 사용됩니다.') .
	'<br/><br/>'
	, 'info', false);

	print_info_box(gettext('Sample Server Configurations') .
	'<br/>' .
	gettext('nginx with LUA') . ':' .
	'<pre> location = /ip {
	default_type text/html;
	content_by_lua \'
		ngx.say("' . htmlspecialchars('<html><head><title>현재 IP 주소 </title></head><body>') . '현재 IP 주소: ")
		ngx.say(ngx.var.remote_addr)
		ngx.say("' . htmlspecialchars('</body></html>') . '")
	\';
	}</pre>' .
	gettext('PHP') .
	'<pre>' .
	htmlspecialchars('<html><head><title>Current IP Check</title></head><body>현재 IP 주소: <?=$_SERVER[\'REMOTE_ADDR\']?></body></html>') .
	'</pre>'
	, 'info', false); ?>
</div>

<?php include("foot.inc");

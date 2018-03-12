<?php
/*
 * services_captiveportal_hostname.php
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
##|*IDENT=page-services-captiveportal-allowedhostnames
##|*NAME=Services: Captive Portal: Allowed Hostnames
##|*DESCR=Allow access to the 'Services: Captive Portal: Allowed Hostnames' page.
##|*MATCH=services_captiveportal_hostname.php*
##|-PRIV

$directionicons = array('to' => '&#x2192;', 'from' => '&#x2190;', 'both' => '&#x21c4;');

$notestr =
	sprintf(gettext('새 호스트 이름을 추가하면 포털 페이지로 이동하지 않고도 DNS 호스트 이름에 전속 포털간 액세스가 가능합니다. ' .
	'포털 페이지의 이미지를 제공하는 웹 서버 또는 다른 네트워크의 DNS 서버에 사용할 수 있습니다. ' .
	'%1$sfrom$2$s 주소를 지정하면 캡 티브 포털에서 클라이언트의 통과 액세스를 항상 허용하는 데 사용할 수 있습니다.'),
	'<em>', '</em>');

require_once("guiconfig.inc");
require_once("functions.inc");
require_once("filter.inc");
require_once("shaper.inc");
require_once("captiveportal.inc");

$cpzone = $_REQUEST['zone'];

$cpzone = strtolower(htmlspecialchars($cpzone));

if (empty($cpzone) || empty($config['captiveportal'][$cpzone])) {
	header("Location: services_captiveportal_zones.php");
	exit;
}

if (!is_array($config['captiveportal'])) {
	$config['captiveportal'] = array();
}

$a_cp =& $config['captiveportal'];

if (isset($cpzone) && !empty($cpzone) && isset($a_cp[$cpzone]['zoneid'])) {
	$cpzoneid = $a_cp[$cpzone]['zoneid'];
}

$pgtitle = array(gettext("서비스"), gettext("전속 포털"), $a_cp[$cpzone]['zone'], gettext("허용된 호스트이름"));
$pglinks = array("", "services_captiveportal_zones.php", "services_captiveportal.php?zone=" . $cpzone, "@self");
$shortcut_section = "captiveportal";

if ($_POST['act'] == "del" && !empty($cpzone) && isset($cpzoneid)) {
	$a_allowedhostnames =& $a_cp[$cpzone]['allowedhostname'];
	if ($a_allowedhostnames[$_POST['id']]) {
		$ipent = $a_allowedhostnames[$_POST['id']];

		if (isset($a_cp[$cpzone]['enable'])) {
			if (is_ipaddr($ipent['hostname'])) {
				$ip = $ipent['hostname'];
			} else {
				$ip = gethostbyname($ipent['hostname']);
			}
			$sn = (is_ipaddrv6($ip)) ? 128 : 32;
			if (is_ipaddr($ip)) {
				$rule = pfSense_ipfw_table_lookup("{$cpzone}_allowed_up", "{$ip}/{$sn}");

				pfSense_ipfw_table("{$cpzone}_allowed_up", IP_FW_TABLE_XDEL, "{$ip}/{$sn}");
				pfSense_ipfw_table("{$cpzone}_allowed_down", IP_FW_TABLE_XDEL, "{$ip}/{$sn}");

				if (is_array($rule) && !empty($rule['pipe'])) {
					captiveportal_free_dn_ruleno($rule['pipe']);
					pfSense_ipfw_pipe("pipe delete {$rule['pipe']}");
					pfSense_ipfw_pipe("pipe delete " . ($rule['pipe']+1));
				}
			}
		}

		unset($a_allowedhostnames[$_POST['id']]);
		write_config();
		captiveportal_allowedhostname_configure();
		header("Location: services_captiveportal_hostname.php?zone={$cpzone}");
		exit;
	}
}

include("head.inc");

$tab_array = array();
$tab_array[] = array(gettext("구성"), false, "services_captiveportal.php?zone={$cpzone}");
$tab_array[] = array(gettext("MACs"), false, "services_captiveportal_mac.php?zone={$cpzone}");
$tab_array[] = array(gettext("허용된 IP 주소"), false, "services_captiveportal_ip.php?zone={$cpzone}");
$tab_array[] = array(gettext("허용된 호스트이름"), true, "services_captiveportal_hostname.php?zone={$cpzone}");
$tab_array[] = array(gettext("바우처"), false, "services_captiveportal_vouchers.php?zone={$cpzone}");
$tab_array[] = array(gettext("파일 매니저"), false, "services_captiveportal_filemanager.php?zone={$cpzone}");
display_top_tabs($tab_array, true);
?>
<div class="table-responsive">
	<table class="table table-hover table-striped table-condensed table-rowdblclickedit sortable-theme-bootstrap" data-sortable>
		<thead>
			<tr>
				<th><?=gettext("호스트이름"); ?></th>
				<th><?=gettext("Description"); ?></th>
				<th data-sortable="false"><?=gettext("Actions"); ?></th>
			</tr>
		</thead>

<?php
if (is_array($a_cp[$cpzone]['allowedhostname'])): ?>
		<tbody>
<?php
$i = 0;
foreach ($a_cp[$cpzone]['allowedhostname'] as $ip): ?>
			<tr>
				<td>
					<?=$directionicons[$ip['dir']]?>&nbsp;<?=strtolower($ip['hostname'])?>
				</td>
				<td >
					<?=htmlspecialchars($ip['descr'])?>
				</td>
				<td>
					<a class="fa fa-pencil"	title="<?=gettext("호스트이름 편집"); ?>" href="services_captiveportal_hostname_edit.php?zone=<?=$cpzone?>&amp;id=<?=$i?>"></a>
					<a class="fa fa-trash"	title="<?=gettext("호스트이름 삭제")?>" href="services_captiveportal_hostname.php?zone=<?=$cpzone?>&amp;act=del&amp;id=<?=$i?>" usepost></a>
				</td>
			</tr>
<?php
$i++;
endforeach; ?>
		<tbody>
	</table>
	<?=$directionicons['to'] . ' = ' . sprintf(gettext('%1$sto%2$s 호스트 이름에 대한 모든 연결이 허용됩니다.'), '<u>', '</u>') . ', '?>
	<?=$directionicons['from'] . ' = ' . sprintf(gettext('호스트 이름 %1$sfrom%2$s의 모든 연결이 허용됩니다.'), '<u>', '</u>') . ', '?>
	<?=$directionicons['both'] . ' = ' . sprintf(gettext('%1$sto%2$s 또는 %1$sfrom%2$s 호스트 이름에 대한 모든 연결이 허용됩니다.'), '<u>', '</u>')?>
<?php
else:
?>
		</tbody>
	</table>
<?php
endif;
?>
</div>

<nav class="action-buttons">
	<a href="services_captiveportal_hostname_edit.php?zone=<?=$cpzone?>&amp;act=add" class="btn btn-success btn-sm">
		<i class="fa fa-plus icon-embed-btn"></i>
		<?=gettext("추가")?>
	</a>
</nav>

<div class="infoblock">
	<?php print_info_box($notestr, 'info', false); ?>
</div>

<?php

include("foot.inc");

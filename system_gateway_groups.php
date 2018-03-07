<?php
/*
 * system_gateway_groups.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2018 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2010 Seth Mos <seth.mos@dds.nl>
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
2018.03.07
한글화 번역 시작
*/

##|+PRIV
##|*IDENT=page-system-gatewaygroups
##|*NAME=System: Gateway Groups
##|*DESCR=Allow access to the 'System: Gateway Groups' page.
##|*MATCH=system_gateway_groups.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("functions.inc");
require_once("filter.inc");
require_once("shaper.inc");
require_once("openvpn.inc");

if (!is_array($config['gateways']['gateway_group'])) {
	$config['gateways']['gateway_group'] = array();
}

$a_gateway_groups = &$config['gateways']['gateway_group'];
$a_gateways = &$config['gateways']['gateway_item'];
$changedesc = gettext("게이트웨이 그룹") . ": ";


$pconfig = $_REQUEST;

if ($_POST['apply']) {

	$retval = 0;

	$retval |= system_routing_configure();
	send_multiple_events(array("service reload dyndnsall", "service reload ipsecdns", "filter reload"));

	/* reconfigure our gateway monitor */
	setup_gateways_monitor();

	if ($retval == 0) {
		clear_subsystem_dirty('staticroutes');
	}

	foreach ($a_gateway_groups as $gateway_group) {
		$gw_subsystem = 'gwgroup.' . $gateway_group['name'];
		if (is_subsystem_dirty($gw_subsystem)) {
			openvpn_resync_gwgroup($gateway_group['name']);
			clear_subsystem_dirty($gw_subsystem);
		}
	}
}

if ($_POST['act'] == "del") {
	if ($a_gateway_groups[$_POST['id']]) {
		$changedesc .= sprintf(gettext("게이트웨이 그룹 %s을(를) 삭제합니다."), $_POST['id']);
		foreach ($config['filter']['rule'] as $idx => $rule) {
			if ($rule['gateway'] == $a_gateway_groups[$_REQUEST['id']]['name']) {
				unset($config['filter']['rule'][$idx]['gateway']);
			}
		}

		unset($a_gateway_groups[$_POST['id']]);
		write_config($changedesc);
		mark_subsystem_dirty('staticroutes');
		header("Location: system_gateway_groups.php");
		exit;
	}
}

function gateway_exists($gwname) {
	$gateways = return_gateways_array();

	if (is_array($gateways)) {
		foreach ($gateways as $gw) {
			if ($gw['name'] == $gwname) {
				return(true);
			}
		}
	}

	return(false);
}

$pgtitle = array(gettext("시스템"), gettext("라우팅"), gettext("게이트웨이 그룹"));
$pglinks = array("", "system_gateways.php", "@self");
$shortcut_section = "gateway-groups";

include("head.inc");

if ($_POST['apply']) {
	print_apply_result_box($retval);
}

if (is_subsystem_dirty('staticroutes')) {
	print_apply_box(gettext("게이트웨이 구성이 변경되었습니다.") . "<br />" . gettext("변경사항을 저장하시면 적용됩니다."));
}

$tab_array = array();
$tab_array[] = array(gettext("게이트웨이"), false, "system_gateways.php");
$tab_array[] = array(gettext("정적 라우트"), false, "system_routes.php");
$tab_array[] = array(gettext("게이트웨이 그룹"), true, "system_gateway_groups.php");
display_top_tabs($tab_array);
?>
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext('게이트웨이 그룹')?></h2></div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed table-rowdblclickedit">
				<thead>
					<tr>
						<th><?=gettext("그룹 이름")?></th>
						<th><?=gettext("게이트웨이")?></th>
						<th><?=gettext("우선순위")?></th>
						<th><?=gettext("Description")?></th>
						<th><?=gettext("Actions")?></th>
					</tr>
				</thead>
				<tbody>
<?php
$i = 0;
foreach ($a_gateway_groups as $gateway_group):
?>
					<tr>
						<td>
						   <?=$gateway_group['name']?>
						</td>
						<td>
<?php
	foreach ($gateway_group['item'] as $item) {
		$itemsplit = explode("|", $item);
		if (gateway_exists($itemsplit[0])) {
			print(htmlspecialchars(strtoupper($itemsplit[0])) . "<br />\n");
		}
	}
?>
						</td>
						<td>
<?php
	foreach ($gateway_group['item'] as $item) {
		$itemsplit = explode("|", $item);
		if (gateway_exists($itemsplit[0])) {
			print("Tier ". htmlspecialchars($itemsplit[1]) . "<br />\n");
		}
	}
?>
						</td>
						<td>
							<?=htmlspecialchars($gateway_group['descr'])?>
						</td>
						<td>
							<a href="system_gateway_groups_edit.php?id=<?=$i?>" class="fa fa-pencil" title="<?=gettext('게이트웨이 그룹 편집')?>"></a>
							<a href="system_gateway_groups_edit.php?dup=<?=$i?>" class="fa fa-clone" title="<?=gettext('게이트웨이 그룹 복사')?>"></a>
							<a href="system_gateway_groups.php?act=del&amp;id=<?=$i?>" class="fa fa-trash" title="<?=gettext('게이트웨이 그룹 삭제')?>" usepost></a>
						</td>
					</tr>
<?php
	$i++;
endforeach;
?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<nav class="action-buttons">
	<a href="system_gateway_groups_edit.php" class="btn btn-success btn-sm">
		<i class="fa fa-plus icon-embed-btn"></i>
		<?=gettext('추가')?>
	</a>
</nav>

<div class="infoblock">
	<?php print_info_box(sprintf(gettext('로드 균형 조정, 장애 조치 또는 정책 기반 라우팅을 사용하려면 방화벽 규칙에서 이러한 게이트웨이 그룹을 사용해야합니다. ' .
						   '%1$s 게이트웨이 그룹으로 트래픽을 보내는 규칙이 없으면 사용되지 않습니다.'), '<br />'), 'info', false); ?>
</div>
<?php
include("foot.inc");

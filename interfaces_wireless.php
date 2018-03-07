<?php
/*
 * interfaces_wireless.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2018 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2010 Erik Fonnesbeck
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
한글화 번역 
*/

##|+PRIV
##|*IDENT=page-interfaces-wireless
##|*NAME=Interfaces: Wireless
##|*DESCR=Allow access to the 'Interfaces: Wireless' page.
##|*MATCH=interfaces_wireless.php*
##|-PRIV

require_once("guiconfig.inc");

if (!is_array($config['wireless'])) {
	$config['wireless'] = array();
}
if (!is_array($config['wireless']['clone'])) {
	$config['wireless']['clone'] = array();
}

$a_clones = &$config['wireless']['clone'];

function clone_inuse($num) {
	global $config, $a_clones;

	$iflist = get_configured_interface_list(true);

	foreach ($iflist as $if) {
		if ($config['interfaces'][$if]['if'] == $a_clones[$num]['cloneif']) {
			return true;
		}
	}

	return false;
}

if ($_POST['act'] == "del") {
	/* check if still in use */
	if (clone_inuse($_POST['id'])) {
		$input_errors[] = gettext("이 무선 클론은 인터페이스로 지정되어있어 삭제할 수 없습니다.");
	} else {
		pfSense_interface_destroy($a_clones[$_POST['id']]['cloneif']);
		unset($a_clones[$_POST['id']]);

		write_config();

		header("Location: interfaces_wireless.php");
		exit;
	}
}


$pgtitle = array(gettext("인터페이스"), gettext("무선"));
$shortcut_section = "wireless";
include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

$tab_array = array();
$tab_array[] = array(gettext("인터페이스 할당"), false, "interfaces_assign.php");
$tab_array[] = array(gettext("인터페이스 그룹"), false, "interfaces_groups.php");
$tab_array[] = array(gettext("무선통신"), true, "interfaces_wireless.php");
$tab_array[] = array(gettext("VLANs"), false, "interfaces_vlan.php");
$tab_array[] = array(gettext("QinQs"), false, "interfaces_qinq.php");
$tab_array[] = array(gettext("PPPs"), false, "interfaces_ppps.php");
$tab_array[] = array(gettext("GREs"), false, "interfaces_gre.php");
$tab_array[] = array(gettext("GIFs"), false, "interfaces_gif.php");
$tab_array[] = array(gettext("브리지"), false, "interfaces_bridge.php");
$tab_array[] = array(gettext("LAGGs"), false, "interfaces_lagg.php");
display_top_tabs($tab_array);
?>
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext('무선통신 인터페이스')?></h2></div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed table-rowdblclickedit">
				<thead>
					<tr>
						<th><?=gettext("인터페이스"); ?></th>
						<th><?=gettext("모드"); ?></th>
						<th><?=gettext("Description"); ?></th>
						<th><?=gettext("Actions"); ?></th>
					</tr>
				</thead>
				<tbody>
<?php

$i = 0;

foreach ($a_clones as $clone) {
?>
					<tr>
						<td>
							<?=htmlspecialchars($clone['cloneif'])?>
						</td>
						<td>
							<?= $wlan_modes[$clone['mode']]; ?>
						</td>
						<td>
							<?=htmlspecialchars($clone['descr'])?>
						</td>
						<td>
							<a class="fa fa-pencil"	title="<?=gettext('WiFi 인터페이스 편집')?>"	href="interfaces_wireless_edit.php?id=<?=$i?>"></a>
							<a class="fa fa-trash"	title="<?=gettext('WiFi 인터페이스 삭제')?>"	href="interfaces_wireless.php?act=del&amp;id=<?=$i?>" usepost></a>
						</td>
					</tr>
<?php
	$i++;
}
?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<nav class="action-buttons">
	<a href="interfaces_wireless_edit.php" class="btn btn-success btn-sm">
		<i class="fa fa-plus icon-embed-btn"></i>
		<?=gettext("추가")?></a>
</nav>
<?php
include("foot.inc");

<?php
/*
 * interfaces_lagg.php
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
2018.03.09
한글화 번역 시
*/

##|+PRIV
##|*IDENT=page-interfaces-lagg
##|*NAME=Interfaces: LAGG:
##|*DESCR=Allow access to the 'Interfaces: LAGG' page.
##|*MATCH=interfaces_lagg.php*
##|-PRIV

require_once("guiconfig.inc");

if (!is_array($config['laggs']['lagg'])) {
	$config['laggs']['lagg'] = array();
}

$a_laggs = &$config['laggs']['lagg'] ;

function lagg_inuse($num) {
	global $config, $a_laggs;

	$iflist = get_configured_interface_list(true);
	foreach ($iflist as $if) {
		if ($config['interfaces'][$if]['if'] == $a_laggs[$num]['laggif']) {
			return true;
		}
	}

	if (is_array($config['vlans']['vlan']) && count($config['vlans']['vlan'])) {
		foreach ($config['vlans']['vlan'] as $vlan) {
			if ($vlan['if'] == $a_laggs[$num]['laggif']) {
				return true;
			}
		}
	}
	return false;
}

if ($_POST['act'] == "del") {
	if (!isset($_POST['id'])) {
		$input_errors[] = gettext("잘못된 파라미터가 제공되었습니다.");
	} else if (empty($a_laggs[$_POST['id']])) {
		$input_errors[] = gettext("잘못된 색인이 제공되었습니다.");
	/* check if still in use */
	} else if (lagg_inuse($_POST['id'])) {
		$input_errors[] = gettext("이 LAGG 인터페이스는 아직 사용 중이기 때문에 삭제할 수 없습니다.");
	} else {
		pfSense_interface_destroy($a_laggs[$_POST['id']]['laggif']);
		unset($a_laggs[$_POST['id']]);

		write_config();

		header("Location: interfaces_lagg.php");
		exit;
	}
}

$pgtitle = array(gettext("인터페이스"), gettext("LAGGs"));
$shortcut_section = "interfaces";
include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

$tab_array = array();
$tab_array[] = array(gettext("인터페이스 과제"), false, "interfaces_assign.php");
$tab_array[] = array(gettext("인터페이스 그룹"), false, "interfaces_groups.php");
$tab_array[] = array(gettext("무선통신"), false, "interfaces_wireless.php");
$tab_array[] = array(gettext("VLANs"), false, "interfaces_vlan.php");
$tab_array[] = array(gettext("QinQs"), false, "interfaces_qinq.php");
$tab_array[] = array(gettext("PPPs"), false, "interfaces_ppps.php");
$tab_array[] = array(gettext("GREs"), false, "interfaces_gre.php");
$tab_array[] = array(gettext("GIFs"), false, "interfaces_gif.php");
$tab_array[] = array(gettext("브리지"), false, "interfaces_bridge.php");
$tab_array[] = array(gettext("LAGGs"), true, "interfaces_lagg.php");
display_top_tabs($tab_array);
?>
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext('LAGG 인터페이스')?></h2></div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed table-rowdblclickedit">
				<thead>
					<tr>
						<th><?=gettext("인터페이스"); ?></th>
						<th><?=gettext("멤버"); ?></th>
						<th><?=gettext("Description"); ?></th>
						<th><?=gettext("Actions"); ?></th>
					</tr>
				</thead>
				<tbody>
<?php

$i = 0;

foreach ($a_laggs as $lagg) {
?>
					<tr>
						<td>
							<?=htmlspecialchars(strtoupper($lagg['laggif']))?>
						</td>
						<td>
							<?=htmlspecialchars($lagg['members'])?>
						</td>
						<td>
							<?=htmlspecialchars($lagg['descr'])?>
						</td>
						<td>
							<a class="fa fa-pencil"	title="<?=gettext('LAGG 인터페이스 편집')?>"	href="interfaces_lagg_edit.php?id=<?=$i?>"></a>
							<a class="fa fa-trash"	title="<?=gettext('LAGG 인터페이스 삭제')?>"	href="interfaces_lagg.php?act=del&amp;id=<?=$i?>" usepost></a>
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
	<a href="interfaces_lagg_edit.php" class="btn btn-success btn-sm">
		<i class="fa fa-plus icon-embed-btn"></i>
		<?=gettext("Add")?>
	</a>
</nav>

<?php
include("foot.inc");

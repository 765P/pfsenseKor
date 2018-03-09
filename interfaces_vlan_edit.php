<?php
/*
 * interfaces_vlan_edit.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2018 Rubicon Communications, LLC (Netgate)
 * All rights reserved.
 *
 * originally based on m0n0wall (http://m0n0.ch/wall)
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

/*
2018.03.09
한글화 번역 시작 
*/

##|+PRIV
##|*IDENT=page-interfaces-vlan-edit
##|*NAME=Interfaces: VLAN: Edit
##|*DESCR=Allow access to the 'Interfaces: VLAN: Edit' page.
##|*MATCH=interfaces_vlan_edit.php*
##|-PRIV

require_once("guiconfig.inc");

if (!is_array($config['vlans']['vlan'])) {
	$config['vlans']['vlan'] = array();
}

$a_vlans = &$config['vlans']['vlan'];

$portlist = get_interface_list();
$lagglist = get_lagg_interface_list();
$portlist = array_merge($portlist, $lagglist);
foreach ($lagglist as $laggif => $lagg) {
	/* LAGG members cannot be assigned */
	$laggmembers = explode(',', $lagg['members']);
	foreach ($laggmembers as $lagm) {
		if (isset($portlist[$lagm])) {
			unset($portlist[$lagm]);
		}
	}
}

if (is_numericint($_REQUEST['id'])) {
	$id = $_REQUEST['id'];
}

if (isset($id) && $a_vlans[$id]) {
	$pconfig['if'] = $a_vlans[$id]['if'];
	$pconfig['vlanif'] = $a_vlans[$id]['vlanif'];
	$pconfig['tag'] = $a_vlans[$id]['tag'];
	$pconfig['pcp'] = $a_vlans[$id]['pcp'];
	$pconfig['descr'] = $a_vlans[$id]['descr'];
}

if ($_POST['save']) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "if tag");
	$reqdfieldsn = array(gettext("상위 인터페이스"), gettext("VLAN 태그"));

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

	if (isset($_POST['tag']) && !vlan_valid_tag($_POST['tag'])) {
		$input_errors[] = gettext("VLAN 태그는 1에서 4094 사이의 정수 여야합니다.");
	}
	if (isset($_POST['pcp']) && !empty($_POST['pcp']) && (!is_numericint($_POST['pcp']) || ($_POST['pcp'] < '0') || ($_POST['pcp'] > '7'))) {
		$input_errors[] = gettext("VLAN 우선 순위는 0과 7 사이의 정수 여야합니다.");
 	}

	if (!does_interface_exist($_POST['if'])) {
		$input_errors[] = gettext("상위로 제공된 인터페이스가 잘못되었습니다.");
	}

	if (isset($id)) {
		if ($_POST['tag'] && $_POST['tag'] != $a_vlans[$id]['tag']) {
			if (!empty($a_vlans[$id]['vlanif']) && convert_real_interface_to_friendly_interface_name($a_vlans[$id]['vlanif']) != NULL) {
				$input_errors[] = gettext("인터페이스가 할당되어있는 동안에는 VLAN 태그를 변경할 수 없습니다.");
			}
		}
	}
	foreach ($a_vlans as $vlan) {
		if (isset($id) && ($a_vlans[$id]) && ($a_vlans[$id] === $vlan)) {
			continue;
		}

		if (($vlan['if'] == $_POST['if']) && ($vlan['tag'] == $_POST['tag'])) {
			$input_errors[] = sprintf(gettext("이 인터페이스에는 이미 % s 태그가있는 VLAN이 정의되어 있습니다."), $vlan['tag']);
			break;
		}
	}
	if (is_array($config['qinqs']['qinqentry'])) {
		foreach ($config['qinqs']['qinqentry'] as $qinq) {
			if ($qinq['tag'] == $_POST['tag'] && $qinq['if'] == $_POST['if']) {
				$input_errors[] = sprintf(gettext('QinQ VLAN이이 태그와 함께 % s에 있습니다. 이 태그를 일반 VLAN에 사용하려면이 태그를 제거하십시오.'), $_POST['if']);
			}
		}
	}

	if (!$input_errors) {
		if (isset($id) && $a_vlans[$id]) {
			if (($a_vlans[$id]['if'] != $_POST['if']) || ($a_vlans[$id]['tag'] != $_POST['tag'])) {
				if (!empty($a_vlans[$id]['vlanif'])) {
					$confif = convert_real_interface_to_friendly_interface_name($a_vlans[$id]['vlanif']);
					// Destroy previous vlan
					pfSense_interface_destroy($a_vlans[$id]['vlanif']);
				} else {
					pfSense_interface_destroy(vlan_interface($a_vlans[id]));
					$confif = convert_real_interface_to_friendly_interface_name(vlan_interface($a_vlans[$id]));
				}
				if ($confif != "") {
					$config['interfaces'][$confif]['if'] = vlan_interface($_POST);
				}
			}
		}
		$vlan = array();
		$vlan['if'] = $_POST['if'];
		$vlan['tag'] = $_POST['tag'];
		$vlan['pcp'] = $_POST['pcp'];
		$vlan['descr'] = $_POST['descr'];
		$vlan['vlanif'] = vlan_interface($_POST);
		$vlanif = interface_vlan_configure($vlan);
		if ($vlanif == NULL || $vlanif != $vlan['vlanif']) {
			pfSense_interface_destroy($vlan['vlanif']);
			$input_errors[] = gettext("인터페이스를 생성하는 동안 오류가 발생했습니다. 다시 시도하십시오.");
		} else {
			if (isset($id) && $a_vlans[$id]) {
				$a_vlans[$id] = $vlan;
			} else {
				$a_vlans[] = $vlan;
			}

			write_config();

			if ($confif != "") {
				interface_configure($confif);
			}
			header("Location: interfaces_vlan.php");
			exit;
		}
	}
}

function build_interfaces_list() {
	global $portlist;

	$list = array();

	foreach ($portlist as $ifn => $ifinfo) {
		if (is_jumbo_capable($ifn)) {
			$list[$ifn] = $ifn . " (" . $ifinfo['mac'] . ")";
			$iface = convert_real_interface_to_friendly_interface_name($ifn);
			if (isset($iface) && strlen($iface) > 0)
				$list[$ifn] .= " - $iface";
		}
	}

	return($list);
}

$pgtitle = array(gettext("인터페이스"), gettext("VLANs"), gettext("편집"));
$pglinks = array("", "interfaces_vlan.php", "@self");
$shortcut_section = "interfaces";
include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

$form = new Form;
$section = new Form_Section('VLAN 구성');

$section->addInput(new Form_Select(
	'if',
	'*Parent Interface',
	$pconfig['if'],
	build_interfaces_list()
))->setWidth(6)->setHelp('VLAN 가능 인터페이스 만 표시됩니다..');

$section->addInput(new Form_Input(
	'tag',
	'*VLAN Tag',
	'text',
	$pconfig['tag'],
	['placeholder' => '1']
))->setWidth(6)->setHelp('802.1Q VLAN tag (between 1 and 4094).');

$section->addInput(new Form_Input(
	'pcp',
	'VLAN Priority',
	'text',
	$pconfig['pcp'],
	['placeholder' => '0']
))->setWidth(6)->setHelp('802.1Q VLAN Priority (between 0 and 7).');

$section->addInput(new Form_Input(
	'descr',
	'Description',
	'text',
	$pconfig['descr'],
	['placeholder' => 'Description']
))->setWidth(6)->setHelp('A group description may be entered here for administrative reference (not parsed).');

$form->addGlobal(new Form_Input(
	'vlanif',
	'vlanif',
	'hidden',
	$pconfig['vlanif']
));

if (isset($id) && $a_vlans[$id]) {
	$form->addGlobal(new Form_Input(
		'id',
		'id',
		'hidden',
		$id
	));
}

$form->add($section);
print $form;

include("foot.inc");

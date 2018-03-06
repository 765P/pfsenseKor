<?php
/*
 * interfaces_lagg_edit.php
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
2018.03.06
한글화 번역 
*/

##|+PRIV
##|*IDENT=page-interfaces-lagg-edit
##|*NAME=Interfaces: LAGG: Edit
##|*DESCR=Allow access to the 'Interfaces: LAGG: Edit' page.
##|*MATCH=interfaces_lagg_edit.php*
##|-PRIV

require_once("guiconfig.inc");

if (!is_array($config['laggs']['lagg'])) {
	$config['laggs']['lagg'] = array();
}

$a_laggs = &$config['laggs']['lagg'];

$portlist = get_interface_list();
$laggprotos	  = array("none", "lacp", "failover", "fec", "loadbalance", "roundrobin");
$laggprotosuc = array(gettext("NONE"), gettext("LACP"), gettext("FAILOVER"), gettext("FEC"), gettext("LOADBALANCE"), gettext("ROUNDROBIN"));

$protohelp =
'<ul>' .
	'<li>' .
		'<strong>' . $laggprotosuc[0] . '</strong><br />' .
		gettext('이 프로토콜은 아무 활동이 없으므로, 레이블 인터페이스 자체를 사용하지 않으면서 트래픽을 사용하지 않도록 설정합니다.') .
	'</li>' .
	'<li>' .
		'<strong>' . $laggprotosuc[1] . '</strong><br />' .
		gettext('IEEE 802.3ad LACP (Link Aggregation Control Protocol) 및 마커 프로토콜을 지원합니다. ' .
				'LACP는 하나 이상의 링크 집계 그룹에있는 피어와 집합 가능한 링크 집합을 협상합니다. ' .
				'각 LAG는 전이중 작동으로 설정된 동일한 속도의 포트로 구성됩니다. ' .
				'트래픽은 LAG의 포트에서 최대 속도로 균형을 유지하게됩니다. ' .
				'대부분의 경우 모든 포트를 포함하는 LAG가 하나만 있습니다. ' .
				'물리적 연결이 변경되는 경우 Link Aggregation은 새로운 구성으로 신속하게 수렴됩니다.') .
	'</li>' .
	'<li>' .
		'<strong>' . $laggprotosuc[2] . '</strong><br />' .
		gettext('마스터 포트를 통해서만 트래픽을 보내고받습니다. ' .
				'마스터 포트를 사용할 수 없게되면 다음 활성 포트가 사용됩니다. ' .
				'추가 된 첫 번째 인터페이스는 마스터 포트입니다. 이후에 추가 된 인터페이스는 페일 오버 장치로 사용됩니다.') .
	'</li>' .
	'<li>' .
		'<strong>' . $laggprotosuc[3] . '</strong><br />' .
		gettext('Cisco EtherChannel을 지원합니다. ' .
				'이것은 정적 설정이며 링크를 모니터링하기 위해 피어 또는 교환 프레임과 집합을 협상하지 않습니다.') .
	'</li>' .
	'<li>' .
		'<strong>' . $laggprotosuc[4] . '</strong><br />' .
		gettext('해시 된 프로토콜 헤더 정보를 기반으로 활성 포트에서 나가는 트래픽의 균형을 맞추고 활성 포트에서 들어오는 트래픽을 허용합니다. ' .
				'이것은 정적 설정이며 링크를 모니터링하기 위해 피어 또는 교환 프레임과 집합을 협상하지 않습니다. ' .
				'해시에는 이더넷 원본 및 대상 주소와 사용 가능한 경우 VLAN 태그와 IP 원본 및 대상 주소가 포함됩니다.') .
	'</li>' .
	'<li>' .
		'<strong>' . $laggprotosuc[5] . '</strong><br />' .
		gettext('모든 활성 포트를 통해 라운드 로빈 스케줄러를 사용하여 나가는 트래픽을 분배하고 활성 포트에서 들어오는 트래픽을 수용합니다.') .
	'</li>' .
'</ul>';

$realifchecklist = array();
/* add LAGG interfaces */
if (is_array($config['laggs']['lagg']) && count($config['laggs']['lagg'])) {
	foreach ($config['laggs']['lagg'] as $lagg) {
		unset($portlist[$lagg['laggif']]);
		$laggiflist = explode(",", $lagg['members']);
		foreach ($laggiflist as $tmpif) {
			$realifchecklist[get_real_interface($tmpif)] = $tmpif;
		}
	}
}

$checklist = get_configured_interface_list(true);

foreach ($checklist as $tmpif) {
	$realifchecklist[get_real_interface($tmpif)] = $tmpif;
}

$id = $_REQUEST['id'];

if (isset($id) && $a_laggs[$id]) {
	$pconfig['laggif'] = $a_laggs[$id]['laggif'];
	$pconfig['members'] = $a_laggs[$id]['members'];
	$laggiflist = explode(",", $a_laggs[$id]['members']);
	foreach ($laggiflist as $tmpif) {
		unset($realifchecklist[get_real_interface($tmpif)]);
	}
	$pconfig['proto'] = $a_laggs[$id]['proto'];
	$pconfig['descr'] = $a_laggs[$id]['descr'];
}

if ($_POST['save']) {
	unset($input_errors);
	$pconfig = $_POST;

	if (is_array($_POST['members'])) {
		$pconfig['members'] = implode(',', $_POST['members']);
	}

	/* input validation */
	$reqdfields = explode(" ", "members proto");
	$reqdfieldsn = array(gettext("멤버 인터페이스"), gettext("Lagg 프로토콜"));

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

	if (is_array($_POST['members'])) {
		foreach ($_POST['members'] as $member) {
			if (!does_interface_exist($member)) {
				$input_errors[] = sprintf(gettext("멤버로 제공된 인터페이스가 잘못되었습니다(%s)."), $member);
			}
		}
	} else if (!does_interface_exist($_POST['members'])) {
		$input_errors[] = gettext("멤버로 제공된 인터페이스가 잘못되었습니다.");
	}

	if (!in_array($_POST['proto'], $laggprotos)) {
		$input_errors[] = gettext("제공된 프로토콜이 잘못되었습니다.");
	}

	if (!$input_errors) {
		$lagg = array();
		$lagg['members'] = implode(',', $_POST['members']);
		$lagg['descr'] = $_POST['descr'];
		$lagg['laggif'] = $_POST['laggif'];
		$lagg['proto'] = $_POST['proto'];
		if (isset($id) && $a_laggs[$id]) {
			$lagg['laggif'] = $a_laggs[$id]['laggif'];
		}

		$lagg['laggif'] = interface_lagg_configure($lagg);
		if ($lagg['laggif'] == "" || !stristr($lagg['laggif'], "lagg")) {
			$input_errors[] = gettext("인터페이스를 생성하는 동안 오류가 발생했습니다. 다시 시도하십시오.");
		} else {
			if (isset($id) && $a_laggs[$id]) {
				$a_laggs[$id] = $lagg;
			} else {
				$a_laggs[] = $lagg;
			}

			write_config();

			$confif = convert_real_interface_to_friendly_interface_name($lagg['laggif']);
			if ($confif != "") {
				interface_configure($confif);
			}

			// reconfigure any VLANs with this lagg as their parent
			if (is_array($config['vlans']['vlan'])) {
				foreach ($config['vlans']['vlan'] as $vlan) {
					if ($vlan['if'] == $lagg['laggif']) {
						interface_vlan_configure($vlan);
						$confif = convert_real_interface_to_friendly_interface_name($vlan['vlanif']);
						if ($confif != "") {
							interface_configure($confif);
						}
					}
				}
			}

			header("Location: interfaces_lagg.php");
			exit;
		}
	}
}

function build_member_list() {
	global $pconfig, $portlist, $realifchecklist;

	$memberlist = array('list' => array(), 'selected' => array());

	foreach ($portlist as $ifn => $ifinfo) {
		if (array_key_exists($ifn, $realifchecklist)) {
			continue;
		}

		$memberlist['list'][$ifn] = $ifn . ' (' . $ifinfo['mac'] . ')';

		if (in_array($ifn, explode(",", $pconfig['members']))) {
			array_push($memberlist['selected'], $ifn);
		}
	}

	return($memberlist);
}

$pgtitle = array(gettext("Interfaces"), gettext("LAGGs"), gettext("편집"));
$pglinks = array("", "interfaces_lagg.php", "@self");
$shortcut_section = "interfaces";
include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

$form = new Form();

$section = new Form_Section('LAGG Configuration');

$memberslist = build_member_list();

$section->addInput(new Form_Select(
	'members',
	'*Parent Interfaces',
	$memberslist['selected'],
	$memberslist['list'],
	true // Allow multiples
))->setHelp('Choose the members that will be used for the link aggregation.');

$section->addInput(new Form_Select(
	'proto',
	'*LAGG Protocol',
	$pconfig['proto'],
	array_combine($laggprotos, $laggprotosuc)
))->setHelp($protohelp);

$section->addInput(new Form_Input(
	'descr',
	'Description',
	'text',
	$pconfig['descr']
))->setHelp("Enter a description here for reference only (Not parsed).");

$section->addInput(new Form_Input(
	'laggif',
	null,
	'hidden',
	$pconfig['laggif']
));

if (isset($id) && $a_laggs[$id]) {
	$section->addInput(new Form_Input(
		'id',
		null,
		'hidden',
		$id
	));
}

$form->add($section);
print($form);

include("foot.inc");

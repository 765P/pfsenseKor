<?php
/*
 * interfaces_bridge_edit.php
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
2018.03.05
한글화 번역 시작
*/

##|+PRIV
##|*IDENT=page-interfaces-bridge-edit
##|*NAME=Interfaces: Bridge edit
##|*DESCR=Allow access to the 'Interfaces: Bridge : Edit' page.
##|*MATCH=interfaces_bridge_edit.php*
##|-PRIV

require_once("guiconfig.inc");

if (!is_array($config['bridges']['bridged'])) {
	$config['bridges']['bridged'] = array();
}

function is_aoadv_used($pconfig) {
	if (($pconfig['static'] !="") ||
	    ($pconfig['private'] != "") ||
	    $pconfig['ip6linklocal'] ||
	    ($pconfig['stp'] != "") ||
	    ($pconfig['span'] != "") ||
	    ($pconfig['edge'] != "") ||
	    ($pconfig['autoedge'] != "") ||
	    ($pconfig['ptp'] != "") ||
	    ($pconfig['autoptp'] != "") ||
	    ($pconfig['maxaddr'] != "") ||
	    ($pconfig['timeout'] != "") ||
	    ($pconfig['maxage'] != "") ||
	    ($pconfig['fwdelay'] != "") ||
	    ($pconfig['hellotime'] != "") ||
	    ($pconfig['priority'] != "") ||
	    (($pconfig['proto'] != "") && ($pconfig['proto'] != "rstp")) ||
	    ($pconfig['holdcnt'] != "")) {
		return true;
	}

	return false;
}

$a_bridges = &$config['bridges']['bridged'];

$ifacelist = get_configured_interface_with_descr();

foreach ($ifacelist as $bif => $bdescr) {
	if (substr(get_real_interface($bif), 0, 3) == "gre") {
		unset($ifacelist[$bif]);
	}
}

$id = $_REQUEST['id'];

if (isset($id) && $a_bridges[$id]) {
	$pconfig['enablestp'] = isset($a_bridges[$id]['enablestp']);
	$pconfig['ip6linklocal'] = isset($a_bridges[$id]['ip6linklocal']);
	$pconfig['descr'] = $a_bridges[$id]['descr'];
	$pconfig['bridgeif'] = $a_bridges[$id]['bridgeif'];
	$pconfig['members'] = $a_bridges[$id]['members'];
	$pconfig['maxaddr'] = $a_bridges[$id]['maxaddr'];
	$pconfig['timeout'] = $a_bridges[$id]['timeout'];
	$pconfig['maxage'] = $a_bridges[$id]['maxage'];
	$pconfig['fwdelay'] = $a_bridges[$id]['fwdelay'];
	$pconfig['hellotime'] = $a_bridges[$id]['hellotime'];
	$pconfig['priority'] = $a_bridges[$id]['priority'];
	$pconfig['proto'] = $a_bridges[$id]['proto'];
	$pconfig['holdcnt'] = $a_bridges[$id]['holdcnt'];

	if (!empty($a_bridges[$id]['ifpriority'])) {
		$pconfig['ifpriority'] = explode(",", $a_bridges[$id]['ifpriority']);
		$ifpriority = array();
		foreach ($pconfig['ifpriority'] as $cfg) {
			list ($key, $value) = explode(":", $cfg);
			$embprioritycfg[$key] = $value;
			foreach ($embprioritycfg as $key => $value) {
				$ifpriority[$key] = $value;
			}
		}
		$pconfig['ifpriority'] = $ifpriority;
	}

	if (!empty($a_bridges[$id]['ifpathcost'])) {
		$pconfig['ifpathcost'] = explode(",", $a_bridges[$id]['ifpathcost']);
		$ifpathcost = array();
		foreach ($pconfig['ifpathcost'] as $cfg) {
			list ($key, $value) = explode(":", $cfg);
			$embpathcfg[$key] = $value;
			foreach ($embpathcfg as $key => $value) {
				$ifpathcost[$key] = $value;
			}
		}
		$pconfig['ifpathcost'] = $ifpathcost;
	}

	if (isset($a_bridges[$id]['static'])) {
		$pconfig['static'] = $a_bridges[$id]['static'];
	}
	if (isset($a_bridges[$id]['private'])) {
		$pconfig['private'] = $a_bridges[$id]['private'];
	}
	if (isset($a_bridges[$id]['stp'])) {
		$pconfig['stp'] = $a_bridges[$id]['stp'];
	}
	if (isset($a_bridges[$id]['span'])) {
		$pconfig['span'] = $a_bridges[$id]['span'];
	}
	if (isset($a_bridges[$id]['edge'])) {
		$pconfig['edge'] = $a_bridges[$id]['edge'];
	}
	if (isset($a_bridges[$id]['autoedge'])) {
		$pconfig['autoedge'] = $a_bridges[$id]['autoedge'];
	}
	if (isset($a_bridges[$id]['ptp'])) {
		$pconfig['ptp'] = $a_bridges[$id]['ptp'];
	}
	if (isset($a_bridges[$id]['autoptp'])) {
		$pconfig['autoptp'] = $a_bridges[$id]['autoptp'];
	}
}

if ($_POST['save']) {
	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "members");
	$reqdfieldsn = array(gettext("인터페이스 멤버"));

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

	if ($_POST['maxage'] && !is_numericint($_POST['maxage'])) {
		$input_errors[] = gettext("Maxage는 6에서 40사이의 정수로 지정되어야 합니다.");
	}
	if ($_POST['maxaddr'] && !is_numericint($_POST['maxaddr'])) {
		$input_errors[] = gettext("Maxaddr은 정수로 지정되어야 합니다.");
	}
	if ($_POST['timeout'] && !is_numericint($_POST['timeout'])) {
		$input_errors[] = gettext("시간초과는 정수로 지정되어야 합니다.");
	}
	if ($_POST['fwdelay'] && !is_numericint($_POST['fwdelay'])) {
		$input_errors[] = gettext("Forward Delay는 4에서 30 사이의 정수로 지정되어야 합니다.");
	}
	if ($_POST['hellotime'] && !is_numericint($_POST['hellotime'])) {
		$input_errors[] = gettext("Hello time for STP는 1과 2 사이의 정수로 지정되어야 합니다.");
	}
	if ($_POST['priority'] && !is_numericint($_POST['priority'])) {
		$input_errors[] = gettext("STP의 우선 순위는 0에서 61440 사이의 정수로 지정되어야 합니다.");
	}
	if ($_POST['holdcnt'] && !is_numericint($_POST['holdcnt'])) {
		$input_errors[] = gettext("STP의 송신 유지 카운트는 1에서 10 사이의 정수로 지정되어야 합니다.");
	}
	foreach ($ifacelist as $ifn => $ifdescr) {
		if ($_POST[$ifn] <> "" && !is_numericint($_POST[$ifn])) {
			$input_errors[] = sprintf(gettext("STP의 %s 인터페이스 우선 순위는 0에서 240 사이의 정수로 지정되어야 합니다."), $ifdescr);
		}
	}

	$i = 0;

	foreach ($ifacelist as $ifn => $ifdescr) {
		if ($_POST["{$ifn}{$i}"] <> "" && !is_numeric($_POST["{$ifn}{$i}"])) {
			$input_errors[] = sprintf(gettext("STP의 %s 인터페이스 경로 비용은 1에서 200000000 사이의 정수로 지정되어야 합니다."), $ifdescr);
		}
		$i++;
	}

	if (!is_array($_POST['members']) || count($_POST['members']) < 1) {
		$input_errors[] = gettext("브리지에 대해 하나 이상의 멤버 인터페이스를 선택해야합니다.");
	}

	if (is_array($_POST['static'])) {
		foreach ($_POST['static'] as $ifstatic) {
			if (is_array($_POST['members']) && !in_array($ifstatic, $_POST['members'])) {
				$input_errors[] = sprintf(gettext('고정 인터페이스(%s)는 브리지에 속해있지 않습니다. 계속하려면 고정 인터페이스를 제거하십시오.'), $ifacelist[$ifstatic]);
			}
		}
		$pconfig['static'] = implode(',', $_POST['static']);
	}
	if (is_array($_POST['private'])) {
		foreach ($_POST['private'] as $ifprivate) {
			if (is_array($_POST['members']) && !in_array($ifprivate, $_POST['members'])) {
				$input_errors[] = sprintf(gettext('개인 인터페이스(%s)는 브리지에 속해있지 않습니다. 계속하려면 개인 인터페이스를 제거하십시오.'), $ifacelist[$ifprivate]);
			}
		}
		$pconfig['private'] = implode(',', $_POST['private']);
	}
	if (is_array($_POST['stp'])) {
		foreach ($_POST['stp'] as $ifstp) {
			if (is_array($_POST['members']) && !in_array($ifstp, $_POST['members'])) {
				$input_errors[] = sprintf(gettext('STP 인터페이스(%s)는 브리지에 속해있지 않습니다. 계속하려면 STP 인터페이스를 제거하십시오.'), $ifacelist[$ifstp]);
			}
		}
		$pconfig['stp'] = implode(',', $_POST['stp']);
	}
	if (is_array($_POST['span'])) {
		$pconfig['span'] = implode(',', $_POST['span']);
	}
	if (is_array($_POST['edge'])) {
		foreach ($_POST['edge'] as $ifedge) {
			if (is_array($_POST['members']) && !in_array($ifedge, $_POST['members'])) {
				$input_errors[] = sprintf(gettext('Edge 인터페이스(%s)는 브리지에 속해있지 않습니다. 계속하려면 Edge 인터페이스를 제거하십시오.'), $ifacelist[$ifedge]);
			}
		}
		$pconfig['edge'] = implode(',', $_POST['edge']);
	}
	if (is_array($_POST['autoedge'])) {
		foreach ($_POST['autoedge'] as $ifautoedge) {
			if (is_array($_POST['members']) && !in_array($ifautoedge, $_POST['members'])) {
				$input_errors[] = sprintf(gettext('Auto Edge 인터페이스(%s)는 브리지에 속해있지 않습니다. 계속하려면 Auto Edge 인터페이스를 제거하십시오.'), $ifacelist[$ifautoedge]);
			}
		}
		$pconfig['autoedge'] = implode(',', $_POST['autoedge']);
	}
	if (is_array($_POST['ptp'])) {
		foreach ($_POST['ptp'] as $ifptp) {
			if (is_array($_POST['members']) && !in_array($ifptp, $_POST['members'])) {
				$input_errors[] = sprintf(gettext('PTP 인터페이스(%s)는 브리지에 속해있지 않습니다. 계속하려면 PTP 인터페이스를 제거하십시오.'), $ifacelist[$ifptp]);
			}
		}
		$pconfig['ptp'] = implode(',', $_POST['ptp']);
	}
	if (is_array($_POST['autoptp'])) {
		foreach ($_POST['autoptp'] as $ifautoptp) {
			if (is_array($_POST['members']) && !in_array($ifautoptp, $_POST['members'])) {
				$input_errors[] = sprintf(gettext('Auto PTP 인터페이스(%s)는 브리지에 속해있지 않습니다. 계속하려면 Auto PTP 인터페이스를 제거하십시오.'), $ifacelist[$ifautoptp]);
			}
		}
		$pconfig['autoptp'] = implode(',', $_POST['autoptp']);
	}
	if (is_array($_POST['members'])) {
		foreach ($_POST['members'] as $ifmembers) {
			if (empty($config['interfaces'][$ifmembers])) {
				$input_errors[] = gettext("전달된 멤버 인터페이스가 구성에 없습니다.");
			}
			if (substr($config['interfaces'][$ifmembers]['if'], 0, 6) == "bridge") {
				$input_errors[] = gettext("브리지 인터페이스는 브리지의 구성원일 수 없습니다.");
			}
			if (is_array($config['interfaces'][$ifmembers]['wireless']) &&
			    $config['interfaces'][$ifmembers]['wireless']['mode'] != "hostap") {
				$input_errors[] = gettext("무선 인터페이스를 브리지하는 것은 호스트 맵 모드에서만 가능합니다.");
			}
			if (is_array($_POST['span']) && in_array($ifmembers, $_POST['span'])) {
				$input_errors[] = sprintf(gettext('스팬 인터페이스(%s)는 브리지에 속해있지 않습니다. 계속하려면 브리지 멤버에서 스팬 인터페이스를 제거하십시오.'), $ifacelist[$ifmembers]);
			}
			foreach ($a_bridges as $a_bridge) {
				if ($_POST['bridgeif'] === $a_bridge['bridgeif']) {
					continue;
				}
				$a_members = explode(',', $a_bridge['members']);
				foreach ($a_members as $a_member) {
					if ($ifmembers === $a_member) {
						$input_errors[] = sprintf(gettext("%s은(는) 다른 브리지의 일부입니다. 계속하려면 브리지 멤버에서 인터페이스를 제거하십시오."), $ifacelist[$ifmembers]);
					}
				}
			}
		}
		$pconfig['members'] = implode(',', $_POST['members']);
	}

	if (!$input_errors) {
		$bridge = array();
		$bridge['members'] = implode(',', $_POST['members']);
		$bridge['enablestp'] = $_POST['enablestp'] ? true : false;
		$bridge['descr'] = $_POST['descr'];
		$bridge['maxaddr'] = $_POST['maxaddr'];
		$bridge['timeout'] = $_POST['timeout'];
		$bridge['maxage'] = $_POST['maxage'];
		$bridge['fwdelay'] = $_POST['fwdelay'];
		$bridge['hellotime'] = $_POST['hellotime'];
		$bridge['priority'] = $_POST['priority'];
		$bridge['proto'] = $_POST['proto'];
		$bridge['holdcnt'] = $_POST['holdcnt'];
		$i = 0;
		$ifpriority = "";
		$ifpathcost = "";

		if ($_POST['ip6linklocal']) {
			$bridge['ip6linklocal'] = true;
		}

		foreach ($ifacelist as $ifn => $ifdescr) {
			if ($_POST[$ifn] <> "") {
				if ($i > 0) {
					$ifpriority .= ",";
				}
				$ifpriority .= $ifn.":".$_POST[$ifn];
			}
			if ($_POST["{$ifn}0"] <> "") {
				if ($i > 0) {
					$ifpathcost .= ",";
				}
				$ifpathcost .= $ifn.":".$_POST["{$ifn}0"];
			}
			$i++;
		}

		$bridge['ifpriority'] = $ifpriority;
		$bridge['ifpathcost'] = $ifpathcost;

		if (isset($_POST['static'])) {
			$bridge['static'] = implode(',', $_POST['static']);
		}
		if (isset($_POST['private'])) {
			$bridge['private'] = implode(',', $_POST['private']);
		}
		if (isset($_POST['stp'])) {
			$bridge['stp'] = implode(',', $_POST['stp']);
		}
		if (isset($_POST['span'])) {
			$bridge['span'] = implode(',', $_POST['span']);
		}
		if (isset($_POST['edge'])) {
			$bridge['edge'] = implode(',', $_POST['edge']);
		}
		if (isset($_POST['autoedge'])) {
			$bridge['autoedge'] = implode(',', $_POST['autoedge']);
		}
		if (isset($_POST['ptp'])) {
			$bridge['ptp'] = implode(',', $_POST['ptp']);
		}
		if (isset($_POST['autoptp'])) {
			$bridge['autoptp'] = implode(',', $_POST['autoptp']);
		}

		$bridge['bridgeif'] = $_POST['bridgeif'];
		interface_bridge_configure($bridge);
		if ($bridge['bridgeif'] == "" || !stristr($bridge['bridgeif'], "bridge")) {
			$input_errors[] = gettext("인터페이스를 생성하는 동안 오류가 발생했습니다. 다시 시도하십시오.");
		} else {
			if (isset($id) && $a_bridges[$id]) {
				$a_bridges[$id] = $bridge;
			} else {
				$a_bridges[] = $bridge;
			}

			write_config();

			$confif = convert_real_interface_to_friendly_interface_name($bridge['bridgeif']);
			if ($confif <> "") {
				interface_configure($confif);
			}

			header("Location: interfaces_bridge.php");
			exit;
		}
	}
}

// port list with the exception of assigned bridge interfaces to prevent invalid configs
function build_port_list($selecton) {
	global $config, $ifacelist;

	$portlist = array('list' => array(), 'selected' => array());

	foreach ($ifacelist as $ifn => $ifdescr) {
		if (substr($config['interfaces'][$ifn]['if'], 0, 6) != "bridge") {
			$portlist['list'][$ifn] = $ifdescr;

			if (in_array($ifn, explode(',', $selecton))) {
				array_push($portlist['selected'], $ifn);
			}
		}
	}

	return($portlist);
}

$pgtitle = array(gettext("인터페이스"), gettext("Bridges"), gettext("편집"));
$pglinks = array("", "interfaces_bridge.php", "@self");
$shortcut_section = "interfaces";
include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

$form = new Form();

$section = new Form_Section('브리지 구성');

$memberslist = build_port_list($pconfig['members']);

$section->addInput(new Form_Select(
	'members',
	'*Member Interfaces',
	$memberslist['selected'],
	$memberslist['list'],
	true // Allow multiples
))->setHelp('Interfaces participating in the bridge.');

$section->addInput(new Form_Input(
	'descr',
	'Description',
	'text',
	$pconfig['descr']
));

// Advanced Additional options
$btnadv = new Form_Button(
	'btnadvopts',
	'Display Advanced',
	null,
	'fa-cog'
);

$btnadv->setAttribute('type','button')->addClass('btn-info btn-sm');

$section->addInput(new Form_StaticText(
	'Advanced Options',
	$btnadv
));

$form->add($section);

$section = new Form_Section('어드밴스드 구성');

$section->addClass('adnlopts');

$section->addInput(new Form_Input(
	'maxaddr',
	'Cache Size',
	'text',
	$pconfig['maxaddr']
))->setHelp('Set the size of the bridge address cache. The default is 2000 entries.');

$section->addInput(new Form_Input(
	'timeout',
	'Cache expire time',
	'text',
	$pconfig['timeout']
))->setHelp('Set the timeout of address cache entries to this number of seconds. If seconds is zero, then address cache entries will not be expired. The default is 1200 seconds.');

$spanlist = build_port_list($pconfig['span']);

$section->addInput(new Form_Select(
	'span',
	'Span Port',
	$spanlist['selected'],
	$spanlist['list'],
	true
))->setHelp('Add the interface named by interface as a span port on the bridge. Span ports transmit a copy of every frame received by the bridge. ' .
			'This is most useful for snooping a bridged network passively on another host connected to one of the span ports of the bridge. %1$s' .
			'%2$sThe span interface cannot be part of the bridge member interfaces.%3$s', '<br />', '<strong>', '</strong>');

$edgelist = build_port_list($pconfig['edge']);

$section->addInput(new Form_Select(
	'edge',
	'Edge Ports',
	$edgelist['selected'],
	$edgelist['list'],
	true
))->setHelp('Set interface as an edge port. An edge port connects directly to end stations and cannot create bridging loops in the network; this allows it to transition straight to forwarding.');

$edgelist = build_port_list($pconfig['autoedge']);

$section->addInput(new Form_Select(
	'autoedge',
	'Auto Edge Ports',
	$edgelist['selected'],
	$edgelist['list'],
	true
))->setHelp('Allow interface to automatically detect edge status. This is the default for all interfaces added to a bridge.' .
			'%1$sThis will disable the autoedge status of interfaces. %2$s', '<strong>', '</strong>');

$edgelist = build_port_list($pconfig['ptp']);

$section->addInput(new Form_Select(
	'ptp',
	'PTP Ports',
	$edgelist['selected'],
	$edgelist['list'],
	true
))->setHelp('Set the interface as a point-to-point link. This is required for straight transitions to forwarding and should be enabled on a direct link to another RSTP-capable switch.');

$edgelist = build_port_list($pconfig['autoptp']);

$section->addInput(new Form_Select(
	'autoptp',
	'Auto PTP Ports',
	$edgelist['selected'],
	$edgelist['list'],
	true
))->setHelp('Automatically detect the point-to-point status on interface by checking the full duplex link status. This is the default for interfaces added to the bridge.' .
			'%1$sThe interfaces selected here will be removed from default autoedge status. %2$s', '<strong>', '</strong>');

$edgelist = build_port_list($pconfig['static']);

$section->addInput(new Form_Select(
	'static',
	'Sticky Ports',
	$edgelist['selected'],
	$edgelist['list'],
	true
))->setHelp('Mark an interface as a "sticky" interface. Dynamically learned address entries are treated as static once entered into the cache. ' .
			'Sticky entries are never aged out of the cache or replaced, even if the address is seen on a different interface.');

$edgelist = build_port_list($pconfig['private']);

$section->addInput(new Form_Select(
	'private',
	'Private Ports',
	$edgelist['selected'],
	$edgelist['list'],
	true
))->setHelp('Mark an interface as a "private" interface. A private interface does not forward any traffic to any other port that is also a private interface. ');

$section->addInput(new Form_Checkbox(
	'ip6linklocal',
	'Enable IPv6 auto linklocal',
	null,
	$pconfig['ip6linklocal']
))->setHelp('When enabled, the AUTO_LINKLOCAL flag is set on the bridge interface and cleared on every member interface. This is required when the bridge interface is used for stateless autoconfiguration. ');

//	STP section
// ToDo: - Should disable spanning tree section when not checked
$section->addInput(new Form_Checkbox(
	'enablestp',
	'Enable RSTP/STP',
	null,
	$pconfig['enablestp']
));

// Show the spanning tree section
$form->add($section);
$section = new Form_Section('RSTP/STP');
$section->addClass('adnlopts');

$section->addInput(new Form_Select(
	'proto',
	'Protocol',
	$pconfig['proto'],
	array('rstp' => 'RSTP',
		  'stp' => 'STP')
))->setHelp('Protocol used for spanning tree.');

$edgelist = build_port_list($pconfig['stp']);

$section->addInput(new Form_Select(
	'stp',
	'STP Interfaces',
	$edgelist['selected'],
	$edgelist['list'],
	true
))->setHelp('Enable Spanning Tree Protocol on interface. The if_bridge(4) driver has support for the IEEE 802.1D Spanning Tree Protocol (STP). ' .
			'STP is used to detect and remove loops in a network topology.');

$section->addInput(new Form_Input(
	'maxage',
	'Valid time',
	'number',
	$pconfig['maxage'],
	['placeholder' => 20, 'min' => 6, 'max' => 40]
))->setHelp('Set the time that a Spanning Tree Protocol configuration is valid. The default is 20 seconds. The minimum is 6 seconds and the maximum is 40 seconds.');

$section->addInput(new Form_Input(
	'fwdelay',
	'Forward time',
	'number',
	$pconfig['fwdelay'],
	['placeholder' => 15, 'min' => 4, 'max' => 30]
))->setHelp('Set the time that must pass before an interface begins forwarding packets when Spanning Tree is enabled. The default is 15 seconds. The minimum is 4 seconds and the maximum is 30 seconds. ');

$section->addInput(new Form_Input(
	'hellotime',
	'Hello time',
	'number',
	$pconfig['hellotime'],
	['placeholder' => 2, 'min' => 1, 'max' => 2, 'step' => '0.1']
))->setHelp('Set the time in seconds between broadcasting of Spanning Tree Protocol configuration messages. The hello time may only be changed when operating in legacy STP mode. ' .
			'The default is 2 seconds. The minimum is 1 second and the maximum is 2 seconds.');

$section->addInput(new Form_Input(
	'priority',
	'Priority',
	'number',
	$pconfig['priority'],
	['placeholder' => 32768, 'min' => 0, 'max' => 61440]
))->setHelp('Set the bridge priority for Spanning Tree. The default is 32768. The minimum is 0 and the maximum is 61440. ');

$section->addInput(new Form_Input(
	'holdcnt',
	'Hold Count',
	'number',
	$pconfig['holdcnt'],
	['placeholder' => 6, 'min' => 1, 'max' => 10]
))->setHelp('Set the transmit hold count for Spanning Tree. This is the number of packets transmitted before being rate limited. The default is 6. The minimum is 1 and the maximum is 10.');

foreach ($ifacelist as $ifn => $ifdescr) {
	$section->addInput(new Form_Input(
		$ifn,
		$ifdescr . ' Priority',
		'number',
		$pconfig['ifpriority'][$ifn],
		['placeholder' => 128, 'min' => 0, 'max' => 240, 'step' => 16]
	))->setHelp('Set the Spanning Tree priority of interface to value. The default is 128. The minimum is 0 and the maximum is 240. Increments of 16.');
}

$i = 0;
foreach ($ifacelist as $ifn => $ifdescr) {
	$section->addInput(new Form_Input(
		$ifn . 0,
		$ifdescr . ' Path cost',
		'number',
		$pconfig['ifpathcost'][$ifn],
		[ 'placeholder' => 0, 'min' => 1, 'max' => 200000000]
	))->setHelp('Set the Spanning Tree path cost of interface to value. The default is calculated from the link speed. '.
		'To change a previously selected path cost back to automatic, set the cost to 0. The minimum is 1 and the maximum is 200000000.');
	$i++;
}

$section->addInput(new Form_Input(
	'bridgeif',
	null,
	'hidden',
	$pconfig['bridgeif']
));

if (isset($id) && $a_bridges[$id]) {
	$section->addInput(new Form_Input(
		'id',
		null,
		'hidden',
		$id
	));
}

$form->add($section);
print($form);
?>
<script type="text/javascript">
//<![CDATA[
events.push(function() {

	// Show advanced additional opts options ======================================================
	var showadvopts = false;

	function show_advopts(ispageload) {
		var text;
		// On page load decide the initial state based on the data.
		if (ispageload) {
			showadvopts = <?php if (is_aoadv_used($pconfig)) {echo 'true';} else {echo 'false';} ?>;
		} else {
			// It was a click, swap the state.
			showadvopts = !showadvopts;
		}

		hideClass('adnlopts', !showadvopts);

		if (showadvopts) {
			text = "<?=gettext('어드밴스드 숨기기');?>";
		} else {
			text = "<?=gettext('어드밴스드 보이기');?>";
		}
		$('#btnadvopts').html('<i class="fa fa-cog"></i> ' + text);
	}

	$('#btnadvopts').click(function(event) {
		show_advopts();
	});

	// ---------- On initial page load ------------------------------------------------------------

	show_advopts(true);
});
//]]>
</script>

<?php include("foot.inc");

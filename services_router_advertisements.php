<?php
/*
 * services_router_advertisements.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2018 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2010 Seth Mos <seth.mos@dds.nl>
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
2018.03.12
한글화 번역 시작
*/

##|+PRIV
##|*IDENT=page-services-router-advertisements
##|*NAME=Services: Router Advertisements
##|*DESCR=Allow access to the 'Services: Router Advertisements' page.
##|*MATCH=services_router_advertisements.php*
##|-PRIV

require_once("guiconfig.inc");

if (!$g['services_dhcp_server_enable']) {
	header("Location: /");
	exit;
}

$if = $_REQUEST['if'];

/* if OLSRD is enabled, allow WAN to house DHCP. */
if ($config['installedpackages']['olsrd']) {
	foreach ($config['installedpackages']['olsrd']['config'] as $olsrd) {
		if ($olsrd['enable']) {
			$is_olsr_enabled = true;
			break;
		}
	}
}

if (!$_REQUEST['if']) {
	$info_msg = gettext("DHCPv6 서버는 정적이 아닌 고유 한 로컬 IP 주소로 구성된 인터페이스에서만 활성화 할 수 있습니다.") . "<br />" .
	    gettext("고정 IP로 구성된 인터페이스 만 표시됩니다.");
}

$iflist = get_configured_interface_with_descr();

/* set the starting interface */
if (!$if || !isset($iflist[$if])) {
	foreach ($iflist as $ifent => $ifname) {
		$oc = $config['interfaces'][$ifent];
		$valid_if_ipaddrv6 = (bool) ($oc['ipaddrv6'] == 'track6' ||
		    (is_ipaddrv6($oc['ipaddrv6']) &&
		    !is_linklocal($oc['ipaddrv6'])));

		if ((!is_array($config['dhcpdv6'][$ifent]) ||
		    !isset($config['dhcpdv6'][$ifent]['enable'])) &&
		    !$valid_if_ipaddrv6) {
			continue;
		}
		$if = $ifent;
		break;
	}
}

if (is_array($config['dhcpdv6'][$if])) {
	/* RA specific */
	$pconfig['ramode'] = $config['dhcpdv6'][$if]['ramode'];
	$pconfig['rapriority'] = $config['dhcpdv6'][$if]['rapriority'];
	$pconfig['rainterface'] = $config['dhcpdv6'][$if]['rainterface'];
	if ($pconfig['rapriority'] == "") {
		$pconfig['rapriority'] = "medium";
	}

	$pconfig['ravalidlifetime'] = $config['dhcpdv6'][$if]['ravalidlifetime'];
	$pconfig['rapreferredlifetime'] = $config['dhcpdv6'][$if]['rapreferredlifetime'];
	$pconfig['raminrtradvinterval'] = $config['dhcpdv6'][$if]['raminrtradvinterval'];
	$pconfig['ramaxrtradvinterval'] = $config['dhcpdv6'][$if]['ramaxrtradvinterval'];
	$pconfig['raadvdefaultlifetime'] = $config['dhcpdv6'][$if]['raadvdefaultlifetime'];

	$pconfig['radomainsearchlist'] = $config['dhcpdv6'][$if]['radomainsearchlist'];
	list($pconfig['radns1'], $pconfig['radns2'], $pconfig['radns3']) = $config['dhcpdv6'][$if]['radnsserver'];
	$pconfig['rasamednsasdhcp6'] = isset($config['dhcpdv6'][$if]['rasamednsasdhcp6']);

	$pconfig['subnets'] = $config['dhcpdv6'][$if]['subnets']['item'];
}
if (!is_array($pconfig['subnets'])) {
	$pconfig['subnets'] = array();
}

$advertise_modes = array(
	"disabled" => 	gettext("비활성화"),
	"router" => 	gettext("라우터전용 - RA Flags [none], Prefix Flags [router]"),
	"unmanaged" => 	gettext("관리되지 않음 - RA Flags [none], Prefix Flags [onlink, auto, router]"),
	"managed" => 	gettext("관리됨 - RA Flags [managed, other stateful], Prefix Flags [onlink, router]"),
	"assist" => 	gettext("보조 - RA Flags [managed, other stateful], Prefix Flags [onlink, auto, router]"),
	"stateless_dhcp" => gettext("상태없는 DHCP - RA Flags [other stateful], Prefix Flags [onlink, auto, router]"));
$priority_modes = array(
	"low" => 	gettext("낮음"),
	"medium" => gettext("일반"),
	"high" => 	gettext("높음"));

$subnets_help = '<span class="help-block">' .
	gettext("서브넷은 CIDR 형식으로 지정됩니다. 각 항목과 관련된 CIDR 마스크를 선택하십시오.	" .
		"/128은 단일 IPv6 호스트를 지정합니다. /64는 일반적인 IPv6 네트워크를 지정합니다.  " .
		"여기에 서브넷이 지정되지 않으면 Router Advertisement(RA) 데몬은 라우터의 인터페이스가 할당 된 서브넷에 알립니다.") .
	'</span>';

// THe use of <div class="infoblock"> here causes the text to be hidden until the user clicks the "info" icon
$ramode_help = gettext('Router Advertisement(RA) 데몬의 운영 모드를 선택하십시오.') .
	'<div class="infoblock">' .
	'<dl class="dl-horizontal responsive">' .
	'<dt>' . gettext('비활성화') . 		 '</dt><dd>' . gettext('이 인터페이스에서는 RADVD를 사용할 수 없습니다.') . '</dd>' .
	'<dt>' . gettext('라우터전용') . 	 '</dt><dd>' . gettext('이 라우터를 광고합니다.') . '</dd>' .
	'<dt>' . gettext('관리되지 않음') . 	 '</dt><dd>' . gettext('Stateless autoconfig를 사용하여이 라우터를 알립니다.') . '</dd>' .
	'<dt>' . gettext('관리됨') . 		 '</dt><dd>' . gettext('DHCPv6 서버를 통해 모든 구성으로이 라우터를 알립니다.') . '</dd>' .
	'<dt>' . gettext('보조') . 		 '</dt><dd>' . gettext('DHCPv6 서버 and/or 상태 비 저장 자동 구성을 통해이 라우터를 구성으로 알립니다.') . '</dd>' .
	'<dt>' . gettext('상태없는 DHCP') . '</dt><dd>' . gettext('DHCPv6를 통해 사용 가능한 무국적 자동 구성 및 기타 구성 정보로이 라우터를 알립니다.') . '</dd>' .
	'</dl>' .
	gettext('"관리됨", "보조"또는 "상태없는 DHCP"로 설정된 경우 pfSense에서 DHCPv6 서버를 활성화 할 필요가 없으며 네트워크의 다른 호스트가 될 수 있습니다.') .
	'</div>';

if ($_POST['save']) {
	unset($input_errors);

	$pconfig = $_POST;

	/* input validation */

	$pconfig['subnets'] = array();
	for ($x = 0; $x < 5000; $x += 1) {
		$address = trim($_POST['subnet_address' . $x]);
		if ($address === "") {
			continue;
		}

		$bits = trim($_POST['subnet_bits' . $x]);
		if ($bits === "") {
			$bits = "128";
		}

		if (is_alias($address)) {
			$pconfig['subnets'][] = $address;
		} else {
			$pconfig['subnets'][] = $address . "/" . $bits;
			if (!is_ipaddrv6($address)) {
				$input_errors[] = sprintf(gettext('잘못된 서브넷 또는 별칭이 지정되었습니다. [%1$s/%2$s]'), $address, $bits);
			}
		}
	}

	if (($_POST['radns1'] && !is_ipaddrv6($_POST['radns1'])) || ($_POST['radns2'] && !is_ipaddrv6($_POST['radns2'])) || ($_POST['radns3'] && !is_ipaddrv6($_POST['radns3']))) {
		$input_errors[] = gettext("각 DNS서버에 올바른 IPv6주소를 지정해야 합니다.");
	}
	if ($_POST['radomainsearchlist']) {
		$domain_array=preg_split("/[ ;]+/", $_POST['radomainsearchlist']);
		foreach ($domain_array as $curdomain) {
			if (!is_domain($curdomain)) {
				$input_errors[] = gettext("올바른 도메인 검색 목록을 지정해야 합니다.");
				break;
			}
		}
	}

	if ($_POST['ravalidlifetime'] && ($_POST['ravalidlifetime'] < 7200)) {
		$input_errors[] = gettext("2 시간 미만의 유효 기간은 클라이언트에 의해 무시됩니다.(RFC 4862 Section 5.5.3 point e)");
	}
	if ($_POST['ravalidlifetime'] && !is_numericint($_POST['ravalidlifetime'])) {
		$input_errors[] = gettext("유효한 유효 기간은 정수 여야합니다.");
	}
	if ($_POST['raminrtradvinterval']) {
		if (!is_numericint($_POST['raminrtradvinterval'])) {
			$input_errors[] = gettext("최소 광고 간격은 정수 여야합니다.");
		}
		if ($_POST['raminrtradvinterval'] < "3") {
			$input_errors[] = gettext("최소 광고 간격은 3보다 작아야합니다.");
		}
		if ($_POST['ramaxrtradvinterval'] && $_POST['raminrtradvinterval'] > (0.75 * $_POST['ramaxrtradvinterval'])) {
			$input_errors[] = gettext("최소 광고 간격은 [0.75 * 최대] 광고 간격보다 길지 않아야합니다.");
		}
	}
	if ($_POST['ramaxrtradvinterval']) {
		if (!is_numericint($_POST['ramaxrtradvinterval'])) {
			$input_errors[] = gettext("최대 보급 간격은 정수 여야합니다.");
		}
		if ($_POST['ramaxrtradvinterval'] < "4" || $_POST['ramaxrtradvinterval'] > "1800") {
			$input_errors[] = gettext("최대 광고 간격은 4 이상 1800 이하 여야합니다.");
		}
	}
	if ($_POST['raadvdefaultlifetime'] && !is_numericint($_POST['raadvdefaultlifetime'])) {
		$input_errors[] = gettext("라우터 수명은 0에서 9000 사이의 정수 여야합니다.");
	}

	if (!$input_errors) {
		if (!is_array($config['dhcpdv6'][$if])) {
			$config['dhcpdv6'][$if] = array();
		}

		$config['dhcpdv6'][$if]['ramode'] = $_POST['ramode'];
		$config['dhcpdv6'][$if]['rapriority'] = $_POST['rapriority'];
		$config['dhcpdv6'][$if]['rainterface'] = $_POST['rainterface'];

		$config['dhcpdv6'][$if]['ravalidlifetime'] = $_POST['ravalidlifetime'];
		$config['dhcpdv6'][$if]['rapreferredlifetime'] = $_POST['rapreferredlifetime'];
		$config['dhcpdv6'][$if]['raminrtradvinterval'] = $_POST['raminrtradvinterval'];
		$config['dhcpdv6'][$if]['ramaxrtradvinterval'] = $_POST['ramaxrtradvinterval'];
		$config['dhcpdv6'][$if]['raadvdefaultlifetime'] = $_POST['raadvdefaultlifetime'];

		$config['dhcpdv6'][$if]['radomainsearchlist'] = $_POST['radomainsearchlist'];
		unset($config['dhcpdv6'][$if]['radnsserver']);
		if ($_POST['radns1']) {
			$config['dhcpdv6'][$if]['radnsserver'][] = $_POST['radns1'];
		}
		if ($_POST['radns2']) {
			$config['dhcpdv6'][$if]['radnsserver'][] = $_POST['radns2'];
		}
		if ($_POST['radns3']) {
			$config['dhcpdv6'][$if]['radnsserver'][] = $_POST['radns3'];
		}

		$config['dhcpdv6'][$if]['rasamednsasdhcp6'] = ($_POST['rasamednsasdhcp6']) ? true : false;

		if (count($pconfig['subnets'])) {
			$config['dhcpdv6'][$if]['subnets']['item'] = $pconfig['subnets'];
		} else {
			unset($config['dhcpdv6'][$if]['subnets']);
		}

		write_config();
		$changes_applied = true;
		$retval = 0;
		$retval |= services_radvd_configure();
	}
}

$pgtitle = array(gettext("Services"), htmlspecialchars(gettext("DHCPv6 서버 & RA")));
$pglinks = array("", "services_dhcpv6.php");

if (!empty($if) && isset($iflist[$if])) {
	$pgtitle[] = $iflist[$if];
	$pglinks[] = "services_dhcpv6.php?if=" . $if;
}
$pgtitle[] = gettext("라우터 광고");
$pglinks[] = "@self";

include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

if ($changes_applied) {
	print_apply_result_box($retval);
}

if ($info_msg) {
	print_info_box($info_msg, 'success');
}

/* active tabs */
$tab_array = array();
$tabscounter = 0;
$i = 0;
foreach ($iflist as $ifent => $ifname) {
	$oc = $config['interfaces'][$ifent];
	// We need interfaces configured with a static IPv6 address or track6 for PD.
	if (!is_ipaddrv6($oc['ipaddrv6']) && $oc['ipaddrv6'] != "track6") {
		continue;
	}

	if ($ifent == $if) {
		$active = true;
	} else {
		$active = false;
	}

	$tab_array[] = array($ifname, $active, "services_router_advertisements.php?if={$ifent}");
	$tabscounter++;
}

if ($tabscounter == 0) {
	include("foot.inc");
	exit;
}

display_top_tabs($tab_array);

$tab_array = array();
$tab_array[] = array(gettext("DHCPv6 "),		 false, "services_dhcpv6.php?if={$if}");
$tab_array[] = array(gettext("라우터 광고"), true,  "services_router_advertisements.php?if={$if}");
display_top_tabs($tab_array, false, 'nav nav-tabs');

$form = new Form();

$section = new Form_Section('Advertisements');

$section->addInput(new Form_Select(
	'ramode',
	'*Router mode',
	$pconfig['ramode'],
	$advertise_modes
))->setHelp($ramode_help);

$section->addInput(new Form_Select(
	'rapriority',
	'*Router priority',
	$pconfig['rapriority'],
	$priority_modes
))->setHelp('Select the Priority for the Router Advertisement (RA) Daemon.');

$carplist = get_configured_vip_list("inet6", VIP_CARP);

$carplistif = array();

if (count($carplist) > 0) {
	foreach ($carplist as $ifname => $vip) {
		if (get_configured_vip_interface($ifname) == $if) {
			$carplistif[$ifname] = $vip;
		}
	}
}

if (count($carplistif) > 0) {
	$iflist = array();

	$iflist['interface'] = convert_friendly_interface_to_friendly_descr($if);
	foreach ($carplistif as $ifname => $vip) {
		$iflist[$ifname] = get_vip_descr($vip) . " - " . $vip;
	}

	$section->addInput(new Form_Select(
		'rainterface',
		'RA Interface',
		$pconfig['rainterface'],
		$iflist
	))->setHelp('Select the Interface for the Router Advertisement (RA) Daemon.');
}

$section->addInput(new Form_Input(
	'ravalidlifetime',
	'Default valid lifetime',
	'number',
	$pconfig['ravalidlifetime'],
	['min' => 1, 'max' => 655350]
))->setHelp('The length of time in seconds (relative to the time the packet is sent) that the prefix is valid for the purpose of on-link determination.%1$s' .
'The default is 86400 seconds.', '<br />');

$section->addInput(new Form_Input(
	'rapreferredlifetime',
	'Default preferred lifetime',
	'text',
	$pconfig['rapreferredlifetime']
))->setHelp('Seconds. The length of time in seconds (relative to the time the packet is sent) that addresses generated from the prefix via stateless address autoconfiguration remain preferred.%1$s' .
			'The default is 14400 seconds.', '<br />');

$section->addInput(new Form_Input(
	'raminrtradvinterval',
	'Minimum RA interval',
	'number',
	$pconfig['raminrtradvinterval'],
	['min' => 3, 'max' => 1350]
))->setHelp('The minimum time allowed between sending unsolicited multicast router advertisements in seconds.');

$section->addInput(new Form_Input(
	'ramaxrtradvinterval',
	'Maximum RA interval',
	'number',
	$pconfig['ramaxrtradvinterval'],
	['min' => 4, 'max' => 1800]
))->setHelp('The maximum time allowed between sending unsolicited multicast router advertisements in seconds.');

$section->addInput(new Form_Input(
	'raadvdefaultlifetime',
	'Router lifetime',
	'number',
	$pconfig['raadvdefaultlifetime'],
	['min' => 0, 'max' => 9000]
))->setHelp('The lifetime associated with the default router in seconds.');

$section->addInput(new Form_StaticText(
	'RA Subnets',
	$subnets_help
));

if (empty($pconfig['subnets'])) {
	$pconfig['subnets'] = array('0' => '/128');
}

$counter = 0;
$numrows = count($pconfig['subnets']) - 1;

foreach ($pconfig['subnets'] as $subnet) {
	$address_name = "subnet_address" . $counter;
	$bits_name = "subnet_bits" . $counter;
	list($address, $subnet) = explode("/", $subnet);

	$group = new Form_Group($counter == 0 ? 'Subnets':'');

	$group->add(new Form_IpAddress(
		$address_name,
		null,
		$address,
		'V6'
	))->addMask($bits_name, $subnet);

	$group->add(new Form_Button(
		'deleterow' . $counter,
		'Delete',
		null,
		'fa-trash'
	))->removeClass('btn-primary')->addClass('btn-warning');

	$group->addClass('repeatable');

	$section->add($group);

	$counter++;
}

$section->addInput(new Form_Button(
	'addrow',
	'Add',
	null,
	'fa-plus'
))->addClass('btn-success');

$form->add($section);

$section = new Form_Section('DNS Configuration');

for ($idx=1; $idx<=3; $idx++) {
	$section->addInput(new Form_IpAddress(
		'radns' . $idx,
		'Server ' . $idx,
		$pconfig['radns' . $idx],
		'ALIASV6'
	))->setHelp(($idx < 3) ? '':'Leave blank to use the system default DNS servers - this interface\'s IP if DNS Forwarder or Resolver is enabled, otherwise the servers configured on the General page');
}

$section->addInput(new Form_Input(
	'radomainsearchlist',
	'Domain search list',
	'text',
	$pconfig['radomainsearchlist']
))->setHelp('The RA server can optionally provide a domain search list. Use the semicolon character as separator.');

$section->addInput(new Form_Checkbox(
	'rasamednsasdhcp6',
	'Settings',
	'Use same settings as DHCPv6 server',
	$pconfig['rasamednsasdhcp6']
));

$section->addInput(new Form_Input(
	'if',
	null,
	'hidden',
	$if
));


$form->add($section);
print($form);
?>

<script type="text/javascript">
//<![CDATA[
events.push(function() {
	// Suppress "Delete row" button if there are fewer than two rows
	checkLastRow();

	// --------- Autocomplete -----------------------------------------------------------------------------------------
	var addressarray = <?= json_encode(get_alias_list(array("host", "network", "openvpn", "urltable"))) ?>;

	$('#radns1, #radns2, #radns3').autocomplete({
		source: addressarray
	});

});
//]]>
</script>

<?php include("foot.inc");

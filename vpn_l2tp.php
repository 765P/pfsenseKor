<?php
/*
 * vpn_l2tp.php
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
한글화 번역 시작
*/

##|+PRIV
##|*IDENT=page-vpn-vpnl2tp
##|*NAME=VPN: L2TP
##|*DESCR=Allow access to the 'VPN: L2TP' page.
##|*MATCH=vpn_l2tp.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("vpn.inc");

if (!is_array($config['l2tp']['radius'])) {
	$config['l2tp']['radius'] = array();
}
$l2tpcfg = &$config['l2tp'];

$pconfig['remoteip'] = $l2tpcfg['remoteip'];
$pconfig['localip'] = $l2tpcfg['localip'];
$pconfig['l2tp_subnet'] = $l2tpcfg['l2tp_subnet'];
$pconfig['mode'] = $l2tpcfg['mode'];
$pconfig['interface'] = $l2tpcfg['interface'];
$pconfig['l2tp_dns1'] = $l2tpcfg['dns1'];
$pconfig['l2tp_dns2'] = $l2tpcfg['dns2'];
$pconfig['radiusenable'] = isset($l2tpcfg['radius']['enable']);
$pconfig['radacct_enable'] = isset($l2tpcfg['radius']['accounting']);
$pconfig['radiusserver'] = $l2tpcfg['radius']['server'];
$pconfig['radiussecret'] = $l2tpcfg['radius']['secret'];
$pconfig['radiusissueips'] = isset($l2tpcfg['radius']['radiusissueips']);
$pconfig['n_l2tp_units'] = $l2tpcfg['n_l2tp_units'];
$pconfig['paporchap'] = $l2tpcfg['paporchap'];
$pconfig['secret'] = $l2tpcfg['secret'];

if ($_POST['save']) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	if ($_POST['mode'] == "server") {
		$reqdfields = explode(" ", "localip remoteip");
		$reqdfieldsn = array(gettext("서버 주소"), gettext("원격 시작 주소"));

		if ($_POST['radiusenable']) {
			$reqdfields = array_merge($reqdfields, explode(" ", "radiusserver radiussecret"));
			$reqdfieldsn = array_merge($reqdfieldsn,
				array(gettext("RADIUS 서버 주소"), gettext("RADIUS 비밀 공유")));
		}

		do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

		if (($_POST['localip'] && !is_ipaddr($_POST['localip']))) {
			$input_errors[] = gettext("유효한 서버 주소를 지정해야합니다.");
		}
		if (is_ipaddr_configured($_POST['localip'])) {
			$input_errors[] = gettext("'서버 주소'매개 변수는이 방화벽에서 현재 사용중인 모든 IP 주소로 설정하면 안됩니다.");
		}
		if (($_POST['l2tp_subnet'] && !is_ipaddr($_POST['remoteip']))) {
			$input_errors[] = gettext("유효한 원격 시작 주소를 지정해야합니다.");
		}
		if (($_POST['radiusserver'] && !is_ipaddr($_POST['radiusserver']))) {
			$input_errors[] = gettext("유효한 RADIUS 서버 주소를 지정해야합니다.");
		}

		if ($_POST['secret'] != $_POST['secret_confirm']) {
			$input_errors[] = gettext("비밀과 확인이 일치해야합니다.");
		}

		if ($_POST['radiussecret'] != $_POST['radiussecret_confirm']) {
			$input_errors[] = gettext("RADIUS 비밀 및 확인은 일치해야합니다.");
		}

		if (!is_numericint($_POST['n_l2tp_units']) || $_POST['n_l2tp_units'] > 255) {
			$input_errors[] = gettext("L2TP 사용자 수는 1에서 255 사이 여야합니다.");
		}

		if (!$input_errors) {
			$_POST['remoteip'] = $pconfig['remoteip'] = gen_subnet($_POST['remoteip'], $_POST['l2tp_subnet']);
			if (is_inrange_v4($_POST['localip'], $_POST['remoteip'], ip_after($_POST['remoteip'], $_POST['n_l2tp_units'] - 1))) {
				$input_errors[] = gettext("지정된 서버 주소가 원격 서브넷에 있습니다.");
			}
			if ($_POST['localip'] == get_interface_ip("lan")) {
				$input_errors[] = gettext("지정된 서버 주소는 LAN 인터페이스 주소와 동일합니다.");
			}
		}

		if (!empty($_POST['l2tp_dns1']) && !is_ipaddrv4(trim($_POST['l2tp_dns1']))) {
			$input_errors[] = gettext("'기본 L2TP DNS 서버'필드에는 유효한 IPv4 주소가 있어야합니다.");
		}
		if (!empty($_POST['l2tp_dns2']) && !is_ipaddrv4(trim($_POST['l2tp_dns2']))) {
			$input_errors[] = gettext("'보조 L2TP DNS 서버'필드에는 유효한 IPv4 주소가 있어야합니다.");
		}
		if (!empty($_POST['l2tp_dns2']) && empty($_POST['l2tp_dns1'])) {
			$input_errors[] = gettext("기본 L2TP DNS 서버가 비어있는 경우 보조 L2TP DNS 서버를 설정할 수 없습니다.");
		}

	}

	if (!$input_errors) {
		$l2tpcfg['remoteip'] = $_POST['remoteip'];
		$l2tpcfg['localip'] = $_POST['localip'];
		$l2tpcfg['l2tp_subnet'] = $_POST['l2tp_subnet'];
		$l2tpcfg['mode'] = $_POST['mode'];
		$l2tpcfg['interface'] = $_POST['interface'];
		$l2tpcfg['n_l2tp_units'] = $_POST['n_l2tp_units'];
		$l2tpcfg['radius']['server'] = $_POST['radiusserver'];
		if ($_POST['radiussecret'] != DMYPWD) {
			$l2tpcfg['radius']['secret'] = $_POST['radiussecret'];
		}

		if ($_POST['secret'] != DMYPWD) {
			$l2tpcfg['secret'] = $_POST['secret'];
		}

		$l2tpcfg['paporchap'] = $_POST['paporchap'];


		if ($_POST['l2tp_dns1'] == "") {
			if (isset($l2tpcfg['dns1'])) {
				unset($l2tpcfg['dns1']);
			}
		} else {
			$l2tpcfg['dns1'] = $_POST['l2tp_dns1'];
		}

		if ($_POST['l2tp_dns2'] == "") {
			if (isset($l2tpcfg['dns2'])) {
				unset($l2tpcfg['dns2']);
			}
		} else {
			$l2tpcfg['dns2'] = $_POST['l2tp_dns2'];
		}

		if ($_POST['radiusenable'] == "yes") {
			$l2tpcfg['radius']['enable'] = true;
		} else {
			unset($l2tpcfg['radius']['enable']);
		}

		if ($_POST['radacct_enable'] == "yes") {
			$l2tpcfg['radius']['accounting'] = true;
		} else {
			unset($l2tpcfg['radius']['accounting']);
		}

		if ($_POST['radiusissueips'] == "yes") {
			$l2tpcfg['radius']['radiusissueips'] = true;
		} else {
			unset($l2tpcfg['radius']['radiusissueips']);
		}

		write_config(gettext("L2TP VPN 구성이 변경되었습니다."));

		$changes_applied = true;
		$retval = 0;
		$retval |= vpn_l2tp_configure();
	}
}

$pgtitle = array(gettext("VPN"), gettext("L2TP"), gettext("구성"));
$pglinks = array("", "@self", "@self");
$shortcut_section = "l2tps";
include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

if ($changes_applied) {
	print_apply_result_box($retval);
}

$tab_array = array();
$tab_array[] = array(gettext("구성"), true, "vpn_l2tp.php");
$tab_array[] = array(gettext("사용자"), false, "vpn_l2tp_users.php");
display_top_tabs($tab_array);

$form = new Form();

$section = new Form_Section("L2TP 연결");

$section->addInput(new Form_Checkbox(
	'mode',
	'Enable',
	'Enable L2TP server',
	($pconfig['mode'] == "server"),
	'server'
));

$form->add($section);

$iflist = array();
$interfaces = get_configured_interface_with_descr();
foreach ($interfaces as $iface => $ifacename) {
	$iflist[$iface] = $ifacename;
}

$section = new Form_Section("Configuration");
$section->addClass('toggle-l2tp-enable');

$section->addInput(new Form_Select(
	'interface',
	'*Interface',
	$pconfig['interface'],
	$iflist
));

$section->addInput(new Form_Input(
	'localip',
	'*Server address',
	'text',
	$pconfig['localip']
))->setHelp('Enter the IP address the L2TP server should give to clients for use as their "gateway". %1$s' .
			'Typically this is set to an unused IP just outside of the client range.%1$s%1$s' .
			'NOTE: This should NOT be set to any IP address currently in use on this firewall.', '<br />');

$section->addInput(new Form_IpAddress(
	'remoteip',
	'*Remote address range',
	$pconfig['remoteip']
))->addMask(l2tp_subnet, $pconfig['l2tp_subnet'])
  ->setHelp('Specify the starting address for the client IP address subnet.');

$section->addInput(new Form_Select(
	'n_l2tp_units',
	'*Number of L2TP users',
	$pconfig['n_l2tp_units'],
	array_combine(range(1, 255, 1), range(1, 255, 1))
));

$section->addPassword(new Form_Input(
	'secret',
	'Secret',
	'password',
	$pconfig['secret']
))->setHelp('Specify optional secret shared between peers. Required on some devices/setups.');

$section->addInput(new Form_Select(
	'paporchap',
	'*Authentication type',
	$pconfig['paporchap'],
	array(
		'chap' => 'CHAP',
		'chap-msv2' => 'MS-CHAPv2',
		'pap' => 'PAP'
		)
))->setHelp('Specifies the protocol to use for authentication.');

$section->addInput(new Form_Input(
	'l2tp_dns1',
	'Primary L2TP DNS server',
	'text',
	$pconfig['l2tp_dns1']
));

$section->addInput(new Form_Input(
	'l2tp_dns2',
	'Secondary L2TP DNS server',
	'text',
	$pconfig['l2tp_dns2']
));

$form->add($section);

$section = new Form_Section("RADIUS");
$section->addClass('toggle-l2tp-enable');

$section->addInput(new Form_Checkbox(
	'radiusenable',
	'Enable',
	'Use a RADIUS server for authentication',
	$pconfig['radiusenable']
))->setHelp('When set, all users will be authenticated using the RADIUS server specified below. The local user database will not be used.');

$section->addInput(new Form_Checkbox(
	'radacct_enable',
	'Accounting',
	'Enable RADIUS accounting',
	$pconfig['radacct_enable']
))->setHelp('Sends accounting packets to the RADIUS server.');

$section->addInput(new Form_IpAddress(
	'radiusserver',
	'*Server',
	$pconfig['radiusserver']
))->setHelp('Enter the IP address of the RADIUS server.');

$section->addPassword(new Form_Input(
	'radiussecret',
	'*Secret',
	'password',
	$pconfig['radiussecret']
))->setHelp('Enter the shared secret that will be used to authenticate to the RADIUS server.');

$section->addInput(new Form_Checkbox(
	'radiusissueips',
	'RADIUS issued IPs',
	'Issue IP Addresses via RADIUS server.',
	$pconfig['radiusissueips']
));

$form->add($section);

print($form);
?>
<div class="infoblock blockopen">
<?php
	print_info_box(gettext("L2TP 클라이언트의 트래픽을 허용하는 방화벽 규칙을 추가하는 것을 잊지 마십시오."), 'info', false);
?>
</div>

<script type="text/javascript">
//<![CDATA[
events.push(function() {

	function setL2TP () {
		hide = ! $('#mode').prop('checked');

		hideClass('toggle-l2tp-enable', hide);
	}

	function setRADIUS () {
		hide = ! $('#radiusenable').prop('checked');

		hideCheckbox('radacct_enable', hide);
		hideInput('radiusserver', hide);
		hideInput('radiussecret', hide);
		hideCheckbox('radiusissueips', hide);
	}

	// on-click
	$('#mode').click(function () {
		setL2TP();
	});

	$('#radiusenable').click(function () {
		setRADIUS();
	});

	// on-page-load
	setRADIUS();
	setL2TP();

});
//]]>
</script>

<?php include("foot.inc")?>

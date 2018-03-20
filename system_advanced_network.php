<?php
/*
 * system_advanced_network.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2018 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2008 Shrew Soft Inc
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
2018.03.07
한글화 번역 시작
*/

##|+PRIV
##|*IDENT=page-system-advanced-network
##|*NAME=System: Advanced: Networking
##|*DESCR=Allow access to the 'System: Advanced: Networking' page.
##|*MATCH=system_advanced_network.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("functions.inc");
require_once("filter.inc");
require_once("shaper.inc");


$pconfig['ipv6nat_enable'] = isset($config['diag']['ipv6nat']['enable']);
$pconfig['ipv6nat_ipaddr'] = $config['diag']['ipv6nat']['ipaddr'];
$pconfig['ipv6allow'] = isset($config['system']['ipv6allow']);
$pconfig['ipv6dontcreatelocaldns'] = isset($config['system']['ipv6dontcreatelocaldns']);
$pconfig['global-v6duid'] = $config['system']['global-v6duid'];
$pconfig['prefer_ipv4'] = isset($config['system']['prefer_ipv4']);
$pconfig['sharednet'] = $config['system']['sharednet'];
$pconfig['disablechecksumoffloading'] = isset($config['system']['disablechecksumoffloading']);
$pconfig['disablesegmentationoffloading'] = isset($config['system']['disablesegmentationoffloading']);
$pconfig['disablelargereceiveoffloading'] = isset($config['system']['disablelargereceiveoffloading']);
$pconfig['ip_change_kill_states'] = $config['system']['ip_change_kill_states'];

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	if ($_POST['ipv6nat_enable'] && !is_ipaddr($_POST['ipv6nat_ipaddr'])) {
		$input_errors[] = gettext("NATIPv6패킷에 대한 IP주소를 지정해야 합니다.");
	}

	switch ($_POST['ipv6duidtype']) {
	case 1:
		if (!empty($_POST['ipv6duidllt_time']) && !empty($_POST['ipv6duidllt_ll'])) {
			$_POST['global-v6duid'] = format_duid(1, $_POST['ipv6duidllt_time'], $_POST['ipv6duidllt_ll']);
		}
		break;
	case 2:
		if (!empty($_POST['ipv6duiden_en']) && !empty($_POST['ipv6duiden_id'])) {
			$_POST['global-v6duid'] = format_duid(2, $_POST['ipv6duiden_en'], $_POST['ipv6duiden_id']);
		}
		break;
	case 3:
		if (!empty($_POST['ipv6duidll'])) {
			$_POST['global-v6duid'] = format_duid(3, $_POST['ipv6duidll']);
		}
		break;
	case 4:
		if (!empty($_POST['ipv6duiduuid'])) {
			$_POST['global-v6duid'] = format_duid(4, $_POST['ipv6duiduuid']);
		}
		break;
	}

	if (!empty($_POST['global-v6duid'])) {
		$_POST['global-v6duid'] = format_duid(0, $_POST['global-v6duid']);
		if (is_duid($_POST['global-v6duid'])) {
			$pconfig['global-v6duid'] = $_POST['global-v6duid'];
		} else {
			$input_errors[] = gettext("A valid DUID must be specified.");
		}
	}

	ob_flush();
	flush();
	if (!$input_errors) {

		if ($_POST['ipv6nat_enable'] == "yes") {
			$config['diag']['ipv6nat']['enable'] = true;
			$config['diag']['ipv6nat']['ipaddr'] = $_POST['ipv6nat_ipaddr'];
		} else {
			if ($config['diag']) {
				if ($config['diag']['ipv6nat']) {
					unset($config['diag']['ipv6nat']['enable']);
					unset($config['diag']['ipv6nat']['ipaddr']);
				}
			}
		}

		if ($_POST['ipv6allow'] == "yes") {
			$config['system']['ipv6allow'] = true;
		} else {
			unset($config['system']['ipv6allow']);
		}

		if ($_POST['ipv6dontcreatelocaldns'] == "yes") {
			$config['system']['ipv6dontcreatelocaldns'] = true;
		} else {
			unset($config['system']['ipv6dontcreatelocaldns']);
		}

		if ($_POST['prefer_ipv4'] == "yes") {
			$config['system']['prefer_ipv4'] = true;
		} else {
			unset($config['system']['prefer_ipv4']);
		}

		if (!empty($_POST['global-v6duid'])) {
			$config['system']['global-v6duid'] = $_POST['global-v6duid'];
		} else {
			unset($config['system']['global-v6duid']);
		}

		if ($_POST['sharednet'] == "yes") {
			$config['system']['sharednet'] = true;
			system_disable_arp_wrong_if();
		} else {
			unset($config['system']['sharednet']);
			system_enable_arp_wrong_if();
		}

		if ($_POST['disablechecksumoffloading'] == "yes") {
			$config['system']['disablechecksumoffloading'] = true;
		} else {
			unset($config['system']['disablechecksumoffloading']);
		}

		if ($_POST['disablesegmentationoffloading'] == "yes") {
			$config['system']['disablesegmentationoffloading'] = true;
		} else {
			unset($config['system']['disablesegmentationoffloading']);
		}

		if ($_POST['disablelargereceiveoffloading'] == "yes") {
			$config['system']['disablelargereceiveoffloading'] = true;
		} else {
			unset($config['system']['disablelargereceiveoffloading']);
		}

		if ($_POST['ip_change_kill_states'] == "yes") {
			$config['system']['ip_change_kill_states'] = true;
		} else {
			unset($config['system']['ip_change_kill_states']);
		}

		setup_microcode();

		// Write out configuration (config.xml)
		write_config();

		// Set preferred protocol
		prefer_ipv4_or_ipv6();

		$changes_applied = true;
		$retval = 0;
		$retval |= filter_configure();
	}
}

$pgtitle = array(gettext("System"), gettext("Advanced"), gettext("Networking"));
$pglinks = array("", "system_advanced_admin.php", "@self");
include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

if ($changes_applied) {
	print_apply_result_box($retval);
}

$tab_array = array();
$tab_array[] = array(gettext("어드민 엑세스"), false, "system_advanced_admin.php");
$tab_array[] = array(htmlspecialchars(gettext(" & NAT")), false, "system_advanced_firewall.php");
$tab_array[] = array(gettext("네트워킹"), true, "system_advanced_network.php");
$tab_array[] = array(gettext("Miscellaneous"), false, "system_advanced_misc.php");
$tab_array[] = array(gettext("시스템 "), false, "system_advanced_sysctl.php");
$tab_array[] = array(gettext("Notifications"), false, "system_advanced_notifications.php");
$duid = get_duid_from_file();
display_top_tabs($tab_array);

$form = new Form;
$section = new Form_Section('IPv6 Options');

$section->addInput(new Form_Checkbox(
	'ipv6allow',
	'Allow IPv6',
	'All IPv6 traffic will be blocked by the firewall unless this box is checked',
	$pconfig['ipv6allow']
))->setHelp('NOTE: 이렇게하면 방화벽에서 IPv6 기능이 비활성화되지 않고 트래픽만 차단됩니다.');

$section->addInput(new Form_Checkbox(
	'ipv6nat_enable',
	'IPv6 over IPv4 Tunneling',
	'Enable IPv6 over IPv4 tunneling',
	$pconfig['ipv6nat_enable']
))->setHelp('이러한 옵션은 IPv4 패킷 라우팅을 통해 IPv6 패킷을 터널링하는 데 사용할 수있는 IPv6 패킷의 IPv4 NAT 캡슐화를위한 RFC ' .
	'2893 호환 메커니즘을 만듭니다. 캡슐화 된 트래픽을 제어하고 전달하기 위해 IPv6 %1$s방화벽 규칙도%2$s 필요합니다.', '<a href="firewall_rules.php">', '</a>');

$section->addInput(new Form_Input(
	'ipv6nat_ipaddr',
	'IPv4 address of Tunnel Peer',
	'text',
	$pconfig['ipv6nat_ipaddr']
));

$section->addInput(new Form_Checkbox(
	'prefer_ipv4',
	'Prefer IPv4 over IPv6',
	'Prefer to use IPv4 even if IPv6 is available',
	$pconfig['prefer_ipv4']
))->setHelp('기본적으로 IPv6이 구성되고 호스트 이름이 IPv6 및 IPv4 주소를 확인하면 IPv6이 사용됩니다. 이 옵션을 선택하면 IPv6보다 '.
	'IPv4가 우선 적용됩니다.');

$section->addInput(new Form_Checkbox(
	'ipv6dontcreatelocaldns',
	'IPv6 DNS entry',
	'Do not generate local IPv6 DNS entries for LAN interfaces',
	$pconfig['ipv6dontcreatelocaldns']
))->setHelp('LAN 인터페이스의 IPv6 구성이 Track으로 설정되고 추적 된 인터페이스의 연결이 끊어지면 hostname을 통해 설정된이 방화벽에 '.
	'대한 연결이 실패 할 수 있습니다. 기본적으로 IPv4 및 IPv6 항목이 모두 시스템의 DNS에 추가되므로 호스트 이름별로 방화벽에 '.
	'액세스 할 때 실수로 이러한 일이 발생할 수 있습니다. 이 옵션을 사용하면 해당 IPv6 레코드가 생성되지 않습니다.');

$section->addInput(new Form_Select(
	'ipv6duidtype',
	'DHCP6 DUID',
	'$ipv6duidtype',
	array('0' => gettext('DUID-RAW: DUID 파일에 저장되거나 방화벽 로그에 표시됨'),
		'1' => gettext('DUID-Rat: 링크 계층 주소와 시간에 기반'),
		'2' => gettext('DUID-EN: 엔터프라이즈 번호에 기반하여 벤더별로 할당'),
		'3' => gettext('DUID-LL: 링크 계층 주소 기준'),
		'4' => gettext('DUID-UUID: 범용 고유 식별자를 기반으로 함')
	)
))->setHelp('DHCPv6 고유 식별자 (DUID)는 IPv6 주소를 요청할 때 방화벽에서 사용됩니다.%1$s%1$s 기본적으로 방화벽은 방화벽 구성에 ' .
		'저장되지 않는 동적 DUID-LLT를 자동으로 만듭니다. 동일한 DUID가 항상 방화벽에 보관되도록하려면이 섹션에 DUID를 '.
		'입력하십시오. 새 DUID는 재부팅 후 또는 WAN 인터페이스가 방화벽에 의해 재구성 된 후에 적용됩니다. 방화벽이 / var에 RAM ' .
		'디스크를 사용하도록 구성된 경우 여기에 DUID를 저장하는 것이 가장 좋습니다.%1$s%1$s 그렇지 않으면 DUID가 재부팅 할 ' .
		'때마다 변경됩니다.', '<br />');

$group = new Form_Group('Raw DUID');

$group->add(new Form_Textarea(
	'global-v6duid',
	'DHCP6 DUID',
	$pconfig['global-v6duid']
	));

$btncopyduid = new Form_Button(
	'btncopyduid',
	'Copy DUID',
	null,
	'fa-clone'
	);

$btncopyduid->setAttribute('type','button')->removeClass('btn-primary')->addClass('btn-success btn-sm');
$group->add($btncopyduid);

$group->setHelp('DUID 복사 버튼을 사용하여 자리 표시 자에 표시된 시스템에서 감지 한 DUID를 복사 할 수 있습니다.');

$section->add($group);

$group = new Form_Group('DUID-LLT');

$group->add(new Form_Input(
	'ipv6duidllt_time',
	'DUID-LLT',
	'text',
	$ipv6duidllt_time
))->setHelp('자정 (2000년 1월 1일 자정)이후의 시간(초)');

$group->add(new Form_Input(
	'ipv6duidllt_ll',
	'Link-layer address',
	'text',
	$ipv6duidllt_ll,
	[ 'placeholder' => 'xx:xx:xx:xx:xx:xx' ]
))->setHelp('Link-layer 주소');

$section->add($group);

$group = new Form_Group('DUID-EN');

$group->add(new Form_Input(
	'ipv6duiden_en',
	'DUID-EN',
	'number',
	$ipv6duiden_en,
	[ 'placeholder' => 'Enterprise Number' ]
))->setHelp('IANA 개인 기업 번호');

$group->add(new Form_Textarea(
	'ipv6duiden_id',
	'Identifier',
	$ipv6duiden_id
))->setHelp('식별자 (가변 길이)');

$section->add($group);

$section->addInput(new Form_Input(
	'ipv6duidll',
	'DUID-LL',
	'text',
	$ipv6duidll,
	[ 'placeholder' => 'xx:xx:xx:xx:xx:xx' ]
))->setHelp('Link-layer 주소');

$section->addInput(new Form_Input(
	'ipv6duiduuid',
	'DUID-UUID',
	'text',
	$ipv6duiduuid,
	[ 'placeholder' => '00000000-0000-0000-0000-000000000000' ]
))->setHelp('범용 고유 식별자');

$form->add($section);
$section = new Form_Section('네트워크 인터페이스');

$section->addInput(new Form_Checkbox(
	'disablechecksumoffloading',
	'Hardware Checksum Offloading',
	'Disable hardware checksum offload',
	isset($config['system']['disablechecksumoffloading'])
))->setHelp('이 옵션을 선택하면 하드웨어 체크섬 오프 로딩이 비활성화됩니다. %1$s일부 하드웨어, 특히 일부 Realtek 카드에서는 체크섬 '.
	'오프 로딩이 해제됩니다. 드물게 드라이버는 체크섬 오프로드 및 일부 특정 NIC에 문제가있을 수 있습니다. 이것은 컴퓨터를 '.
	'재부팅하거나 각 인터페이스를 다시 구성한 후에 적용됩니다.', '<br/>');

$section->addInput(new Form_Checkbox(
	'disablesegmentationoffloading',
	'Hardware TCP Segmentation Offloading',
	'Disable hardware TCP segmentation offload',
	isset($config['system']['disablesegmentationoffloading'])
))->setHelp('이 옵션을 선택하면 하드웨어 TCP 세그먼트 오프로드 (TSO, TSO4, TSO6)가 사용 불가능하게됩니다. 이 오프로드는 일부 '.
	'하드웨어 드라이버에서 손상되어 일부 특정 NIC의 성능에 영향을 줄 수 있습니다. 이것은 컴퓨터를 재부팅하거나 각 인터페이스를 '.
	'다시 구성한 후에 적용됩니다.');

$section->addInput(new Form_Checkbox(
	'disablelargereceiveoffloading',
	'Hardware Large Receive Offloading',
	'Disable hardware large receive offload',
	isset($config['system']['disablelargereceiveoffloading'])
))->setHelp('이 옵션을 선택하면 하드웨어 큰 수신 오프 로딩 (LRO)이 비활성화됩니다. 이 오프로드는 일부 하드웨어 드라이버에서 손상되어 '.
	'일부 특정 NIC의 성능에 영향을 줄 수 있습니다. 이것은 컴퓨터를 재부팅하거나 각 인터페이스를 다시 구성한 후에 적용됩니다.');

$section->addInput(new Form_Checkbox(
	'sharednet',
	'ARP Handling',
	'Suppress ARP messages',
	isset($pconfig['sharednet'])
))->setHelp('이 옵션은 여러 인터페이스가 동일한 브로드 캐스트 도메인에있을 때 ARP 로그 메시지를 표시하지 않습니다.');

$section->addInput(new Form_Checkbox(
	'ip_change_kill_states',
	'Reset All States',
	'Reset all states if WAN IP Address changes',
	isset($pconfig['ip_change_kill_states'])
))->setHelp('This option resets all states when a WAN IP Address changes instead of only '.
    'states associated with the previous IP Address.');

if (get_freebsd_version() == 8) {
	$section->addInput(new Form_Checkbox(
		'flowtable',
		'Enable flowtable support',
		$pconfig['flowtable']
	))->setHelp('Enables infrastructure for caching flows as a means of accelerating '.
		'L3 and L2 lookups as well as providing stateful load balancing when used with '.
		'RADIX_MPATH.');
}

$form->add($section);
print $form;
?>

<script type="text/javascript">
//<![CDATA[
events.push(function() {

	// Show/hide IPv4 address of Tunnel Peer input field
	function showHideIpv6nat() {
		hideInput('ipv6nat_ipaddr', !$('#ipv6nat_enable').prop('checked'));
	}

	// Set placeholder on raw DUID and DUID-EN Identifier Textareas, time for DUID-LLT
	// Parse DUID if set in config and set corresponding DUID type and input values on page
	function setIpv6duid() {
		$('#global-v6duid').attr('placeholder', '<?=$duid?>');
		$('#ipv6duidllt_time').val((Date.now() / 1000 | 0) - 946684800);
		$('#ipv6duiden_id').attr('placeholder', 'xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx');
		<?php if (isset($pconfig['global-v6duid'])): ?>
		var duid = '<?=$pconfig['global-v6duid']?>';
		var duidtype = parseInt(duid.substr(6, 5).replace(':', ''), 16);
		switch (duidtype) {
		case 1:
			$('#ipv6duidllt_time').val(parseInt(duid.substr(18, 11).replace(/:/g, ''), 16));
			$('#ipv6duidllt_ll').val(duid.substr(-17));
			break;
		case 2:
			$('#ipv6duiden_en').val(parseInt(duid.substr(12, 11).replace(/:/g, ''), 16));
			$('#ipv6duiden_id').val(duid.substr(24));
			break;
		case 3:
			$('#ipv6duidll').val(duid.substr(-17));
			break;
		case 4:
			var uuid = duid.substr(-47).replace(/:/g, '');
			$('#ipv6duiduuid').val(uuid.substr(0, 8) + '-' + uuid.substr(8, 4) + '-' + uuid.substr(12, 4) + '-' + uuid.substr(16, 4) + '-' + uuid.substr(20));
			break;
		default:
		}
		if (0 < duidtype && duidtype < 5)
			$('#ipv6duidtype').val(duidtype);
		<?php endif; ?>
	}

	// Show/hide DUID type subsections
	function showHideIpv6duid() {
		hideInput('global-v6duid', $('#ipv6duidtype').prop('value') != '0');
		hideInput('ipv6duidllt_time', $('#ipv6duidtype').prop('value') != '1');
		hideInput('ipv6duiden_en', $('#ipv6duidtype').prop('value') != '2');
		hideInput('ipv6duidll', $('#ipv6duidtype').prop('value') != '3');
		hideInput('ipv6duiduuid', $('#ipv6duidtype').prop('value') != '4');
	}

	// On changing selection for DUID type
	$('#ipv6duidtype').change(function() {
		showHideIpv6duid();
	});

	// On click, copy the placeholder DUID to the input field
	$('#btncopyduid').click(function() {
		if ('<?=$duid?>' != '--:--:--:--:--:--:--:--:--:--:--:--:--:--:--:--')
			$('#global-v6duid').val('<?=$duid?>');
	});

	// On clicking IPv6 over IPv4 Tunneling checkbox
	$('#ipv6nat_enable').click(function () {
		showHideIpv6nat();
	});

	// On page load
	showHideIpv6nat();
	setIpv6duid();
	showHideIpv6duid();


});
//]]>
</script>

<?php include("foot.inc");

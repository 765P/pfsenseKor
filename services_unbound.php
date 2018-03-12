<?php
/*
 * services_unbound.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2018 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2014 Warren Baker (warren@pfsense.org)
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
##|*IDENT=page-services-dnsresolver
##|*NAME=Services: DNS Resolver
##|*DESCR=Allow access to the 'Services: DNS Resolver' page.
##|*MATCH=services_unbound.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("unbound.inc");
require_once("pfsense-utils.inc");
require_once("system.inc");

if (!is_array($config['unbound'])) {
	$config['unbound'] = array();
}

$a_unboundcfg =& $config['unbound'];

if (!is_array($a_unboundcfg['hosts'])) {
	$a_unboundcfg['hosts'] = array();
}

$a_hosts =& $a_unboundcfg['hosts'];

if (!is_array($a_unboundcfg['domainoverrides'])) {
	$a_unboundcfg['domainoverrides'] = array();
}

$a_domainOverrides = &$a_unboundcfg['domainoverrides'];

if (isset($a_unboundcfg['enable'])) {
	$pconfig['enable'] = true;
}
if (isset($a_unboundcfg['dnssec'])) {
	$pconfig['dnssec'] = true;
}
if (isset($a_unboundcfg['forwarding'])) {
	$pconfig['forwarding'] = true;
}
if (isset($a_unboundcfg['regdhcp'])) {
	$pconfig['regdhcp'] = true;
}
if (isset($a_unboundcfg['regdhcpstatic'])) {
	$pconfig['regdhcpstatic'] = true;
}
if (isset($a_unboundcfg['regovpnclients'])) {
	$pconfig['regovpnclients'] = true;
}

$pconfig['port'] = $a_unboundcfg['port'];
$pconfig['custom_options'] = base64_decode($a_unboundcfg['custom_options']);

if (empty($a_unboundcfg['active_interface'])) {
	$pconfig['active_interface'] = array();
} else {
	$pconfig['active_interface'] = explode(",", $a_unboundcfg['active_interface']);
}

if (empty($a_unboundcfg['outgoing_interface'])) {
	$pconfig['outgoing_interface'] = array();
} else {
	$pconfig['outgoing_interface'] = explode(",", $a_unboundcfg['outgoing_interface']);
}

if (empty($a_unboundcfg['system_domain_local_zone_type'])) {
	$pconfig['system_domain_local_zone_type'] = "transparent";
} else {
	$pconfig['system_domain_local_zone_type'] = $a_unboundcfg['system_domain_local_zone_type'];
}


if ($_POST['apply']) {
	$retval = 0;
	$retval |= services_unbound_configure();
	if ($retval == 0) {
		clear_subsystem_dirty('unbound');
	}
	/* Update resolv.conf in case the interface bindings exclude localhost. */
	system_resolvconf_generate();
	/* Start or restart dhcpleases when it's necessary */
	system_dhcpleases_configure();
}

if ($_POST['save']) {
	$pconfig = $_POST;
	unset($input_errors);

	if (isset($pconfig['enable']) && isset($config['dnsmasq']['enable'])) {
		if ($pconfig['port'] == $config['dnsmasq']['port']) {
			$input_errors[] = gettext("DNS 전달자는이 포트를 사용하여 활성화됩니다. 비 충돌 포트를 선택하거나 DNS 전달자를 비활성화하십시오.");
		}
	}

	// forwarding mode requires having valid DNS servers
	if (isset($pconfig['forwarding'])) {
		$founddns = false;
		if (isset($config['system']['dnsallowoverride'])) {
			$dns_servers = get_dns_servers();
			if (is_array($dns_servers)) {
				foreach ($dns_servers as $dns_server) {
					if (!ip_in_subnet($dns_server, "127.0.0.0/8")) {
						$founddns = true;
					}
				}
			}
		}
		if (is_array($config['system']['dnsserver'])) {
			foreach ($config['system']['dnsserver'] as $dnsserver) {
				if (is_ipaddr($dnsserver)) {
					$founddns = true;
				}
			}
		}
		if ($founddns == false) {
			$input_errors[] = gettext("전달 모드를 사용하려면 시스템 &gt; 일반 설정에서 하나 이상의 DNS 서버를 지정해야합니다.");
		}
	}

	if (empty($pconfig['active_interface'])) {
		$input_errors[] = gettext("바인딩을 위해 하나 이상의 네트워크 인터페이스를 선택해야합니다.");
	} else if (!isset($config['system']['dnslocalhost']) && (!in_array("lo0", $pconfig['active_interface']) && !in_array("all", $pconfig['active_interface']))) {
		$input_errors[] = gettext("이 시스템은 DNS 확인자를 DNS 서버로 사용하도록 구성되어 있으므로 네트워크 인터페이스에서 로컬 호스트 또는 모두를 선택해야합니다.");
	}

	if (empty($pconfig['outgoing_interface'])) {
		$input_errors[] = gettext("하나 이상의 발신 네트워크 인터페이스를 선택해야합니다.");
	}

	if ($pconfig['port'] && !is_port($pconfig['port'])) {
		$input_errors[] = gettext("올바른 포트 번호를 지정해야 합니다.");
	}

	if (is_array($pconfig['active_interface']) && !empty($pconfig['active_interface'])) {
		$display_active_interface = $pconfig['active_interface'];
		$pconfig['active_interface'] = implode(",", $pconfig['active_interface']);
	}

	if ((isset($pconfig['regdhcp']) || isset($pconfig['regdhcpstatic'])) && !is_dhcp_server_enabled()) {
		$input_errors[] = gettext("DNS확인자에서 DHCP등록이 작동하려면 DHCP서버가 사용하도록 설정되어 있어야 합니다.");
	}

	if (($pconfig['system_domain_local_zone_type'] == "redirect") && isset($pconfig['regdhcp'])) {
		$input_errors[] = gettext('리다이렉션의 시스템 도메인 로컬 영역 유형이 동적 DHCP등록과 호환되지 않습니다.');
	}

	$display_custom_options = $pconfig['custom_options'];
	$pconfig['custom_options'] = base64_encode(str_replace("\r\n", "\n", $pconfig['custom_options']));

	if (is_array($pconfig['outgoing_interface']) && !empty($pconfig['outgoing_interface'])) {
		$display_outgoing_interface = $pconfig['outgoing_interface'];
		$pconfig['outgoing_interface'] = implode(",", $pconfig['outgoing_interface']);
	}

	$test_output = array();
	if (test_unbound_config($pconfig, $test_output)) {
		$input_errors[] = gettext("생성된 구성 파일은 바인딩 되지 않은 상태로 구문 분석할 수 없습니다. 다음 오류를 해결하십시오.:");
		$input_errors = array_merge($input_errors, $test_output);
	}

	if (!$input_errors) {
		$a_unboundcfg['enable'] = isset($pconfig['enable']);
		$a_unboundcfg['port'] = $pconfig['port'];
		$a_unboundcfg['dnssec'] = isset($pconfig['dnssec']);
		$a_unboundcfg['forwarding'] = isset($pconfig['forwarding']);
		$a_unboundcfg['regdhcp'] = isset($pconfig['regdhcp']);
		$a_unboundcfg['regdhcpstatic'] = isset($pconfig['regdhcpstatic']);
		$a_unboundcfg['regovpnclients'] = isset($pconfig['regovpnclients']);
		$a_unboundcfg['active_interface'] = $pconfig['active_interface'];
		$a_unboundcfg['outgoing_interface'] = $pconfig['outgoing_interface'];
		$a_unboundcfg['system_domain_local_zone_type'] = $pconfig['system_domain_local_zone_type'];
		$a_unboundcfg['custom_options'] = $pconfig['custom_options'];

		write_config(gettext("DNS확인자가 구성되었습니다."));
		mark_subsystem_dirty('unbound');
	}

	$pconfig['active_interface'] = $display_active_interface;
	$pconfig['outgoing_interface'] = $display_outgoing_interface;
	$pconfig['custom_options'] = $display_custom_options;
}


if ($pconfig['custom_options']) {
	$customoptions = true;
} else {
	$customoptions = false;
}

if ($_POST['act'] == "del") {
	if ($_POST['type'] == 'host') {
		if ($a_hosts[$_POST['id']]) {
			unset($a_hosts[$_POST['id']]);
			write_config(gettext("Host override deleted from DNS Resolver."));
			mark_subsystem_dirty('unbound');
			header("Location: services_unbound.php");
			exit;
		}
	} elseif ($_POST['type'] == 'doverride') {
		if ($a_domainOverrides[$_POST['id']]) {
			unset($a_domainOverrides[$_POST['id']]);
			write_config(gettext("Domain override deleted from DNS Resolver."));
			mark_subsystem_dirty('unbound');
			header("Location: services_unbound.php");
			exit;
		}
	}
}

function build_if_list($selectedifs) {
	$interface_addresses = get_possible_listen_ips(true);
	$iflist = array('options' => array(), 'selected' => array());

	$iflist['options']['all']	= gettext("All");
	if (empty($selectedifs) || empty($selectedifs[0]) || in_array("all", $selectedifs)) {
		array_push($iflist['selected'], "all");
	}

	foreach ($interface_addresses as $laddr => $ldescr) {
		$iflist['options'][$laddr] = htmlspecialchars($ldescr);

		if ($selectedifs && in_array($laddr, $selectedifs)) {
			array_push($iflist['selected'], $laddr);
		}
	}

	unset($interface_addresses);

	return($iflist);
}

$pgtitle = array(gettext("Services"), gettext("DNS확인자"), gettext("일반 설정"));
$pglinks = array("", "@self", "@self");
$shortcut_section = "resolver";

include_once("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

if ($_POST['apply']) {
	print_apply_result_box($retval);
}

if (is_subsystem_dirty('unbound')) {
	print_apply_box(gettext("DNS확인자 구성이 변경되었습니다.") . "<br />" . gettext("변경사항을 저장하시면 적용됩니다."));
}

$tab_array = array();
$tab_array[] = array(gettext("일반 설정"), true, "services_unbound.php");
$tab_array[] = array(gettext("어드밴스드 설정"), false, "services_unbound_advanced.php");
$tab_array[] = array(gettext("액세스 목록"), false, "/services_unbound_acls.php");
display_top_tabs($tab_array, true);

$form = new Form();

$section = new Form_Section('일반 DNS확인자 옵션');

$section->addInput(new Form_Checkbox(
	'enable',
	'Enable',
	'Enable DNS resolver',
	$pconfig['enable']
));

$section->addInput(new Form_Input(
	'port',
	'Listen Port',
	'number',
	$pconfig['port'],
	['placeholder' => '53']
))->setHelp('The port used for responding to DNS queries. It should normally be left blank unless another service needs to bind to TCP/UDP port 53.');

$activeiflist = build_if_list($pconfig['active_interface']);

$section->addInput(new Form_Select(
	'active_interface',
	'*Network Interfaces',
	$activeiflist['selected'],
	$activeiflist['options'],
	true
))->addClass('general', 'resizable')->setHelp('Interface IPs used by the DNS Resolver for responding to queries from clients. If an interface has both IPv4 and IPv6 IPs, both are used. Queries to other interface IPs not selected below are discarded. ' .
			'The default behavior is to respond to queries on every available IPv4 and IPv6 address.');

$outiflist = build_if_list($pconfig['outgoing_interface']);

$section->addInput(new Form_Select(
	'outgoing_interface',
	'*Outgoing Network Interfaces',
	$outiflist['selected'],
	$outiflist['options'],
	true
))->addClass('general', 'resizable')->setHelp('Utilize different network interface(s) that the DNS Resolver will use to send queries to authoritative servers and receive their replies. By default all interfaces are used.');

$section->addInput(new Form_Select(
	'system_domain_local_zone_type',
	'*System Domain Local Zone Type',
	$pconfig['system_domain_local_zone_type'],
	unbound_local_zone_types()
))->setHelp('The local-zone type used for the pfSense system domain (System | General Setup | Domain).  Transparent is the default.  Local-Zone type descriptions are available in the unbound.conf(5) manual pages.');

$section->addInput(new Form_Checkbox(
	'dnssec',
	'DNSSEC',
	'Enable DNSSEC Support',
	$pconfig['dnssec']
));

$section->addInput(new Form_Checkbox(
	'forwarding',
	'DNS Query Forwarding',
	'Enable Forwarding Mode',
	$pconfig['forwarding']
))->setHelp('If this option is set, DNS queries will be forwarded to the upstream DNS servers defined under'.
					' %1$sSystem &gt; General Setup%2$s or those obtained via DHCP/PPP on WAN'.
					' (if DNS Server Override is enabled there).','<a href="system.php">','</a>');

$section->addInput(new Form_Checkbox(
	'regdhcp',
	'DHCP Registration',
	'Register DHCP leases in the DNS Resolver',
	$pconfig['regdhcp']
))->setHelp('If this option is set, then machines that specify their hostname when requesting a DHCP lease will be registered'.
					' in the DNS Resolver, so that their name can be resolved.'.
					' The domain in %1$sSystem &gt; General Setup%2$s should also be set to the proper value.','<a href="system.php">','</a>');

$section->addInput(new Form_Checkbox(
	'regdhcpstatic',
	'Static DHCP',
	'Register DHCP static mappings in the DNS Resolver',
	$pconfig['regdhcpstatic']
))->setHelp('If this option is set, then DHCP static mappings will be registered in the DNS Resolver, so that their name can be resolved. '.
					'The domain in %1$sSystem &gt; General Setup%2$s should also be set to the proper value.','<a href="system.php">','</a>');

$section->addInput(new Form_Checkbox(
	'regovpnclients',
	'OpenVPN Clients',
	'Register connected OpenVPN clients in the DNS Resolver',
	$pconfig['regovpnclients']
))->setHelp(sprintf('If this option is set, then the common name (CN) of connected OpenVPN clients will be registered in the DNS Resolver, so that their name can be resolved. This only works for OpenVPN servers (Remote Access SSL/TLS) operating in "tun" mode. '.
					'The domain in %sSystem: General Setup%s should also be set to the proper value.','<a href="system.php">','</a>'));

$btnadv = new Form_Button(
	'btnadvcustom',
	'Custom options',
	null,
	'fa-cog'
);

$btnadv->setAttribute('type','button')->addClass('btn-info btn-sm');

$section->addInput(new Form_StaticText(
	'Display Custom Options',
	$btnadv
));

$section->addInput(new Form_Textarea (
	'custom_options',
	'Custom options',
	$pconfig['custom_options']
))->setHelp('Enter any additional configuration parameters to add to the DNS Resolver configuration here, separated by a newline.');

$form->add($section);
print($form);
?>

<script type="text/javascript">
//<![CDATA[
events.push(function() {

	// Show advanced custom options ==============================================
	var showadvcustom = false;

	function show_advcustom(ispageload) {
		var text;
		// On page load decide the initial state based on the data.
		if (ispageload) {
			showadvcustom = <?=($customoptions ? 'true' : 'false');?>;
		} else {
			// It was a click, swap the state.
			showadvcustom = !showadvcustom;
		}

		hideInput('custom_options', !showadvcustom);

		if (showadvcustom) {
			text = "<?=gettext('커스텀 옵션 숨기기');?>";
		} else {
			text = "<?=gettext('커스텀 옵션 보이기');?>";
		}
		$('#btnadvcustom').html('<i class="fa fa-cog"></i> ' + text);
	}

	// If the enable checkbox is not checked, hide all inputs
	function hideGeneral() {
		var hide = ! $('#enable').prop('checked');

		hideMultiClass('general', hide);
		hideInput('port', hide);
		hideSelect('system_domain_local_zone_type', hide);
		hideCheckbox('dnssec', hide);
		hideCheckbox('forwarding', hide);
		hideCheckbox('regdhcp', hide);
		hideCheckbox('regdhcpstatic', hide);
		hideCheckbox('regovpnclients', hide);
		hideInput('btnadvcustom', hide);
		hideInput('custom_options', hide || !showadvcustom);
	}

	// Un-hide additional controls
	$('#btnadvcustom').click(function(event) {
		show_advcustom();
	});

	// When 'enable' is clicked, disable/enable the following hide inputs
	$('#enable').click(function() {
		hideGeneral();
	});

	// On initial load
	if ($('#custom_options').val().length == 0) {
		hideInput('custom_options', true);
	}

	hideGeneral();
	show_advcustom(true);

});
//]]>
</script>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext("호스트 재정의")?></h2></div>
	<div class="panel-body table-responsive">
		<table class="table table-striped table-hover table-condensed sortable-theme-bootstrap table-rowdblclickedit" data-sortable>
			<thead>
				<tr>
					<th><?=gettext("호스트")?></th>
					<th><?=gettext("호스트 상위 도메인")?></th>
					<th><?=gettext("호스트에 대해 반환할 IP")?></th>
					<th><?=gettext("Description")?></th>
					<th><?=gettext("Actions")?></th>
				</tr>
			</thead>
			<tbody>
<?php
$i = 0;
foreach ($a_hosts as $hostent):
?>
				<tr>
					<td>
						<?=$hostent['host']?>
					</td>
					<td>
						<?=$hostent['domain']?>
					</td>
					<td>
						<?=$hostent['ip']?>
					</td>
					<td>
						<?=htmlspecialchars($hostent['descr'])?>
					</td>
					<td>
						<a class="fa fa-pencil"	title="<?=gettext('호스트 재정의 편집')?>" href="services_unbound_host_edit.php?id=<?=$i?>"></a>
						<a class="fa fa-trash"	title="<?=gettext('호스트 재정의 삭제')?>" href="services_unbound.php?type=host&amp;act=del&amp;id=<?=$i?>" usepost></a>
					</td>
				</tr>

<?php
	if ($hostent['aliases']['item'] && is_array($hostent['aliases']['item'])):
		foreach ($hostent['aliases']['item'] as $alias):
?>
				<tr>
					<td>
						<?=$alias['host']?>
					</td>
					<td>
						<?=$alias['domain']?>
					</td>
					<td>
						<?=gettext("을 위한 Alias ");?><?=$hostent['host'] ? $hostent['host'] . '.' . $hostent['domain'] : $hostent['domain']?>
					</td>
					<td>
						<i class="fa fa-angle-double-right text-info"></i>
						<?=htmlspecialchars($alias['description'])?>
					</td>
					<td>
						<a class="fa fa-pencil"	title="<?=gettext('호스트 재정의 편집')?>" 	href="services_unbound_host_edit.php?id=<?=$i?>"></a>
					</td>
				</tr>
<?php
		endforeach;
	endif;
	$i++;
endforeach;
?>
			</tbody>
		</table>
	</div>
</div>

<span class="help-block">
	리졸버의 표준 DNS조회 프로세스가 재정의되어야 하는 모든 개별 호스트를 입력하고 특정 IPv4또는 IPv6주소를 리졸버에 의해 자동으로 반환해야 합니다.
	'test', 'mycompany.localdomain', '1.168.192.in-addr.arpa'또는 'somesite.com'과 같이 표준 및 비표준 이름과 상위 도메인을 입력 할 수 있습니다.
	호스트에 대한 검색 시도는 자동으로 지정된 IP주소를 반환하고 도메인에 대한 일반적인 검색 서버는 호스트의 레코드에 대해 쿼리 되지 않습니다.
</span>

<nav class="action-buttons">
	<a href="services_unbound_host_edit.php" class="btn btn-sm btn-success">
		<i class="fa fa-plus icon-embed-btn"></i>
		<?=gettext('추가')?>
	</a>
</nav>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext("도메인 재정의")?></h2></div>
	<div class="panel-body table-responsive">
		<table class="table table-striped table-hover table-condensed sortable-theme-bootstrap table-rowdblclickedit" data-sortable>
			<thead>
				<tr>
					<th><?=gettext("Domain")?></th>
					<th><?=gettext("Lookup 서버 IP주소")?></th>
					<th><?=gettext("Description")?></th>
					<th><?=gettext("Actions")?></th>
				</tr>
			</thead>

			<tbody>
<?php
$i = 0;
foreach ($a_domainOverrides as $doment):
?>
				<tr>
					<td>
						<?=$doment['domain']?>&nbsp;
					</td>
					<td>
						<?=$doment['ip']?>&nbsp;
					</td>
					<td>
						<?=htmlspecialchars($doment['descr'])?>&nbsp;
					</td>
					<td>
						<a class="fa fa-pencil"	title="<?=gettext('도메인 재정의 편집')?>" href="services_unbound_domainoverride_edit.php?id=<?=$i?>"></a>
						<a class="fa fa-trash"	title="<?=gettext('도메인 재정의 삭제')?>" href="services_unbound.php?act=del&amp;type=doverride&amp;id=<?=$i?>" usepost></a>
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

<span class="help-block">
	리졸버의 표준 DNS조회 프로세스를 재정의하고 대신 다른(비표준)조회 서버를 쿼리할 도메인을 입력합니다.
	'test', 'mycompany.localdomain', '1.168.192.in-addr.arpa'또는 'somesite.com'과 같이 비표준의 '유효하지 않은'로컬 도메인과 하위 도메인을 입력 할 수도 있습니다.
	IP 주소는 도메인 (모든 하위 도메인 포함)의 신뢰할 수있는 조회 서버로 취급되며 다른 조회 서버는 쿼리되지 않습니다.
</span>

<nav class="action-buttons">
	<a href="services_unbound_domainoverride_edit.php" class="btn btn-sm btn-success">
		<i class="fa fa-plus icon-embed-btn"></i>
		<?=gettext('추가')?>
	</a>
</nav>

<div class="infoblock">
	<?php print_info_box(sprintf(gettext('DNS 확인 프로그램을 사용하는 경우 DHCP '.
		'서비스 (사용하는 경우)는 자동으로 LAN IP 주소를 DHCP 클라이언트에 '.
		'대한 DNS 서버로 제공하여 DNS 확인 프로그램을 사용합니다. '.
		'전달 기능이 활성화 된 경우 DNS 확인 프로그램은 [시스템 &gt; 일반 설정]에'.
		'입력 된 DNS 서버 또는 WAN상의 DHCP / PPP로 DNS 서버 목록을 무시하도록 허용 된'.
		'경우 WAN에서 DHCP 또는 PPP를 통해 얻은 DNS 서버를 사용합니다.'), '<a href="system.php">', '</a>'), 'info', false); ?>
</div>

<?php include("foot.inc");

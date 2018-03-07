<?php
/*
 * services_dhcp_edit.php
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
2018.03.07
한글화 번역 
*/

##|+PRIV
##|*IDENT=page-services-dhcpserver-editstaticmapping
##|*NAME=Services: DHCP Server: Edit static mapping
##|*DESCR=Allow access to the 'Services: DHCP Server: Edit static mapping' page.
##|*MATCH=services_dhcp_edit.php*
##|-PRIV

function staticmapcmp($a, $b) {
	return ipcmp($a['ipaddr'], $b['ipaddr']);
}

function staticmaps_sort($ifgui) {
	global $g, $config;

	usort($config['dhcpd'][$ifgui]['staticmap'], "staticmapcmp");
}

require_once('globals.inc');

if (!$g['services_dhcp_server_enable']) {
	header("Location: /");
	exit;
}

require_once("guiconfig.inc");

$if = $_REQUEST['if'];

if (!$if) {
	header("Location: services_dhcp.php");
	exit;
}

if (!is_array($config['dhcpd'])) {
	$config['dhcpd'] = array();
}

if (!is_array($config['dhcpd'][$if])) {
	$config['dhcpd'][$if] = array();
}

if (!is_array($config['dhcpd'][$if]['staticmap'])) {
	$config['dhcpd'][$if]['staticmap'] = array();
}

if (!is_array($config['dhcpd'][$if]['pool'])) {
	$config['dhcpd'][$if]['pool'] = array();
}

$a_pools = &$config['dhcpd'][$if]['pool'];

$static_arp_enabled=isset($config['dhcpd'][$if]['staticarp']);
$netboot_enabled=isset($config['dhcpd'][$if]['netboot']);
$a_maps = &$config['dhcpd'][$if]['staticmap'];
$ifcfgip = get_interface_ip($if);
$ifcfgsn = get_interface_subnet($if);
$ifcfgdescr = convert_friendly_interface_to_friendly_descr($if);

$id = $_REQUEST['id'];

if (isset($id) && $a_maps[$id]) {
	$pconfig['mac'] = $a_maps[$id]['mac'];
	$pconfig['cid'] = $a_maps[$id]['cid'];
	$pconfig['hostname'] = $a_maps[$id]['hostname'];
	$pconfig['ipaddr'] = $a_maps[$id]['ipaddr'];
	$pconfig['filename'] = $a_maps[$id]['filename'];
	$pconfig['rootpath'] = $a_maps[$id]['rootpath'];
	$pconfig['descr'] = $a_maps[$id]['descr'];
	$pconfig['arp_table_static_entry'] = isset($a_maps[$id]['arp_table_static_entry']);
	$pconfig['deftime'] = $a_maps[$id]['defaultleasetime'];
	$pconfig['maxtime'] = $a_maps[$id]['maxleasetime'];
	$pconfig['gateway'] = $a_maps[$id]['gateway'];
	$pconfig['domain'] = $a_maps[$id]['domain'];
	$pconfig['domainsearchlist'] = $a_maps[$id]['domainsearchlist'];
	list($pconfig['wins1'], $pconfig['wins2']) = $a_maps[$id]['winsserver'];
	list($pconfig['dns1'], $pconfig['dns2'], $pconfig['dns3'], $pconfig['dns4']) = $a_maps[$id]['dnsserver'];
	$pconfig['ddnsdomain'] = $a_maps[$id]['ddnsdomain'];
	$pconfig['ddnsdomainprimary'] = $a_maps[$id]['ddnsdomainprimary'];
	$pconfig['ddnsdomainkeyname'] = $a_maps[$id]['ddnsdomainkeyname'];
	$pconfig['ddnsdomainkey'] = $a_maps[$id]['ddnsdomainkey'];
	$pconfig['ddnsupdate'] = isset($a_maps[$id]['ddnsupdate']);
	$pconfig['ddnsforcehostname'] = isset($a_maps[$id]['ddnsforcehostname']);
	list($pconfig['ntp1'], $pconfig['ntp2']) = $a_maps[$id]['ntpserver'];
	$pconfig['tftp'] = $a_maps[$id]['tftp'];
} else {
	$pconfig['mac'] = $_REQUEST['mac'];
	$pconfig['cid'] = $_REQUEST['cid'];
	$pconfig['hostname'] = $_REQUEST['hostname'];
	$pconfig['filename'] = $_REQUEST['filename'];
	$pconfig['rootpath'] = $_REQUEST['rootpath'];
	$pconfig['descr'] = $_REQUEST['descr'];
	$pconfig['arp_table_static_entry'] = $_REQUEST['arp_table_static_entry'];
	$pconfig['deftime'] = $_REQUEST['defaultleasetime'];
	$pconfig['maxtime'] = $_REQUEST['maxleasetime'];
	$pconfig['gateway'] = $_REQUEST['gateway'];
	$pconfig['domain'] = $_REQUEST['domain'];
	$pconfig['domainsearchlist'] = $_REQUEST['domainsearchlist'];
	$pconfig['wins1'] = $_REQUEST['wins1'];
	$pconfig['wins2'] = $_REQUEST['wins2'];
	$pconfig['dns1'] = $_REQUEST['dns1'];
	$pconfig['dns2'] = $_REQUEST['dns2'];
	$pconfig['dns3'] = $_REQUEST['dns3'];
	$pconfig['dns4'] = $_REQUEST['dns4'];
	$pconfig['ddnsdomain'] = $_REQUEST['ddnsdomain'];
	$pconfig['ddnsdomainprimary'] = $_REQUEST['ddnsdomainprimary'];
	$pconfig['ddnsdomainkeyname'] = $_REQUEST['ddnsdomainkeyname'];
	$pconfig['ddnsdomainkey'] = $_REQUEST['ddnsdomainkey'];
	$pconfig['ddnsupdate'] = isset($_REQUEST['ddnsupdate']);
	$pconfig['ddnsforcehostname'] = isset($_REQUEST['ddnsforcehostname']);
	$pconfig['ntp1'] = $_REQUEST['ntp1'];
	$pconfig['ntp2'] = $_REQUEST['ntp2'];
	$pconfig['tftp'] = $_REQUEST['tftp'];
}

if ($_POST['save']) {
	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = array();
	$reqdfieldsn = array();

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

	/* either MAC or Client-ID must be specified */
	if (empty($_POST['mac']) && empty($_POST['cid'])) {
		$input_errors[] = gettext("MAC주소 또는 클라이언트 식별자를 지정해주십시오.");
	}

	/* normalize MAC addresses - lowercase and convert Windows-ized hyphenated MACs to colon delimited */
	$_POST['mac'] = strtolower(str_replace("-", ":", $_POST['mac']));

	if ($_POST['hostname']) {
		preg_match("/\-\$/", $_POST['hostname'], $matches);
		if ($matches) {
			$input_errors[] = gettext("호스트 이름은 RFC952에 따라 하이픈으로 끝날 수 없습니다.");
		}
		if (!is_hostname($_POST['hostname'])) {
			$input_errors[] = gettext("호스트 이름에는 문자 A-Z, 0-9 및 '-'만 사용할 수 있습니다.");
		} else {
			if (!is_unqualified_hostname($_POST['hostname'])) {
				$input_errors[] = gettext("유효한 호스트 이름이 지정되었지만 도메인 이름 부분은 생략해야합니다.");
			}
		}
	}

	if (($_POST['ipaddr'] && !is_ipaddrv4($_POST['ipaddr']))) {
		$input_errors[] = gettext("유효한 IPv4 주소를 지정해야합니다.");
	}

	if (($_POST['mac'] && !is_macaddr($_POST['mac']))) {
		$input_errors[] = gettext("유효한 MAC 주소를 지정해야합니다.");
	}
	if ($static_arp_enabled && !$_POST['ipaddr']) {
		$input_errors[] = gettext("정적 ARP가 사용됩니다. IP 주소를 지정해야합니다.");
	}

	/* check for overlaps */
	foreach ($a_maps as $mapent) {
		if (isset($id) && ($a_maps[$id]) && ($a_maps[$id] === $mapent)) {
			continue;
		}
		if ((($mapent['mac'] == $_POST['mac']) && $mapent['mac']) ||
		    (($mapent['cid'] == $_POST['cid']) && $mapent['cid'])) {
			$input_errors[] = gettext("동일한 MAC 주소 또는 클라이언트 식별자가 존재합니다.");
			break;
		}
	}

	/* make sure it's not within the dynamic subnet */
	if ($_POST['ipaddr']) {
		if (is_inrange_v4($_POST['ipaddr'], $config['dhcpd'][$if]['range']['from'], $config['dhcpd'][$if]['range']['to'])) {
			$input_errors[] = sprintf(gettext("IP주소는 이 인터페이스의 DHCP범위 내에 있어서는 안 됩니다."));
		}

		foreach ($a_pools as $pidx => $p) {
			if (is_inrange_v4($_POST['ipaddr'], $p['range']['from'], $p['range']['to'])) {
				$input_errors[] = gettext("IP주소가 이 인터페이스용 DHCP풀에 구성된 범위 내에 있으면 안 됩니다.");
				break;
			}
		}

		$lansubnet_start = gen_subnetv4($ifcfgip, $ifcfgsn);
		$lansubnet_end = gen_subnetv4_max($ifcfgip, $ifcfgsn);
		if (!is_inrange_v4($_POST['ipaddr'], $lansubnet_start, $lansubnet_end)) {
			$input_errors[] = sprintf(gettext("IP주소는%s서브넷에 있어야 합니다."), $ifcfgdescr);
		}

		if ($_POST['ipaddr'] == $lansubnet_start) {
			$input_errors[] = sprintf(gettext("IP주소는%s네트워크 주소일 수 없습니다."), $ifcfgdescr);
		}

		if ($_POST['ipaddr'] == $lansubnet_end) {
			$input_errors[] = sprintf(gettext("IP주소는%s브로드캐스트 주소일 수 없습니다."), $ifcfgdescr);
		}
	}

	if (($_POST['gateway'] && !is_ipaddrv4($_POST['gateway']))) {
		$input_errors[] = gettext("게이트 웨이에 올바른 IPv4주소를 지정해야 합니다.");
	}
	if (($_POST['wins1'] && !is_ipaddrv4($_POST['wins1'])) || ($_POST['wins2'] && !is_ipaddrv4($_POST['wins2']))) {
		$input_errors[] = gettext("주/보조 WINS서버에 유효한 IPv4주소를 지정해야 합니다.");
	}

	$parent_ip = get_interface_ip($_POST['if']);
	if (is_ipaddrv4($parent_ip) && $_POST['gateway']) {
		$parent_sn = get_interface_subnet($_POST['if']);
		if (!ip_in_subnet($_POST['gateway'], gen_subnet($parent_ip, $parent_sn) . "/" . $parent_sn) && !ip_in_interface_alias_subnet($_POST['if'], $_POST['gateway'])) {
			$input_errors[] = sprintf(gettext("게이트 웨이 주소%s이(가)선택한 인터페이스의 서브넷 내에 있지 않습니다."), $_POST['gateway']);
		}
	}
	if (($_POST['dns1'] && !is_ipaddrv4($_POST['dns1'])) ||
	    ($_POST['dns2'] && !is_ipaddrv4($_POST['dns2'])) ||
	    ($_POST['dns3'] && !is_ipaddrv4($_POST['dns3'])) ||
	    ($_POST['dns4'] && !is_ipaddrv4($_POST['dns4']))) {
		$input_errors[] = gettext("각 DNS서버에 대해 올바른 IPv4 주소를 지정해주십시오.");
	}

	if ($_POST['deftime'] && (!is_numeric($_POST['deftime']) || ($_POST['deftime'] < 60))) {
		$input_errors[] = gettext("기본 리스 시간은 60초 이상이어야 합니다.");
	}
	if ($_POST['maxtime'] && (!is_numeric($_POST['maxtime']) || ($_POST['maxtime'] < 60) || ($_POST['maxtime'] <= $_POST['deftime']))) {
		$input_errors[] = gettext("최대 리스 시간은 60초 이상이어야 하며 기본 리스 시간보다 높아야 합니다.");
	}
	if (($_POST['ddnsdomain'] && !is_domain($_POST['ddnsdomain']))) {
		$input_errors[] = gettext("동적 DNS등록에 올바른 도메인 이름을 지정해주십시오.");
	}
	if (($_POST['ddnsdomain'] && !is_ipaddrv4($_POST['ddnsdomainprimary']))) {
		$input_errors[] = gettext("동적 도메인 이름에 유효한 기본 도메인 이름 서버 IPv4주소를 지정해주십시오.");
	}
	if (($_POST['ddnsdomainkey'] && !$_POST['ddnsdomainkeyname']) ||
	    ($_POST['ddnsdomainkeyname'] && !$_POST['ddnsdomainkey'])) {
		$input_errors[] = gettext("올바른 도메인 키와 키 이름을 모두 지정해야 합니다.");
	}
	if ($_POST['domainsearchlist']) {
		$domain_array=preg_split("/[ ;]+/", $_POST['domainsearchlist']);
		foreach ($domain_array as $curdomain) {
			if (!is_domain($curdomain)) {
				$input_errors[] = gettext("올바른 도메인 검색 목록을 지정해주십시오.");
				break;
			}
		}
	}

	if (($_POST['ntp1'] && !is_ipaddrv4($_POST['ntp1'])) || ($_POST['ntp2'] && !is_ipaddrv4($_POST['ntp2']))) {
		$input_errors[] = gettext("기본/보조 NTP 서버에 유효한 IPv4 주소를 지정해주십시오.");
	}
	if ($_POST['tftp'] && !is_ipaddrv4($_POST['tftp']) && !is_domain($_POST['tftp']) && !filter_var($_POST['tftp'], FILTER_VALIDATE_URL)) {
		$input_errors[] = gettext("유효한 IPv4 주소, 호스트 이름 또는 URL을 TFTP 서버에 지정해주십시오.");
	}
	if (($_POST['nextserver'] && !is_ipaddrv4($_POST['nextserver']))) {
		$input_errors[] = gettext("네트워크 부트 서버에 유효한 IPv4 주소를 지정해주십시오.");
	}
	if (isset($_POST['arp_table_static_entry']) && empty($_POST['mac'])) {
		$input_errors[] = gettext("정적ARP와 함께 사용하시려면 유효한 MAC주소를 지정해주십시오.");
	}
	if (isset($_POST['arp_table_static_entry']) && empty($_POST['ipaddr'])) {
		$input_errors[] = gettext("정적ARP와 함께 사용하시려면 유효한 IPv4주소를 지정해주십시오.");
	}

	if (!$input_errors) {
		$mapent = array();
		$mapent['mac'] = $_POST['mac'];
		$mapent['cid'] = $_POST['cid'];
		$mapent['ipaddr'] = $_POST['ipaddr'];
		$mapent['hostname'] = $_POST['hostname'];
		$mapent['descr'] = $_POST['descr'];
		$mapent['arp_table_static_entry'] = ($_POST['arp_table_static_entry']) ? true : false;
		$mapent['filename'] = $_POST['filename'];
		$mapent['rootpath'] = $_POST['rootpath'];
		$mapent['defaultleasetime'] = $_POST['deftime'];
		$mapent['maxleasetime'] = $_POST['maxtime'];

		unset($mapent['winsserver']);
		if ($_POST['wins1']) {
			$mapent['winsserver'][] = $_POST['wins1'];
		}
		if ($_POST['wins2']) {
			$mapent['winsserver'][] = $_POST['wins2'];
		}

		unset($mapent['dnsserver']);
		if ($_POST['dns1']) {
			$mapent['dnsserver'][] = $_POST['dns1'];
		}
		if ($_POST['dns2']) {
			$mapent['dnsserver'][] = $_POST['dns2'];
		}
		if ($_POST['dns3']) {
			$mapent['dnsserver'][] = $_POST['dns3'];
		}
		if ($_POST['dns4']) {
			$mapent['dnsserver'][] = $_POST['dns4'];
		}

		$mapent['gateway'] = $_POST['gateway'];
		$mapent['domain'] = $_POST['domain'];
		$mapent['domainsearchlist'] = $_POST['domainsearchlist'];
		$mapent['ddnsdomain'] = $_POST['ddnsdomain'];
		$mapent['ddnsdomainprimary'] = $_POST['ddnsdomainprimary'];
		$mapent['ddnsdomainkeyname'] = $_POST['ddnsdomainkeyname'];
		$mapent['ddnsdomainkey'] = $_POST['ddnsdomainkey'];
		$mapent['ddnsupdate'] = ($_POST['ddnsupdate']) ? true : false;
		$mapent['ddnsforcehostname'] = ($_POST['ddnsforcehostname']) ? true : false;

		unset($mapent['ntpserver']);
		if ($_POST['ntp1']) {
			$mapent['ntpserver'][] = $_POST['ntp1'];
		}
		if ($_POST['ntp2']) {
			$mapent['ntpserver'][] = $_POST['ntp2'];
		}

		$mapent['tftp'] = $_POST['tftp'];
		$mapent['ldap'] = $_POST['ldap'];

		if (isset($id) && $a_maps[$id]) {
			$a_maps[$id] = $mapent;
		} else {
			$a_maps[] = $mapent;
		}
		staticmaps_sort($if);

		write_config();

		if (isset($config['dhcpd'][$if]['enable'])) {
			mark_subsystem_dirty('staticmaps');
			if (isset($config['dnsmasq']['enable']) && isset($config['dnsmasq']['regdhcpstatic'])) {
				mark_subsystem_dirty('hosts');
			}
			if (isset($config['unbound']['enable']) && isset($config['unbound']['regdhcpstatic'])) {
				mark_subsystem_dirty('unbound');
			}
		}

		/* Configure static ARP entry, or remove ARP entry if this host is dynamic. See https://redmine.pfsense.org/issues/6821 */
		if ($mapent['arp_table_static_entry']) {
			mwexec("/usr/sbin/arp -S " . escapeshellarg($mapent['ipaddr']) . " " . escapeshellarg($mapent['mac']));
		} else {
			mwexec("/usr/sbin/arp -d " . escapeshellarg($mapent['ipaddr']));
		}

		header("Location: services_dhcp.php?if={$if}");
		exit;
	}
}

// Get our MAC address
$ip = $_SERVER['REMOTE_ADDR'];
$mymac = arp_get_mac_by_ip($ip, false);

$iflist = get_configured_interface_with_descr();
$ifname = '';

if (!empty($if) && isset($iflist[$if])) {
	$ifname = $iflist[$if];
}
$pgtitle = array(gettext("서비스"), gettext("DHCP 서버"), $ifname, gettext("정적 매핑 편집"));
$pglinks = array("", "services_dhcp.php", "services_dhcp.php?if={$if}", "@self");
$shortcut_section = "dhcp";

include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

$form = new Form();

$section = new Form_Section(sprintf("%s의 정적 DHCP 매핑", $ifcfgdescr));

$macaddress = new Form_Input(
	'mac',
	'MAC Address',
	'text',
	$pconfig['mac'],
	['placeholder' => 'xx:xx:xx:xx:xx:xx']
);

$btnmymac = new Form_Button(
	'btnmymac',
	'Copy My MAC',
	null,
	'fa-clone'
	);

$btnmymac->setAttribute('type','button')->removeClass('btn-primary')->addClass('btn-success btn-sm');

$group = new Form_Group('MAC Address');
$group->add($macaddress);
if (!empty($mymac)) {
	$group->add($btnmymac);
}
$group->setHelp('MAC address (6 hex octets separated by colons)');
$section->add($group);

$section->addInput(new Form_Input(
	'cid',
	'Client Identifier',
	'text',
	$pconfig['cid']
));

$section->addInput(new Form_IpAddress(
	'ipaddr',
	'IP Address',
	$pconfig['ipaddr'],
	'V4'
))->setHelp('If an IPv4 address is entered, the address must be outside of the pool.%1$s' .
			'If no IPv4 address is given, one will be dynamically allocated from the pool.%1$s%1$s' .
			'The same IP address may be assigned to multiple mappings.', '<br />');

$section->addInput(new Form_Input(
	'hostname',
	'Hostname',
	'text',
	$pconfig['hostname']
))->setHelp('Name of the host, without domain part.');

if ($netboot_enabled) {
	$section->addInput(new Form_Input(
		'filename',
		'Netboot filename',
		'text',
		$pconfig['filename']
	))->setHelp('Name of the file that should be loaded when this host boots off of the network, overrides setting on main page.');

	$section->addInput(new Form_Input(
		'rootpath',
		'Root Path',
		'text',
		$pconfig['rootpath']
	))->setHelp('Enter the root-path-string, overrides setting on main page.');
}

$section->addInput(new Form_Input(
	'descr',
	'Description',
	'text',
	$pconfig['descr']
))->setHelp('A description may be entered here for administrative reference (not parsed).');

$section->addInput(new Form_Checkbox(
	'arp_table_static_entry',
	'ARP Table Static Entry',
	'Create an ARP Table Static Entry for this MAC & IP Address pair.',
	$pconfig['arp_table_static_entry']
));

$group = new Form_Group('WINS Servers');

$group->add(new Form_Input(
	'wins1',
	null,
	'text',
	$pconfig['wins1'],
	['placeholder' => 'WINS 1']
));

$group->add(new Form_Input(
	'wins2',
	null,
	'text',
	$pconfig['wins2'],
	['placeholder' => 'WINS 2']
));

$section->add($group);
$group = new Form_Group('DNS Servers');

$group->add(new Form_Input(
	'dns1',
	null,
	'text',
	$pconfig['dns1'],
	['placeholder' => 'DNS 1']
));

$group->add(new Form_Input(
	'dns2',
	null,
	'text',
	$pconfig['dns2'],
	['placeholder' => 'DNS 2']
));

$group->add(new Form_Input(
	'dns3',
	null,
	'text',
	$pconfig['dns3'],
	['placeholder' => 'DNS 3']
));

$group->add(new Form_Input(
	'dns4',
	null,
	'text',
	$pconfig['dns4'],
	['placeholder' => 'DNS 4']
));

$group->setHelp('Note: leave blank to use the system default DNS servers - this interface\'s IP if DNS Forwarder or Resolver is enabled, otherwise the servers configured on the General page.');

$section->add($group);

$section->addInput(new Form_Input(
	'gateway',
	'Gateway',
	'text',
	$pconfig['gateway']
))->setHelp('The default is to use the IP on this interface of the firewall as the gateway. Specify an alternate gateway here if this is not the correct gateway for the network.');

$section->addInput(new Form_Input(
	'domain',
	'Domain name',
	'text',
	$pconfig['domain']
))->setHelp('The default is to use the domain name of this system as the default domain name provided by DHCP. An alternate domain name may be specified here. ');

$section->addInput(new Form_Input(
	'domainsearchlist',
	'Domain search list',
	'text',
	$pconfig['domainsearchlist']
))->setHelp('The DHCP server can optionally provide a domain search list. Use the semicolon character as separator.');

$section->addInput(new Form_Input(
	'deftime',
	'Default lease time (Seconds)',
	'text',
	$pconfig['deftime']
))->setHelp('Used for clients that do not ask for a specific expiration time. The default is 7200 seconds.');

$section->addInput(new Form_Input(
	'maxtime',
	'Maximum lease time (Seconds)',
	'text',
	$pconfig['maxtime']
))->setHelp('This is the maximum lease time for clients that ask for a specific expiration time. The default is 86400 seconds.');

$btnadv = new Form_Button(
	'btnadvdns',
	'Display Advanced',
	null,
	'fa-cog'
);

$btnadv->setAttribute('type','button')->addClass('btn-info btn-sm');

$section->addInput(new Form_StaticText(
	'Dynamic DNS',
	$btnadv
));

$section->addInput(new Form_Checkbox(
	'ddnsupdate',
	'DHCP Registration',
	'Enable registration of DHCP client names in DNS.',
	$pconfig['ddnsupdate']
));

$section->addInput(new Form_Checkbox(
	'ddnsforcehostname',
	'DDNS Hostname',
	'Make dynamic DNS registered hostname the same as Hostname above.',
	$pconfig['ddnsforcehostname']
));

$section->addInput(new Form_Input(
	'ddnsdomain',
	'DDNS Domain',
	'text',
	$pconfig['ddnsdomain']
))->setHelp('Leave blank to disable dynamic DNS registration. Enter the dynamic DNS domain which will be used to register client names in the DNS server.');

$section->addInput(new Form_IpAddress(
	'ddnsdomainprimary',
	'DDNS Server IP',
	$pconfig['ddnsdomainprimary'],
	'V4'
))->setHelp('Enter the primary domain name server IP address for the dynamic domain name.');

$section->addInput(new Form_Input(
	'ddnsdomainkeyname',
	'DDNS Domain Key name',
	'text',
	$pconfig['ddnsdomainkeyname']
))->setHelp('Enter the dynamic DNS domain key name which will be used to register client names in the DNS server.');

$section->addInput(new Form_Input(
	'ddnsdomainkey',
	'DDNS Domain Key secret',
	'text',
	$pconfig['ddnsdomainkey']
))->setHelp('Enter the dynamic DNS domain key secret which will be used to register client names in the DNS server.');

$btnadv = new Form_Button(
	'btnadvntp',
	'Display Advanced',
	null,
	'fa-cog'
);

$btnadv->setAttribute('type','button')->addClass('btn-info btn-sm');

$section->addInput(new Form_StaticText(
	'NTP servers',
	$btnadv
));

$group = new Form_Group('NTP Servers');

$group->add(new Form_Input(
	'ntp1',
	'NTP Server 1',
	'text',
	$pconfig['ntp1'],
	['placeholder' => 'NTP 1']
));

$group->add(new Form_Input(
	'ntp2',
	'NTP Server 2',
	'text',
	$pconfig['ntp2'],
	['placeholder' => 'NTP 2']
));

$group->addClass('ntpclass');

$section->add($group);

$btnadv = new Form_Button(
	'btnadvtftp',
	'Display Advanced',
	null,
	'fa-cog'
);

$btnadv->setAttribute('type','button')->addClass('btn-info btn-sm');

$section->addInput(new Form_StaticText(
	'TFTP servers',
	$btnadv
));

$section->addInput(new Form_Input(
	'tftp',
	'TFTP Server',
	'text',
	$pconfig['tftp']
))->setHelp('Leave blank to disable. Enter a full hostname or IP for the TFTP server.');

$form->add($section);
print($form);
?>

<script type="text/javascript">
//<![CDATA[
events.push(function() {

	// Show advanced DNS options ======================================================================================
	var showadvdns = false;

	function show_advdns(ispageload) {
		var text;
		// On page load decide the initial state based on the data.
		if (ispageload) {
<?php
			if (!$pconfig['ddnsupdate'] && !$pconfig['ddnsforcehostname'] && empty($pconfig['ddnsdomain']) && empty($pconfig['ddnsdomainprimary']) &&
			    empty($pconfig['ddnsdomainkeyname']) && empty($pconfig['ddnsdomainkey'])) {
				$showadv = false;
			} else {
				$showadv = true;
			}
?>
			showadvdns = <?php if ($showadv) {echo 'true';} else {echo 'false';} ?>;
		} else {
			// It was a click, swap the state.
			showadvdns = !showadvdns;
		}

		hideCheckbox('ddnsupdate', !showadvdns);
		hideCheckbox('ddnsforcehostname', !showadvdns);
		hideInput('ddnsdomain', !showadvdns);
		hideInput('ddnsdomainprimary', !showadvdns);
		hideInput('ddnsdomainkeyname', !showadvdns);
		hideInput('ddnsdomainkey', !showadvdns);

		if (showadvdns) {
			text = "<?=gettext('어드밴스드 숨기기');?>";
		} else {
			text = "<?=gettext('어드밴스드 표시');?>";
		}
		$('#btnadvdns').html('<i class="fa fa-cog"></i> ' + text);
	}

	$('#btnadvdns').click(function(event) {
		show_advdns();
	});

	// Show advanced NTP options ======================================================================================
	var showadvntp = false;

	function show_advntp(ispageload) {
		var text;
		// On page load decide the initial state based on the data.
		if (ispageload) {
<?php
			if (empty($pconfig['ntp1']) && empty($pconfig['ntp2'])) {
				$showadv = false;
			} else {
				$showadv = true;
			}
?>
			showadvntp = <?php if ($showadv) {echo 'true';} else {echo 'false';} ?>;
		} else {
			// It was a click, swap the state.
			showadvntp = !showadvntp;
		}

		hideInput('ntp1', !showadvntp);
		hideInput('ntp2', !showadvntp);

		if (showadvntp) {
			text = "<?=gettext('어드밴스드 숨기기');?>";
		} else {
			text = "<?=gettext('어드밴스드 보이기');?>";
		}
		$('#btnadvntp').html('<i class="fa fa-cog"></i> ' + text);
	}

	$('#btnadvntp').click(function(event) {
		show_advntp();
	});

	// Show advanced TFTP options ======================================================================================
	var showadvtftp = false;

	function show_advtftp(ispageload) {
		var text;
		// On page load decide the initial state based on the data.
		if (ispageload) {
<?php
			if (empty($pconfig['tftp'])) {
				$showadv = false;
			} else {
				$showadv = true;
			}
?>
			showadvtftp = <?php if ($showadv) {echo 'true';} else {echo 'false';} ?>;
		} else {
			// It was a click, swap the state.
			showadvtftp = !showadvtftp;
		}

		hideInput('tftp', !showadvtftp);

		if (showadvtftp) {
			text = "<?=gettext('어드밴스드 숨기기');?>";
		} else {
			text = "<?=gettext('어드밴스드 보이기');?>";
		}
		$('#btnadvtftp').html('<i class="fa fa-cog"></i> ' + text);
	}

	$('#btnadvtftp').click(function(event) {
		show_advtftp();
	});

	// On click, copy the hidden 'mymac' text to the 'mac' input
	$("#btnmymac").click(function() {
		$('#mac').val('<?=$mymac?>');
	});

	// On initial load
	show_advdns(true);
	show_advntp(true);
	show_advtftp(true);
});
//]]>
</script>

<?php include("foot.inc");

<?php
/*
 * services_unbound_host_edit.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2018 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2014 Warren Baker (warren@decoy.co.za)
 * Copyright (c) 2003-2005 Bob Zoller <bob@kludgebox.com>
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
2018.03.14
한글화 번역 시작
*/

##|+PRIV
##|*IDENT=page-services-dnsresolver-edithost
##|*NAME=Services: DNS Resolver: Edit host
##|*DESCR=Allow access to the 'Services: DNS Resolver: Edit host' page.
##|*MATCH=services_unbound_host_edit.php*
##|-PRIV

function hostcmp($a, $b) {
	return strcasecmp($a['host'], $b['host']);
}

function hosts_sort() {
	global $g, $config;

	if (!is_array($config['unbound']['hosts'])) {
		return;
	}

	usort($config['unbound']['hosts'], "hostcmp");
}

require_once("guiconfig.inc");

if (!is_array($config['unbound']['hosts'])) {
	$config['unbound']['hosts'] = array();
}

$a_hosts = &$config['unbound']['hosts'];
$id = $_REQUEST['id'];

if (isset($id) && $a_hosts[$id]) {
	$pconfig['host'] = $a_hosts[$id]['host'];
	$pconfig['domain'] = $a_hosts[$id]['domain'];
	$pconfig['ip'] = $a_hosts[$id]['ip'];
	$pconfig['descr'] = $a_hosts[$id]['descr'];
	$pconfig['aliases'] = $a_hosts[$id]['aliases'];
}

if ($_POST['save']) {
	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "domain ip");
	$reqdfieldsn = array(gettext("도메인"), gettext("IP주소"));

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

	if ($_POST['host']) {
		if (!is_hostname($_POST['host'])) {
			$input_errors[] = gettext("호스트 이름에는 A-Z, 0-9,'_'및'-'만 포함할 수 있습니다. '-'로 시작하거나 끝나지 않을 수 있습니다.");
		} else {
			if (!is_unqualified_hostname($_POST['host'])) {
				$input_errors[] = gettext("올바른 호스트 이름이 지정되었지만 도메인 이름 부분은 생략해야 합니다.");
			}
		}
	}

	if (($_POST['domain'] && !is_domain($_POST['domain']))) {
		$input_errors[] = gettext("올바른 도메인을 지정해야 합니다.");
	}

	if (($_POST['ip'] && !is_ipaddr($_POST['ip']))) {
		$input_errors[] = gettext("올바른 IP주소를 지정해야 합니다.");
	}

	/* collect aliases */
	$aliases = array();
	if (!empty($_POST['aliashost0'])) {
		foreach ($_POST as $key => $value) {
			$entry = '';
			if (!substr_compare('aliashost', $key, 0, 9)) {
				$entry = substr($key, 9);
				$field = 'host';
			} elseif (!substr_compare('aliasdomain', $key, 0, 11)) {
				$entry = substr($key, 11);
				$field = 'domain';
			} elseif (!substr_compare('aliasdescription', $key, 0, 16)) {
				$entry = substr($key, 16);
				$field = 'description';
			}
			if (ctype_digit($entry)) {
				$aliases[$entry][$field] = $value;
			}
		}

		$pconfig['aliases']['item'] = $aliases;

		/* validate aliases */
		foreach ($aliases as $idx => $alias) {
			$aliasreqdfields = array('aliasdomain' . $idx);
			$aliasreqdfieldsn = array(gettext("Alias Domain"));

			do_input_validation($_POST, $aliasreqdfields, $aliasreqdfieldsn, $input_errors);

			if ($alias['host']) {
				if (!is_hostname($alias['host'])) {
					$input_errors[] = gettext("not start or end with '-'.");
				} else {
					if (!is_unqualified_hostname($alias['host'])) {
						$input_errors[] = gettext("별칭 목록의 호스트 이름에는 A-Z, 0-9및'-'문자만 포함될 수 있습니다. '-'로 시작하거나 끝나지 않습니다.");
					}
				}
			}
			if (($alias['domain'] && !is_domain($alias['domain']))) {
				$input_errors[] = gettext("별칭 목록에 올바른 도메인을 지정해야 합니다.");
			}
		}
	}

	/* check for overlaps */
	foreach ($a_hosts as $hostent) {
		if (isset($id) && ($a_hosts[$id]) && ($a_hosts[$id] === $hostent)) {
			continue;
		}

		if (($hostent['host'] == $_POST['host']) &&
		    ($hostent['domain'] == $_POST['domain'])) {
			if (is_ipaddrv4($hostent['ip']) && is_ipaddrv4($_POST['ip'])) {
				$input_errors[] = gettext("이 호스트/도메인 재정의 조합이 IPv4주소와 함께 이미 있습니다.");
				break;
			}
			if (is_ipaddrv6($hostent['ip']) && is_ipaddrv6($_POST['ip'])) {
				$input_errors[] = gettext("이 호스트/도메인 재정의 조합이 IPv6주소와 함께 이미 있습니다.");
				break;
			}
		}
	}

	if (!$input_errors) {
		$hostent = array();
		$hostent['host'] = $_POST['host'];
		$hostent['domain'] = $_POST['domain'];
		$hostent['ip'] = $_POST['ip'];
		$hostent['descr'] = $_POST['descr'];
		$hostent['aliases']['item'] = $aliases;

		if (isset($id) && $a_hosts[$id]) {
			$a_hosts[$id] = $hostent;
		} else {
			$a_hosts[] = $hostent;
		}
		hosts_sort();

		mark_subsystem_dirty('unbound');

		write_config(gettext("DNS확인자에 대해 구성된 호스트 재정의입니다."));

		header("Location: services_unbound.php");
		exit;
	}
}

$pgtitle = array(gettext("Services"), gettext("DNS 확인자"), gettext("일반세팅"), gettext("호스트 오버라이드 편집"));
$pglinks = array("", "services_unbound.php", "services_unbound.php", "@self");
$shortcut_section = "resolver";
include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

$form = new Form();

$section = new Form_Section('Host Override Options');

$section->addInput(new Form_Input(
	'host',
	'Host',
	'text',
	$pconfig['host']
))->setHelp('Name of the host, without the domain part%1$s' .
			'e.g. enter "myhost" if the full domain name is "myhost.example.com"', '<br />');

$section->addInput(new Form_Input(
	'domain',
	'*Domain',
	'text',
	$pconfig['domain']
))->setHelp('Parent domain of the host%1$s' .
			'e.g. enter "example.com" for "myhost.example.com"', '<br />');

$section->addInput(new Form_IpAddress(
	'ip',
	'*IP Address',
	$pconfig['ip']
))->setHelp('IPv4 or IPv6 address to be returned for the host%1$s' .
			'e.g.: 192.168.100.100 or fd00:abcd::1', '<br />');

$section->addInput(new Form_Input(
	'descr',
	'Description',
	'text',
	$pconfig['descr']
))->setHelp('A description may be entered here for administrative reference (not parsed).');

if (isset($id) && $a_hosts[$id]) {
	$section->addInput(new Form_Input(
		'id',
		null,
		'hidden',
		$id
	));
}

$section->addInput(new Form_StaticText(
	'',
	'<span class="help-block">' .
	gettext("이 페이지는 특정 호스트에 대한 일반적인 검색 프로세스를 재정의하는 데 사용됩니다. 호스트는 이름과 상위 도메인에 의해 정의됩니다" .
		"(예:'somesite.google.com'이 호스트='somesite'및 상위 도메인='google.com'으로 입력됨). " .
		"해당 호스트를 검색하려고하면 지정된 IP 주소가 자동으로 반환되고 도메인의 일반적인 외부 조회 서버는 쿼리되지 않습니다. " .
		"이름과 상위 도메인 모두 'test', 'mycompany.localdomain'또는 '1.168.192.in-addr.arpa'와 같은  " .
		"'non-standard', 'invalid'및 'local'도메인뿐만 아니라 'www'또는 'google.co.uk'과 같이 공개적으로 확인할 수있는 이름을 포함 할 수 있습니다.") .
	'</span>'
));

$form->add($section);

$section = new Form_Section('Additional Names for this Host');

if (!$pconfig['aliases']['item']) {
	$pconfig['aliases']['item'] = array('host' => "");
}

if ($pconfig['aliases']['item']) {
	$counter = 0;
	$last = count($pconfig['aliases']['item']) - 1;

	foreach ($pconfig['aliases']['item'] as $item) {
		$group = new Form_Group(null);
		$group->addClass('repeatable');

		$group->add(new Form_Input(
			'aliashost' . $counter,
			null,
			'text',
			$item['host']
		))->setHelp($counter == $last ? 'Host name':null);

		$group->add(new Form_Input(
			'aliasdomain' . $counter,
			null,
			'text',
			$item['domain']
		))->setHelp($counter == $last ? 'Domain':null);

		$group->add(new Form_Input(
			'aliasdescription' . $counter,
			null,
			'text',
			$item['description']
		))->setHelp($counter == $last ? 'Description':null);

		$group->add(new Form_Button(
			'deleterow' . $counter,
			'Delete',
			null,
			'fa-trash'
		))->addClass('btn-warning')->addClass('nowarn');

		$section->add($group);
		$counter++;
	}
}

$form->addGlobal(new Form_Button(
	'addrow',
	'Add Host Name',
	null,
	'fa-plus'
))->removeClass('btn-primary')->addClass('btn-success addbtn');

$section->addInput(new Form_StaticText(
	'',
	'<span class="help-block">'.
	gettext("여러 이름을 사용하여 호스트에 액세스 할 수 있으면 대체해야하는 호스트의 다른 이름을 입력하십시오.") .
	'</span>'
));

$form->add($section);
print($form);

include("foot.inc");

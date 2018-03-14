<?php
/*
 * services_unbound_domainoverride_edit.php
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
##|*IDENT=page-services-dnsresolver-editdomainoverride
##|*NAME=Services: DNS Resolver: Edit Domain Override
##|*DESCR=Allow access to the 'Services: DNS Resolver: Edit Domain Override' page.
##|*MATCH=services_unbound_domainoverride_edit.php*
##|-PRIV

require_once("guiconfig.inc");

if (!is_array($config['unbound']['domainoverrides'])) {
	$config['unbound']['domainoverrides'] = array();
}

$a_domainOverrides = &$config['unbound']['domainoverrides'];
$id = $_REQUEST['id'];

if (isset($id) && $a_domainOverrides[$id]) {
	$pconfig['domain'] = $a_domainOverrides[$id]['domain'];
	$pconfig['ip'] = $a_domainOverrides[$id]['ip'];
	$pconfig['descr'] = $a_domainOverrides[$id]['descr'];
}

if ($_POST['save']) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "domain ip");
	$reqdfieldsn = array(gettext("도메인"), gettext("IP주소"));

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

	function String_Begins_With($needle, $haystack) {
		return (substr($haystack, 0, strlen($needle)) == $needle);
	}

	if (String_Begins_With(_msdcs, $_POST['domain'])) {
		$subdomainstr = substr($_POST['domain'], 7);
		if ($subdomainstr && !is_domain($subdomainstr)) {
			$input_errors[] = gettext("유효한 도메인은 _msks 다음에 지정되어야 합니다.");
		}
	} elseif ($_POST['domain'] && !is_domain($_POST['domain'])) {
		$input_errors[] = gettext("올바른 도메인을 지정해야 합니다.");
	}

	if ($_POST['ip']) {
		if (strpos($_POST['ip'], '@') !== false) {
			$ip_details = explode("@", $_POST['ip']);
			if (!is_ipaddr($ip_details[0]) || !is_port($ip_details[1])) {
				$input_errors[] = gettext("예를 들어 192.168.100.10@5353과 같이 올바른 IP주소와 포트를 지정해야 합니다.");
			}
		} else if (!is_ipaddr($_POST['ip'])) {
			$input_errors[] = gettext("예를 들어 192.168.100.10과 같이 올바른 IP주소를 지정해야 합니다.");
		}
	}

	if (!$input_errors) {
		$doment = array();
		$doment['domain'] = $_POST['domain'];
		$doment['ip'] = $_POST['ip'];
		$doment['descr'] = $_POST['descr'];

		if (isset($id) && $a_domainOverrides[$id]) {
			$a_domainOverrides[$id] = $doment;
		} else {
			$a_domainOverrides[] = $doment;
		}

		mark_subsystem_dirty('unbound');

		write_config(gettext("DNS확인자에 대해 도메인 재정의가 구성되었습니다."));

		header("Location: services_unbound.php");
		exit;
	}
}

$pgtitle = array(gettext("Services"), gettext("DNS 확인자"), gettext("일반 설정"), gettext("도메인 재정의 편집"));
$pglinks = array("", "services_unbound.php", "services_unbound.php", "@self");
$shortcut_section = "resolver";
include("head.inc");

if ($input_errors) {
        print_input_errors($input_errors);
}

$form = new Form();

$section = new Form_Section('Domains to Override with Custom Lookup Servers');

$section->addInput(new Form_Input(
	'domain',
	'*Domain',
	'text',
	$pconfig['domain']
))->setHelp('Domain whose lookups will be directed to a user-specified DNS lookup server.');

$section->addInput(new Form_IpAddress(
	'ip',
	'*IP Address',
	$pconfig['ip']
))->setHelp('IPv4 or IPv6 address of the authoritative DNS server for this domain. e.g.: 192.168.100.100%1$s' .
			'To use a non-default port for communication, append an \'@\' with the port number.', '<br />')->setPattern('[a-zA-Z0-9@.:]+');

$section->addInput(new Form_Input(
	'descr',
	'Description',
	'text',
	$pconfig['descr']
))->setHelp('A description may be entered here for administrative reference (not parsed).');

if (isset($id) && $a_domainOverrides[$id]) {
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
	gettext("이 페이지는 해결 프로그램의 표준 DNS조회 프로세스가 재정의될 도메인을 지정하는 데 사용되며, 해결 프로그램에서 대신 다른(비표준)조회 서버를 쿼리 합니다. " .
	"'test', 'mycompany.localdomain'또는 '1.168.192.in-addr.arpa'과 같은 'non-standard', 'invalid'및 'local'도메인은 물론 'org', 'info', " .
	"'google.co.uk'과 같이 공개적으로 확인할 수있는 도메인을 입력 할 수도 있습니다. 입력 된 IP 주소는 도메인 (모든 하위 도메인 포함)의 " .
	"신뢰할 수있는 조회 서버의 IP 주소로 취급되며 다른 조회 서버는 쿼리되지 않습니다.") .
	'</span>'
));

$form->add($section);

print $form;

include("foot.inc");

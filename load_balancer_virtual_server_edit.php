<?php
/*
 * load_balancer_virtual_server_edit.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2018 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2005-2008 Bill Marquette <bill.marquette@gmail.com>
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
한글화 번역 추가
*/

##|+PRIV
##|*IDENT=page-loadbalancer-virtualserver-edit
##|*NAME=Load Balancer: Virtual Server: Edit
##|*DESCR=Allow access to the 'Load Balancer: Virtual Server: Edit' page.
##|*MATCH=load_balancer_virtual_server_edit.php*
##|-PRIV

require_once("guiconfig.inc");

if (isset($_POST['referer'])) {
	$referer = $_POST['referer'];
} else {
	$referer = (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/load_balancer_virtual_server.php');
}

if (!is_array($config['load_balancer']['virtual_server'])) {
	$config['load_balancer']['virtual_server'] = array();
}

$a_vs = &$config['load_balancer']['virtual_server'];
$id = $_REQUEST['id'];

if (isset($id) && $a_vs[$id]) {
  $pconfig = $a_vs[$id];
} else {
  // Sane defaults
  $pconfig['mode'] = 'redirect_mode';
}

$changedesc = gettext("로드 밸런서: 가상 서버:") . " ";
$changecount = 0;

$allowed_protocols = array("tcp", "dns");

if ($_POST['save']) {
	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	switch ($pconfig['mode']) {
		case "redirect_mode": {
			$reqdfields = explode(" ", "ipaddr name mode");
			$reqdfieldsn = array(gettext("IP 주소"), gettext("Name"), gettext("Mode"));
			break;
		}
		case "relay_mode": {
			$reqdfields = explode(" ", "ipaddr name mode relay_protocol");
			$reqdfieldsn = array(gettext("IP 주소"), gettext("Name"), gettext("Mode"), gettext("릴레이 프로토콜"));
			break;
		}
	}

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

	for ($i = 0; isset($config['load_balancer']['virtual_server'][$i]); $i++) {
		if (($_POST['name'] == $config['load_balancer']['virtual_server'][$i]['name']) && ($i != $id)) {
			$input_errors[] = gettext("동일한 이름의 가상서버가 존재합니다.");
		}
	}

	if (preg_match('/[ \/]/', $_POST['name'])) {
		$input_errors[] = gettext("이름에 공백 또는 슬래시는 사용할 수 없습니다.");
	}

	if (strlen($_POST['name']) > 32) {
		$input_errors[] = gettext("이름은 32자 이하로 설정해주십시오.");
	}

	if ($_POST['port'] != "" && !is_port_or_alias($_POST['port'])) {
		$input_errors[] = gettext("포트는 1에서 65535사이의 정수, 포트 별칭 또는 공백이어야 합니다.");
	}

	if (!is_ipaddroralias($_POST['ipaddr']) && !is_subnetv4($_POST['ipaddr'])) {
		$input_errors[] = sprintf(gettext("%s은(는) 올바른 IP주소, IPv4서브넷 또는 별칭이 아닙니다."), $_POST['ipaddr']);
	} else if (is_subnetv4($_POST['ipaddr']) && subnet_size($_POST['ipaddr']) > 64) {
		$input_errors[] = sprintf(gettext("%s은(는)64개 이상의 IP주소를 포함하는 서브넷입니다."), $_POST['ipaddr']);
	}

	if (!in_array($_POST['relay_protocol'], $allowed_protocols)) {
		$input_errors[] = gettext("제출된 릴레이 프로토콜이 유효하지 않습니다.");
	}

	if ((strtolower($_POST['relay_protocol']) == "dns") && !empty($_POST['sitedown'])) {
		$input_errors[] = gettext("DNS릴레이 프로토콜을 사용하는 경우에는 FallBackPool을 선택할 수 없습니다.");
	}

	if (!$input_errors) {
		$vsent = array();
		if (isset($id) && $a_vs[$id]) {
			$vsent = $a_vs[$id];
		}
		if ($vsent['name'] != "") {
			$changedesc .= " " . sprintf(gettext("modified '%s' vs:"), $vsent['name']);
		} else {
			$changedesc .= " " . sprintf(gettext("created '%s' vs:"), $_POST['name']);
		}

		update_if_changed("name", $vsent['name'], $_POST['name']);
		update_if_changed("descr", $vsent['descr'], $_POST['descr']);
		update_if_changed("poolname", $vsent['poolname'], $_POST['poolname']);
		update_if_changed("port", $vsent['port'], $_POST['port']);
		update_if_changed("sitedown", $vsent['sitedown'], $_POST['sitedown']);
		update_if_changed("ipaddr", $vsent['ipaddr'], $_POST['ipaddr']);
		update_if_changed("mode", $vsent['mode'], $_POST['mode']);
		update_if_changed("relay protocol", $vsent['relay_protocol'], $_POST['relay_protocol']);

		if ($_POST['sitedown'] == "") {
			unset($vsent['sitedown']);
		}

		if (isset($id) && $a_vs[$id]) {
			if ($a_vs[$id]['name'] != $_POST['name']) {
				/* Because the VS name changed, mark the old name for cleanup. */
				cleanup_lb_mark_anchor($a_vs[$id]['name']);
			}
			$a_vs[$id] = $vsent;
		} else {
			$a_vs[] = $vsent;
		}

		if ($changecount > 0) {
			/* Mark virtual server dirty */
			mark_subsystem_dirty('loadbalancer');
			write_config($changedesc);
		}

		header("Location: load_balancer_virtual_server.php");
		exit;
	}
}

$pgtitle = array(gettext("Services"), gettext("로드 밸런서"), gettext("가상서버"), gettext(""));
$pglinks = array("", "load_balancer_pool.php", "load_balancer_virtual_server.php", "@self");
$shortcut_section = "relayd-virtualservers";

include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

$form = new Form();

$form->setAction("load_balancer_virtual_server_edit.php");

$section = new Form_Section('로드 밸런서 편집 - 가상 서버 항목');

$section->addInput(new Form_Input(
	'name',
	'*Name',
	'text',
	$pconfig['name']
));

$section->addInput(new Form_Input(
	'descr',
	'Description',
	'text',
	$pconfig['descr']
));

$section->addInput(new Form_Input(
	'ipaddr',
	'*IP Address',
	'text',
	$pconfig['ipaddr']
))->setHelp('This is normally the WAN IP address for the server to listen on. ' .
			'All connections to this IP and port will be forwarded to the pool cluster. ' .
			'A host alias listed in Firewall -&gt; Aliases may also be specified here.');

$section->addInput(new Form_Input(
	'port',
	'Port',
	'number',
	$pconfig['port']
))->setHelp('Port that the clients will connect to. All connections to this port will be forwarded to the pool cluster. ' .
			'If left blank listening ports from the pool will be used.' . " " .
			'A port alias listed in Firewall -&gt; Aliases may also be specified here.');

if (count($config['load_balancer']['lbpool']) == 0) {
	$section->addInput(new Form_StaticText(
		'Virtual Server Pool',
		'Please add a pool on the "Pools" tab to use this feature. '
	));
} else {

	$list = array();
	for ($i = 0; isset($config['load_balancer']['lbpool'][$i]); $i++) {
		$list[$config['load_balancer']['lbpool'][$i]['name']] = $config['load_balancer']['lbpool'][$i]['name'];
	}

	$section->addInput(new Form_Select(
		'poolname',
		'Virtual Server Pool',
		$pconfig['poolname'],
		$list
	));
}

if (count($config['load_balancer']['lbpool']) == 0) {
	$section->addInput(new Form_StaticText(
		'Fall-back Pool',
		'Please add a pool on the "Pools" tab to use this feature. '
	));
} else {
	$list = array();
	for ($i = 0; isset($config['load_balancer']['lbpool'][$i]); $i++) {
		$list[$config['load_balancer']['lbpool'][$i]['name']] = $config['load_balancer']['lbpool'][$i]['name'];
	}

	$section->addInput(new Form_Select(
		'sitedown',
		'Fall-back Pool',
		$pconfig['sitedown'],
		["" => "None"] + $list
	));
}

$section->addInput(new Form_Input(
	'mode',
	null,
	'hidden',
	'redirect_mode'
));

$section->addInput(new Form_Select(
	'relay_protocol',
	'Relay Protocol',
	$pconfig['relay_protocol'],
	['tcp' => 'TCP', 'dns' => 'DNS']
));

if (isset($id) && $a_vs[$id] && $_REQUEST['act'] != 'dup') {
	$section->addInput(new Form_Input(
		'id',
		null,
		'hidden',
		$id
	));
}

$form->add($section);
print($form);

print_info_box(gettext('설정을 마치면 가상 서버/풀에 대한 방화벽 규칙을 추가하는 것을 잊지 마십시오.'));
?>
<script type="text/javascript">
//<![CDATA[
events.push(function() {
    // --------- Autocomplete -----------------------------------------------------------------------------------------
    var addressarray = <?= json_encode(get_alias_list(array("host", "network", "openvpn", "urltable"))) ?>;
    var customarray = <?= json_encode(get_alias_list(array("port", "url_ports", "urltable_ports"))) ?>;

    $('#ipaddr').autocomplete({
        source: addressarray
    });

    $('#port').autocomplete({
        source: customarray
    });
});
//]]>
</script>
<?php
include("foot.inc");

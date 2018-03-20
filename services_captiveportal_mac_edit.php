<?php
/*
 * services_captiveportal_mac_edit.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2018 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2004 Dinesh Nair <dinesh@alphaque.com>
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
2018.03.08
한글화 번역 시작
*/

##|+PRIV
##|*IDENT=page-services-captiveportal-editmacaddresses
##|*NAME=Services: Captive Portal: Edit MAC Addresses
##|*DESCR=Allow access to the 'Services: Captive Portal: Edit MAC Addresses' page.
##|*MATCH=services_captiveportal_mac_edit.php*
##|-PRIV

function passthrumacscmp($a, $b) {
	return strcmp($a['mac'], $b['mac']);
}

function passthrumacs_sort() {
	global $config, $cpzone;

	usort($config['captiveportal'][$cpzone]['passthrumac'], "passthrumacscmp");
}

require_once("guiconfig.inc");
require_once("functions.inc");
require_once("filter.inc");
require_once("shaper.inc");
require_once("captiveportal.inc");

global $cpzone;
global $cpzoneid;

$cpzone = strtolower(htmlspecialchars($_REQUEST['zone']));

if (empty($cpzone) || empty($config['captiveportal'][$cpzone])) {
	header("Location: services_captiveportal_zones.php");
	exit;
}

if (!is_array($config['captiveportal'])) {
	$config['captiveportal'] = array();
}

$a_cp =& $config['captiveportal'];

$pgtitle = array(gettext("Services"), gettext("Captive Portal"), $a_cp[$cpzone]['zone'], gettext("MACs"), gettext("Edit"));
$pglinks = array("", "services_captiveportal_zones.php", "services_captiveportal.php?zone=" . $cpzone, "services_captiveportal_mac.php?zone=" . $cpzone, "@self");
$shortcut_section = "captiveportal";

if (is_numericint($_REQUEST['id'])) {
	$id = $_REQUEST['id'];
}

if (!is_array($a_cp[$cpzone]['passthrumac'])) {
	$a_cp[$cpzone]['passthrumac'] = array();
}

$a_passthrumacs = &$a_cp[$cpzone]['passthrumac'];

if (isset($id) && $a_passthrumacs[$id]) {
	$pconfig['action'] = $a_passthrumacs[$id]['action'];
	$pconfig['mac'] = $a_passthrumacs[$id]['mac'];
	$pconfig['bw_up'] = $a_passthrumacs[$id]['bw_up'];
	$pconfig['bw_down'] = $a_passthrumacs[$id]['bw_down'];
	$pconfig['descr'] = $a_passthrumacs[$id]['descr'];
	$pconfig['username'] = $a_passthrumacs[$id]['username'];
}

if ($_POST['save']) {
	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "action mac");
	$reqdfieldsn = array(gettext("Action"), gettext("MAC 주소"));

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

	$_POST['mac'] = strtolower(str_replace("-", ":", $_POST['mac']));

	if ($_POST['mac']) {
		if (is_macaddr($_POST['mac'])) {
			$iflist = get_interface_list();
			foreach ($iflist as $if) {
				if ($_POST['mac'] == strtolower($if['mac'])) {
					$input_errors[] = sprintf(gettext("MAC 주소 %s은(는) 로컬 인터페이스에 속합니다. 여기서는 사용할 수 없습니다."), $_POST['mac']);
					break;
				}
			}
		} else {
			$input_errors[] = sprintf(gettext('유효한 MAC 주소를 지정해야합니다.[%s]'), $_POST['mac']);
		}
	}
	if ($_POST['bw_up'] && !is_numeric($_POST['bw_up'])) {
		$input_errors[] = gettext("업로드 속도는 정수 여야합니다.");
	}
	if ($_POST['bw_down'] && !is_numeric($_POST['bw_down'])) {
		$input_errors[] = gettext("다운로드 속도는 정수 여야합니다.");
	}
	if ($_POST['bw_up'] && ($_POST['bw_up'] > 999999 || $_POST['bw_up'] < 1)) {
		$input_errors[] = gettext("업로드 속도는 1에서 999999사이 여야합니다.");
	}
	if ($_POST['bw_down'] && ($_POST['bw_down'] > 999999 || $_POST['bw_down'] < 1)) {
		$input_errors[] = gettext("다운로드 속도는 1에서 999999사이 여야합니다.");
	}

	foreach ($a_passthrumacs as $macent) {
		if (isset($id) && ($a_passthrumacs[$id]) && ($a_passthrumacs[$id] === $macent)) {
			continue;
		}

		if ($macent['mac'] == $_POST['mac']) {
			$input_errors[] = sprintf(gettext('[%s]는 이미 존재합니다.'), $_POST['mac']);
			break;
		}
	}

	if (!$input_errors) {
		$mac = array();
		$mac['action'] = $_POST['action'];
		$mac['mac'] = $_POST['mac'];
		if ($_POST['bw_up']) {
			$mac['bw_up'] = $_POST['bw_up'];
		}
		if ($_POST['bw_down']) {
			$mac['bw_down'] = $_POST['bw_down'];
		}
		if ($_POST['username']) {
			$mac['username'] = $_POST['username'];
		}

		$mac['descr'] = $_POST['descr'];

		if (isset($id) && $a_passthrumacs[$id]) {
			$oldmac = $a_passthrumacs[$id];
			$a_passthrumacs[$id] = $mac;
		} else {
			$oldmac = $mac;
			$a_passthrumacs[] = $mac;
		}
		passthrumacs_sort();

		write_config();

		if (isset($config['captiveportal'][$cpzone]['enable'])) {
			$cpzoneid = $config['captiveportal'][$cpzone]['zoneid'];
			$rules = captiveportal_passthrumac_delete_entry($oldmac);
			$rules .= captiveportal_passthrumac_configure_entry($mac);
			$uniqid = uniqid("{$cpzone}_macedit");
			file_put_contents("{$g['tmp_path']}/{$uniqid}_tmp", $rules);
			mwexec("/sbin/ipfw -q {$g['tmp_path']}/{$uniqid}_tmp");
			@unlink("{$g['tmp_path']}/{$uniqid}_tmp");
			unset($cpzoneid);
		}

		header("Location: services_captiveportal_mac.php?zone={$cpzone}");
		exit;
	}
}

// Get the MAC address
$ip = $_SERVER['REMOTE_ADDR'];
$mymac = `/usr/sbin/arp -an | grep '('{$ip}')' | head -n 1 | cut -d" " -f4`;
$mymac = str_replace("\n", "", $mymac);

include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

$form = new Form();

$section = new Form_Section('MAC 주소 룰 편집');

$section->addInput(new Form_Select(
	'action',
	'*Action',
	strtolower($pconfig['action']),
	array('pass' => gettext('통과'), 'block' => gettext('Block'))
))->setHelp('이 MAC 주소에서 오는 패킷으로 수행 할 작업을 선택하십시오.');

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

$group = new Form_Group('*MAC Address');
$group->add($macaddress);
$group->add($btnmymac);
$group->setHelp('콜론으로 구분된 6개의 16진수');
$section->add($group);

$section->addInput(new Form_Input(
	'descr',
	'Description',
	'text',
	$pconfig['descr']
))->setHelp('관리 참조(구문 분석되지 않음)에 대한 설명을 여기에 입력 할 수 있습니다.');

$section->addInput(new Form_Input(
	'bw_up',
	'Bandwidth up',
	'text',
	$pconfig['bw_up']
))->setHelp('이 MAC에 시행 할 업로드 제한을 Kbit/s로 입력하십시오.');

$section->addInput(new Form_Input(
	'bw_down',
	'Bandwidth down',
	'text',
	$pconfig['bw_down']
))->setHelp('이 MAC에 시행 할 다운로드 제한을 Kbit/s로 입력하십시오.');

$section->addInput(new Form_Input(
	'zone',
	null,
	'hidden',
	$cpzone
));

if (isset($id) && $a_passthrumacs[$id]) {
	$section->addInput(new Form_Input(
		'id',
		null,
		'hidden',
		$id
	));
}

if (isset($pconfig['username']) && $pconfig['username']) {
	$section->addInput(new Form_Input(
		'username',
		null,
		'hidden',
		$pconfig['username']
	));
}

$form->add($section);
print($form);
?>

<script type="text/javascript">
//<![CDATA[
events.push(function() {
	// On click, copy the hidden 'mymac' text to the 'mac' input
	$("#btnmymac").click(function() {
		$('#mac').val('<?=$mymac?>');
	});
});
//]]>
</script>

<?php include("foot.inc");

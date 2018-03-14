<?php
/*
 * interfaces_gif_edit.php
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
2018.03.14
한글화 번역 추가
*/

##|+PRIV
##|*IDENT=page-interfaces-gif-edit
##|*NAME=Interfaces: GIF: Edit
##|*DESCR=Allow access to the 'Interfaces: GIF: Edit' page.
##|*MATCH=interfaces_gif_edit.php*
##|-PRIV

require_once("guiconfig.inc");

if (!is_array($config['gifs']['gif'])) {
	$config['gifs']['gif'] = array();
}

$a_gifs = &$config['gifs']['gif'];
$id = $_REQUEST['id'];


if (isset($id) && $a_gifs[$id]) {
	$pconfig['if'] = $a_gifs[$id]['if'];
	if (!empty($a_gifs[$id]['ipaddr'])) {
		$pconfig['if'] = $pconfig['if'] . '|' . $a_gifs[$id]['ipaddr'];
	}
	$pconfig['gifif'] = $a_gifs[$id]['gifif'];
	$pconfig['remote-addr'] = $a_gifs[$id]['remote-addr'];
	$pconfig['tunnel-remote-net'] = $a_gifs[$id]['tunnel-remote-net'];
	$pconfig['tunnel-local-addr'] = $a_gifs[$id]['tunnel-local-addr'];
	$pconfig['tunnel-remote-addr'] = $a_gifs[$id]['tunnel-remote-addr'];
	$pconfig['link1'] = isset($a_gifs[$id]['link1']);
	$pconfig['link2'] = isset($a_gifs[$id]['link2']);
	$pconfig['descr'] = $a_gifs[$id]['descr'];
}

if ($_POST['save']) {
	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "if remote-addr tunnel-local-addr tunnel-remote-addr tunnel-remote-net");
	$reqdfieldsn = array(gettext("상위 인터페이스"), gettext("gif 원격 주소"), gettext("gif 터널 로컬 주소"), gettext("gif 터널 로컬 주소"), gettext("gif 터널 원격 주소"));

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

	if ((!is_ipaddr($_POST['tunnel-local-addr']) || is_subnet($_POST['tunnel-local-addr'])) ||
	    (!is_ipaddr($_POST['tunnel-remote-addr']) || is_subnet($_POST['tunnel-remote-addr'])) ||
	    (!is_ipaddr($_POST['remote-addr']) || is_subnet($_POST['remote-addr']))) {
		$input_errors[] = gettext("터널 로컬 및 터널 원격 필드는 유효한 IP 주소를 가져야하며 CIDR 마스크 또는 접두사를 포함해서는 안됩니다.");
	}

	if (!is_numericint($_POST['tunnel-remote-net'])) {
		$input_errors[] = gettext("GIF 터널 서브넷은 정수 여야합니다.");
	}

	if (is_ipaddrv4($_POST['tunnel-local-addr'])) {
		if (!is_ipaddrv4($_POST['tunnel-remote-addr'])) {
			$input_errors[] = gettext("GIF 터널 원격 주소는 터널 로컬 주소가 IPv4 인 IPv4 여야합니다.");
		}
		if ($_POST['tunnel-remote-net'] > 32 || $_POST['tunnel-remote-net'] < 1) {
			$input_errors[] = gettext("GIF 터널 서브넷은 1에서 32 사이의 정수 여야합니다.");
		}
	}

	if (is_ipaddrv6($_POST['tunnel-local-addr'])) {
		if (!is_ipaddrv6($_POST['tunnel-remote-addr'])) {
			$input_errors[] = gettext("GIF 터널 원격 주소는 터널 로컬 주소가 IPv6 인 IPv6이어야합니다.");
		}
		if ($_POST['tunnel-remote-net'] > 128 || $_POST['tunnel-remote-net'] < 1) {
			$input_errors[] = gettext("GIF 터널 서브넷은 1에서 128 사이의 정수 여야합니다.");
		}
	}

	$alias = strstr($_POST['if'], '|');
	if ((is_ipaddrv4($alias) && !is_ipaddrv4($_POST['remote-addr'])) ||
	    (is_ipaddrv6($alias) && !is_ipaddrv6($_POST['remote-addr']))) {
		$input_errors[] = gettext("별칭 IP 주소 패밀리는 원격 피어 주소 패밀리와 일치해야합니다.");
	}

	foreach ($a_gifs as $gif) {
		if (isset($id) && ($a_gifs[$id]) && ($a_gifs[$id] === $gif)) {
			continue;
		}

		/* FIXME: needs to perform proper subnet checks in the future */
		if (($gif['if'] == $interface) && ($gif['tunnel-remote-addr'] == $_POST['tunnel-remote-addr'])) {
			$input_errors[] = sprintf(gettext("네트워크 %s이(가)있는 gif가 이미 정의되어 있습니다."), $gif['tunnel-remote-addr']);
			break;
		}
	}

	if (!$input_errors) {
		$gif = array();
		list($gif['if'], $gif['ipaddr']) = explode("|", $_POST['if']);
		$gif['tunnel-local-addr'] = $_POST['tunnel-local-addr'];
		$gif['tunnel-remote-addr'] = $_POST['tunnel-remote-addr'];
		$gif['tunnel-remote-net'] = $_POST['tunnel-remote-net'];
		$gif['remote-addr'] = $_POST['remote-addr'];
		$gif['descr'] = $_POST['descr'];
		if (isset($_POST['link1'])) {
			$gif['link1'] = '';
		}
		if (isset($_POST['link2'])) {
			$gif['link2'] = '';
		}
		$gif['gifif'] = $_POST['gifif'];
		$gif['gifif'] = interface_gif_configure($gif);

		if ($gif['gifif'] == "" || !stristr($gif['gifif'], "gif")) {
			$input_errors[] = gettext("인터페이스를 생성하는 동안 오류가 발생했습니다. 다시 시도하십시오.");
		} else {
			if (isset($id) && $a_gifs[$id]) {
				$a_gifs[$id] = $gif;
			} else {
				$a_gifs[] = $gif;
			}

			write_config();

			$confif = convert_real_interface_to_friendly_interface_name($gif['gifif']);

			if ($confif != "") {
				interface_configure($confif);
			}

			header("Location: interfaces_gif.php");
			exit;
		}
	}
}

function build_parent_list() {
	$parentlist = array();
	$portlist = get_possible_listen_ips();
	foreach ($portlist as $ifn => $ifinfo) {
		$parentlist[$ifn] = $ifinfo;
	}

	return($parentlist);
}

$pgtitle = array(gettext("인터페이스"), gettext("GIFs"), gettext("편집"));
$pglinks = array("", "interfaces_gif.php", "@self");
$shortcut_section = "interfaces";
include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

$form = new Form();

$section = new Form_Section('GIF Configuration');

$section->addInput(new Form_Select(
	'if',
	'*Parent Interface',
	$pconfig['if'],
	build_parent_list()
))->setHelp('This interface serves as the local address to be used for the GIF tunnel.');

$section->addInput(new Form_IpAddress(
	'remote-addr',
	'*GIF Remote Address',
	$pconfig['remote-addr']
))->setHelp('Peer address where encapsulated gif packets will be sent.');

$section->addInput(new Form_IpAddress(
	'tunnel-local-addr',
	'*GIF tunnel local address',
	$pconfig['tunnel-local-addr']
))->setHelp('Local gif tunnel endpoint.');

$section->addInput(new Form_IpAddress(
	'tunnel-remote-addr',
	'*GIF tunnel remote address',
	$pconfig['tunnel-remote-addr']
))->setHelp('Remote GIF address endpoint.');

$section->addInput(new Form_Select(
	'tunnel-remote-net',
	'*GIF tunnel subnet',
	$pconfig['tunnel-remote-net'],
	array_combine(range(128, 1, -1), range(128, 1, -1))
))->setHelp('The subnet is used for determining the network that is tunnelled.');

$section->addInput(new Form_Checkbox(
	'link1',
	'ECN friendly behavior',
	'ECN friendly behavior violates RFC2893. This should be used in mutual agreement with the peer. ',
	$pconfig['link1']
));

$section->addInput(new Form_Checkbox(
	'link2',
	'Outer Source Filtering',
	'Disable automatic filtering of the outer GIF source which ensures a match with the configured remote peer. When disabled, martian and inbound filtering is not performed which allows asymmetric routing of the outer traffic.',
	$pconfig['link2']
));

$section->addInput(new Form_Input(
	'descr',
	'Description',
	'text',
	$pconfig['descr']
))->setHelp('A description may be entered here for administrative reference (not parsed).');

$section->addInput(new Form_Input(
	'gifif',
	null,
	'hidden',
	$pconfig['gifif']
));

if (isset($id) && $a_gifs[$id]) {
	$section->addInput(new Form_Input(
		'id',
		null,
		'hidden',
		$id
	));
}

$form->add($section);
print($form);

include("foot.inc");

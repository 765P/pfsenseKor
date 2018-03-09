<?php
/*
 * system_gateway_groups_edit.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2018 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2010 Seth Mos <seth.mos@dds.nl>
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
##|*IDENT=page-system-gateways-editgatewaygroups
##|*NAME=System: Gateways: Edit Gateway Groups
##|*DESCR=Allow access to the 'System: Gateways: Edit Gateway Groups' page.
##|*MATCH=system_gateway_groups_edit.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("ipsec.inc");
require_once("vpn.inc");

if (!is_array($config['gateways']['gateway_group'])) {
	$config['gateways']['gateway_group'] = array();
}

$a_gateway_groups = &$config['gateways']['gateway_group'];
$a_gateways = return_gateways_array();

$categories = array(
	'down' => gettext("멤버 아래로"),
	'downloss' => gettext("패킷 손실"),
	'downlatency' => gettext("긴 대기시간"),
	'downlosslatency' => gettext("패킷 손실 또는 긴 대기시간"));

if (isset($_REQUEST['id']) && is_numericint($_REQUEST['id'])) {
	$id = $_REQUEST['id'];
}

if (isset($_REQUEST['dup']) && is_numericint($_REQUEST['dup'])) {
	$id = $_REQUEST['dup'];
}

if (isset($id) && $a_gateway_groups[$id]) {
	$pconfig['name'] = $a_gateway_groups[$id]['name'];
	$pconfig['item'] = &$a_gateway_groups[$id]['item'];
	$pconfig['descr'] = $a_gateway_groups[$id]['descr'];
	$pconfig['trigger'] = $a_gateway_groups[$id]['trigger'];
}

if (isset($_REQUEST['dup']) && is_numericint($_REQUEST['dup'])) {
	unset($id);
}

if (isset($_POST['save'])) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "name");
	$reqdfieldsn = explode(",", "Name");

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

	if (!isset($_POST['name'])) {
		$input_errors[] = gettext("유효한 게이트웨이 그룹 이름을 지정해야합니다.");
	}
	if (!is_validaliasname($_POST['name'])) {
		$input_errors[] = invalidaliasnamemsg($_POST['name'], gettext("게이트웨이 그룹"));
	}

	if (isset($_POST['name'])) {
		/* check for overlaps */
		if (is_array($a_gateway_groups)) {
			foreach ($a_gateway_groups as $gateway_group) {
				if (isset($id) && ($a_gateway_groups[$id]) && ($a_gateway_groups[$id] === $gateway_group)) {
					if ($gateway_group['name'] != $_POST['name']) {
						$input_errors[] = gettext("게이트웨이 그룹의 이름 변경은 허용되지 않습니다.");
					}
					continue;
				}

				if ($gateway_group['name'] == $_POST['name']) {
					$input_errors[] = sprintf(gettext('이름이 "%s"인 게이트웨이 그룹이 이미 있습니다.'), $_POST['name']);
					break;
				}
			}
		}
	}

	/* Build list of items in group with priority */
	$pconfig['item'] = array();
	foreach ($a_gateways as $gwname => $gateway) {
		if ($_POST[$gwname] > 0) {
			$vipname = "{$gwname}_vip";
			/* we have a priority above 0 (disabled), add item to list */
			$pconfig['item'][] = "{$gwname}|{$_POST[$gwname]}|{$_POST[$vipname]}";
		}
		/* check for overlaps */
		if ($_POST['name'] == $gwname) {
			$input_errors[] = sprintf(gettext('게이트웨이 그룹은 게이트웨이 "%s"와 같은 이름을 가질 수 없습니다. 다른 이름을 선택하십시오.'), $_POST['name']);
		}

	}
	if (count($pconfig['item']) == 0) {
		$input_errors[] = gettext("이 그룹에서 사용할 게이트웨이를 선택하지 않았습니다.");
	}

	if (!$input_errors) {
		$gateway_group = array();
		$gateway_group['name'] = $_POST['name'];
		$gateway_group['item'] = $pconfig['item'];
		$gateway_group['trigger'] = $_POST['trigger'];
		$gateway_group['descr'] = $_POST['descr'];

		if (isset($id) && $a_gateway_groups[$id]) {
			$a_gateway_groups[$id] = $gateway_group;
		} else {
			$a_gateway_groups[] = $gateway_group;
		}

		mark_subsystem_dirty('staticroutes');
		mark_subsystem_dirty('gwgroup.' . $gateway_group['name']);

		write_config();

		header("Location: system_gateway_groups.php");
		exit;
	}
}

$pgtitle = array(gettext("System"), gettext("라우팅"), gettext("게이트웨이 그룹"), gettext("편집"));
$pglinks = array("", "system_gateways.php", "system_gateway_groups.php", "@self");
$shortcut_section = "gateway-groups";

function build_gateway_protocol_map (&$a_gateways) {
	$result = array();
	foreach ($a_gateways as $gwname => $gateway) {
		$result[$gwname] = $gateway['ipprotocol'];
	}

	return $result;
}

function build_vip_list($family = 'all') {
	global $gateway;

	$list = array('address' => gettext('인터페이스 주소'));

	$viplist = get_configured_vip_list($family);
	foreach ($viplist as $vip => $address) {
		if ($gateway['friendlyiface'] == get_configured_vip_interface($vip)) {
			$list[$vip] = "$address";
			if (get_vip_descr($address)) {
				$list[$vip] .= " (". get_vip_descr($address) .")";
			}
		}
	}

	return($list);
}

include("head.inc");

$gateway_protocol = build_gateway_protocol_map($a_gateways);
$gateway_array	= array_keys($a_gateways);
$protocol_array	  = array_values($gateway_protocol);
$protocol_array	  = array_values(array_unique($gateway_protocol));

if ($input_errors) {
	print_input_errors($input_errors);
}

function get_gw_family($gwname) {
	$gateways = return_gateways_array();

	if (is_array($gateways)) {
		foreach ($gateways as $gw) {
			if ($gw['name'] == $gwname) {
				return($gw['ipprotocol']);
			}
		}
	}

	return("none");
}

$form = new Form();

$section = new Form_Section('Edit Gateway Group Entry');

$section->addInput(new Form_Input(
	'name',
	'*Group Name',
	'text',
	$pconfig['name']
));

$row = 0;
$numrows = count($a_gateways) - 1;

$group = new Form_Group('*Gateway Priority');
$group->add(new Form_StaticText('', ''))->setReadonly();
$group->add(new Form_StaticText('', ''))->setReadonly();
$group->add(new Form_StaticText('', ''))->setReadonly();
$group->add(new Form_StaticText('', ''))->setWidth(3)->setReadonly();
$section->add($group);

// Determine the protocol family this group pertains to. We loop through every item
// just in case any have been removed and so have no family (orphans?)

if (is_array($pconfig['item'])) {
	foreach ($pconfig['item'] as $idx => $item) {
		$itemsplit = explode("|", $item);

		$family = get_gw_family($itemsplit[0]);

		if (($family == "inet") || ($family == "inet6")) {
			break;
		}
	}
}

foreach ($a_gateways as $gwname => $gateway) {
	if (!empty($pconfig['item'])) {
		$af = explode("|", $pconfig['item'][0]);
		if ($gateway['ipprotocol'] != $family) {
			$rows++;
			continue;
		}
	}

	$selected = '0';
	$vaddress = '';
	foreach ((array)$pconfig['item'] as $item) {
		$itemsplit = explode("|", $item);
		if ($itemsplit[0] == $gwname) {
			$selected = $itemsplit[1];
			if (count($itemsplit) >= 3) {
				$vaddress = $itemsplit[2];
			}
			break;
		}
	}

	$group = new Form_Group(null);
	$group->addClass($gateway['ipprotocol']);

	$group->add(new Form_Input(
		'gwname' . $row,
		'Group Name',
		'text',
		$gateway['name']
	))->setReadonly();

	$tr = gettext("Tier");
	$group->add(new Form_Select(
		$gwname,
		'Tier',
		$selected,
		array(
			'0' => 'Never',
			'1' => $tr . ' 1',
			'2' => $tr . ' 2',
			'3' => $tr . ' 3',
			'4' => $tr . ' 4',
			'5' => $tr . ' 5'
		)
	))->addClass('row')->addClass($gateway['ipprotocol']);

	$group->add(new Form_Select(
		$gwname . '_vip',
		'Virtual IP',
		$vaddress,
		build_vip_list($gateway['ipprotocol'])
	));

	$group->add(new Form_Input(
		'description',
		'Group Name',
		'text',
		$gateway['descr']
	))->setWidth(3)->setReadonly();

	$section->add($group);

	$row++;
} // e-o-foreach

$group = new Form_Group(null);
$group->add(new Form_StaticText('', ''))->setHelp('Gateway')->setReadonly();
$group->add(new Form_StaticText('', ''))->setHelp('Tier')->setReadonly();
$group->add(new Form_StaticText('', ''))->setHelp('Virtual IP')->setReadonly();
$group->add(new Form_StaticText('', ''))->setWidth(3)->setHelp('Description')->setReadonly();
$section->add($group);

$section->addInput(new Form_StaticText(
	'Link Priority',
	'The priority selected here defines in what order failover and balancing of links will be done. ' .
	'Multiple links of the same priority will balance connections until all links in the priority will be exhausted. ' .
	'If all links in a priority level are exhausted then the next available link(s) in the next priority level will be used.'
));

$section->addInput(new Form_StaticText(
	'Virtual IP',
	'The virtual IP field selects which (virtual) IP should be used when this group applies to a local Dynamic DNS, IPsec or OpenVPN endpoint.'
));

$section->addInput(new Form_Select(
	'trigger',
	'*Trigger Level',
	$pconfig['trigger'],
	array(
		'0' => gettext('Member down'),
		'1' => gettext('Packet Loss'),
		'2' => gettext('High Latency'),
		'3' => gettext('Packet Loss or High latency')
	)
))->setHelp('When to trigger exclusion of a member');

$section->addInput(new Form_Input(
	'descr',
	'Description',
	'text',
	$pconfig['descr']
))->setHelp('A description may be entered here for administrative reference (not parsed).');

if (isset($id) && $a_gateway_groups[$id]) {
	$section->addInput(new Form_Input(
	'id',
	null,
	'hidden',
	$id
	));
}

$form->add($section);

print($form);
?>

<script type="text/javascript">
//<![CDATA[
events.push(function() {
	// Hides all elements of the specified class. This will usually be a section or group
	function hideClass(s_class, hide) {
		if (hide) {
			$('.' + s_class).hide();
		} else {
			$('.' + s_class).show();
		}
	}

	// On changing a Tier selector on any row, find which protocol it uses (class)
	// and disable the opposite
	$('.row').on('change', function() {
		// If user selects 'Never', unhide all rows
		if ($(this).find(":selected").index() == 0) {
			hideClass('inet', false);
			hideClass('inet6', false);
		} else { // Otherwise hide the rows that are not of 'this' protocol
			if ($(this).hasClass('inet6')) {
				hideClass('inet', true);
			} else {
				hideClass('inet6', true);
			}
		}
	});
});
//]]>
</script>

<?php
include("foot.inc");

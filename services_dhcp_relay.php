<?php
/*
 * services_dhcp_relay.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2018 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2003-2004 Justin Ellison <justin@techadvise.com>
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
2018.03.16
한글화 번역 시작
*/

##|+PRIV
##|*IDENT=page-services-dhcprelay
##|*NAME=Services: DHCP Relay
##|*DESCR=Allow access to the 'Services: DHCP Relay' page.
##|*MATCH=services_dhcp_relay.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("filter.inc");
$pconfig['enable'] = isset($config['dhcrelay']['enable']);

if (empty($config['dhcrelay']['interface'])) {
	$pconfig['interface'] = array();
} else {
	$pconfig['interface'] = explode(",", $config['dhcrelay']['interface']);
}

$pconfig['agentoption'] = isset($config['dhcrelay']['agentoption']);
$pconfig['server'] = $config['dhcrelay']['server'];

$iflist = array_intersect_key(
	get_configured_interface_with_descr(),
	array_flip(
		array_filter(
			array_keys(get_configured_interface_with_descr()),
			function($if) {
				return is_ipaddr(get_interface_ip($if));
			}
		)
	)
);

/*   set the enabled flag which will tell us if DHCP server is enabled
 *   on any interface.   We will use this to disable dhcp-relay since
 *   the two are not compatible with each other.
 */
$dhcpd_enabled = false;
if (is_array($config['dhcpd'])) {
	foreach ($config['dhcpd'] as $dhcpif => $dhcp) {
		if (isset($dhcp['enable']) && isset($config['interfaces'][$dhcpif]['enable'])) {
			$dhcpd_enabled = true;
			break;
		}
	}
}

if ($_POST) {

	unset($input_errors);

	$pconfig = $_POST;

	/* input validation */
	if ($_POST['enable']) {
		$reqdfields = explode(" ", "interface");
		$reqdfieldsn = array(gettext("인터페이스"));

		do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

		$svrlist = '';

		for ($idx=0; $idx<count($_POST); $idx++) {
			if ($_POST['server' . $idx]) {
				if (!empty($_POST['server' . $idx])) { // Filter out any empties
					if (!is_ipaddrv4($_POST['server' . $idx])) {
						$input_errors[] = sprintf(gettext("대상 서버 IP 주소 %s은(는) 유효한 IPv4 주소가 아닙니다."), $_POST['server' . $idx]);
					}

					if (!empty($svrlist)) {
						$svrlist .= ',';
					}

					$svrlist .= $_POST['server' . $idx];
				}
			}
		}

		// Check that the user input something in one of the Destination Server fields
		if (empty($svrlist)) {
			$input_errors[] = gettext("하나 이상의 대상 서버 IP 주소를 지정해야합니다.");
		}
	}

	// Now $svrlist is a comma separated list of servers ready to save to the config system
	$pconfig['server'] = $svrlist;

	if (!$input_errors) {
		$config['dhcrelay']['enable'] = $_POST['enable'] ? true : false;
		$config['dhcrelay']['interface'] = implode(",", $_POST['interface']);
		$config['dhcrelay']['agentoption'] = $_POST['agentoption'] ? true : false;
		$config['dhcrelay']['server'] = $svrlist;

		write_config();

		$changes_applied = true;
		$retval = 0;
		$retval |= services_dhcrelay_configure();
		$retval |= filter_configure();
	}
}

$pgtitle = array(gettext("서비스"), gettext("DHCP 릴레이"));
$shortcut_section = "dhcp";
include("head.inc");

if ($dhcpd_enabled) {
	print_info_box(gettext("DHCP 서버가 현재 활성화되어 있습니다. 인터페이스에서 DHCP 서버가 활성화되어있는 동안 DHCP 릴레이 서비스를 활성화할 수 없습니다."), 'danger', false);
	include("foot.inc");
	exit;
}

if ($input_errors) {
	print_input_errors($input_errors);
}

if ($changes_applied) {
	print_apply_result_box($retval);
}

$form = new Form;

$section = new Form_Section('DHCP Relay Configuration');

$section->addInput(new Form_Checkbox(
	'enable',
	'Enable',
	'Enable DHCP relay on interface',
	$pconfig['enable']
));

$section->addInput(new Form_Select(
	'interface',
	'*Interface(s)',
	$pconfig['interface'],
	$iflist,
	true
))->setHelp('Interfaces without an IP address will not be shown.');

$section->addInput(new Form_Checkbox(
	'agentoption',
	'',
	'Append circuit ID and agent ID to requests',
	$pconfig['agentoption']
))->setHelp(
	'If this is checked, the DHCP relay will append the circuit ID (%s interface number) and the agent ID to the DHCP request.',
	$g['product_name']
	);

$counter = 0;
foreach (explode(',', $pconfig['server']) as $server) {
	$group = new Form_Group($counter == 0 ? gettext("*수신지 서버"):'');
	$group->addClass('repeatable');

	$group->add(new Form_IpAddress(
		'server' . $counter,
		'Destination server',
		$server,
		'V4'
	))->setWidth(4)
	  ->setHelp('This is the IPv4 address of the server to which DHCP requests are relayed.');

	$group->add(new Form_Button(
		'deleterow' . $counter,
		'Delete',
		null,
		'fa-trash'
	))->addClass('btn-warning');

	$section->add($group);
	$counter++;
}

$form->add($section);

$form->addGlobal(new Form_Button(
	'addrow',
	"Add server",
	null,
	'fa-plus'
))->addClass('btn-success addbtn');

print $form;
?>
<script type="text/javascript">
//<![CDATA[
	events.push(function() {

		function updateSection(hide) {
			if (hide) {
				$('[name="interface[]"]').parent().parent('div').addClass('hidden');
			} else {
				$('[name="interface[]"]').parent().parent('div').removeClass('hidden');
			}

			hideCheckbox('agentoption', hide);
			hideClass('repeatable', hide);
		}

		$('#enable').click(function () {
			updateSection(!this.checked);
    	});

    	updateSection(!$('#enable').prop('checked'));

		// Suppress "Delete row" button if there are fewer than two rows
		checkLastRow();
	});
//]]>
</script>

<?php
include("foot.inc");

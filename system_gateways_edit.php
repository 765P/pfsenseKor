<?php
/*
 * system_gateways_edit.php
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
2018.03.07
한글화 번역 시작
*/

##|+PRIV
##|*IDENT=page-system-gateways-editgateway
##|*NAME=System: Gateways: Edit Gateway
##|*DESCR=Allow access to the 'System: Gateways: Edit Gateway' page.
##|*MATCH=system_gateways_edit.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("pkg-utils.inc");

if (isset($_POST['referer'])) {
	$referer = $_POST['referer'];
} else {
	$referer = (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/system_gateways.php');
}

$a_gateways = return_gateways_array(true, false, true, true);

if (!is_array($config['gateways']['gateway_item'])) {
	$config['gateways']['gateway_item'] = array();
}

$a_gateway_item = &$config['gateways']['gateway_item'];
$dpinger_default = return_dpinger_defaults();

if (is_numericint($_REQUEST['id'])) {
	$id = $_REQUEST['id'];
}

if (isset($_REQUEST['dup']) && is_numericint($_REQUEST['dup'])) {
	$id = $_REQUEST['dup'];
}

if (isset($id) && $a_gateways[$id]) {
	$pconfig = array();
	$pconfig['name'] = $a_gateways[$id]['name'];
	$pconfig['weight'] = $a_gateways[$id]['weight'];
	$pconfig['interval'] = $a_gateways[$id]['interval'];
	$pconfig['loss_interval'] = $a_gateways[$id]['loss_interval'];
	$pconfig['alert_interval'] = $a_gateways[$id]['alert_interval'];
	$pconfig['time_period'] = $a_gateways[$id]['time_period'];
	$pconfig['interface'] = $a_gateways[$id]['interface'];
	$pconfig['friendlyiface'] = $a_gateways[$id]['friendlyiface'];
	$pconfig['ipprotocol'] = $a_gateways[$id]['ipprotocol'];
	if (isset($a_gateways[$id]['dynamic'])) {
		$pconfig['dynamic'] = true;
	}
	$pconfig['gateway'] = $a_gateways[$id]['gateway'];
	$pconfig['defaultgw'] = isset($a_gateways[$id]['defaultgw']);
	$pconfig['force_down'] = isset($a_gateways[$id]['force_down']);
	$pconfig['latencylow'] = $a_gateways[$id]['latencylow'];
	$pconfig['latencyhigh'] = $a_gateways[$id]['latencyhigh'];
	$pconfig['losslow'] = $a_gateways[$id]['losslow'];
	$pconfig['losshigh'] = $a_gateways[$id]['losshigh'];
	$pconfig['monitor'] = $a_gateways[$id]['monitor'];
	$pconfig['monitor_disable'] = isset($a_gateways[$id]['monitor_disable']);
	$pconfig['action_disable'] = isset($a_gateways[$id]['action_disable']);
	$pconfig['data_payload'] = $a_gateways[$id]['data_payload'];
	$pconfig['nonlocalgateway'] = isset($a_gateways[$id]['nonlocalgateway']);
	$pconfig['descr'] = $a_gateways[$id]['descr'];
	$pconfig['attribute'] = $a_gateways[$id]['attribute'];
	$pconfig['disabled'] = isset($a_gateways[$id]['disabled']);
}

if (isset($_REQUEST['dup']) && is_numericint($_REQUEST['dup'])) {
	unset($id);
	unset($pconfig['attribute']);
}

if (isset($id) && $a_gateways[$id]) {
	$realid = $a_gateways[$id]['attribute'];
}

if ($_POST['save']) {

	$input_errors = validate_gateway($_POST, $id);

	if (count($input_errors) == 0) {
		save_gateway($_POST, $realid);
		header("Location: system_gateways.php");
		exit;
	} else {
		$pconfig = $_POST;
		if (empty($_POST['friendlyiface'])) {
			$pconfig['friendlyiface'] = $_POST['interface'];
		}
	}
}

$pgtitle = array(gettext("시스템"), gettext("라우팅"), gettext("게이트웨이"), gettext("Edit"));
$pglinks = array("", "system_gateways.php", "system_gateways.php", "@self");
$shortcut_section = "gateways";

include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

$form = new Form;

/* If this is a system gateway we need this var */
if (($pconfig['attribute'] == "system") || is_numeric($pconfig['attribute'])) {
	$form->addGlobal(new Form_Input(
		'attribute',
		null,
		'hidden',
		$pconfig['attribute']
	));
}

if (isset($id) && $a_gateways[$id]) {
	$form->addGlobal(new Form_Input(
		'id',
		null,
		'hidden',
		$id
	));
}

$form->addGlobal(new Form_Input(
	'friendlyiface',
	null,
	'hidden',
	$pconfig['friendlyiface']
));

$section = new Form_Section('게이트웨이 편집');

$section->addInput(new Form_Checkbox(
	'disabled',
	'Disabled',
	'Disable this gateway',
	$pconfig['disabled']
))->setHelp('Set this option to disable this gateway without removing it from the list.');

$section->addInput(new Form_Select(
	'interface',
	'*Interface',
	$pconfig['friendlyiface'],
	get_configured_interface_with_descr(true)
))->setHelp('Choose which interface this gateway applies to.');

$section->addInput(new Form_Select(
	'ipprotocol',
	'*Address Family',
	$pconfig['ipprotocol'],
	array(
		"inet" => "IPv4",
		"inet6" => "IPv6"
	)
))->setHelp('Choose the Internet Protocol this gateway uses.');

$section->addInput(new Form_Input(
	'name',
	'*Name',
	'text',
	$pconfig['name']
))->setHelp('Gateway name');

$egw = new Form_Input(
	'gateway',
	'Gateway',
	'text',
	($pconfig['dynamic'] ? 'dynamic' : $pconfig['gateway'])
);

$egw->setHelp('게이트웨이 IP주소');

if ($pconfig['dynamic']) {
	$egw->setReadonly();
}

$section->addInput($egw);

$section->addInput(new Form_Checkbox(
	'defaultgw',
	'Default Gateway',
	'This will select the above gateway as the default gateway.',
	$pconfig['defaultgw']
));

$section->addInput(new Form_Checkbox(
	'monitor_disable',
	'Gateway Monitoring',
	'Disable Gateway Monitoring',
	$pconfig['monitor_disable']
))->toggles('.toggle-monitor-ip')->setHelp('This will consider this gateway as always being up.');

$section->addInput(new Form_Checkbox(
	'action_disable',
	'Gateway Action',
	'Disable Gateway Monitoring Action',
	$pconfig['action_disable']
))->setHelp('No action will be taken on gateway events. The gateway is always considered up.');

$group = new Form_Group('모니터 IP');
$group->addClass('toggle-monitor-ip', 'collapse');

if (!$pconfig['monitor_disable'])
	$group->addClass('in');

$group->add(new Form_Input(
	'monitor',
	null,
	'text',
	($pconfig['gateway'] == $pconfig['monitor'] ? '' : $pconfig['monitor'])
))->setHelp('Enter an alternative address here to be '.
	'used to monitor the link. This is used for the quality RRD graphs as well as the '.
	'load balancer entries. Use this if the gateway does not respond to ICMP echo '.
	'requests (pings).');
$section->add($group);

$section->addInput(new Form_Checkbox(
	'force_down',
	'Force state',
	'Mark Gateway as Down',
	$pconfig['force_down']
))->setHelp('This will force this gateway to be considered down.');

$section->addInput(new Form_Input(
	'descr',
	'Description',
	'text',
	$pconfig['descr']
))->setHelp('A description may be entered here for reference (not parsed).');

// Add a button to provide access to the advanced fields
$btnadv = new Form_Button(
	'btnadvopts',
	'Display Advanced',
	null,
	'fa-cog'
);

$btnadv->setAttribute('type','button')->addClass('btn-info btn-sm');

$section->addInput(new Form_StaticText(
	null,
	$btnadv
));

$form->add($section);
$section = new Form_Section('Advanced');

$section->addClass('adnlopts');

$section->addInput(new Form_Select(
	'weight',
	'Weight',
	$pconfig['weight'],
	array_combine(range(1, 30), range(1, 30))
))->setHelp('Weight for this gateway when used in a Gateway Group.');

$section->addInput(new Form_Input(
	'data_payload',
	'Data Payload',
	'number',
	$pconfig['data_payload'],
	['placeholder' => $dpinger_default['data_payload']]
))->setHelp('Define data payload to send on ICMP packets to gateway monitor IP.');

$group = new Form_Group('Latency thresholds');
$group->add(new Form_Input(
	'latencylow',
	'From',
	'number',
	$pconfig['latencylow'],
	['placeholder' => $dpinger_default['latencylow']]
));
$group->add(new Form_Input(
	'latencyhigh',
	'To',
	'number',
	$pconfig['latencyhigh'],
	['placeholder' => $dpinger_default['latencyhigh']]
));
$group->setHelp('Low and high thresholds for latency in milliseconds. ' .
	'Default is %1$d/%2$d.', $dpinger_default['latencylow'], $dpinger_default['latencyhigh']);

$section->add($group);

$group = new Form_Group('Packet Loss thresholds');
$group->add(new Form_Input(
	'losslow',
	'From',
	'number',
	$pconfig['losslow'],
	['placeholder' => $dpinger_default['losslow']]
));
$group->add(new Form_Input(
	'losshigh',
	'To',
	'number',
	$pconfig['losshigh'],
	['placeholder' => $dpinger_default['losshigh']]
));
$group->setHelp('Low and high thresholds for packet loss in %%. ' .
	'Default is %1$d/%2$d.', $dpinger_default['losslow'], $dpinger_default['losshigh']);
$section->add($group);

$section->addInput(new Form_Input(
	'interval',
	'Probe Interval',
	'number',
	$pconfig['interval'],
	[
		'placeholder' => $dpinger_default['interval'],
		'max' => 86400
	]
))->setHelp('How often an ICMP probe will be sent in milliseconds. Default is %d.', $dpinger_default['interval']);

$section->addInput(new Form_Input(
	'loss_interval',
	'Loss Interval',
	'number',
	$pconfig['loss_interval'],
	['placeholder' => $dpinger_default['loss_interval']]
))->setHelp('Time interval in milliseconds before packets are treated as lost. '.
	'Default is %d.', $dpinger_default['loss_interval']);

$group = new Form_Group('Time Period');
$group->add(new Form_Input(
	'time_period',
	null,
	'number',
	$pconfig['time_period'],
	[
		'placeholder' => $dpinger_default['time_period']
	]
));
$group->setHelp('Time period in milliseconds over which results are averaged. Default is %d.',
	$dpinger_default['time_period']);
$section->add($group);

$group = new Form_Group('Alert interval');
$group->add(new Form_Input(
	'alert_interval',
	null,
	'number',
	$pconfig['alert_interval'],
	[
		'placeholder' => $dpinger_default['alert_interval']
	]
));
$group->setHelp('Time interval in milliseconds between checking for an alert condition. Default is %d.',
	$dpinger_default['alert_interval']);
$section->add($group);

$section->addInput(new Form_StaticText(
	gettext('추가 정보'),
	'<span class="help-block">'.
	gettext('시간, 프로브 간격 및 손실 간격은 밀접한 관련이 있습니다. ' .
		'이러한 값 사이의 비율은 보고된 숫자의 정확도와 경고의 적시성을 제어합니다.') .
	'<br/><br/>' .
	gettext('더 긴 시간이 있으면 왕복 시간과 손실에 대해 더 자연스러운 결과를 얻을 수 있지만, ' .
		'지연 또는 손실 경고가 트리거 되기 전까지의 시간이 증가합니다.') .
	'<br/><br/>' .
	gettext('프로브 간격이 짧을수록 지연 시간이나 손실 경고가 트리거 되기 전에 필요한 시간이 줄어들지만 ' .
		'더 많은 네트워크 리소스가 사용됩니다. 프로브 간격이 길어지면 ' .
		'품질 그래프의 정확도가 저하됩니다.') .
	'<br/><br/>' .
	gettext('시간에 대한 프로브 간격의 비율(손실 간격 제외)도 손실 보고의 해결을 제어한다. ' .
		'분해능을 확인하기 위해 다음 공식을 사용할 수 있습니다:') .
	'<br/><br/>' .
	gettext('&nbsp;&nbsp;&nbsp;&nbsp;100*프로브 간격/(시간 간격-손실 간격)') .
	'<br/><br/>' .
	gettext('가장 가까운 정수로 반올림하면 손실 보고에 대한 분해능이 ' .
		'백분율로 표시됩니다. 기본 값은 1%입니다.') .
	'<br/><br/>' .
	gettext('대부분의 사용 사례에 기본 설정이 권장됩니다. 하지만 ' .
		'설정을 변경하는 경우 다음 제한 사항을 준수하십시오.') .
	'<br/><br/>' .
	gettext('- 이 시간 간격은 프로브 간격에 손실 간격을 더한 값의 두배보다 커야 합니다. ' .
		'이를 통해 항상 최소 1개의 프로브가 완성된 상태를 유지할 수 있습니다. ') .
	'<br/><br/>' .
	gettext('- 경고 간격은 프로브 간격보다 크거나 같아야 합니다. ' .
		'프로브를 실행하는 것보다 경고를 더 자주 확인하는 포인트는 없습니다.') .
	'<br/><br/>' .
	gettext('- 손실 간격이 높은 지연 시간 임계값보다 크거나 같아야 합니다.') .
	'</span>'
));

$section->addInput(new Form_Checkbox(
	'nonlocalgateway',
	'Use non-local gateway',
	'Use non-local gateway through interface specific route.',
	$pconfig['nonlocalgateway']
))->setHelp('This will allow use of a gateway outside of this interface\'s subnet. This is usually indicative of a configuration error, but is required for some scenarios.');

$form->add($section);

print $form;
?>

<script type="text/javascript">
//<![CDATA[
events.push(function() {

	// Show advanced additional opts options ===========================================================================
	var showadvopts = false;

	function show_advopts(ispageload) {
		var text;
		// On page load decide the initial state based on the data.
		if (ispageload) {
<?php
			if (!(!empty($pconfig['latencylow']) || !empty($pconfig['latencyhigh']) ||
			    !empty($pconfig['losslow']) || !empty($pconfig['losshigh']) || !empty($pconfig['data_payload']) ||
			    (!empty($pconfig['weight']) && $pconfig['weight'] > 1) ||
			    (!empty($pconfig['interval']) && !($pconfig['interval'] == $dpinger_default['interval'])) ||
			    (!empty($pconfig['loss_interval']) && !($pconfig['loss_interval'] == $dpinger_default['loss_interval'])) ||
			    (!empty($pconfig['time_period']) && !($pconfig['time_period'] == $dpinger_default['time_period'])) ||
			    (!empty($pconfig['alert_interval']) && !($pconfig['alert_interval'] == $dpinger_default['alert_interval'])) ||
			    (!empty($pconfig['nonlocalgateway']) && $pconfig['nonlocalgateway']))) {
				$showadv = false;
			} else {
				$showadv = true;
			}
?>
			showadvopts = <?php if ($showadv) {echo 'true';} else {echo 'false';} ?>;
		} else {
			// It was a click, swap the state.
			showadvopts = !showadvopts;
		}

		hideClass('adnlopts', !showadvopts);

		if (showadvopts) {
			text = "<?=gettext('어드밴스드 숨기기');?>";
		} else {
			text = "<?=gettext('어드밴스드 ');?>";
		}
		$('#btnadvopts').html('<i class="fa fa-cog"></i> ' + text);
	}

	$('#btnadvopts').click(function(event) {
		show_advopts();
	});

	// ---------- On initial page load ------------------------------------------------------------

	show_advopts(true);
});
//]]>
</script>

<?php include("foot.inc");

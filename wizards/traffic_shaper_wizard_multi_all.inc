<?php
/*
 * traffic_shaper_wizard_multi_all.inc
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

require_once("config.inc");
require_once("interfaces.inc");
require_once("util.inc");

function step1_stepbeforeformdisplay() {
	global $stepid, $savemsg, $pkg;

	$fields =& $pkg['step'][0]['fields']['field'];

	$lans = 0;
	$wans = 0;

	$iflisttmp = get_configured_interface_with_descr();
	foreach ($iflisttmp as $if => $ifdesc) {
		if (!is_altq_capable(get_real_interface($if)))
			continue;
		if (interface_has_gateway($if) || interface_has_gatewayv6($if))
			$wans++;
		else
			$lans++;
	}

	foreach ($fields as &$field) {
		if ($field['name'] == 'numberofconnections') {
			$field['value'] = $wans;
			if ($wans == 0) {
				$field['description'] .= sprint_info_box(gettext("이 방화벽에는 AL. 트래픽 조절을 사용할 수 있는 WAN형식 인터페이스가 할당되어 있지 않습니다."), 'danger', false);
			}
		} else if ($field['name'] == 'numberoflocalinterfaces') {
			$field['value'] = $lans;
			if ($lans == 0) {
				$field['description'] .= sprint_info_box(gettext("이 방화벽에는 AL. 트래픽 조절 기능을 사용할 수 있는 LAN유형 인터페이스가 할당되어 있지 않습니다."), 'danger', false);
			}
		}
	}
}

function step1_submitphpaction() {
	global $stepid, $savemsg;

	if (!isset($_POST['numberofconnections'])) {
		$savemsg=gettext("연결 수를 지정해야 합니다.");
		$stepid--;
		return;
	}
	if (intval($_POST['numberofconnections']) < 1) {
		$savemsg=gettext("연결 수는 1보다 커야 합니다.");
		$stepid--;
		return;
	}

	if (!isset($_POST['numberoflocalinterfaces'])) {
		$savemsg=gettext("LAN유형 인터페이스 수를 지정해야 합니다.");
		$stepid--;
		return;
	}
	if (intval($_POST['numberoflocalinterfaces']) < 1) {
		$savemsg=gettext("LAN유형 인터페이스의 수는 1개보다 커야 합니다.");
		$stepid--;
		return;
	}
}

function step2_stepbeforeformdisplay() {
	global $config, $pkg;
	global $stepid, $savemsg;

	$list_all_ifs = false;
	$wans = 0;
	$lans = 0;
	$iflist = array();
	$iflisttmp = get_configured_interface_with_descr();
	foreach ($iflisttmp as $if => $ifdesc) {
		if (!is_altq_capable(get_real_interface($if)))
			continue;
		if (interface_has_gateway($if) || interface_has_gatewayv6($if))
			$wans++;
		else
			$lans++;
		$iflist[$if] = $ifdesc;
	}
	if ($wans == 0) {
		$list_all_ifs = true;
		$wans = $lans;
	}
	$numberofconnections = intval($config['ezshaper']['step1']['numberofconnections']);
	if ($numberofconnections > $wans) {
		$savemsg=gettext("지정한 연결 수가 ALP지원 인터페이스의 수보다 많습니다!");
		$stepid--;
		return;
	}

	$numberoflocalinterfaces = intval($config['ezshaper']['step1']['numberoflocalinterfaces']);
	if ($numberoflocalinterfaces > $lans) {
		$savemsg=gettext("지정한 연결 수가 할당된 ALTQ를 사용할 수 있는 인터페이스 수보다 많습니다!");
		$stepid--;
		return;
	}
	$cfgname = "traffic_shaper_wizard_multi_all.xml";

	$fields =& $pkg['step'][1]['fields']['field'];

	/*
	unset($config['ezshaper']['step2']);
	$config['ezshaper']['step2'] = array();
	write_config();
	*/
	$fields = array();

	for ($i = 0; $i < $numberoflocalinterfaces; $i++) {
		$field = array();
		$interface_friendly = $i+1;
		$field['name'] = "인터페이스 LAN에 대한 연결 속도 및 스케줄러 정보 설정 #{$interface_friendly}";
		$field['type'] = "listtopic";
		$fields[] = $field;

		$field = array();
		$field['displayname'] = "Interface & Scheduler";
		$field['name'] = "local{$i}interface";
		$field['type'] = "select";
		$field['options']['option'] = array();
		foreach ($iflist as $ifname => $ifdescr) {
			// Skip wan interfaces here
			if (interface_has_gateway($ifname) ||
			    interface_has_gatewayv6($ifname)) {
				continue;
			}
			$opts = array();
			$opts['displayname'] = $ifdescr;
			$opts['name'] = $ifname;
			$opts['value'] = $ifname;
			$field['options']['option'][] = $opts;
		}
		$field['combinefieldsbegin'] = "true";
		$field['bindstofield'] = "ezshaper->step2->local{$i}interface";
		$fields[] = $field;

		$field = array();
		$field['combinefieldsend'] = "true";
		$field['dontdisplayname'] = "true";
		$field['dontcombinecells'] = "true";
		$field['name'] = "local{$i}downloadscheduler";
		$field['type'] = "select";
		$field['typehint'] = "이 로컬 인터페이스에 적용할 부문 대기 중.";
		$field['options']['option'] = array();
		$opts = array();
		$opts['name'] = "PRIQ";
		$opts['value'] = "PRIQ";
		$field['options']['option'][] = $opts;
		$opts = array();
		$opts['name'] = "HFSC";
		$opts['value'] = "HFSC";
		$field['options']['option'][] = $opts;
		$opts = array();
		$opts['name'] = "CBQ";
		$opts['value'] = "CBQ";
		$field['options']['option'][] = $opts;
		$field['bindstofield'] = "ezshaper->step2->local{$i}downloadscheduler";
		$fields[] = $field;
	}

	for ($i = 0; $i < $numberofconnections; $i++) {
		$field = array();
		$interface_friendly = $i+1;
		$field['name'] = "인터페이스 WAN에 대한 연결 속도 및 스케줄러 정보 설정 # {$interface_friendly}";
		$field['type'] = "listtopic";
		$fields[] = $field;

		$field = array();
		$field['displayname'] = "인터페이스 & 스케줄러";
		$field['name'] = "conn{$i}interface";
		$field['type'] = "select";
		$interface_real = $i+1;
		$field['options']['option'] = array();
		foreach ($iflist as $ifname => $ifdescr) {
			// Skip lan interfaces here
			if (!$list_all_ifs &&
			    !interface_has_gateway($ifname) &&
			    !interface_has_gatewayv6($ifname)) {
				continue;
			}
			$opts = array();
			$opts['displayname'] = $ifdescr;
			$opts['name'] = $ifname;
			$opts['value'] = $ifname;
			$field['options']['option'][] = $opts;
		}
		$field['bindstofield'] = "ezshaper->step2->conn{$i}interface";
		$field['combinefieldsbegin'] = "true";
		$fields[] = $field;

		$field = array();
		$field['combinefieldsend'] = "true";
		$field['dontdisplayname'] = "true";
		$field['dontcombinecells'] = "true";
		$field['name'] = "conn{$i}uploadscheduler";
		$field['type'] = "select";
		$field['typehint'] = "이 연결의 업로드에 적용할 대기 중인 규칙입니다.";
		$field['options']['option'] = array();
		$opts = array();
		$opts['name'] = "PRIQ";
		$opts['value'] = "PRIQ";
		$field['options']['option'][] = $opts;
		$opts = array();
		$opts['name'] = "HFSC";
		$opts['value'] = "HFSC";
		$field['options']['option'][] = $opts;
		$opts = array();
		$opts['name'] = "CBQ";
		$opts['value'] = "CBQ";
		$field['options']['option'][] = $opts;
		$field['bindstofield'] = "ezshaper->step2->conn{$i}uploadscheduler";
		$fields[] = $field;

		$field = array();
		$field['displayname'] = "Upload";
		$field['name'] = "conn{$i}upload";
		$field['type'] = "input";
		$field['bindstofield'] = "ezshaper->step2->conn{$i}upload";
		$field['combinefieldsbegin'] = "true";
		$fields[] = $field;

		$field = array();
		$field['combinefieldsend'] = "true";
		$field['dontdisplayname'] = "true";
		$field['dontcombinecells'] = "true";
		$field['name'] = "conn{$i}uploadspeed";
		$field['typehint'] = "이 연결에 대역 폭을 업로드합니다.";
		$field['type'] = "select";
		$field['options']['option'] = array();
		$opts = array();
		$opts['value'] = "Kb";
		$opts['name'] = "Kbit/s";
		$field['options']['option'][] = $opts;
		$opts = array();
		$opts['value'] = "Mb";
		$opts['name'] = "Mbit/s";
		$field['options']['option'][] = $opts;
		$opts = array();
		$opts['value'] = "Gb";
		$opts['name'] = "Gbit/s";
		$field['options']['option'][] = $opts;
		$field['bindstofield'] = "ezshaper->step2->conn{$i}uploadspeed";
		$fields[] = $field;

		$field = array();
		$field['displayname'] = "다운로드";
		$field['name'] = "conn{$i}download";
		$field['type'] = "input";
		$field['bindstofield'] = "ezshaper->step2->conn{$i}download";
		$field['combinefieldsbegin'] = "true";
		$fields[] = $field;

		$field = array();
		$field['combinefieldsend'] = "true";
		$field['dontdisplayname'] = "true";
		$field['dontcombinecells'] = "true";
		$field['name'] = "conn{$i}downloadspeed";
		$field['typehint'] = "이 연결에서 대역 폭을 다운로드합니다.";
		$field['type'] = "select";
		$field['options']['option'] = array();
		$opts = array();
		$opts['value'] = "Kb";
		$opts['name'] = "Kbit/s";
		$field['options']['option'][] = $opts;
		$opts = array();
		$opts['value'] = "Mb";
		$opts['name'] = "Mbit/s";
		$field['options']['option'][] = $opts;
		$opts = array();
		$opts['value'] = "Gb";
		$opts['name'] = "Gbit/s";
		$field['options']['option'][] = $opts;
		$field['bindstofield'] = "ezshaper->step2->conn{$i}downloadspeed";
		$fields[] = $field;
	}
	$field = array();
	$field['name'] = "Next";
	$field['type'] = "submit";
	$fields[] = $field;
}

function step2_stepsubmitphpaction() {
	global $config;
	global $stepid, $savemsg;
	$sumdownloads = 0;

	/* Input Validation */
	$steps = intval($config['ezshaper']['step1']['numberofconnections']);
	$localint = intval($config['ezshaper']['step1']['numberoflocalinterfaces']);
	for ($i = 0; $i < $steps; $i++) {
		for ($j = $i + 1; $j <= $steps; $j++) {
			if ($_POST["conn{$i}interface"] == $_POST["conn{$j}interface"]) {
				$savemsg=gettext("{$i}과 {$j}연결에 대해서는 동일한 인터페이스를 선택할 수 없습니다.");
				$stepid--;
				return;
			}
			if (trim($_POST["conn{$i}uploadscheduler"]) != "PRIQ") {
				if (!is_numeric($_POST["conn{$i}upload"])) {
					$savemsg = gettext("연결{$i}의 업로드 대역 폭이 잘못되었습니다.");
					$stepid--;
					return;
				}
				if (!is_numeric($_POST["conn{$i}download"])) {
					$savemsg = gettext("연결{$i}의 다운로드 대역 폭이 잘못되었습니다.");
					$stepid--;
					return;
				}
				$upbw = $_POST["conn{$i}upload"];
				$downbw = $_POST["conn{$i}download"];
				if ($upbw < 1 || $downbw < 1) {
					$savemsg = gettext("지정한 대역 폭은 1보다 작을 수 없습니다.");
					$stepid--;
					return;
				}
				if (intval($upbw) < 128 && $_POST["conn{$i}uploadspeed"] == "Kb" && trim($_POST["conn{$i}uploadscheduler"]) == "CBQ") {
					$savemsg=gettext("128KB보다 작은 업로드는 CBQ스케줄러의 연결{$i}에 지원되지 않습니다.");
					$stepid--;
					return;
				}
			}
		}
		for ($j = 0; $j < $localint; $j++) {
			if ($_POST["conn{$i}interface"] == $_POST["local{$j}interface"]) {
				$savemsg=gettext("로컬과 외부에 동일한 인터페이스를 선택할 수 없습니다.");
				$stepid--;
				return;
			}
		}
	}
	for ($i = 0; $i < $localint; $i++) {
		for ($j = $i + 1; $j < $localint; $j++) {
			if ($_POST["local{$i}interface"] == $_POST["local{$j}interface"]) {
				$savemsg=gettext("로컬 인터페이스에서는 동일한 인터페이스를 두번 선택할 수 없습니다.");
				$stepid--;
				return;
			}
		}
	}

	/* This is necessary since the wizard expects predefined fields. */
	unset($config['ezshaper']['step2']);
	$config['ezshaper']['step2'] = array();

	for ($i = 0; $i < $localint; $i++) {
		$config['ezshaper']['step2']["local{$i}downloadscheduler"] = $_POST["local{$i}downloadscheduler"];
		$config['ezshaper']['step2']["local{$i}interface"] = $_POST["local{$i}interface"];
	}

	for ($i = 0; $i < $steps; $i++) {
		$config['ezshaper']['step2']["conn{$i}uploadscheduler"] = $_POST["conn{$i}uploadscheduler"];
		$config['ezshaper']['step2']["conn{$i}upload"] = $_POST["conn{$i}upload"];
		$config['ezshaper']['step2']["conn{$i}uploadspeed"] = $_POST["conn{$i}uploadspeed"];
		$config['ezshaper']['step2']["conn{$i}download"] = $_POST["conn{$i}download"];
		$config['ezshaper']['step2']["conn{$i}downloadspeed"] = $_POST["conn{$i}downloadspeed"];
		$config['ezshaper']['step2']["conn${i}interface"] = $_POST["conn{$i}interface"];
	}
}

function step3_stepbeforeformdisplay() {
	global $config, $pkg;
	global $stepid, $savemsg;

	$cfgname = "traffic_shaper_wizard_multi_all.xml";

	$numberofconnections = intval($config['ezshaper']['step1']['numberofconnections']);
	$numberoflocalinterfaces = intval($config['ezshaper']['step1']['numberoflocalinterfaces']);

	$fields =& $pkg['step'][1]['fields']['field'];

	$voipfields =& $pkg['step'][2]['fields']['field'];

	$voipfields = array();
	$enablefields = array();

	$field = array();
	$field['name'] = "enable";
	$field['type'] = "checkbox";
	$field['typehint'] = "IP트래픽에 대해 음성 우선 순위를 지정하십시오.";
	$field['bindstofield'] = "ezshaper->step3->enable";
	$field['descritpion'] = "이렇게 하면 VOIP트래픽의 우선 순위가 다른 모든 트래픽보다 높아집니다.";
	$voipfields[] = $field;

	$field = array();
	$field['name'] = "VOIP관련 설정";
	$field['type'] = "listtopic";
	$voipfields[] = $field;

	$field['name'] = "Provider";
	$enablefields[] = "Provider";
	$field['type'] = "select";
	$field['description'] = "제공자가 나열되지 않은 경우 일반을 선택합니다.";
	$field['options']['option'] = array();
	$opts = array();
	$opts['name'] = "Generic (lowdelay)";
	$opts['value'] = "Generic";
	$field['options']['option'][] = $opts;
	$opts = array();
	$opts['name'] = "VoicePulse";
	$opts['value'] = "VoicePulse";
	$field['options']['option'][] = $opts;
	$opts = array();
	$opts['name'] = "Asterisk/Vonage";
	$opts['value'] = "Asterisk";
	$field['options']['option'][] = $opts;
	$opts = array();
	$opts['name'] = "PanasonicTDA";
	$opts['value'] = "Panasonic";
	$field['options']['option'][] = $opts;
	$field['bindstofield'] = "ezshaper->step3->provider";
	$voipfields[] = $field;

	$field = array();
	$field['displayname'] = "Upstream SIP 서버";
	$field['name'] = "upstream_sip_server";
	$enablefields[] = "upstream_sip_server";
	$field['type'] = "inputalias";
	$field['description'] = "(선택 사항)이 옵션을 선택하면 공급 업체 필드가 재정의됩니다. 이를 통해 <strong>원격</strong> PBX또는 SIP트렁크의 IP주소를 제공하여 우선 순위를 정할 수 있습니다.  <br />참고:방화벽 별칭도 이 위치에서 사용할 수 있습니다.";
	$field['message'] = "IP주소 필드는 비어 있지 않으며 IP주소처럼 보이지 않습니다.";
	$field['bindstofield'] = "ezshaper->step3->address";
	$voipfields[] = $field;

	for ($i = 0; $i < $numberofconnections; $i++) {
		$field = array();
		$interface_friendly = $i+1;
		$field['name'] = "Connection WAN #{$interface_friendly}";
		$field['type'] = "listtopic";
		$voipfields[] = $field;

		$field = array();
		$field['displayname'] = "Upload";
		$field['name'] = "conn{$i}upload";
		$enablefields[] = "conn{$i}upload";
		$field['type'] = "input";
		$field['bindstofield'] = "ezshaper->step3->conn{$i}upload";
		$field['combinefieldsbegin'] = "true";
		$voipfields[] = $field;

		$field = array();
		$field['combinefieldsend'] = "true";
		$field['dontdisplayname'] = "true";
		$field['dontcombinecells'] = "true";
		$field['name'] = "conn{$i}uploadspeed";
		$enablefields[] = "conn{$i}uploadspeed";
		$field['typehint'] = "연결 시 VOIP전화에 대한 대역 폭 보증을{$i}에 업로드합니다.";
		$field['displayname'] = "Units";
		$field['type'] = "select";
		$field['options']['option'] = array();
		$opts = array();
		$opts['value'] = "Kb";
		$opts['name'] = "Kbit/s";
		$field['options']['option'][] = $opts;
		$opts = array();
		$opts['value'] = "Mb";
		$opts['name'] = "Mbit/s";
		$field['options']['option'][] = $opts;
		$opts = array();
		$opts['value'] = "Gb";
		$opts['name'] = "Gbit/s";
		$field['options']['option'][] = $opts;
		$field['bindstofield'] = "ezshaper->step3->conn{$i}uploadspeed";
		$voipfields[] = $field;
	}

	for ($i = 0; $i < $numberoflocalinterfaces; $i++) {
		$field = array();
		$interface_friendly = $i+1;
		$field['name'] = "Connection LAN #{$interface_friendly}";
		$field['type'] = "listtopic";
		$voipfields[] = $field;

		$field = array();
		$field['displayname'] = "Download";
		$field['name'] = "local{$i}download";
		$enablefields[] = "local{$i}download";
		$field['type'] = "input";
		$field['bindstofield'] = "ezshaper->step3->local{$i}download";
		$field['combinefieldsbegin'] = "true";
		$voipfields[] = $field;

		$field = array();
		$field['combinefieldsend'] = "true";
		$field['dontdisplayname'] = "true";
		$field['dontcombinecells'] = "true";
		$field['name'] = "local{$i}downloadspeed";
		$enablefields[] = "local{$i}downloadspeed";
		$field['typehint'] = "연결 시 VOIP전화에 대한 대역 폭 보증을 다운로드합니다.";
		$field['displayname'] = "Units";
		$field['type'] = "select";
		$field['options']['option'] = array();
		$opts = array();
		$opts['value'] = "Kb";
		$opts['name'] = "Kbit/s";
		$field['options']['option'][] = $opts;
		$opts = array();
		$opts['value'] = "Mb";
		$opts['name'] = "Mbit/s";
		$field['options']['option'][] = $opts;
		$opts = array();
		$opts['value'] = "Gb";
		$opts['name'] = "Gbit/s";
		$field['options']['option'][] = $opts;
		$field['bindstofield'] = "ezshaper->step3->local{$i}downloadspeed";
		$voipfields[] = $field;
	}

	$field = array();
	$field['name'] = "Next";
	$field['type'] = "submit";
	$voipfields[] = $field;
	$voipfields[0]['enablefields'] = implode(",", $enablefields);
}

function step3_stepsubmitphpaction() {
	global $config;
	global $stepid, $savemsg;

	if (!$_POST['enable'])
		return;

	if ($_POST['upstream_sip_server']) {
		if (!is_ipaddroralias($_POST['upstream_sip_server'])) {
			/* item is not an ip or alias.  error out */
			$savemsg=gettext("주소는 올바른 IP주소 또는 방화벽 별칭이어야 합니다. 계속하려면 이 값을 수정하십시오.");
			$stepid--;
			return;
		}
	}

	$steps = intval($config['ezshaper']['step1']['numberofconnections']);
	for ($i = 0; $i < $steps; $i++) {
		if ($config['ezshaper']['step2']["conn{$i}uploadscheduler"] == "PRIQ")
			continue;
		if (!is_numeric($_POST["conn{$i}upload"])) {
			$savemsg = gettext("연결의 업로드 대역 폭{$i}이(가) 잘못되었습니다.");
			$stepid--;
			return;
		}
		if ($_POST["conn{$i}uploadspeed"] == "%") {
			if (intval($_POST["conn{$i}upload"]) > 80) {
				$savemsg=gettext("{$i}연결의 VoIP업로드 대역 폭을 연결의 80%이상으로 설정할 수 없습니다.");
				$stepid--;
				return;
			}
		} else {
			$factor = wizard_get_bandwidthtype_scale($config['ezshaper']['step2']["conn{$i}uploadspeed"]);
			$ifbw = $factor * floatval($config['ezshaper']['step2']["conn{$i}upload"]);
			$factor = wizard_get_bandwidthtype_scale($_POST["conn{$i}uploadspeed"]);
			$input_bw = $factor * floatval($_POST["conn{$i}upload"]);
			if ((0.8 * $ifbw) < $input_bw) {
				$savemsg=gettext("연결{$i}의 VoIP업로드 대역 폭은 연결의 80%보다 크게 설정할 수 없다.");
				$stepid--;
				return;
			}
		}
	}

	$localint = intval($config['ezshaper']['step1']['numberoflocalinterfaces']);
	for ($i = 0; $i < $localint; $i++) {
		if ($config['ezshaper']['step2']["local{$i}downloadscheduler"] == "PRIQ")
			continue;
		if (!is_numeric($_POST["local{$i}download"])) {
			$savemsg = gettext("연결의 다운로드 대역 폭{$i}이(가) 잘못되었습니다.");
			$stepid--;
			return;
		}
		if ($_POST["local{$i}downloadspeed"] == "%") {
			if (intval($_POST["local{$i}download"]) > 80) {
				$savemsg=gettext("연결{$i}의 VoIP다운로드 대역 폭은 연결의 80%보다 크게 설정할 수 없다.");
				$stepid--;
				return;
			}
		} else {
			for ($j = 0; $j < $steps; $j++) {
				$factor = wizard_get_bandwidthtype_scale($config['ezshaper']['step2']["conn{$j}downloadspeed"]);
				$ifbw = $factor * floatval($config['ezshaper']['step2']["conn{$j}download"]);
				$factor = wizard_get_bandwidthtype_scale($_POST["local{$i}downloadspeed"]);
				$input_bw = $factor * floatval($_POST["local{$i}download"]);
				if ((0.8 * $ifbw) < $input_bw) {
					$savemsg=gettext("{$j}연결 시 VoIP다운로드 대역 폭을 연결의 80%이상으로 설정할 수 없습니다.");
					$stepid--;
					return;
				}
			}
		}
	}

	/* This is necessary since the wizard expects predefined fields. */
	unset($config['ezshaper']['step3']);
	$config['ezshaper']['step3'] = array();

	if (!empty($_POST['upstream_sip_server']))
		$config['ezshaper']['step3']['address'] = $_POST['upstream_sip_server'];
	if ($_POST['enable'] == 'on')
		$config['ezshaper']['step3']['enable'] = 'on';
	if (!empty($_POST['provider'])) {
		$VoIPproviders = array("Generic", "VoicePulse", "Asterisk", "Panasonic");
		if (in_array($_POST['provider'], $VoIPproviders)) {
			$config['ezshaper']['step3']['provider'] = $_POST['provider'];
		}
	}
	for ($i = 0; $i < $localint; $i++) {
		$config['ezshaper']['step3']["local{$i}download"] = $_POST["local{$i}download"];
		$config['ezshaper']['step3']["local{$i}downloadspeed"] = $_POST["local{$i}downloadspeed"];
	}

	for ($i = 0; $i < $steps; $i++) {
		$config['ezshaper']['step3']["conn{$i}upload"] = $_POST["conn{$i}upload"];
		$config['ezshaper']['step3']["conn{$i}uploadspeed"] = $_POST["conn{$i}uploadspeed"];
	}
}

function step4_stepsubmitphpaction() {
	global $config;
	global $stepid, $savemsg;

	if ( $_POST['enable'] ) {
		if (!$_POST['bandwidth']) {
			$savemsg="대역 폭의 값을 지정해야 합니다.";

			$stepid--;
			return;
		}
		if (!is_numeric($_POST['bandwidth'])) {
			$savemsg="게시된 값이 유효한 대역 폭이 아닙니다.";
			$stepid--;
			return;
		}
		if ($_POST['bandwidthspeed'] <> "%") {
			$savemsg = gettext("대역 폭 사양의 백분율만 허용됩니다.");
			$stepid--;
			return;
		}
		$bw = $_POST['bandwidth'];
		if ($bw > 15 || $bw < 2) {
			$savemsg="값은 2%에서 15%사이여야 합니다!";
			$stepid--;
			return;
		}
		if ($_POST['address'] <> "" && !is_ipaddroralias($_POST['address'])) {
			/* item is not an ip or alias.  error out */
			$savemsg=gettext("주소는 올바른 IP주소 또는 방화벽 별칭이어야 합니다. 계속하려면 이 값을 수정하십시오.");
			$stepid--;
		}
	}
}

function step5_stepsubmitphpaction() {
	global $stepid, $savemsg;
	if ( $_POST['enable'] ) {
		if ($_POST['p2pcatchall']) {
			if (!is_numeric($_POST['bandwidth'])) {
				$savemsg="게시된 값이 올바른 대역 폭이 아닙니다.";
				$stepid--;
			}
			if ($_POST['bandwidthspeed'] <> "%") {
				$savemsg = gettext("대역 폭 사양의 백분율만 허용됩니다.");
				$stepid--;
				return;
			}
			$bw = $_POST['bandwidth'];
			if ($bw > 15 || $bw < 2) {
				$savemsg="값은 2%에서 15%사이여야 합니다!";
				$stepid--;
				return;
			}
		}
	}
}

function step8_stepsubmitphpaction() {
	global $g, $config;

	/* save the new configuration */
	apply_all_chosen_items();

	/* reset rrd queues */
	system("rm -f /var/db/rrd/*queuedrops.rrd");
	system("rm -f /var/db/rrd/*queues.rrd");
	enable_rrd_graphing();

	/* apply the new configuration to the system */
	filter_configure();

	/* And we're no longer dirty! */
	clear_subsystem_dirty('shaper');

	update_filter_reload_status("Initializing", true);
	header("Location: status_filter_reload.php");
	exit;
}

function apply_all_chosen_items() {
	global $config, $g, $altq_list_queues, $gamesplist, $voiplist, $othersplist, $p2plist;

	require_once("wizardapp.inc");

	/*
	 * Wipe previous config.
	 * Doing it here makes sense since we can wipe the previous config only after
	 * the user decides to do so, finishing the wizard.
	 */
	if (isset($config['shaper']['queue']))
		unset($config['shaper']['queue']);
	/* XXX: This is redundant, because this should be handled by converter at startup. */
	if (isset($config['shaper']['rule']))
		unset($config['shaper']['rule']);
	foreach ($config['filter']['rule'] as $key => $rule)
		if ($rule['wizard'] == "yes")
			unset($config['filter']['rule'][$key]);

	/* restart the cached config */
	unset($altq_list_queues);
	$altq_list_queues = array();

	$steps = intval($config['ezshaper']['step1']['numberofconnections']);

	$interfacelist = array();

	for ($i = 0; $i < $steps; $i++) {

		$tmppath = array();
		$altq =& new altq_root_queue();

		$altq->SetInterface($config['ezshaper']['step2']["conn{$i}interface"]);
		$interfacelist[] = $config['ezshaper']['step2']["conn{$i}interface"];
		$altq->SetScheduler($config['ezshaper']['step2']["conn{$i}uploadscheduler"]);
		$altq->SetBandwidth(floatval($config['ezshaper']['step2']["conn{$i}upload"]));
		$altq->SetBwscale($config['ezshaper']['step2']["conn{$i}uploadspeed"]);
		$altq->SetEnabled("on");
		$altq_list_queues[$altq->GetQname()] =& $altq;
		array_push($tmppath, $config['ezshaper']['step2']["conn{$i}interface"]);
		$altq->SetLink($tmppath);
		$altq->wconfig();

		$sched = $config['ezshaper']['step2']["conn{$i}uploadscheduler"];
		$voipbw =0;
		$voipbwunit = "Kb";
		$voip = false;
		$penalty = false;
		$penaltybw = 0;
		$penaltybwunit = "Kb";
		$p2p = false;
		$p2pcatchall = false;
		$p2pcatchbw = 0;
		$p2pcatchbwunit = "%";
		$games = false;
		$otherpriority = false;
		$remainbw = 0;
		$factor = 0;
		$upfactor = wizard_get_bandwidthtype_scale($config['ezshaper']['step2']["conn{$i}uploadspeed"]);
		$upbw = floatval($config['ezshaper']['step2']["conn{$i}upload"]) * $upfactor;

		if ($config['ezshaper']['step3']['enable']) {
			$voip = true;
			if ($sched != "PRIQ") {
				$voipbw = $config['ezshaper']['step3']["conn{$i}upload"];
				$voipbwunit = $config['ezshaper']['step3']["conn{$i}uploadspeed"];
				if ($voipbwunit == "%")
					$factor =  $upbw/100;
				else
					$factor = wizard_get_bandwidthtype_scale($voipbwunit);
				$remainbw += $voipbw * $factor;
			}
		}
		if ($config['ezshaper']['step4']['enable']) {
			$penalty = true;
			if ($sched != "PRIQ") {
				$penaltybw = $config['ezshaper']['step4']['bandwidth'];
				$penaltybwunit = $config['ezshaper']['step4']['bandwidthunit'];
				if ($penaltybwunit == "%")
					$factor = $upbw/100;
				else
					$factor = wizard_get_bandwidthtype_scale($penaltybwunit);
				$remainbw += $penaltybw * $factor;
			}
		} else {
			$penalty = false;
			$penaltybw = 0;
		}
		if ($config['ezshaper']['step5']['enable']) {
			$p2p = true;
			if ($config['ezshaper']['step5']['p2pcatchall']) {
				$p2pcatchall = true;
				if ($sched != "PRIQ") {
					$p2pcatchbw = $config['ezshaper']['step5']['bandwidth'];
					$p2pcatchbwunit = $config['ezshaper']['step5']['bandwidthunit'];
					if ($p2pcatchbwunit == "%")
						$factor = $upbw/100;
					else
						$factor = wizard_get_bandwidthtype_scale($p2pcatchbwunit);
					$remainbw += $p2pcatchbw * $factor;
				}
			} else {
				$p2pcatchall = false;
				$p2pcatchbw = 0;
			}
		} else {
			$p2p = false;
			$p2pcatchall = false;
			$p2pcatchbw = 0;
		}
		if ($config['ezshaper']['step6']['enable']) {
			$games = true;
		} else {
			$games = false;
		}

		if ($config['ezshaper']['step7']['enable']) {
			$otherpriority = true;
		} else  {
			$otherpriority = false;
		}

		if ($sched != "PRIQ") {
			$remainbw = round($remainbw / $upbw * 100, 2);

			if (intval($remainbw) > 0 && intval($remainbw) > 30) {
				$savemsg=gettext("사용자 지정 대역 폭이 30%보다 큽니다. 마법사를 계속하려면 이 옵션을 낮추십시오.");
				header("Location: wizard.php?xml=traffic_shaper_wizard_multi_all.xml&stepid=2&message={$savemsg}");
				exit;
			} else {
				$remainbw = 100 - $remainbw;
			}

			if ($sched == "CBQ")
				$q =& new cbq_queue();
			else if ($sched == "HFSC")
				$q =& new hfsc_queue();
			$tmpcf = array();
			$tmpcf['name'] = "qInternet";
			//$tmpcf['priority'] = 6;
			$tmpcf['ecn'] = "on";
			$tmpcf['enabled'] = "on";
			If ($sched == "CBQ") {
				$tmpcf['bandwidth'] = floatval($config['ezshaper']['step2']["conn{$i}upload"]);
				$tmpcf['bandwidthtype'] = $config['ezshaper']['step2']["conn{$i}uploadspeed"];
			}
			else if ($sched == "HFSC") {
				$tmpcf['linkshare3'] =
					floatval($config['ezshaper']['step2']["conn{$i}upload"]) . $config['ezshaper']['step2']["conn{$i}uploadspeed"];
				$tmpcf['upperlimit3'] =
					floatval($config['ezshaper']['step2']["conn{$i}upload"]) . $config['ezshaper']['step2']["conn{$i}uploadspeed"];
				$tmpcf['upperlimit'] = "on";


				$tmpcf['linkshare'] = "on";
				$tmpcf['bandwidth'] =  floatval($config['ezshaper']['step2']["conn{$i}upload"]);
				$tmpcf['bandwidthtype'] = $config['ezshaper']['step2']["conn{$i}uploadspeed"];
			}

			array_push($tmppath, "qInternet");
			$qtmp =& $altq->add_queue($q, $tmpcf, $tmppath, $input_errors);
			//array_pop($tmppath);
			//echo "qInternet <br />";
			//var_dump($input_errors);
			$qtmp->wconfig();
			$altq =& $qtmp;
		}

		if ($sched == "PRIQ")
			$q =& new priq_queue();
		else if ($sched == "CBQ")
			$q =& new cbq_queue();
		else if ($sched == "HFSC")
			$q =& new hfsc_queue();
		$tmpcf = array();
		$tmpcf['name'] = "qACK";
		$tmpcf['priority'] = 6;
		$tmpcf['ecn'] = "on";
		$tmpcf['enabled'] = "on";
		If ($sched == "CBQ") {
			$tmpcf['borrow'] = "on";
			$tmpcf['bandwidth'] = intval($remainbw * 0.2);
			$tmpcf['bandwidthtype'] = "%";
		}
		else if ($sched == "HFSC") {
			$lkbw = 0.20 * $remainbw;
			$tmpcf['linkshare3'] = intval($lkbw) . "%";
			$tmpcf['linkshare'] = "on";
			$tmpcf['bandwidth'] = intval($lkbw);
			$tmpcf['bandwidthtype'] = "%";
		}
		array_push($tmppath, "qACK");
		$qtmp =& $altq->add_queue($q, $tmpcf, $tmppath, $input_errors);
		array_pop($tmppath);
		//echo "qACK <br />";
		//var_dump($input_errors);
		$qtmp->wconfig();

		if ($sched == "PRIQ")
			$q =& new priq_queue();
		else if ($sched == "CBQ")
			$q =& new cbq_queue();
		else if ($sched == "HFSC")
			$q =& new hfsc_queue();
		$tmpcf = array();
		if ($p2pcatchall)
			$tmpcf['name'] = "qOthersDefault";
		else
			$tmpcf['name'] = "qDefault";
		$tmpcf['priority'] = 3;
		$tmpcf['enabled'] = "on";
		if (!$p2pcatchall)
			$tmpcf['default'] = "on";
		$tmpcf['ecn'] = "on";
		if ($sched == "CBQ") {
			$tmpcf['borrow'] = "on";
			$tmpcf['bandwidth'] = $remainbw * 0.1; /* 10% bandwidth */
			$tmpcf['bandwidthtype'] = "%";
		} else if ($sched == "HFSC") {
			$tmpcf['bandwidth'] = $remainbw * 0.1; /* 10% bandwidth */
			$tmpcf['bandwidthtype'] = "%";
		}
		array_push($tmppath, $tmpcf['name']);
		$qtmp =& $altq->add_queue($q, $tmpcf, $tmppath, $input_errors);
		array_pop($tmppath);
		//echo "qDefault <br />";
			//var_dump($input_errors);
			$qtmp->wconfig();

			if ($p2p) {
				if ($sched == "PRIQ")
					$q =& new priq_queue();
				else if ($sched == "CBQ")
					$q =& new cbq_queue();
				else if ($sched == "HFSC")
					$q =& new hfsc_queue();
				$tmpcf = array();
				$tmpcf['name'] = "qP2P";
				$tmpcf['priority'] = 1;
				$tmpcf['ecn'] = "on";
				$tmpcf['enabled'] = "on";
				if ($p2pcatchall) {
					if ($sched == "CBQ") {
						$tmpcf['borrow'] = "on";
						$tmpcf['bandwidth'] = $p2pcatchbw;
						$tmpcf['bandwidthtype'] = $p2pcatchbwunit;
					} else if ($sched == "HFSC") {
						$tmpcf['linkshare'] = "on";
						$tmpcf['linkshare3'] = "{$p2pcatchbw}{$p2pcatchbwunit}";
						$tmpcf['upperlimit'] = "on";
						$tmpcf['upperlimit3'] = "{$p2pcatchbw}{$p2pcatchbwunit}";
						$tmpcf['bandwidth'] = $p2pcatchbw;
						$tmpcf['bandwidthtype'] = $p2pcatchbwunit;
					}
					$tmpcf['default'] = "on";

				} else {
					if ($sched == "CBQ") {
						$tmpcf['borrow'] = "on";
						$tmpcf['bandwidth'] = $remainbw * 0.05; /* 5% bandwidth */
						$tmpcf['bandwidthtype'] = "%";
					} else if ($sched == "HFSC") {
						$tmpbw = $remainbw * 0.05; /* 5% bandwidth */
						$tmpcf['linkshare'] = "on";
						$tmpcf['linkshare3'] = "{$tmpbw}%";
						$tmpcf['upperlimit'] = "on";
						$tmpcf['upperlimit3'] = "{$tmpbw}%";
						$tmpcf['bandwidth'] = $tmpbw;
						$tmpcf['bandwidthtype'] = "%";
					}
				}
				array_push($tmppath, "qP2P");
				$qtmp =& $altq->add_queue($q, $tmpcf, $tmppath, $input_errors);
				array_pop($tmppath);
				//echo "qP2P <br />";
				//var_dump($input_errors);
				$qtmp->wconfig();
			}

			if ($voip) {
				if ($sched == "PRIQ")
					$q =& new priq_queue();
				else if ($sched == "CBQ")
					$q =& new cbq_queue();
				else if ($sched == "HFSC")
					$q =& new hfsc_queue();
				$tmpcf = array();
				$tmpcf['name'] = "qVoIP";
				$tmpcf['priority'] = 7;
				$tmpcf['ecn'] = "on";
				$tmpcf['enabled'] = "on";
				if ($sched == "CBQ") {
					$tmpcf['borrow'] = "on";
					if ($voipbw > 0) {
						$tmpcf['bandwidth'] = $voipbw;
						$tmpcf['bandwidthtype'] = $voipbwunit;
					} else {
						$tmpcf['bandwidth'] = $remainbw * 0.2; /* 20% bandwidth */
						$tmpcf['bandwidthtype'] = "%";
					}
				} else if ($sched == "HFSC") {
					if ($voipbw > 0) {
						$tmpcf['realtime3'] = "{$voipbw}{$voipbwunit}";
					} else {
						$voipbw = $remainbw * 0.20; /* 20% bandwidth */
						$tmpcf['realtime3'] = "{$voipbw}%";
					}
					$tmpcf['realtime'] = "on";
					$tmpcf['bandwidth'] = 32;
					$tmpcf['bandwidthtype'] = "Kb";
				}
				array_push($tmppath, "qVoIP");
				$qtmp =& $altq->add_queue($q, $tmpcf, $tmppath, $input_errors);
				array_pop($tmppath);
				//echo "qVoIP <br />";
				//var_dump($input_errors);
				$qtmp->wconfig();
			}

			if ($games) {
				if ($sched == "PRIQ")
					$q =& new priq_queue();
				else if ($sched == "CBQ")
					$q =& new cbq_queue();
				else if ($sched == "HFSC")
					$q =& new hfsc_queue();
				$tmpcf = array();
				$tmpcf['name'] = "qGames";
				$tmpcf['priority'] = 5;
				$tmpcf['enabled'] = "on";
				$tmpcf['ecn'] = "on";
				if ($sched == "CBQ") {
					$tmpcf['borrow'] = "on";
					$tmpcf['bandwidth'] = $remainbw * 0.2; /* 20% bandwidth */
					$tmpcf['bandwidthtype'] = "%";
				} else if ($sched == "HFSC") {
					$gamesbw = $remainbw * 0.2; /* 20% bandwidth */
					$tmpcf['linkshare'] = "on";
					$tmpcf['linkshare3'] = "{$gamesbw}%";
					$tmpcf['bandwidth'] = "{$gamesbw}";
					$tmpcf['bandwidthtype'] = "%";
				}
				array_push($tmppath, "qGames");
				$qtmp =& $altq->add_queue($q, $tmpcf, $tmppath, $input_errors);
				array_pop($tmppath);
				//echo "qGames <br />";
				//var_dump($input_errors);
				$qtmp->wconfig();
			}

			if ($otherpriority) {
				if ($sched == "PRIQ")
					$q =& new priq_queue();
				else if ($sched == "CBQ")
					$q =& new cbq_queue();
				else if ($sched == "HFSC")
					$q =& new hfsc_queue();
				$tmpcf = array();
				$tmpcf['name'] = "qOthersHigh";
				$tmpcf['priority'] = 4;
				$tmpcf['ecn'] = "on";
				$tmpcf['enabled'] = "on";
				if ($sched == "CBQ") {
					$tmpcf['borrow'] = "on";
					$tmpcf['bandwidth'] = $remainbw * 0.1; /* 10% bandwidth */
					$tmpcf['bandwidthtype'] = "%";
				} else if ($sched == "HFSC") {
					$tmpcf['linkshare'] = "on";
					$otherbw = $remainbw * 0.1; /* 10% bandwidth */
					$tmpcf['linkshare3'] = "{$otherbw}%";
					$tmpcf['bandwidth'] = $otherbw;
					$tmpcf['bandwidthtype'] = "%";
				}
				array_push($tmppath, "qOthersHigh");
				$qtmp =& $altq->add_queue($q, $tmpcf, $tmppath, $input_errors);
				array_pop($tmppath);
				//echo "qHigh <br />";
				//var_dump($input_errors);
				$qtmp->wconfig();


				if ($sched == "PRIQ")
					$q =& new priq_queue();
				else if ($sched == "CBQ")
					$q =& new cbq_queue();
				else if ($sched == "HFSC")
					$q =& new hfsc_queue();
				$tmpcf = array();
				$tmpcf['name'] = "qOthersLow";
				$tmpcf['priority'] = 2;
				$tmpcf['ecn'] = "on";
				$tmpcf['enabled'] = "on";
				if ($sched == "CBQ") {
					$tmpcf['borrow'] = "on";
					if ($penalty) {
						$tmpcf['bandwidthtype'] = $penaltybwunit;
						$tmpcf['bandwidth'] = $penaltybw;
					} else {
						$tmpcf['bandwidth'] = $remainbw * 0.05; /* 5% bandwidth */
						$tmpcf['bandwidthtype'] = "%";
					}
				} else if ($sched == "HFSC") {
					if ($penalty) {
						$tmpcf['linkshare3'] = "{$penaltybw}{$penaltybwunit}";
						$tmpcf['bandwidth'] = $penaltybw;
						$tmpcf['bandwidthtype'] = $penaltybwunit;
					} else {
						$lsbw = $remainbw * 0.05;
						$tmpcf['linkshare3'] = "{$lsbw}%"; /* 5% bandwidth */
						$tmpcf['bandwidth'] = $lsbw;
						$tmpcf['bandwidthtype'] = "%";
					}
					$tmpcf['linkshare'] = "on";
				}
				array_push($tmppath, "qOthersLow");
				$qtmp =& $altq->add_queue($q, $tmpcf, $tmppath, $input_errors);
				array_pop($tmppath);
				//echo "qLow <br />";
				//var_dump($input_errors);
				$qtmp->wconfig();
			}
			array_pop($tmppath);
		}

	/* LAN bandwidth ----------------------------------------------------------------------------------------- */
	$localint = intval($config['ezshaper']['step1']['numberoflocalinterfaces']);
	$lanbw = 0;
	for ($i = 0; $i < $steps; $i++) {
		$down = wizard_get_bandwidthtype_scale($config['ezshaper']['step2']["conn{$i}downloadspeed"]);
		$input_bw = floatval($config['ezshaper']['step2']["conn{$i}download"]) * $down;
		$lanbw += $input_bw;
	}

	for ($i = 0; $i < $localint; $i++) {

		$tmppath = array();
		$altq =& new altq_root_queue();

		$altq->SetInterface($config['ezshaper']['step2']["local{$i}interface"]);
		$altq->SetScheduler($config['ezshaper']['step2']["local{$i}downloadscheduler"]);
		$altq->SetBandwidth($lanbw/1000);
		$altq->SetBwscale("Kb");
		$altq->SetEnabled("on");
		$altq_list_queues[$altq->GetQname()] =& $altq;
		array_push($tmppath, $config['ezshaper']['step2']["local{$i}interface"]);
		$altq->SetLink($tmppath);
		//var_dump($input_errors);
		$altq->wconfig();

		$sched = $config['ezshaper']['step2']["local{$i}downloadscheduler"];
		$voipbw =0;
		$voipbwunit = "%";
		$voip = false;
		$penalty = false;
		$penaltybw = 0;
		$penaltybwunit = "%";
		$p2p = false;
		$p2pcatchall = false;
		$p2pcatchbw = 0;
		$games = false;
		$otherpriority = false;
		$remainbw = 0;

		if ($config['ezshaper']['step3']['enable']) {
			$voip = true;
			if ($sched != "PRIQ") {
				$voipbw = $config['ezshaper']['step3']["local{$i}download"];
				$voipbwunit = $config['ezshaper']['step3']["local{$i}downloadspeed"];
				if ($sched != HFSC) {
					if ($voipbwunit == "%")
						$factor = $lanbw/100;
					else
						$factor = wizard_get_bandwidthtype_scale($voipbwunit);
					$remainbw += floatval($voipbw) * $factor;
				} else
					$remainbw += 32000; /* 32Kbit/s reserved for HFSC link sharing */
			}
		}
		if ($config['ezshaper']['step4']['enable']) {
			$penalty = true;
			if ($sched != "PRIQ") {
				$penaltybw = $config['ezshaper']['step4']['bandwidth'];
				$penaltybwunit = $config['ezshaper']['step4']['bandwidthunit'];
				if ($penaltybwunit == "%")
					$factor = $lanbw/100;
				else
					$factor = wizard_get_bandwidthtype_scale($penaltybwunit);
				$remainbw += floatval($penaltybw) * $factor;
			}
		} else {
			$penalty = false;
			$penaltybw = 0;
		}
		if ($config['ezshaper']['step5']['enable']) {
			$p2p = true;
			if ($config['ezshaper']['step5']['p2pcatchall']) {
				$p2pcatchall = true;
				if ($sched != "PRIQ") {
					$p2pcatchbw = $config['ezshaper']['step5']['bandwidth'];
					$p2pcatchbwunit = $config['ezshaper']['step5']['bandwidthunit'];
					if ($p2pcatchbwunit == "%")
						$factor = $upbw/100;
					else
						$factor = wizard_get_bandwidthtype_scale($p2pcatchbwunit);
					$remainbw += floatval($p2pcatchbw) * $factor;
				}
			} else {
				$p2pcatchall = false;
				$p2pcatchbw = 0;
			}
		} else {
			$p2p = false;
			$p2pcatchall = false;
			$p2pcatchbw = 0;
		}
		if ($config['ezshaper']['step6']['enable']) {
			$games = true;
		} else {
			$games = false;
		}

		if ($config['ezshaper']['step7']['enable']) {
			$otherpriority = true;
		} else  {
			$otherpriority = false;
		}
		if ($sched != "PRIQ") {
			$remainbw = round($remainbw / $lanbw * 100, 2);

			if (intval($remainbw) > 0 && intval($remainbw) > 40) {
				$savemsg=gettext("사용자 지정 대역 폭은 40%이상입니다. 마법사를 계속하려면 해당 기능을 낮추십시오.");
				header("Location: wizard.php?xml=traffic_shaper_wizard_multi_all.xml&stepid=2&message={$savemsg}");
				exit;
			} else {
				$remainbw = 100 - $remainbw;
			}

			$lanqbw = $lanbw;
		}
		if (!$p2pcatchall) {
			if ($sched == "PRIQ")
				$q =& new priq_queue();
			else if ($sched == "CBQ")
				$q =& new cbq_queue();
			else if ($sched == "HFSC")
				$q =& new hfsc_queue();
			$tmpcf = array();
			$tmpcf['name'] = "qLink";
			$tmpcf['priority'] = 2;
			$tmpcf['enabled'] = "on";
			$tmpcf['default'] = "on";
			$tmpcf['qlimit'] = 500;
			$tmpcf['ecn'] = "on";
			if ($sched == "CBQ") {
				$tmpcf['borrow'] = "on";
				$tmpcf['bandwidth'] = 20; /* 20% bandwidth */
				$tmpcf['bandwidthtype'] = "%";
			} else if ($sched == "HFSC") {
				$tmpcf['bandwidth'] = 20; /* 20% bandwidth */
				$tmpcf['bandwidthtype'] = "%";
			}
			array_push($tmppath, $tmpcf['name']);
			$qtmp =& $altq->add_queue($q, $tmpcf, $tmppath, $input_errors);
			array_pop($tmppath);
			//echo "qDefault <br />";
			//var_dump($input_errors);
			$qtmp->wconfig();

			if ($sched != "PRIQ")
				$lanqbw *= 0.8; /* lanqbw - 20% */
		}

		if ($sched != "PRIQ") {
			if ($sched == "CBQ")
				$q =& new cbq_queue();
			else if ($sched == "HFSC")
				$q =& new hfsc_queue();
			$tmpcf = array();
			$tmpcf['name'] = "qInternet";
			//$tmpcf['priority'] = 6;
			$tmpcf['ecn'] = "on";
			$tmpcf['enabled'] = "on";
			If ($sched == "CBQ") {
				$tmpcf['bandwidth'] = $lanqbw/1000;
				$tmpcf['bandwidthtype'] = "Kb";
			}
			else if ($sched == "HFSC") {
				$tmpcf['linkshare3'] = ($lanqbw/1000) . "Kb";
				$tmpcf['upperlimit3'] = ($lanqbw/1000) . "Kb";
				$tmpcf['upperlimit'] = "on";
				$tmpcf['linkshare'] = "on";
				$tmpcf['bandwidth'] =  $lanqbw/1000;
				$tmpcf['bandwidthtype'] = "Kb";
			}
			array_push($tmppath, "qInternet");
			$qtmp =& $altq->add_queue($q, $tmpcf, $tmppath, $input_errors);
			//array_pop($tmppath);
			//echo "qInternet <br />";
			//var_dump($input_errors);
			$qtmp->wconfig();
			$altq =& $qtmp;
		}

			if ($sched == "PRIQ")
				$q =& new priq_queue();
			else if ($sched == "CBQ")
				$q =& new cbq_queue();
			else if ($sched == "HFSC")
			$q =& new hfsc_queue();
			$tmpcf = array();
			$tmpcf['name'] = "qACK";
			$tmpcf['priority'] = 6;
			$tmpcf['ecn'] = "on";
			$tmpcf['enabled'] = "on";
			If ($sched == "CBQ") {
				$tmpcf['borrow'] = "on";
				$tmpcf['bandwidth'] = $remainbw * 0.2;
				$tmpcf['bandwidthtype'] = "%";
			}
			else if ($sched == "HFSC") {
				$lkbw = 0.20 * $remainbw;
				$tmpcf['linkshare3'] = "{$lkbw}%";
				$tmpcf['linkshare'] = "on";
				$tmpcf['bandwidth'] = $lkbw;
				$tmpcf['bandwidthtype'] = "%";
			}
			array_push($tmppath, "qACK");
			$qtmp =& $altq->add_queue($q, $tmpcf, $tmppath, $input_errors);
			array_pop($tmppath);
			//echo "qACK <br />";
			//var_dump($input_errors);
			$qtmp->wconfig();

			if ($p2p) {
				if ($sched == "PRIQ")
					$q =& new priq_queue();
				else if ($sched == "CBQ")
					$q =& new cbq_queue();
				else if ($sched == "HFSC")
					$q =& new hfsc_queue();
				$tmpcf = array();
				$tmpcf['name'] = "qP2P";
				$tmpcf['priority'] = 1;
				$tmpcf['ecn'] = "on";
				$tmpcf['enabled'] = "on";
				if ($p2pcatchall) {
					if ($sched == "CBQ") {
						$tmpcf['borrow'] = "on";
						$tmpcf['bandwidth'] = $p2pcatchbw;
						$tmpcf['bandwidthtype'] = $p2pcatchbwunit;
					} else if ($sched == "HFSC") {
						$tmpcf['linkshare'] = "on";
						$tmpcf['linkshare3'] = "{$p2pcatchbw}{$p2pcatchbwunit}";
						$tmpcf['upperlimit'] = "on";
						$tmpcf['upperlimit3'] = "{$p2pcatchbw}{$p2pcatchbwunit}";
						$tmpcf['bandwidth'] = $p2pcatchbw;
						$tmpcf['bandwidthtype'] = $p2pcatchbwunit;
					}
					$tmpcf['default'] = "on";
					$tmpcf['qlimit'] = 500;
				} else {
					if ($sched == "CBQ") {
						$tmpcf['borrow'] = "on";
						$tmpcf['bandwidth'] = $remainbw * 0.05; /* 5% bandwidth */
						$tmpcf['bandwidthtype'] = "%";
					} else if ($sched == "HFSC") {
						$tmpbw = $remainbw * 0.05; /* 5% bandwidth */
						$tmpcf['linkshare'] = "on";
						$tmpcf['linkshare3'] = "{$tmpbw}%";
						$tmpcf['upperlimit'] = "on";
						$tmpcf['upperlimit3'] = "{$tmpbw}%";
						$tmpcf['bandwidth'] = $tmpbw;
						$tmpcf['bandwidthtype'] = "%";
					}
				}
				array_push($tmppath, "qP2P");
				$qtmp =& $altq->add_queue($q, $tmpcf, $tmppath, $input_errors);
				array_pop($tmppath);
				//echo "qP2P <br />";
				//var_dump($input_errors);
				$qtmp->wconfig();
			}

			if ($voip) {
				if ($sched == "PRIQ")
					$q =& new priq_queue();
				else if ($sched == "CBQ")
					$q =& new cbq_queue();
				else if ($sched == "HFSC")
					$q =& new hfsc_queue();
				$tmpcf = array();
				$tmpcf['name'] = "qVoIP";
				$tmpcf['priority'] = 7;
				$tmpcf['ecn'] = "on";
				$tmpcf['enabled'] = "on";
				if ($sched == "CBQ") {
					$tmpcf['borrow'] = "on";
					if ($voipbw > 0) {
						$tmpcf['bandwidth'] = $voipbw;
						$tmpcf['bandwidthtype'] = $voipbwunit;
					} else {
						$tmpcf['bandwidth'] = $remainbw * 0.2; /* 20% bandwidth */
						$tmpcf['bandwidthtype'] = "%";
					}
				} else if ($sched == "HFSC") {
					if ($voipbw > 0) {
						$tmpcf['realtime3'] = "{$voipbw}{$voipbwunit}";
					} else {
						$voipbw = $remainbw * 0.20; /* 20% bandwidth */
						$tmpcf['realtime3'] = "{$voipbw}%";
					}
					$tmpcf['realtime'] = "on";
					$tmpcf['bandwidth'] = 32;
					$tmpcf['bandwidthtype'] = "Kb";
				}
				array_push($tmppath, "qVoIP");
				$qtmp =& $altq->add_queue($q, $tmpcf, $tmppath, $input_errors);
				array_pop($tmppath);
				//echo "qVoIP <br />";
				//var_dump($input_errors);
				$qtmp->wconfig();
			}

			if ($games) {
				if ($sched == "PRIQ")
					$q =& new priq_queue();
				else if ($sched == "CBQ")
					$q =& new cbq_queue();
				else if ($sched == "HFSC")
					$q =& new hfsc_queue();
				$tmpcf = array();
				$tmpcf['name'] = "qGames";
				$tmpcf['priority'] = 5;
				$tmpcf['enabled'] = "on";
				$tmpcf['ecn'] = "on";
				if ($sched == "CBQ") {
					$tmpcf['borrow'] = "on";
					$tmpcf['bandwidth'] = $remainbw * 0.2; /* 20% bandwidth */
					$tmpcf['bandwidthtype'] = "%";
				} else if ($sched == "HFSC") {
					$gamesbw = $remainbw * 0.2; /* 20% bandwidth */
					$tmpcf['linkshare'] = "on";
					$tmpcf['linkshare3'] = "{$gamesbw}%";
					$tmpcf['bandwidth'] = "{$gamesbw}";
					$tmpcf['bandwidthtype'] = "%";
				}
				array_push($tmppath, "qGames");
				$qtmp =& $altq->add_queue($q, $tmpcf, $tmppath, $input_errors);
				array_pop($tmppath);
				//echo "qGames <br />";
				//var_dump($input_errors);
				$qtmp->wconfig();
			}

			if ($otherpriority) {
				if ($sched == "PRIQ")
					$q =& new priq_queue();
				else if ($sched == "CBQ")
					$q =& new cbq_queue();
				else if ($sched == "HFSC")
					$q =& new hfsc_queue();
				$tmpcf = array();
				$tmpcf['name'] = "qOthersHigh";
				$tmpcf['priority'] = 4;
				$tmpcf['ecn'] = "on";
				$tmpcf['enabled'] = "on";
				if ($sched == "CBQ") {
					$tmpcf['borrow'] = "on";
					$tmpcf['bandwidth'] = $remainbw * 0.1; /* 10% bandwidth */
					$tmpcf['bandwidthtype'] = "%";
				} else if ($sched == "HFSC") {
					$tmpcf['linkshare'] = "on";
					$otherbw = $remainbw * 0.1; /* 10% bandwidth */
					$tmpcf['linkshare3'] = "{$otherbw}%";
					$tmpcf['bandwidth'] = $otherbw;
					$tmpcf['bandwidthtype'] = "%";
				}
				array_push($tmppath, "qOthersHigh");
				$qtmp =& $altq->add_queue($q, $tmpcf, $tmppath, $input_errors);
				array_pop($tmppath);
				//echo "qHigh <br />";
				//var_dump($input_errors);
				$qtmp->wconfig();


				if ($sched == "PRIQ")
					$q =& new priq_queue();
				else if ($sched == "CBQ")
					$q =& new cbq_queue();
				else if ($sched == "HFSC")
					$q =& new hfsc_queue();
				$tmpcf = array();
				$tmpcf['name'] = "qOthersLow";
				$tmpcf['priority'] = 3;
				$tmpcf['ecn'] = "on";
				$tmpcf['enabled'] = "on";
				if ($sched == "CBQ") {
					$tmpcf['borrow'] = "on";
					if ($penalty) {
						$tmpcf['bandwidth'] = $penaltybw;
						$tmpcf['bandwidthtype'] = $penaltybwunit;
					} else {
						$tmpcf['bandwidthtype'] = "%";
						$tmpcf['bandwidth'] = $remainbw * 0.05; /* 5% bandwidth */
					}
				} else if ($sched == "HFSC") {
					if ($penalty) {
						$tmpcf['linkshare3'] = "{$penaltybw}{$penaltybwunit}";
						$tmpcf['bandwidth'] = $penaltybw;
						$tmpcf['bandwidthtype'] = $penaltybwunit;
					} else {
						$lsbw = $remainbw * 0.05;
						$tmpcf['linkshare3'] = "{$lsbw}%"; /* 5% bandwidth */
						$tmpcf['bandwidth'] = $lsbw;
						$tmpcf['bandwidthtype'] = "%";
					}
					$tmpcf['linkshare'] = "on";
				}
				array_push($tmppath, "qOthersLow");
				$qtmp =& $altq->add_queue($q, $tmpcf, $tmppath, $input_errors);
				array_pop($tmppath);
				//echo "qLow <br />";
				//var_dump($input_errors);
				$qtmp->wconfig();
			}
			array_pop($tmppath);
	}

/* End LAN bandwidth ------------------------------------------------------------------------------------- */



	if (!is_array($config['filter']['rule']))
		$config['filter']['rule'] = array();

	$interfacelist = implode(",", $interfacelist);

	/* Rules */
	if ($penalty) {
		if (is_ipaddr($config['ezshaper']['step4']['address']) || is_alias($config['ezshaper']['step4']['address'])) {
			$rule = array();
			$rule['type'] = "match";
			$rule['interface'] = $interfacelist;
			$rule['descr'] = gettext("Penalty Box");
			$rule['defaultqueue'] = "qOthersLow";
			$rule['source']['address'] = $config['ezshaper']['step4']['address'];
			$rule['destination']['any'] = TRUE;
			$rule['floating'] = "yes";
			$rule['wizard'] = "yes";
			$rule['enabled'] = "on";
			$rule['created'] = make_config_revision_entry(null, gettext("Traffic Shaper Wizard"));
			$config['filter']['rule'][] = $rule;

		}
	}

			/* If user specifies an IP, we don't bother with providers */
			if ($voip) {
				if (is_ipaddr($config['ezshaper']['step3']['address']) || is_alias($config['ezshaper']['step3']['address'])) {
					/* create VOIP rules */
					$rule = array();
					$rule['type'] = "match";
					//$rule['interface'] = $interfacelist;
					$rule['descr'] = gettext("Connections From Upstream SIP Server");
					$rule['protocol'] = "udp";
					$rule['defaultqueue'] = "qVoIP";
					$rule['source']['address'] = $config['ezshaper']['step3']['address'];
					$rule['destination']['any'] = TRUE;
					$rule['floating'] = "yes";
					$rule['wizard'] = "yes";
					$rule['enabled'] = "on";
					$rule['created'] = make_config_revision_entry(null, gettext("Traffic Shaper Wizard"));
					$config['filter']['rule'][] = $rule;

					$rule = array();
					$rule['type'] = "match";
					//$rule['interface'] = $interfacelist;
					$rule['descr'] = gettext("업 스트림 SIP서버 연결");
					$rule['protocol'] = "udp";
					$rule['defaultqueue'] = "qVoIP";
					$rule['source']['any'] = TRUE;
					$rule['destination']['address'] = $config['ezshaper']['step3']['address'];
					$rule['floating'] = "yes";
					$rule['wizard'] = "yes";
					$rule['enabled'] = "on";
					$rule['created'] = make_config_revision_entry(null, gettext("트래픽 셰이퍼 마법사"));
					$config['filter']['rule'][] = $rule;

				} elseif ($config['ezshaper']['step3']['provider'] == "Generic") {
					/* create VOIP rules */
					$rule = array();
					$rule['type'] = "match";
					$rule['interface'] = $interfacelist;
					$rule['descr'] = "DiffServ/Lowdelay/Upload";
					$rule['protocol'] = "udp";
					$rule['source']['any'] = TRUE;
					$rule['defaultqueue'] = "qVoIP";
					$rule['destination']['any'] = TRUE;
					$rule['iptos'] = "lowdelay";
					$rule['floating'] = "yes";
					$rule['wizard'] = "yes";
					$rule['enabled'] = "on";
					$rule['created'] = make_config_revision_entry(null, gettext("트래픽 셰이퍼 마법사"));
					$config['filter']['rule'][] = $rule;

				} else {
					/* loop through voiplist[] */
					foreach ($voiplist[$config['ezshaper']['step3']['provider']] as $voip) {
						$rule = array();
						$rule['type'] = "match";
						$rule['interface'] = $interfacelist;
						$rule['defaultqueue'] = 'qVoIP';
						$rule['source']['any'] = TRUE;
						$rule['destination']['any'] = TRUE;
						$rule['descr'] = "m_voip {$voip[0]} outbound";
						$rule['floating'] = "yes";
						$rule['wizard'] = "yes";
						$rule['enabled'] = "on";
						$rule['destination']['port'] = $voip[2]."-".$voip[3];
						if ($voip[1] != '')
							$rule['protocol'] = $voip[1];
						$rule['created'] = make_config_revision_entry(null, gettext("트래픽 셰이퍼 마법사"));
						$config['filter']['rule'][] = $rule;
					}
				}
			}

			/* loop through p2plist[] */
			if ($p2p) {
				foreach ($config['ezshaper']['step5'] as $key => $val) {
					if (!is_array($p2plist[$key]))
						continue;
					foreach ($p2plist[$key] as $p2pclient) {
						$rule = array();
						$rule['type'] = "match";
						$rule['interface'] = $interfacelist;
						$rule['defaultqueue'] = 'qP2P';
						$rule['source']['any'] = TRUE;
						$rule['destination']['any'] = TRUE;
						$rule['descr'] = "m_P2P {$p2pclient[0]} outbound";
						$rule['floating'] = "yes";
						$rule['wizard'] = "yes";
						$rule['destination']['port'] = $p2pclient[2]."-".$p2pclient[3];
						if ($p2pclient[1] != '')
							$rule['protocol'] = $p2pclient[1];
						$rule['created'] = make_config_revision_entry(null, gettext("트래픽 셰이퍼 마법사"));
						$config['filter']['rule'][] = $rule;
					}
				}
			}

			/* loop through gamesplist[] */
			if ($games) {
				foreach ($config['ezshaper']['step6'] as $key => $val) {
					if (!is_array($gamesplist[$key]))
										continue;
					foreach ($gamesplist[$key] as $Gameclient) {
						$rule = array();
						$rule['type'] = "match";
						$rule['interface'] = $interfacelist;
						$rule['defaultqueue'] = 'qGames';
						if ($Gameclient[1] == "tcp")
							$rule['ackqueue'] = 'qACK';
						$rule['source']['any'] = TRUE;
						$rule['destination']['any'] = TRUE;
						$rule['floating'] = "yes";
						$rule['wizard'] = "yes";
						$rule['enabled'] = "on";
						$rule['descr'] = "m_Game {$Gameclient[0]} outbound";
						$rule['destination']['port'] = $Gameclient[2]."-".$Gameclient[3];
						if ($Gameclient[1] != '')
							$rule['protocol'] = $Gameclient[1];
						$rule['created'] = make_config_revision_entry(null, gettext("트래픽 셰이퍼 마법사"));
						$config['filter']['rule'][] = $rule;
					}
				}
			}

	/* loop through othersplist[] */
	if ($otherpriority) {
		foreach ($config['ezshaper']['step7'] as $key => $val) {
			if (!is_array($othersplist[$key]))
				continue;
			foreach ($othersplist[$key] as $otherclient) {
				$rule = array();
				$rule['type'] = "match";
				$rule['interface'] = $interfacelist;
				switch ($val) {
				case "H":
					$rule['defaultqueue'] = 'qOthersHigh'; /* posted value H or L */
					if ($otherclient[1] == "tcp")
								$rule['ackqueue'] = 'qACK';
					$loop = 0;
					break;
				case "L":
					$rule['defaultqueue'] = 'qOthersLow'; /* posted value H or L */
					if ($otherclient[1] == "tcp")
						$rule['ackqueue'] = 'qACK';
					$loop = 0;
					break;
				case "D":
					if ($p2pcatchall) {
						$loop = 0;
						$rule['defaultqueue'] = 'qOthersDefault';
						if ($otherclient[1] == "tcp")
							$rule['ackqueue'] = 'qACK';
					} else
						$loop = 1; /* It automatically goes to default queue */
					break;
				default:
					$loop = 1;
				}
				if (!$loop) {
					$rule['source']['any'] = TRUE;
					$rule['destination']['any'] = TRUE;
					$rule['floating'] = "yes";
					$rule['wizard'] = "yes";
					$rule['enabled'] = "on";
					$rule['descr'] = "m_Other {$otherclient[0]} outbound";

					if ($otherclient[2] or $otherclient[3]) {
						$rule['destination']['port'] = $otherclient[2]."-".$otherclient[3];
					}
					if ($otherclient[1] != '')
						$rule['protocol'] = $otherclient[1];
					$rule['created'] = make_config_revision_entry(null, gettext("트래픽 셰이퍼 마법사"));
					$config['filter']['rule'][] = $rule;
				}
			}
		}
	}
	write_config(gettext("LVMSense트래픽 셰이퍼 마법사를 통해 저장된 셰이퍼 구성입니다."));
}

function wizard_get_bandwidthtype_scale($type = "b") {
	switch ($type) {
	case "Gb":
		$factor = 1024 * 1024 * 1024;
		break;
	case "Mb":
		$factor = 1024 * 1024;
		break;
	case "Kb":
		$factor = 1024;
		break;
	case "b":
	default:
		$factor = 1;
		break;
	}
	return intval($factor);
}

?>

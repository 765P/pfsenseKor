<?php
/*
 * system_advanced_firewall.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2018 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2008 Shrew Soft Inc
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
##|*IDENT=page-system-advanced-firewall
##|*NAME=System: Advanced: Firewall & NAT
##|*DESCR=Allow access to the 'System: Advanced: Firewall & NAT' page.
##|*MATCH=system_advanced_firewall.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("functions.inc");
require_once("filter.inc");
require_once("shaper.inc");

$pconfig['disablefilter'] = $config['system']['disablefilter'];
$pconfig['scrubnodf'] = $config['system']['scrubnodf'];
$pconfig['scrubrnid'] = $config['system']['scrubrnid'];
$pconfig['optimization'] = $config['filter']['optimization'];
$pconfig['adaptivestart'] = $config['system']['adaptivestart'];
$pconfig['adaptiveend'] = $config['system']['adaptiveend'];
$pconfig['maximumstates'] = $config['system']['maximumstates'];
$pconfig['aliasesresolveinterval'] = $config['system']['aliasesresolveinterval'];
$old_aliasesresolveinterval = $config['system']['aliasesresolveinterval'];
$pconfig['checkaliasesurlcert'] = isset($config['system']['checkaliasesurlcert']);
$pconfig['maximumtableentries'] = $config['system']['maximumtableentries'];
$pconfig['maximumfrags'] = $config['system']['maximumfrags'];
$pconfig['disablereplyto'] = isset($config['system']['disablereplyto']);
$pconfig['disablenegate'] = isset($config['system']['disablenegate']);
$pconfig['bogonsinterval'] = $config['system']['bogons']['interval'];
$pconfig['disablenatreflection'] = $config['system']['disablenatreflection'];
$pconfig['enablebinatreflection'] = $config['system']['enablebinatreflection'];
$pconfig['reflectiontimeout'] = $config['system']['reflectiontimeout'];
$pconfig['bypassstaticroutes'] = isset($config['filter']['bypassstaticroutes']);
$pconfig['disablescrub'] = isset($config['system']['disablescrub']);
$pconfig['tftpinterface'] = explode(",", $config['system']['tftpinterface']);
$pconfig['disablevpnrules'] = isset($config['system']['disablevpnrules']);
$pconfig['tcpfirsttimeout'] = $config['system']['tcpfirsttimeout'];
$pconfig['tcpopeningtimeout'] = $config['system']['tcpopeningtimeout'];
$pconfig['tcpestablishedtimeout'] = $config['system']['tcpestablishedtimeout'];
$pconfig['tcpclosingtimeout'] = $config['system']['tcpclosingtimeout'];
$pconfig['tcpfinwaittimeout'] = $config['system']['tcpfinwaittimeout'];
$pconfig['tcpclosedtimeout'] = $config['system']['tcpclosedtimeout'];
$pconfig['udpfirsttimeout'] = $config['system']['udpfirsttimeout'];
$pconfig['udpsingletimeout'] = $config['system']['udpsingletimeout'];
$pconfig['udpmultipletimeout'] = $config['system']['udpmultipletimeout'];
$pconfig['icmpfirsttimeout'] = $config['system']['icmpfirsttimeout'];
$pconfig['icmperrortimeout'] = $config['system']['icmperrortimeout'];
$pconfig['otherfirsttimeout'] = $config['system']['otherfirsttimeout'];
$pconfig['othersingletimeout'] = $config['system']['othersingletimeout'];
$pconfig['othermultipletimeout'] = $config['system']['othermultipletimeout'];

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	if ((isset($_POST['adaptivestart']) && !isset($_POST['adaptiveend'])) || (!isset($_POST['adaptivestart']) && isset($_POST['adaptiveend']))) {
		$input_errors[] = gettext("방화벽 적응성 값을 함께 설정해야합니다.");
	}
	if (isset($_POST['adaptivestart']) && (strlen($_POST['adaptivestart']) > 0) && !is_numericint($_POST['adaptivestart'])) {
		$input_errors[] = gettext("방화벽 적응성 시작 값은 정수 여야합니다.");
	}
	if (isset($_POST['adaptive-end']) && (strlen($_POST['adaptive-end']) > 0) && !is_numericint($_POST['adaptive-end'])) {
		$input_errors[] = gettext("방화벽 적응성 끝 값은 정수 여야합니다.");
	}
	if ($_POST['firewall-maximum-states'] && !is_numericint($_POST['firewall-maximum-states'])) {
		$input_errors[] = gettext("방화벽 최대 상태는 정수 여야합니다.");
	}
	if ($_POST['aliases-hostnames-resolve-interval'] && !is_numericint($_POST['aliases-hostnames-resolve-interval'])) {
		$input_errors[] = gettext("alias 호스트 이름 결정 간격은 정수여야 합니다.");
	}
	if ($_POST['firewall-maximum-table-entries'] && !is_numericint($_POST['firewall-maximum-table-entries'])) {
		$input_errors[] = gettext("방화벽 최대 테이블 항목 값은 정수여야 합니다.");
	}
	if ($_POST['maximumfrags'] && !is_numericint($_POST['maximumfrags'])) {
		$input_errors[] = gettext("방화벽 최대 조각 입력 값은 정수여야 합니다.");
	}
	if ($_POST['tcpidletimeout'] && !is_numericint($_POST['tcpidletimeout'])) {
		$input_errors[] = gettext("TCP유휴 시간 초과는 정수여야 합니다.");
	}
	if ($_POST['reflectiontimeout'] && !is_numericint($_POST['reflectiontimeout'])) {
		$input_errors[] = gettext("반사 시간 초과는 정수여야 합니다.");
	}
	if ($_POST['tcpfirsttimeout'] && !is_numericint($_POST['tcpfirsttimeout'])) {
		$input_errors[] = gettext("TCP첫번째 시간 초과 값은 정수여야 합니다.");
	}
	if ($_POST['tcpopeningtimeout'] && !is_numericint($_POST['tcpopeningtimeout'])) {
		$input_errors[] = gettext("TCP 오픈 시간 초과 값은 정수여야 합니다.");
	}
	if ($_POST['tcpestablishedtimeout'] && !is_numericint($_POST['tcpestablishedtimeout'])) {
		$input_errors[] = gettext("TCP설정 시간 초과 값은 정수여야 합니다.");
	}
	if ($_POST['tcpclosingtimeout'] && !is_numericint($_POST['tcpclosingtimeout'])) {
		$input_errors[] = gettext("TCP마감 시간 초과 값은 정수여야 합니다.");
	}
	if ($_POST['tcpfinwaittimeout'] && !is_numericint($_POST['tcpfinwaittimeout'])) {
		$input_errors[] = gettext("TCP FIN대기 시간 초과 값은 정수여야 합니다.");
	}
	if ($_POST['tcpclosedtimeout'] && !is_numericint($_POST['tcpclosedtimeout'])) {
		$input_errors[] = gettext("TCP 닫힘 시간 초과 값은 정수여야 합니다.");
	}
	if ($_POST['udpfirsttimeout'] && !is_numericint($_POST['udpfirsttimeout'])) {
		$input_errors[] = gettext("UDP의 첫번째 시간 초과 값은 정수여야 합니다.");
	}
	if ($_POST['udpsingletimeout'] && !is_numericint($_POST['udpsingletimeout'])) {
		$input_errors[] = gettext("UDP단일 시간 초과 값이 정수여야 합니다.");
	}
	if ($_POST['udpmultipletimeout'] && !is_numericint($_POST['udpmultipletimeout'])) {
		$input_errors[] = gettext("UDP다중 시간 초과 값은 정수여야 합니다.");
	}
	if ($_POST['icmpfirsttimeout'] && !is_numericint($_POST['icmpfirsttimeout'])) {
		$input_errors[] = gettext("ICMP첫번째 타임 아웃 값은 정수여야 합니다.");
	}
	if ($_POST['icmperrortimeout'] && !is_numericint($_POST['icmperrortimeout'])) {
		$input_errors[] = gettext("ICMP오류 타임 아웃 값은 정수여야 합니다.");
	}
	if ($_POST['otherfirsttimeout'] && !is_numericint($_POST['otherfirsttimeout'])) {
		$input_errors[] = gettext("다른 첫번째 시간 초과 값은 정수여야 합니다.");
	}
	if ($_POST['othersingletimeout'] && !is_numericint($_POST['othersingletimeout'])) {
		$input_errors[] = gettext("기타 단일 타임 아웃 값은 정수여야 합니다.");
	}
	if ($_POST['othermultipletimeout'] && !is_numericint($_POST['othermultipletimeout'])) {
		$input_errors[] = gettext("다른 다중 시간 초과 값은 정수여야 합니다.");
	}

	ob_flush();
	flush();

	if (!$input_errors) {

		if ($_POST['disablefilter'] == "yes") {
			$config['system']['disablefilter'] = "enabled";
		} else {
			unset($config['system']['disablefilter']);
		}

		if ($_POST['disablevpnrules'] == "yes") {
			$config['system']['disablevpnrules'] = true;
		} else {
			unset($config['system']['disablevpnrules']);
		}
		if ($_POST['rfc959workaround'] == "yes") {
			$config['system']['rfc959workaround'] = "enabled";
		} else {
			unset($config['system']['rfc959workaround']);
		}

		if ($_POST['scrubnodf'] == "yes") {
			$config['system']['scrubnodf'] = "enabled";
		} else {
			unset($config['system']['scrubnodf']);
		}

		if ($_POST['scrubrnid'] == "yes") {
			$config['system']['scrubrnid'] = "enabled";
		} else {
			unset($config['system']['scrubrnid']);
		}

		if (is_numericint($_POST['adaptiveend'])) {
			$config['system']['adaptiveend'] = $_POST['adaptiveend'];
		} else {
			unset($config['system']['adaptiveend']);
		}
		if (is_numericint($_POST['adaptivestart'])) {
			$config['system']['adaptivestart'] = $_POST['adaptivestart'];
		} else {
			unset($config['system']['adaptivestart']);
		}

		if ($_POST['checkaliasesurlcert'] == "yes") {
			$config['system']['checkaliasesurlcert'] = true;
		} else {
			unset($config['system']['checkaliasesurlcert']);
		}

		$config['system']['optimization'] = $_POST['optimization'];
		$config['system']['maximumstates'] = $_POST['maximumstates'];
		$config['system']['aliasesresolveinterval'] = $_POST['aliasesresolveinterval'];
		$config['system']['maximumtableentries'] = $_POST['maximumtableentries'];
		$config['system']['maximumfrags'] = $_POST['maximumfrags'];

		if (!empty($_POST['tcpfirsttimeout'])) {
			$config['system']['tcpfirsttimeout'] = $_POST['tcpfirsttimeout'];
		} else {
			unset($config['system']['tcpfirsttimeout']);
		}
		if (!empty($_POST['tcpopeningtimeout'])) {
			$config['system']['tcpopeningtimeout'] = $_POST['tcpopeningtimeout'];
		} else {
			unset($config['system']['tcpopeningtimeout']);
		}
		if (!empty($_POST['tcpestablishedtimeout'])) {
			$config['system']['tcpestablishedtimeout'] = $_POST['tcpestablishedtimeout'];
		} else {
			unset($config['system']['tcpestablishedtimeout']);
		}
		if (!empty($_POST['tcpclosingtimeout'])) {
			$config['system']['tcpclosingtimeout'] = $_POST['tcpclosingtimeout'];
		} else {
			unset($config['system']['tcpclosingtimeout']);
		}
		if (!empty($_POST['tcpfinwaittimeout'])) {
			$config['system']['tcpfinwaittimeout'] = $_POST['tcpfinwaittimeout'];
		} else {
			unset($config['system']['tcpfinwaittimeout']);
		}
		if (!empty($_POST['tcpclosedtimeout'])) {
			$config['system']['tcpclosedtimeout'] = $_POST['tcpclosedtimeout'];
		} else {
			unset($config['system']['tcpclosedtimeout']);
		}
		if (!empty($_POST['udpfirsttimeout'])) {
			$config['system']['udpfirsttimeout'] = $_POST['udpfirsttimeout'];
		} else {
			unset($config['system']['udpfirsttimeout']);
		}
		if (!empty($_POST['udpsingletimeout'])) {
			$config['system']['udpsingletimeout'] = $_POST['udpsingletimeout'];
		} else {
			unset($config['system']['udpsingletimeout']);
		}
		if (!empty($_POST['udpmultipletimeout'])) {
			$config['system']['udpmultipletimeout'] = $_POST['udpmultipletimeout'];
		} else {
			unset($config['system']['udpmultipletimeout']);
		}
		if (!empty($_POST['icmpfirsttimeout'])) {
			$config['system']['icmpfirsttimeout'] = $_POST['icmpfirsttimeout'];
		} else {
			unset($config['system']['icmpfirsttimeout']);
		}
		if (!empty($_POST['icmperrortimeout'])) {
			$config['system']['icmperrortimeout'] = $_POST['icmperrortimeout'];
		} else {
			unset($config['system']['icmperrortimeout']);
		}
		if (!empty($_POST['otherfirsttimeout'])) {
			$config['system']['otherfirsttimeout'] = $_POST['otherfirsttimeout'];
		} else {
			unset($config['system']['otherfirsttimeout']);
		}
		if (!empty($_POST['othersingletimeout'])) {
			$config['system']['othersingletimeout'] = $_POST['othersingletimeout'];
		} else {
			unset($config['system']['othersingletimeout']);
		}
		if (!empty($_POST['othermultipletimeout'])) {
			$config['system']['othermultipletimeout'] = $_POST['othermultipletimeout'];
		} else {
			unset($config['system']['othermultipletimeout']);
		}

		if ($_POST['natreflection'] == "proxy") {
			unset($config['system']['disablenatreflection']);
			unset($config['system']['enablenatreflectionpurenat']);
		} else if ($_POST['natreflection'] == "purenat") {
			unset($config['system']['disablenatreflection']);
			$config['system']['enablenatreflectionpurenat'] = "yes";
		} else {
			$config['system']['disablenatreflection'] = "yes";
			unset($config['system']['enablenatreflectionpurenat']);
		}

		if ($_POST['enablebinatreflection'] == "yes") {
			$config['system']['enablebinatreflection'] = "yes";
		} else {
			unset($config['system']['enablebinatreflection']);
		}

		if ($_POST['disablereplyto'] == "yes") {
			$config['system']['disablereplyto'] = $_POST['disablereplyto'];
		} else {
			unset($config['system']['disablereplyto']);
		}

		if ($_POST['disablenegate'] == "yes") {
			$config['system']['disablenegate'] = $_POST['disablenegate'];
		} else {
			unset($config['system']['disablenegate']);
		}

		if ($_POST['enablenatreflectionhelper'] == "yes") {
			$config['system']['enablenatreflectionhelper'] = "yes";
		} else {
			unset($config['system']['enablenatreflectionhelper']);
		}

		$config['system']['reflectiontimeout'] = $_POST['reflection-timeout'];

		if ($_POST['bypassstaticroutes'] == "yes") {
			$config['filter']['bypassstaticroutes'] = $_POST['bypassstaticroutes'];
		} elseif (isset($config['filter']['bypassstaticroutes'])) {
			unset($config['filter']['bypassstaticroutes']);
		}

		if ($_POST['disablescrub'] == "yes") {
			$config['system']['disablescrub'] = $_POST['disablescrub'];
		} else {
			unset($config['system']['disablescrub']);
		}

		if ($_POST['tftpinterface']) {
			$config['system']['tftpinterface'] = implode(",", $_POST['tftpinterface']);
		} else {
			unset($config['system']['tftpinterface']);
		}

		if ($_POST['bogonsinterval'] != $config['system']['bogons']['interval']) {
			switch ($_POST['bogonsinterval']) {
				case 'daily':
					install_cron_job("/usr/bin/nice -n20 /etc/rc.update_bogons.sh", true, "1", "3", "*", "*", "*", "root", false);
					break;
				case 'weekly':
					install_cron_job("/usr/bin/nice -n20 /etc/rc.update_bogons.sh", true, "1", "3", "*", "*", "0", "root", false);
					break;
				case 'monthly':
					// fall through
				default:
					install_cron_job("/usr/bin/nice -n20 /etc/rc.update_bogons.sh", true, "1", "3", "1", "*", "*", "root", false);
			}
			$config['system']['bogons']['interval'] = $_POST['bogonsinterval'];
		}

		write_config(gettext("Changed Advanced Firewall/NAT settings."));

		// Kill filterdns when value changes, filter_configure() will restart it
		if (($old_aliasesresolveinterval != $config['system']['aliasesresolveinterval']) &&
		    isvalidpid("{$g['varrun_path']}/filterdns.pid")) {
			killbypid("{$g['varrun_path']}/filterdns.pid");
		}

		$changes_applied = true;
		$retval = 0;
		$retval |= filter_configure();
	}
}

$pgtitle = array(gettext("시스템"), gettext("어드밴스드"), htmlspecialchars(gettext("방화벽 & NAT")));
$pglinks = array("", "system_advanced_admin.php", "@self");
include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

if ($changes_applied) {
	print_apply_result_box($retval);
}

$tab_array = array();
$tab_array[] = array(gettext("어드민 엑세스"), false, "system_advanced_admin.php");
$tab_array[] = array(htmlspecialchars(gettext("방화벽 & NAT")), true, "system_advanced_firewall.php");
$tab_array[] = array(gettext("네트워킹"), false, "system_advanced_network.php");
$tab_array[] = array(gettext("Miscellaneous"), false, "system_advanced_misc.php");
$tab_array[] = array(gettext("시스템 터널링"), false, "system_advanced_sysctl.php");
$tab_array[] = array(gettext("Notifications"), false, "system_advanced_notifications.php");
display_top_tabs($tab_array);

?><div id="container"><?php

$form = new Form;
$section = new Form_Section('Firewall Advanced');

$section->addInput(new Form_Checkbox(
	'scrubnodf',
	'IP Do-Not-Fragment compatibility',
	'Clear invalid DF bits instead of dropping the packets',
	isset($config['system']['scrubnodf'])
))->setHelp('이렇게하면 단편화 된 패킷을 생성하는 호스트와 DF 비트 세트로 통신 할 수 있습니다. '.
	'이렇게하면 필터가 그러한 패킷을 삭제하지 않고 대신 조각 비트를 지우게됩니다.');

$section->addInput(new Form_Checkbox(
	'scrubrnid',
	'IP Random id generation',
	'Insert a stronger ID into IP header of packets passing through the filter.',
	isset($config['system']['scrubrnid'])
))->setHelp('패킷의 IP 식별 필드를 임의의 값으로 대체하여 예측 가능한 값을 사용하는 운영 체제를 보완합니다. 이 옵션은 선택적 패킷 재 '.
	'조립 후에 조각화되지 않은 패킷에만 적용됩니다.');

$section->addInput($input = new Form_Select(
	'optimization',
	'Firewall Optimization Options',
	$config['system']['optimization'],
	array(
		'normal' => 'Normal',
		'high-latency' => gettext('높은대기시간'),
		'aggressive' => gettext('Aggressive'),
		'conservative' => gettext('Conservative'),
	)
))->setHelp('사용할 상태 테이블 최적화 유형 선택하십시오.');

$section->addInput(new Form_Checkbox(
	'disablefilter',
	'Disable Firewall',
	'Disable all packet filtering.',
	isset($config['system']['disablefilter'])
))->setHelp('Note: 이것은 %1$s을 라우팅 전용 플랫폼으로 변환합니다! %2$s'.
	'Note: NAT를 해제합니다! 방화벽 규칙이 아닌 NAT 만 사용 중지하려면 %3$sOutbound NAT %4$s 페이지를 방문하십시오.', $g["product_name"], '<br/>', '<a href="firewall_nat_out.php">', '</a>');

$section->addInput(new Form_Checkbox(
	'disablescrub',
	'Disable Firewall Scrub',
	'Disables the PF scrubbing option which can sometimes interfere with NFS traffic.',
	isset($config['system']['disablescrub'])
));

$group = new Form_Group('Firewall Adaptive Timeouts');

$group->add(new Form_Input(
	'adaptivestart',
	'Adaptive start',
	'number',
	$pconfig['adaptivestart'],
	['min' => 0]
))->setHelp('상태 항목 수가이 값을 초과하면 적응 형 배율이 시작됩니다. 모든 타임 아웃 값은 인수 (adaptive.end - 상태 수) / ('.
	'adaptive.end - adaptive.start)로 선형 적으로 조정됩니다. 방화벽 최대 상태 값의 기본값은 60 %입니다.');

$group->add(new Form_Input(
	'adaptiveend',
	'Adaptive end',
	'number',
	$pconfig['adaptiveend'],
	['min' => 0]
))->setHelp('이 수의 상태 항목에 도달하면 모든 시간 초과 값은 0이되어 효과적으로 모든 상태 항목을 즉시 제거합니다. 이 값은 축척 비율을 '.
	'정의하는 데 사용되며 실제로 도달하지 않아야합니다 (낮은 상태 제한 설정, 아래 참조). 방화벽 최대 상태 값의 기본값은 120'.
	'%입니다.');

$group->setHelp('상태에 대한 시간 초과는 상태 테이블 항목의 수가 증가함에 따라 적응 적으로 조정될 수 있습니다. 기본 값을 사용하려면 '.
	'비워두고, 적응 시간 초과를 비활성화하려면 0으로 설정하십시오.');

$section->add($group);

$section->addInput(new Form_Input(
	'maximumstates',
	'Firewall Maximum States',
	'number',
	$pconfig['maximumstates'],
	['min' => 1, 'placeholder' => pfsense_default_state_size()]
))->setHelp('방화벽 상태 테이블에 보관할 최대 연결 수입니다. %1$s참고 : '.
	'기본값으로 비워 두십시오. 이 시스템에서 기본 크기는 % 2 $ d입니다.', '<br/>', pfsense_default_state_size());

$section->addInput(new Form_Input(
	'maximumtableentries',
	'Firewall Maximum Table Entries',
	'text',
	$pconfig['maximumtableentries'],
	['placeholder' => pfsense_default_table_entries_size()]
))->setHelp('별칭, sshlockout, snort 등과 같은 시스템의 최대 테이블 항목 수입니다. %1$s참고 : 기본값으로 비워 두십시오. 이 '.
	'시스템에서 기본 크기는 %2$d입니다.',
	'<br/>',
	pfsense_default_table_entries_size());

$section->addInput(new Form_Input(
	'maximumfrags',
	'Firewall Maximum Fragment Entries',
	'text',
	$pconfig['maximumfrags']
))->setHelp('스크럽 규칙에 따라 재 조립할 때 보유 할 최대 패킷 조각 수입니다. 기본(5000) 값으로 비워 두십시오.');

$section->addInput(new Form_Checkbox(
	'bypassstaticroutes',
	'Static route filtering',
	'Bypass firewall rules for traffic on the same interface',
	$pconfig['bypassstaticroutes']
))->setHelp('이 옵션은 하나 이상의 고정 경로가 정의 된 경우에만 적용됩니다. 이 기능을 사용하면 동일한 인터페이스를 통해 들어오고 나가는 '.
	'트래픽이 방화벽에 의해 검사되지 않습니다. 이는 여러 서브넷이 동일한 인터페이스에 연결된 일부 상황에서 바람직 할 수 있습니다.');

$section->addInput(new Form_Checkbox(
	'disablevpnrules',
	'Disable Auto-added VPN rules',
	'Disable all auto-added VPN rules.',
	isset($config['system']['disablevpnrules'])
))->setHelp('Note: 이렇게하면 IPsec에 대해 자동으로 추가 된 규칙이 비활성화됩니다.');

$section->addInput(new Form_Checkbox(
	'disablereplyto',
	'Disable reply-to',
	'Disable reply-to on WAN rules',
	$pconfig['disablereplyto']
))->setHelp('다중 WAN의 경우 일반적으로 트래픽이 도착하는 동일한 인터페이스를 떠나지 않도록해야하므로 기본적으로 reply-to가 자동으로  '.
	'추가됩니다. 브리징을 사용할 때 WAN 게이트웨이 IP가 브리지 인터페이스 뒤에있는 호스트의 게이트웨이 IP와 다른 경우이 동작을 '.
	'사용하지 않아야합니다.');

$section->addInput(new Form_Checkbox(
	'disablenegate',
	'Disable Negate rules',
	'Disable Negate rule on policy routing rules',
	$pconfig['disablenegate']
))->setHelp('다중 WAN의 경우 정책 라우팅을 사용할 때 트래픽이 직접 연결된 네트워크 및 VPN 네트워크에 도달하도록하는 것이 일반적입니다. '.
	'특수 목적으로는 비활성화 할 수 있지만 이러한 네트워크에 대한 규칙을 수동으로 만들어야합니다.');

$section->addInput(new Form_Input(
	'aliasesresolveinterval',
	'Aliases Hostnames Resolve Interval',
	'text',
	$pconfig['aliasesresolveinterval'],
	['placeholder' => '300']
))->setHelp('별칭에 구성된 호스트 이름을 확인하는 데 사용되는 간격 (초)입니다. %1$s참고 : 기본값 (300 초)의 경우이 항목을 비워 둡니다.', '<br/>');

$section->addInput(new Form_Checkbox(
	'checkaliasesurlcert',
	'Check certificate of aliases URLs',
	'Verify HTTPS certificates when downloading alias URLs',
	$pconfig['checkaliasesurlcert']
))->setHelp('별명에있는 모든 HTTPS 주소에 대해 인증서가 유효한지 확인하십시오. 유효하지 않거나 취소 된 경우 다운로드하지 마십시오.');

$form->add($section);
$section = new Form_Section('Bogon Networks');

$section->addInput(new Form_Select(
	'bogonsinterval',
	'Update Frequency',
	empty($pconfig['bogonsinterval']) ? 'monthly' : $pconfig['bogonsinterval'],
	array(
		'monthly' => gettext('월 단위'),
		'weekly' => gettext('주 단위'),
		'daily' => gettext('일별'),
	)
))->setHelp('The frequency of updating the lists of IP addresses that are '.
	'reserved (but not RFC 1918) or not yet assigned by IANA.');

$form->add($section);

if (count($config['interfaces']) > 1) {
	$section = new Form_Section('Network Address Translation');

	if (isset($config['system']['disablenatreflection'])) {
		$value = 'disable';
	} elseif (!isset($config['system']['enablenatreflectionpurenat'])) {
		$value = 'proxy';
	} else {
		$value = 'purenat';
	}

	$section->addInput(new Form_Select(
		'natreflection',
		'NAT Reflection mode for port forwards',
		$value,
		array(
			'disable' => gettext('비활성화'),
			'proxy' => gettext('NAT + proxy'),
			'purenat' => gettext('Pure NAT'),
		)
	))->setHelp('%1$s순수 NAT 모드는 NAT 규칙 세트를 사용하여 패킷을 포트 대상으로 전달합니다. 그것은 더 나은 확장 성을 가지고 있지만 '.
		'규칙이 로드 될 때 대상과의 통신에 사용되는 인터페이스와 게이트웨이 IP를 정확하게 결정할 수 있어야합니다. 프로토콜의 한계 '.
		'이외의 포트 수에는 고유 한 제한이 없습니다. 포트 전달에 사용할 수있는 모든 프로토콜이 지원됩니다. %2$s NAT + 프록시 '.
		'모드는 도우미 프로그램을 사용하여 패킷을 대상 포트로 전달합니다. 이는 규칙이로드 될 때 대상과의 통신에 사용되는 인터페이스 '.
		'및 / 또는 게이트웨이 IP를 정확하게 결정할 수없는 설정에서 유용합니다. 리플렉션 규칙은 500 포트보다 큰 범위에 대해서는 '.
		'생성되지 않으며 모든 포트 전달간에 총 1000 포트 이상에 대해서는 사용되지 않습니다. TCP 및 UDP 프로토콜 만 지원됩니다. '.
		'%3$s개별 규칙이 규칙에 따라이 시스템 설정을 무시하도록 구성 될 수 있습니다.',
		'</span><ul class="help-block"><li>', '</li><li>', '</li></ul><span class="help-block">');

	$section->addInput(new Form_Input(
		'reflectiontimeout',
		'Reflection Timeout',
		'number',
		$config['system']['reflectiontimeout'],
		['min' => 1]
	))->setHelp('Reflection timeout의 값을 초 단위로 입력하십시오. %1$sNote : NAT + 프록시 모드에서 포트 포워드의 리플렉션에만 적용됩니다.', '<br/>');

	$section->addInput(new Form_Checkbox(
		'enablebinatreflection',
		'Enable NAT Reflection for 1:1 NAT',
		'Automatic creation of additional NAT redirect rules from within the internal networks.',
		isset($config['system']['enablebinatreflection'])
	))->setHelp('참고 : 1:1 매핑의 리플렉션은 1:1 매핑의 인바운드 구성 요소에만 적용됩니다. 이 기능은 포트 포워드의 순수 NAT 모드와 '.
		'동일합니다. 자세한 내용은 위의 순수 NAT 모드 설명을 참조하십시오. 개별 규칙은 규칙에 따라이 시스템 설정을 무시하도록 구성 '.
		' 수 있습니다.');

	$section->addInput(new Form_Checkbox(
		'enablenatreflectionhelper',
		'Enable automatic outbound NAT for Reflection',
		'Automatic create outbound NAT rules that direct traffic back out to the same subnet it originated from.',
		isset($config['system']['enablenatreflectionhelper'])
	))->setHelp('1:1 NAT에 대한 포트 전달 또는 NAT 반영을위한 NAT Reflection의 순수 NAT 모드의 모든 기능에 필요합니다. Note : '.
		'이것은 지정된 인터페이스에서만 작동합니다. 다른 인터페이스는 라우터를 통해 응답 패킷을 되돌려 보내는 아웃 바운드 NAT 규칙을 '.
		'수동으로 만들어야합니다.');

	$section->addInput(new Form_Select(
		'tftpinterface',
		'TFTP Proxy',
		$pconfig['tftpinterface'],
		get_configured_interface_with_descr(),
		true
	))->setHelp('TFTP 프록시 도우미를 사용할 인터페이스를 선택하십시오.');

	$form->add($section);
}

$section = new Form_Section('State Timeouts (seconds - blank for default)');

$tcpTimeouts = array('First', 'Opening', 'Established', 'Closing', 'FIN Wait', 'Closed');
foreach ($tcpTimeouts as $name) {
	$keyname = 'tcp'. strtolower(str_replace(" ", "", $name)) .'timeout';
	$section->addInput(new Form_Input(
		$keyname,
		'TCP '. $name,
		'number',
		$config['system'][$keyname]
	));
}

$udpTimeouts = array('First', 'Single', 'Multiple');
foreach ($udpTimeouts as $name) {
	$keyname = 'udp'. strtolower(str_replace(" ", "", $name)) .'timeout';
	$section->addInput(new Form_Input(
		$keyname,
		'UDP '. $name,
		'number',
		$config['system'][$keyname]
	));
}

$icmpTimeouts = array('First', 'Error');
foreach ($icmpTimeouts as $name) {
	$keyname = 'icmp'. strtolower(str_replace(" ", "", $name)) .'timeout';
	$section->addInput(new Form_Input(
		$keyname,
		'ICMP '. $name,
		'number',
		$config['system'][$keyname]
	));
}

$otherTimeouts = array('First', 'Single', 'Multiple');
foreach ($otherTimeouts as $name) {
	$keyname = 'other'. strtolower(str_replace(" ", "", $name)) .'timeout';
	$section->addInput(new Form_Input(
		$keyname,
		'Other '. $name,
		'number',
		$config['system'][$keyname]
	));
}

$form->add($section);

print $form;

?></div>
<script type="text/javascript">
//<![CDATA[
events.push(function() {
	// Change help text based on the selector value
	function setOptText(val) {
		var htext = '<span class="text-success">';

		if (val == 'normal') {
			htext += '<?=gettext("기본 최적화 알고리즘");?>';
		} else if (val == 'high-latency') {
			htext += '<?=gettext("예를 들어 위성 링크, 유휴 연결은 기본값보다 나중에 만료됩니다.");?>';
		} else if (val == 'aggressive') {
			htext += '<?=gettext("유휴 연결을 더 빨리 만료시킵니다. CPU와 메모리를보다 효율적으로 사용하지만 합법적인 유휴 연결을 끊을 수 있습니다.");?>';
		} else if (val == 'conservative') {
			htext += '<?=gettext("메모리 사용량 및 CPU활용도를 높임으로써 올바른 유휴 연결이 손실되는 것을 방지하기 위해 노력합니다.");?>';
		}

		htext += '</span>';
		setHelpText('optimization', htext);
	}

	// ---------- Click checkbox handlers ---------------------------------------------------------

	$('#optimization').on('change', function() {
		setOptText(this.value);
	});

	// ---------- On initial page load ------------------------------------------------------------

	setOptText($('#optimization').val())
});
//]]>
</script>
<?php
include("foot.inc");
?>

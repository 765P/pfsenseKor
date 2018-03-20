<?php
/*
 * system_advanced_misc.php
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
##|*IDENT=page-system-advanced-misc
##|*NAME=System: Advanced: Miscellaneous
##|*DESCR=Allow access to the 'System: Advanced: Miscellaneous' page.
##|*MATCH=system_advanced_misc.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("functions.inc");
require_once("filter.inc");
require_once("shaper.inc");
require_once("vpn.inc");
require_once("vslb.inc");

$pconfig['proxyurl'] = $config['system']['proxyurl'];
$pconfig['proxyport'] = $config['system']['proxyport'];
$pconfig['proxyuser'] = $config['system']['proxyuser'];
$pconfig['proxypass'] = $config['system']['proxypass'];
$pconfig['harddiskstandby'] = $config['system']['harddiskstandby'];
$pconfig['lb_use_sticky'] = isset($config['system']['lb_use_sticky']);
$pconfig['srctrack'] = $config['system']['srctrack'];
$pconfig['gw_switch_default'] = isset($config['system']['gw_switch_default']);
$pconfig['powerd_enable'] = isset($config['system']['powerd_enable']);
$pconfig['crypto_hardware'] = $config['system']['crypto_hardware'];
$pconfig['thermal_hardware'] = $config['system']['thermal_hardware'];
$pconfig['schedule_states'] = isset($config['system']['schedule_states']);
$pconfig['gw_down_kill_states'] = isset($config['system']['gw_down_kill_states']);
$pconfig['skip_rules_gw_down'] = isset($config['system']['skip_rules_gw_down']);
$pconfig['use_mfs_tmpvar'] = isset($config['system']['use_mfs_tmpvar']);
$pconfig['use_mfs_tmp_size'] = $config['system']['use_mfs_tmp_size'];
$pconfig['use_mfs_var_size'] = $config['system']['use_mfs_var_size'];
$pconfig['do_not_send_uniqueid'] = isset($config['system']['do_not_send_uniqueid']);

$use_mfs_tmpvar_before = isset($config['system']['use_mfs_tmpvar']) ? true : false;
$use_mfs_tmpvar_after = $use_mfs_tmpvar_before;

$pconfig['powerd_ac_mode'] = "hadp";
if (!empty($config['system']['powerd_ac_mode'])) {
	$pconfig['powerd_ac_mode'] = $config['system']['powerd_ac_mode'];
}

$pconfig['powerd_battery_mode'] = "hadp";
if (!empty($config['system']['powerd_battery_mode'])) {
	$pconfig['powerd_battery_mode'] = $config['system']['powerd_battery_mode'];
}

$pconfig['powerd_normal_mode'] = "hadp";
if (!empty($config['system']['powerd_normal_mode'])) {
	$pconfig['powerd_normal_mode'] = $config['system']['powerd_normal_mode'];
}

$crypto_modules = array(
	'aesni' => gettext("AES-NI CPU기반 가속"),
	'cryptodev' => gettext("BSD암호화 장치(암호화 드라이브)"),
	'aesni_cryptodev' => gettext("AES-NI 및 BSD암호화 장치(aenni, 암호기)"),
);

$thermal_hardware_modules = array(
	'coretemp' => gettext("Intel코어*CPU 온-다이 열 센서"),
	'amdtemp' => gettext("AMD9W, K10및 K11CPU 온-다이 열 센서"));

if ($_POST) {
	unset($input_errors);
	$pconfig = $_POST;

	ob_flush();
	flush();

	if (!empty($_POST['crypto_hardware']) && !array_key_exists($_POST['crypto_hardware'], $crypto_modules)) {
		$input_errors[] = gettext("올바른 암호화 가속기를 선택하십시오.");
	}

	if (!empty($_POST['thermal_hardware']) && !array_key_exists($_POST['thermal_hardware'], $thermal_hardware_modules)) {
		$input_errors[] = gettext("올바른 열 하드웨어 센서를 선택하십시오.");
	}

	if (!empty($_POST['use_mfs_tmp_size']) && (!is_numeric($_POST['use_mfs_tmp_size']) || ($_POST['use_mfs_tmp_size'] < 40))) {
		$input_errors[] = gettext("/tmp 디렉토리 크기는 숫자여야 하며 40MiB이상이어야 합니다.");
	}

	if (!empty($_POST['use_mfs_var_size']) && (!is_numeric($_POST['use_mfs_var_size']) || ($_POST['use_mfs_var_size'] < 60))) {
		$input_errors[] = gettext("/var 디렉토리 크기는 숫자여야 하며 60MiB이상이어야 합니다.");
	}

	if (!empty($_POST['proxyport']) && !is_port($_POST['proxyport'])) {
		$input_errors[] = gettext("프록시 포트는 유효한 포트 번호(1에서 65535 사이)여야 합니다.");
	}

	if (!empty($_POST['proxyurl']) && !is_fqdn($_POST['proxyurl']) && !is_ipaddr($_POST['proxyurl'])) {
		$input_errors[] = gettext("프록시 URL은 올바른 IP주소 또는 FQDN이어야 합니다.");
	}

	if (!empty($_POST['proxyuser']) && preg_match("/[^a-zA-Z0-9\.\-_@]/", $_POST['proxyuser'])) {
		$input_errors[] = gettext("프록시 사용자 이름에 잘못된 문자가 있습니다.");
	}

	if ($_POST['proxypass'] != $_POST['proxypass_confirm']) {
		$input_errors[] = gettext("프록시 암호가 서로 일치하지 않습니다.");
	}

	if (!$input_errors) {

		if ($_POST['harddiskstandby'] <> "") {
			$config['system']['harddiskstandby'] = $_POST['harddiskstandby'];
			system_set_harddisk_standby();
		} else {
			unset($config['system']['harddiskstandby']);
		}

		if ($_POST['proxyurl'] <> "") {
			$config['system']['proxyurl'] = $_POST['proxyurl'];
		} else {
			unset($config['system']['proxyurl']);
		}

		if ($_POST['proxyport'] <> "") {
			$config['system']['proxyport'] = $_POST['proxyport'];
		} else {
			unset($config['system']['proxyport']);
		}

		if ($_POST['proxyuser'] <> "") {
			$config['system']['proxyuser'] = $_POST['proxyuser'];
		} else {
			unset($config['system']['proxyuser']);
		}

		if ($_POST['proxypass'] <> "") {
			if ($_POST['proxypass'] != DMYPWD) {
				$config['system']['proxypass'] = $_POST['proxypass'];
			}
		} else {
			unset($config['system']['proxypass']);
		}

		$need_relayd_restart = false;
		if ($_POST['lb_use_sticky'] == "yes") {
			if (!isset($config['system']['lb_use_sticky'])) {
				$config['system']['lb_use_sticky'] = true;
				$need_relayd_restart = true;
			}
			if ($config['system']['srctrack'] != $_POST['srctrack']) {
				$config['system']['srctrack'] = $_POST['srctrack'];
				$need_relayd_restart = true;
			}
		} else {
			if (isset($config['system']['lb_use_sticky'])) {
				unset($config['system']['lb_use_sticky']);
				$need_relayd_restart = true;
			}
		}

		if ($_POST['gw_switch_default'] == "yes") {
			$config['system']['gw_switch_default'] = true;
		} else {
			unset($config['system']['gw_switch_default']);
		}

		if ($_POST['pkg_nochecksig'] == "yes") {
			$config['system']['pkg_nochecksig'] = true;
		} elseif (isset($config['system']['pkg_nochecksig'])) {
			unset($config['system']['pkg_nochecksig']);
		}

		if ($_POST['do_not_send_uniqueid'] == "yes") {
			$config['system']['do_not_send_uniqueid'] = true;
		} else {
			unset($config['system']['do_not_send_uniqueid']);
		}

		if ($_POST['powerd_enable'] == "yes") {
			$config['system']['powerd_enable'] = true;
		} else {
			unset($config['system']['powerd_enable']);
		}

		$config['system']['powerd_ac_mode'] = $_POST['powerd_ac_mode'];
		$config['system']['powerd_battery_mode'] = $_POST['powerd_battery_mode'];
		$config['system']['powerd_normal_mode'] = $_POST['powerd_normal_mode'];

		if ($_POST['crypto_hardware']) {
			$config['system']['crypto_hardware'] = $_POST['crypto_hardware'];
		} else {
			unset($config['system']['crypto_hardware']);
		}

		if ($_POST['thermal_hardware']) {
			$config['system']['thermal_hardware'] = $_POST['thermal_hardware'];
		} else {
			unset($config['system']['thermal_hardware']);
		}

		if ($_POST['schedule_states'] == "yes") {
			$config['system']['schedule_states'] = true;
		} else {
			unset($config['system']['schedule_states']);
		}

		if ($_POST['gw_down_kill_states'] == "yes") {
			$config['system']['gw_down_kill_states'] = true;
		} else {
			unset($config['system']['gw_down_kill_states']);
		}

		if ($_POST['skip_rules_gw_down'] == "yes") {
			$config['system']['skip_rules_gw_down'] = true;
		} else {
			unset($config['system']['skip_rules_gw_down']);
		}

		if ($_POST['use_mfs_tmpvar'] == "yes") {
			$config['system']['use_mfs_tmpvar'] = true;
			$use_mfs_tmpvar_after = true;
		} else {
			unset($config['system']['use_mfs_tmpvar']);
			$use_mfs_tmpvar_after = false;
		}

		$config['system']['use_mfs_tmp_size'] = $_POST['use_mfs_tmp_size'];
		$config['system']['use_mfs_var_size'] = $_POST['use_mfs_var_size'];

		if (isset($_POST['rrdbackup'])) {
			if (($_POST['rrdbackup'] > 0) && ($_POST['rrdbackup'] <= 24)) {
				$config['system']['rrdbackup'] = intval($_POST['rrdbackup']);
			} else {
				unset($config['system']['rrdbackup']);
			}
		}
		if (isset($_POST['dhcpbackup'])) {
			if (($_POST['dhcpbackup'] > 0) && ($_POST['dhcpbackup'] <= 24)) {
				$config['system']['dhcpbackup'] = intval($_POST['dhcpbackup']);
			} else {
				unset($config['system']['dhcpbackup']);
			}
		}
		if (isset($_POST['logsbackup'])) {
			if (($_POST['logsbackup'] > 0) && ($_POST['logsbackup'] <= 24)) {
				$config['system']['logsbackup'] = intval($_POST['logsbackup']);
			} else {
				unset($config['system']['logsbackup']);
			}
		}

		// Add/Remove RAM disk periodic backup cron jobs according to settings and installation type.
		// Remove the cron jobs on full install if not using RAM disk.
		// Add the cron jobs on all others if the periodic backup option is set.  Otherwise the cron job is removed.
		if (!isset($config['system']['use_mfs_tmpvar'])) {
			/* See #7146 for detail on why the extra parameters are needed for the time being. */
			install_cron_job("/etc/rc.backup_rrd.sh", false, null, null, null, null, null, null, false);
			install_cron_job("/etc/rc.backup_dhcpleases.sh", false, null, null, null, null, null, null, false);
			install_cron_job("/etc/rc.backup_logs.sh", false, null, null, null, null, null, null, false);
		} else {
			/* See #7146 for detail on why the extra parameters are needed for the time being. */
			install_cron_job("/etc/rc.backup_rrd.sh", ($config['system']['rrdbackup'] > 0), $minute="0", "*/{$config['system']['rrdbackup']}", '*', '*', '*', 'root', false);
			install_cron_job("/etc/rc.backup_dhcpleases.sh", ($config['system']['dhcpbackup'] > 0), $minute="0", "*/{$config['system']['dhcpbackup']}", '*', '*', '*', 'root', false);
			install_cron_job("/etc/rc.backup_logs.sh", ($config['system']['logsbackup'] > 0), $minute="0", "*/{$config['system']['logsbackup']}", '*', '*', '*', 'root', false);
		}

		write_config();

		$changes_applied = true;
		$retval = 0;
		system_resolvconf_generate(true);
		$retval |= filter_configure();

		activate_powerd();
		load_crypto();
		load_thermal_hardware();
		if ($need_relayd_restart) {
			relayd_configure();
		}
	}
}

$pgtitle = array(gettext("시스템"), gettext("어드밴스드"), gettext("Miscellaneous"));
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
$tab_array[] = array(htmlspecialchars(gettext("방화벽 & NAT")), false, "system_advanced_firewall.php");
$tab_array[] = array(gettext("네트워킹"), false, "system_advanced_network.php");
$tab_array[] = array(gettext("Miscellaneous"), true, "system_advanced_misc.php");
$tab_array[] = array(gettext("시스템 "), false, "system_advanced_sysctl.php");
$tab_array[] = array(gettext("Notifications"), false, "system_advanced_notifications.php");
display_top_tabs($tab_array);

$form = new Form;
$section = new Form_Section('프록시 지원');

$section->addInput(new Form_Input(
	'proxyurl',
	'Proxy URL',
	'text',
	$pconfig['proxyurl']
))->setHelp('이 시스템이 아웃 바운드 인터넷 액세스에 사용할 프록시 서버의 호스트 이름 또는 IP 주소입니다.');

$section->addInput(new Form_Input(
	'proxyport',
	'Proxy Port',
	'text',
	$pconfig['proxyport']
))->setHelp('프록시 서버가 수신 대기중인 포트입니다.');

$section->addInput(new Form_Input(
	'proxyuser',
	'Proxy Username',
	'text',
	$pconfig['proxyuser']
))->setHelp('프록시 서버 인증을위한 사용자 이름. 선택 사항으로 인증을 '.
	'사용하지 않으려면 비워 두십시오.');

$section->addPassword(new Form_Input(
	'proxypass',
	'Proxy Password',
	'password',
	$pconfig['proxypass']
))->setHelp('프록시 서버 인증용 비밀번호.');

$form->add($section);
$section = new Form_Section('Load Balancing');

$group = new Form_Group('Load Balancing');

$group->add(new Form_Checkbox(
	'lb_use_sticky',
	'Use sticky connections',
	'Use sticky connections',
	$pconfig['lb_use_sticky']
))->setHelp('연속적인 연결은 동일한 소스의 연결이 동일한 웹 서버로 전송되는 라운드 로빈 방식으로 서버로 재 지정됩니다. 이 "고정 '.
	'연결"은이 연결을 참조하는 상태가있는 한 존재합니다. 상태가 만료되면 끈적 거리는 연결도 끊깁니다. 해당 호스트의 추가 연결은 '.
	'라운드 로빈의 다음 웹 서버로 리디렉션됩니다. 이 옵션을 변경하면로드 균형 조정 서비스가 다시 시작됩니다.');

$group->add(new Form_Input(
	'srctrack',
	'Source tracking timeout',
	'number',
	$pconfig['srctrack'],
	["placeholder" => "0"]
))->setHelp('고정 연결에 대한 소스 추적 제한 시간을 설정하십시오. 기본적으로이 값은 0이므로 상태가 만료되면 소스 추적이 제거됩니다. 이 '.
	'시간 초과를 더 높게 설정하면 소스/대상 관계가 더 오랜 시간 지속됩니다.');

$section->add($group);

$section->addInput(new Form_Checkbox(
	'gw_switch_default',
	'Default gateway switching',
	'Enable default gateway switching',
	$pconfig['gw_switch_default']
))->setHelp('기본 게이트웨이가 다운되면 기본 게이트웨이를 다른 사용 가능한 게이트웨이로 전환하십시오. '.
	'이는 게이트웨이 그룹을 사용하는 대부분의 모든 시나리오에서 불필요하므로 기본적으로 활성화되지 않습니다.');

$form->add($section);
$section = new Form_Section('Power Savings');

$section->addInput(new Form_Checkbox(
	'powerd_enable',
	'PowerD',
	'Enable PowerD',
	$pconfig['powerd_enable']
))->setHelp('The powerd utility monitors '.
	'powerd 유틸리티는 시스템 상태를 모니터링하고 이에 따라 다양한 전원 제어 옵션을 설정합니다. AC 전원이나 배터리를 사용하는 '.
	'동안 개별적으로 선택할 수있는 4 가지 모드 (최대, 최소, 적응 및 고 적응)를 제공합니다. 최대, 최소, 적응 형 및 고주파 형은 '.
	'max, min, adp, hadp로 축약 될 수 있습니다. 최대 모드는 최고 성능 값을 선택합니다. 최소 모드는 가장 낮은 성능 값을 '.
	'선택하여 절전 효과를 극대화합니다. 적응 형 모드는 시스템이 유휴 상태 일 때 성능을 저하시키고 시스템이 사용 중일 때 성능을 '.
	'저하시킴으로써 균형을 유지하려고 시도합니다. 작은 성능 손실 사이의 균형을 잘 유지하여 전력을 크게 절감 할 수 있습니다. '.
	'Hiadaptive 모드는 유사 적응 형 모드이지만 성능과 상호 작용이 전력 소비보다 더 중요한 시스템에 맞게 조정됩니다. 주파수를 '.
	'더 빨리 올리고 속도를 줄이며 CPU 부하를 두 배로 줄입니다.');

$modes = array(
	'hadp' => gettext('Hiadaptive'),
	'adp' => gettext('Adaptive'),
	'min' => gettext('최소'),
	'max' => gettext('최대'),
);

$section->addInput(new Form_Select(
	'powerd_ac_mode',
	'AC Power',
	$pconfig['powerd_ac_mode'],
	$modes
));

$section->addInput(new Form_Select(
	'powerd_battery_mode',
	'Battery Power',
	$pconfig['powerd_battery_mode'],
	$modes
));

$section->addInput(new Form_Select(
	'powerd_normal_mode',
	'Unknown Power',
	$pconfig['powerd_normal_mode'],
	$modes
));

$form->add($section);
$section = new Form_Section('Cryptographic & Thermal Hardware');

$section->addInput(new Form_Select(
	'crypto_hardware',
	'Cryptographic Hardware',
	$pconfig['crypto_hardware'],
	['' => gettext('None')] + $crypto_modules
))->setHelp('암호화 가속기 모듈은 하드웨어 지원을 사용하여 칩이있는 시스템에서 일부 암호화 기능을 가속화합니다. BSD Crypto Device '.
	'모듈을로드하면 Hifn 또는 ubsec 칩셋과 같이 커널에 내장 된 드라이버를 사용하여 가속 장치에 액세스 할 수 있습니다. '.
	'방화벽에 암호 칩이 포함되어 있지 않으면이 옵션이 적용되지 않습니다. 선택한 모듈을 언로드하려면이 옵션을 "none"으로 설정 한 '.
	'다음 재부팅하십시오.');

$section->addInput(new Form_Select(
	'thermal_hardware',
	'Thermal Sensors',
	$pconfig['thermal_hardware'],
	array('' => 'None/ACPI') + $thermal_hardware_modules
))->setHelp('지원되는 CPU를 사용하면 온도 센서를 선택하면 해당 드라이버를로드하여 온도를 읽을 수 있습니다. 이 값을 "None"으로 설정하면 '.
	'ACPI 호환 마더 보드 센서가있는 경우 온도를 읽으려고 시도합니다. 시스템에 지원되는 온도 센서 칩이 없으면이 옵션이 적용되지 '.
	'않습니다. 선택한 모듈을 언로드하려면이 옵션을 "none"으로 설정 한 다음 재부팅하십시오.');

$form->add($section);
$section = new Form_Section('Schedules');

$section->addInput(new Form_Checkbox(
	'schedule_states',
	'Schedule States',
	'Do not kill connections when schedule expires',
	$pconfig['schedule_states']
))->setHelp('기본적으로 일정이 만료되면 해당 일정에서 허용하는 연결이 끊어집니다. 이 옵션은 기존 연결에 대한 상태를 지우지 않음으로써 '.
	'해당 동작을 재정의합니다.');

$form->add($section);
$section = new Form_Section('Gateway Monitoring');

$section->addInput(new Form_Checkbox(
	'gw_down_kill_states',
	'State Killing on Gateway Failure',
	'Flush all states when a gateway goes down',
	$pconfig['gw_down_kill_states']
))->setHelp('이 상자를 선택하면 게이트웨이가 다운 될 때 모니터링 프로세스가 모든 상태를 플러시합니다.');

$section->addInput(new Form_Checkbox(
	'skip_rules_gw_down',
	'Skip rules when gateway is down',
	'Do not create rules when gateway is down',
	$pconfig['skip_rules_gw_down']
))->setHelp('기본적으로 룰에 지정된 게이트웨이가 있고이 게이트웨이가 작동 중지되면 게이트웨이를 생략하여 룰이 작성됩니다. 이 옵션은 대신 '.
	'전체 규칙을 생략하여 해당 동작을 재정의합니다.');

$form->add($section);
$section = new Form_Section('RAM Disk Settings (Reboot to Apply Changes)');

$section->addInput(new Form_Checkbox(
	'use_mfs_tmpvar',
	'Use RAM Disks',
	'Use memory file system for /tmp and /var',
	$pconfig['use_mfs_tmpvar']
))->setHelp('하드 디스크를 사용하지 말고 전체 설치시 RAM 디스크 (메모리 파일 시스템 디스크)로 /tmp 및 /var를 사용하도록 '.
	'설정하십시오. 이를 설정하면 /tmp 및 /var에있는 데이터가 손실됩니다. RRD, DHCP 임대 및 로그 디렉토리가 유지됩니다. 이 '.
	'설정을 변경하면 "저장"을 클릭 한 후 방화벽이 재부팅됩니다.');

$group = new Form_Group('RAM Disk Size');

$group->add(new Form_Input(
	'use_mfs_tmp_size',
	'/tmp RAM Disk Size',
	'number',
	$pconfig['use_mfs_tmp_size'],
	['placeholder' => 40]
))->setHelp('/tmp RAM 디스크<br />40보다 낮게 설정하지 마십시오.');

$group->add(new Form_Input(
	'use_mfs_var_size',
	'/var RAM Disk Size',
	'number',
	$pconfig['use_mfs_var_size'],
	['placeholder' => 60]
))->setHelp('/var RAM 디스크<br />60보다 낮게 설정하지 마십시오.');

$group->setHelp('RAM 디스크의 크기를 MiB 단위로 설정합니다.');

$section->add($group);

$group = new Form_Group('Periodic RAM Disk Data Backups');

$group->add(new Form_Input(
	'rrdbackup',
	'Periodic RRD Backup',
	'number',
	$config['system']['rrdbackup'],
	['min' => 0, 'max' => 24, 'placeholder' => '1 to 24 hours']
))->setHelp('RRD Data');

$group->add(new Form_Input(
	'dhcpbackup',
	'Periodic DHCP Leases Backup',
	'number',
	$config['system']['dhcpbackup'],
	['min' => 0, 'max' => 24, 'placeholder' => '1 to 24 hours']
))->setHelp('DHCP Leases');

$group->add(new Form_Input(
	'logsbackup',
	'Periodic Logs Backup',
	'number',
	$config['system']['logsbackup'],
	['min' => 0, 'max' => 24, 'placeholder' => '1 to 24 hours']
))->setHelp('로그 디렉토리');

$group->setHelp('RAM 디스크 데이터의 이러한 부분을 정기적으로 백업하여 다음 부팅시 자동으로 복원 할 수 있도록 간격을 시간 단위로 '.
	'설정합니다. 백업 빈도가 높을수록 미디어에 더 많은 쓰기 작업이 수행됩니다.');

$section->add($group);

$form->add($section);

$section = new Form_Section('Hardware Settings');

$opts = array(0.5,  1, 2,  3,  4,  5,  7.5,  10,  15,  20,  30,  60);
$vals = array(  6, 12, 24, 36, 48, 60,  90, 120, 180, 240, 241, 242);

$section->addInput(new Form_Select(
	'harddiskstandby',
	'Hard disk standby time',
	$pconfig['harddiskstandby'],
	['' => gettext("항상 켜기")] + array_combine($opts, $vals)
))->setHelp('마지막 액세스 이후 선택한 시간(분)이 경과하면 하드 디스크를 대기 모드로 전환합니다. %1$s%2$sCF 카드에는 이것을 설정하지 ' .
			'마십시오.%3$s', '<br />', '<strong>', '</strong>');

$form->add($section);

$section = new Form_Section('설치 피드백');

$section->addInput(new Form_Checkbox(
	'do_not_send_uniqueid',
	'Netgate Device ID',
	'Do NOT send Netgate Device ID with user agent',
	$pconfig['do_not_send_uniqueid']
))->setHelp('이 옵션을 사용하면 User-Agent 헤더의 일부로 Netgate 장치 ID를 pfSense에 보내지 않습니다.');

$form->add($section);

print $form;

$ramdisk_msg = gettext('설정이 변경되었습니다. 이 작업을 수행하려면 방화벽\n재부팅이 필요합니다.\n\n재부팅하시겠습니까?');
$use_mfs_tmpvar_changed = (($use_mfs_tmpvar_before !== $use_mfs_tmpvar_after) && !$input_errors);
?>

<script type="text/javascript">
//<![CDATA[
events.push(function() {
	// Has the Use ramdisk checkbox changed state?
	if (<?=(int)$use_mfs_tmpvar_changed?> && confirm("<?=$ramdisk_msg?>")) {
		postSubmit({override : 'yes'}, 'diag_reboot.php')
	}

	// source track timeout field is disabled if sticky connections not enabled
	$('#lb_use_sticky').click(function () {
		disableInput('srctrack', !$(this).prop("checked"));
	});

	disableInput('srctrack', !$('#lb_use_sticky').prop("checked"));

});
//]]>
</script>

<?php
include("foot.inc");

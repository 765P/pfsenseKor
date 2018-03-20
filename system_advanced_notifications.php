<?php
/*
 * system_advanced_notifications.php
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
한글화 번역 
*/

##|+PRIV
##|*IDENT=page-system-advanced-notifications
##|*NAME=System: Advanced: Notifications
##|*DESCR=Allow access to the 'System: Advanced: Notifications' page.
##|*MATCH=system_advanced_notifications.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("notices.inc");
require_once("pfsense-utils.inc");

// Growl
$pconfig['disable_growl'] = isset($config['notifications']['growl']['disable']);
if ($config['notifications']['growl']['password']) {
	$pconfig['password'] = $config['notifications']['growl']['password'];
}
if ($config['notifications']['growl']['ipaddress']) {
	$pconfig['ipaddress'] = $config['notifications']['growl']['ipaddress'];
}

if ($config['notifications']['growl']['notification_name']) {
	$pconfig['notification_name'] = $config['notifications']['growl']['notification_name'];
} else {
  $pconfig['notification_name'] = "{$g['product_name']} growl alert";
}

if ($config['notifications']['growl']['name']) {
	$pconfig['name'] = $config['notifications']['growl']['name'];
} else {
  $pconfig['name'] = 'pfSense-Growl';
}


// SMTP
$pconfig['disable_smtp'] = isset($config['notifications']['smtp']['disable']);
if ($config['notifications']['smtp']['ipaddress']) {
	$pconfig['smtpipaddress'] = $config['notifications']['smtp']['ipaddress'];
}
if ($config['notifications']['smtp']['port']) {
	$pconfig['smtpport'] = $config['notifications']['smtp']['port'];
}
if (isset($config['notifications']['smtp']['ssl'])) {
	$pconfig['smtpssl'] = true;
}
if (!empty($config['notifications']['smtp']['timeout'])) {
	$pconfig['smtptimeout'] = $config['notifications']['smtp']['timeout'];
}
if ($config['notifications']['smtp']['notifyemailaddress']) {
	$pconfig['smtpnotifyemailaddress'] = $config['notifications']['smtp']['notifyemailaddress'];
}
if ($config['notifications']['smtp']['username']) {
	$pconfig['smtpusername'] = $config['notifications']['smtp']['username'];
}
if ($config['notifications']['smtp']['password']) {
	$pconfig['smtppassword'] = $config['notifications']['smtp']['password'];
}
if ($config['notifications']['smtp']['authentication_mechanism']) {
	$pconfig['smtpauthmech'] = $config['notifications']['smtp']['authentication_mechanism'];
}
if ($config['notifications']['smtp']['fromaddress']) {
	$pconfig['smtpfromaddress'] = $config['notifications']['smtp']['fromaddress'];
}

// System Sounds
$pconfig['disablebeep'] = isset($config['system']['disablebeep']);

if ($_POST) {
	unset($input_errors);
	$pconfig = $_POST;

	$testgrowl = isset($_POST['test-growl']);
	$testsmtp = isset($_POST['test-smtp']);
	if (isset($_POST['save']) || $testsmtp || $testgrowl) {

		// Growl
		$config['notifications']['growl']['ipaddress'] = $_POST['ipaddress'];
		if ($_POST['password'] != DMYPWD) {
			if ($_POST['password'] == $_POST['password_confirm']) {
				$config['notifications']['growl']['password'] = $_POST['password'];
			} else {
				// Bug #7129 - do not nag people about passwords mismatch when growl is disabled
				if ($_POST['disable_growl'] != "yes") {
					$input_errors[] = gettext("Growl 패스워드가 서로 일치하지 않습니다.");
				}
			}
		}

		$config['notifications']['growl']['name'] = $_POST['name'];
		$config['notifications']['growl']['notification_name'] = $_POST['notification_name'];

		if ($_POST['disable_growl'] == "yes") {
			$config['notifications']['growl']['disable'] = true;
		} else {
			unset($config['notifications']['growl']['disable']);
		}

		// SMTP
		$config['notifications']['smtp']['ipaddress'] = $_POST['smtpipaddress'];
		$config['notifications']['smtp']['port'] = $_POST['smtpport'];
		if (isset($_POST['smtpssl'])) {
			$config['notifications']['smtp']['ssl'] = true;
		} else {
			unset($config['notifications']['smtp']['ssl']);
		}

		$config['notifications']['smtp']['timeout'] = $_POST['smtptimeout'];
		$config['notifications']['smtp']['notifyemailaddress'] = $_POST['smtpnotifyemailaddress'];
		$config['notifications']['smtp']['username'] = $_POST['smtpusername'];

		if ($_POST['smtppassword'] != DMYPWD) {
			if ($_POST['smtppassword'] == $_POST['smtppassword_confirm']) {
				$config['notifications']['smtp']['password'] = $_POST['smtppassword'];
			} else {
				if ($_POST['disable_smtp'] != "yes") {
					// Bug #7129 - do not nag people about passwords mismatch when SMTP notifications are disabled
					$input_errors[] = gettext("SMTP 패스워드가 서로 일치하지 않습니다.");
				}
			}
		}

		$config['notifications']['smtp']['authentication_mechanism'] = $_POST['smtpauthmech'];
		$config['notifications']['smtp']['fromaddress'] = $_POST['smtpfromaddress'];

		if ($_POST['disable_smtp'] == "yes") {
			$config['notifications']['smtp']['disable'] = true;
		} else {
			unset($config['notifications']['smtp']['disable']);
		}

		// System Sounds
		if ($_POST['disablebeep'] == "yes") {
			$config['system']['disablebeep'] = true;
		} else {
			unset($config['system']['disablebeep']);
		}

		if (!$input_errors && !$testsmtp && !$testgrowl) {
			write_config();

			pfSenseHeader("system_advanced_notifications.php");
			return;
		}

	}

	if ($testgrowl) {
		// Send test message via growl
		if (isset($config['notifications']['growl']['ipaddress'])) {
			unlink_if_exists($g['vardb_path'] . "/growlnotices_lastmsg.txt");
			register_via_growl();
			$test_result = notify_via_growl(sprintf(gettext("%s에서 온 테스트 메시지입니다. 이 메시지는 무시하셔도 좋습니다."), $g['product_name']), true);
			if (empty($test_result)) {
				$test_result = gettext("Growl 테스트 알림이 전송되었습니다.");
				$test_class = 'success';
			} else {
				$test_class = 'danger';
			}
		}
	}

	if ($testsmtp) {
		// Send test message via smtp
		if (file_exists("/var/db/notices_lastmsg.txt")) {
			unlink("/var/db/notices_lastmsg.txt");
		}
		$test_result = notify_via_smtp(sprintf(gettext("%s에서 온 테스트 메시지입니다. 이 메시지는 무시하셔도 좋습니다."), $g['product_name']), true);
		if (empty($test_result)) {
			$test_result = gettext("SMTP 테스트 이메일이 성공적으로 전송되었습니다.");
			$test_class = 'success';
		} else {
			$test_class = 'danger';
		}
	}
}

$pgtitle = array(gettext("시스템"), gettext("어드밴스드"), gettext("Notifications"));
$pglinks = array("", "system_advanced_admin.php", "@self");
include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

if ($test_result) {
	print_info_box($test_result, $test_class);
}

$tab_array = array();
$tab_array[] = array(gettext("어드민 엑세스"), false, "system_advanced_admin.php");
$tab_array[] = array(htmlspecialchars(gettext("방화벽 & NAT")), false, "system_advanced_firewall.php");
$tab_array[] = array(gettext("네트워킹"), false, "system_advanced_network.php");
$tab_array[] = array(gettext("Miscellaneous"), false, "system_advanced_misc.php");
$tab_array[] = array(gettext("System 터널링"), false, "system_advanced_sysctl.php");
$tab_array[] = array(gettext("Notifications"), true, "system_advanced_notifications.php");
display_top_tabs($tab_array);

$form = new Form;

$section = new Form_Section('E-Mail');

$section->addInput(new Form_Checkbox(
	'disable_smtp',
	'Disable SMTP',
	'Disable SMTP Notifications',
	$pconfig['disable_smtp']
))->setHelp('SMTP 알림을 비활성화하지만 아래 설정을 유지하려면이 옵션을 선택하십시오. '.
	'패키지와 같은 다른 메커니즘은 기능을 수행하기 위해 이러한 설정을 필요로 할 수 있습니다.');

$section->addInput(new Form_Input(
	'smtpipaddress',
	'E-Mail server',
	'text',
	$pconfig['smtpipaddress']
))->setHelp('알림을 보낼 SMTP 전자 메일 서버의 FQDN 또는 IP 주소입니다.');

$section->addInput(new Form_Input(
	'smtpport',
	'SMTP Port of E-Mail server',
	'number',
	$pconfig['smtpport']
))->setHelp('SMTP 전자 메일 서버의 포트이며 일반적으로 25, 587 (제출) 또는 465 (smtps)입니다.');

$section->addInput(new Form_Input(
	'smtptimeout',
	'Connection timeout to E-Mail server',
	'number',
	$pconfig['smtptimeout']
))->setHelp('SMTP 서버가 연결될 때까지 기다리는 시간 (초)입니다. 기본값은 20 초입니다.');

$group = new Form_Group('Secure SMTP Connection');
$group->add(new Form_Checkbox(
	'smtpssl',
	'Enable SSL/TLS',
	'Enable SMTP over SSL/TLS',
	isset($pconfig['smtpssl'])
));

$section->add($group);

$section->addInput(new Form_Input(
	'smtpfromaddress',
	'From e-mail address',
	'text',
	$pconfig['smtpfromaddress']
))->setHelp('보낸 사람 필드에 표시 될 전자 메일 주소입니다.');

$section->addInput(new Form_Input(
	'smtpnotifyemailaddress',
	'Notification E-Mail address',
	'text',
	$pconfig['smtpnotifyemailaddress']
))->setHelp('이메일 알림을 보낼 전자 메일 주소를 입력하십시오.');

// This name prevents the browser from auto-filling the field. We change it on submit
$section->addInput(new Form_Input(
	'smtpusername',
	'Notification E-Mail auth username (optional)',
	'text',
	$pconfig['smtpusername'],
	['autocomplete' => 'off']
))->setHelp('SMTP 인증을위한 전자 메일 주소 사용자 이름을 입력하십시오.');

$section->addPassword(new Form_Input(
	'smtppassword',
	'Notification E-Mail auth password',
	'password',
	$pconfig['smtppassword']
))->setHelp('SMTP 인증을위한 전자 메일 계정 암호를 입력하십시오.');

$section->addInput(new Form_Select(
	'smtpauthmech',
	'Notification E-Mail auth mechanism',
	$pconfig['smtpauthmech'],
	$smtp_authentication_mechanisms
))->setHelp('SMTP 서버가 사용하는 인증 메커니즘을 선택하십시오. PLAIN의 경우 대부분 Exchange 또는 Office365와 같은 일부 서버에서는 LOGIN이 필요할 수 있습니다. ');

$section->addInput(new Form_Button(
	'test-smtp',
	'Test SMTP Settings',
	null,
	'fa-send'
))->addClass('btn-info')->setHelp('서비스가 사용 안 함으로 표시된 경우에도 테스트 알림이 전송됩니다. '.
	'마지막 SAVED 값이 사용되며 여기에 입력 한 값이 반드시 필요하지는 않습니다.');

$form->add($section);

$section = new Form_Section('Sounds');

$section->addInput(new Form_Checkbox(
	'disablebeep',
	'Startup/Shutdown Sound',
	'Disable the startup/shutdown beep',
	$pconfig['disablebeep']
))->setHelp('이 옵션을 선택하면 시작 및 종료 소리가 더 이상 재생되지 않습니다.');

$form->add($section);

$section = new Form_Section('Growl');

$section->addInput(new Form_Checkbox(
	'disable_growl',
	'Disable Growl',
	'Disable Growl Notifications',
	$pconfig['disable_growl']
))->setHelp('알림을 비활성화하지만 아래 설정을 유지하려면이 옵션을 선택하십시오.');

$section->addInput(new Form_Input(
	'name',
	'Registration Name',
	'text',
	$pconfig['name'],
	['placeholder' => 'pfSense-Growl']
))->setHelp('Growl 서버에 등록 할 이름을 입력하십시오.');

$section->addInput(new Form_Input(
	'notification_name',
	'Notification Name',
	'text',
	$pconfig['notification_name'],
	['placeholder' => $g["product_name"].' growl alert']

))->setHelp('Growl 알림의 이름을 입력하십시오.');

$section->addInput(new Form_Input(
	'ipaddress',
	'IP Address',
	'text',
	$pconfig['ipaddress']
))->setHelp('이것은 통지를 보낼 IP 주소입니다.');

$section->addPassword(new Form_Input(
	'password',
	'Password',
	'text',
	$pconfig['password']
))->setHelp('원격 Growl 알림 장치의 비밀번호를 입력하십시오.');

$section->addInput(new Form_Button(
	'test-growl',
	'Test Growl Settings',
	null,
	'fa-rss'
))->addClass('btn-info')->setHelp('서비스가 사용 안 함으로 표시된 경우에도 테스트 알림이 전송됩니다.');

$form->add($section);
print($form);

include("foot.inc");

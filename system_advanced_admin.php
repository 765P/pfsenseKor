<?php
/*
 * system_advanced_admin.php
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
한글화 번역 시작
*/

##|+PRIV
##|*IDENT=page-system-advanced-admin
##|*NAME=System: Advanced: Admin Access Page
##|*DESCR=Allow access to the 'System: Advanced: Admin Access' page.
##|*MATCH=system_advanced_admin.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("functions.inc");
require_once("filter.inc");
require_once("shaper.inc");

$pconfig['webguiproto'] = $config['system']['webgui']['protocol'];
$pconfig['webguiport'] = $config['system']['webgui']['port'];
$pconfig['max_procs'] = ($config['system']['webgui']['max_procs']) ? $config['system']['webgui']['max_procs'] : 2;
$pconfig['ssl-certref'] = $config['system']['webgui']['ssl-certref'];
$pconfig['disablehttpredirect'] = isset($config['system']['webgui']['disablehttpredirect']);
$pconfig['disablehsts'] = isset($config['system']['webgui']['disablehsts']);
$pconfig['disableconsolemenu'] = isset($config['system']['disableconsolemenu']);
$pconfig['noantilockout'] = isset($config['system']['webgui']['noantilockout']);
$pconfig['nodnsrebindcheck'] = isset($config['system']['webgui']['nodnsrebindcheck']);
$pconfig['nohttpreferercheck'] = isset($config['system']['webgui']['nohttpreferercheck']);
$pconfig['pagenamefirst'] = isset($config['system']['webgui']['pagenamefirst']);
$pconfig['loginautocomplete'] = isset($config['system']['webgui']['loginautocomplete']);
$pconfig['althostnames'] = $config['system']['webgui']['althostnames'];
$pconfig['enableserial'] = $config['system']['enableserial'];
$pconfig['serialspeed'] = $config['system']['serialspeed'];
$pconfig['primaryconsole'] = $config['system']['primaryconsole'];
$pconfig['enablesshd'] = $config['system']['enablesshd'];
$pconfig['sshport'] = $config['system']['ssh']['port'];
$pconfig['sshdkeyonly'] = isset($config['system']['ssh']['sshdkeyonly']);
$pconfig['quietlogin'] = isset($config['system']['webgui']['quietlogin']);

$a_cert =& $config['cert'];
$certs_available = false;

if (is_array($a_cert) && count($a_cert)) {
	$certs_available = true;
} else {
	$a_cert = array();
}

if (!$pconfig['webguiproto'] || !$certs_available) {
	$pconfig['webguiproto'] = "http";
}

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	if ($_POST['webguiport']) {
		if (!is_port($_POST['webguiport'])) {
			$input_errors[] = gettext("A valid webConfigurator port number must be specified");
		}
	}

	if ($_POST['max_procs']) {
		if (!is_numericint($_POST['max_procs']) || ($_POST['max_procs'] < 1) || ($_POST['max_procs'] > 500)) {
			$input_errors[] = gettext("Max Processes must be a number 1 or greater");
		}
	}

	if ($_POST['althostnames']) {
		$althosts = explode(" ", $_POST['althostnames']);
		foreach ($althosts as $ah) {
			if (!is_ipaddr($ah) && !is_hostname($ah)) {
				$input_errors[] = sprintf(gettext("Alternate hostname %s is not a valid hostname."), htmlspecialchars($ah));
			}
		}
	}

	if ($_POST['sshport']) {
		if (!is_port($_POST['sshport'])) {
			$input_errors[] = gettext("A valid port number must be specified");
		}
	}

	if ($_POST['sshdkeyonly'] == "yes") {
		$config['system']['ssh']['sshdkeyonly'] = "enabled";
	} else if (isset($config['system']['ssh']['sshdkeyonly'])) {
		unset($config['system']['ssh']['sshdkeyonly']);
	}

	ob_flush();
	flush();

	if (!$input_errors) {
		if (update_if_changed("webgui protocol", $config['system']['webgui']['protocol'], $_POST['webguiproto'])) {
			$restart_webgui = true;
		}

		if (update_if_changed("webgui port", $config['system']['webgui']['port'], $_POST['webguiport'])) {
			$restart_webgui = true;
		}

		if (update_if_changed("webgui certificate", $config['system']['webgui']['ssl-certref'], $_POST['ssl-certref'])) {
			$restart_webgui = true;
		}

		if (update_if_changed("webgui max processes", $config['system']['webgui']['max_procs'], $_POST['max_procs'])) {
			$restart_webgui = true;
		}

		// Restart the webgui only if this actually changed
		if ($_POST['webgui-redirect'] == "yes") {
			if ($config['system']['webgui']['disablehttpredirect'] != true) {
				$restart_webgui = true;
			}

			$config['system']['webgui']['disablehttpredirect'] = true;
		} else {
			if ($config['system']['webgui']['disablehttpredirect'] == true) {
				$restart_webgui = true;
			}

			unset($config['system']['webgui']['disablehttpredirect']);
		}

		if ($_POST['webgui-hsts'] == "yes") {
			if ($config['system']['webgui']['disablehsts'] != true) {
				$restart_webgui = true;
			}

			$config['system']['webgui']['disablehsts'] = true;
		} else {
			if ($config['system']['webgui']['disablehsts'] == true) {
				$restart_webgui = true;
			}

			unset($config['system']['webgui']['disablehsts']);
		}

		if ($_POST['webgui-login-messages'] == "yes") {
			$config['system']['webgui']['quietlogin'] = true;
		} else {
			unset($config['system']['webgui']['quietlogin']);
		}

		if ($_POST['disableconsolemenu'] == "yes") {
			$config['system']['disableconsolemenu'] = true;
		} else {
			unset($config['system']['disableconsolemenu']);
		}

		if ($_POST['noantilockout'] == "yes") {
			$config['system']['webgui']['noantilockout'] = true;
		} else {
			unset($config['system']['webgui']['noantilockout']);
		}

		if ($_POST['enableserial'] == "yes" || $g['enableserial_force']) {
			$config['system']['enableserial'] = true;
		} else {
			unset($config['system']['enableserial']);
		}

		if (is_numericint($_POST['serialspeed'])) {
			$config['system']['serialspeed'] = $_POST['serialspeed'];
		} else {
			unset($config['system']['serialspeed']);
		}

		if ($_POST['primaryconsole']) {
			$config['system']['primaryconsole'] = $_POST['primaryconsole'];
		} else {
			unset($config['system']['primaryconsole']);
		}

		if ($_POST['nodnsrebindcheck'] == "yes") {
			$config['system']['webgui']['nodnsrebindcheck'] = true;
		} else {
			unset($config['system']['webgui']['nodnsrebindcheck']);
		}

		if ($_POST['nohttpreferercheck'] == "yes") {
			$config['system']['webgui']['nohttpreferercheck'] = true;
		} else {
			unset($config['system']['webgui']['nohttpreferercheck']);
		}

		if ($_POST['pagenamefirst'] == "yes") {
			$config['system']['webgui']['pagenamefirst'] = true;
		} else {
			unset($config['system']['webgui']['pagenamefirst']);
		}

		if ($_POST['loginautocomplete'] == "yes") {
			$config['system']['webgui']['loginautocomplete'] = true;
		} else {
			unset($config['system']['webgui']['loginautocomplete']);
		}

		if ($_POST['althostnames']) {
			$config['system']['webgui']['althostnames'] = $_POST['althostnames'];
		} else {
			unset($config['system']['webgui']['althostnames']);
		}

		$sshd_enabled = $config['system']['enablesshd'];
		if ($_POST['enablesshd']) {
			$config['system']['enablesshd'] = "enabled";
		} else {
			unset($config['system']['enablesshd']);
		}

		$sshd_keyonly = isset($config['system']['sshdkeyonly']);
		if ($_POST['sshdkeyonly']) {
			$config['system']['sshdkeyonly'] = true;
		} else {
			unset($config['system']['sshdkeyonly']);
		}

		$sshd_port = $config['system']['ssh']['port'];
		if ($_POST['sshport']) {
			$config['system']['ssh']['port'] = $_POST['sshport'];
		} else if (isset($config['system']['ssh']['port'])) {
			unset($config['system']['ssh']['port']);
		}

		if (($sshd_enabled != $config['system']['enablesshd']) ||
		    ($sshd_keyonly != $config['system']['sshdkeyonly']) ||
		    ($sshd_port != $config['system']['ssh']['port'])) {
			$restart_sshd = true;
		}

		if ($restart_webgui) {
			global $_SERVER;
			$http_host_port = explode("]", $_SERVER['HTTP_HOST']);
			/* IPv6 address check */
			if (strstr($_SERVER['HTTP_HOST'], "]")) {
				if (count($http_host_port) > 1) {
					array_pop($http_host_port);
					$host = str_replace(array("[", "]"), "", implode(":", $http_host_port));
					$host = "[{$host}]";
				} else {
					$host = str_replace(array("[", "]"), "", implode(":", $http_host_port));
					$host = "[{$host}]";
				}
			} else {
				list($host) = explode(":", $_SERVER['HTTP_HOST']);
			}
			$prot = $config['system']['webgui']['protocol'];
			$port = $config['system']['webgui']['port'];
			if ($port) {
				$url = "{$prot}://{$host}:{$port}/system_advanced_admin.php";
			} else {
				$url = "{$prot}://{$host}/system_advanced_admin.php";
			}
		}

		write_config();

		$changes_applied = true;
		$retval = 0;
		$retval |= filter_configure();

		if ($restart_webgui) {
			$extra_save_msg = sprintf("<br />" . gettext("One moment...redirecting to %s in 20 seconds."), $url);
		}

		setup_serial_port();
		// Restart DNS in case dns rebinding toggled
		if (isset($config['dnsmasq']['enable'])) {
			services_dnsmasq_configure();
		} elseif (isset($config['unbound']['enable'])) {
			services_unbound_configure();
		}
	}
}

$pgtitle = array(gettext("시스템"), gettext("어드밴스드"), gettext("어드민 엑세스"));
$pglinks = array("", "@self", "@self");
include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

if ($changes_applied) {
	print_apply_result_box($retval, $extra_save_msg);
}

$tab_array = array();
$tab_array[] = array(gettext("어드민 엑세스"), true, "system_advanced_admin.php");
$tab_array[] = array(htmlspecialchars(gettext("방화벽 & NAT")), false, "system_advanced_firewall.php");
$tab_array[] = array(gettext("네트워킹"), false, "system_advanced_network.php");
$tab_array[] = array(gettext("Miscellaneous"), false, "system_advanced_misc.php");
$tab_array[] = array(gettext("시스템 터널링"), false, "system_advanced_sysctl.php");
$tab_array[] = array(gettext("Notifications"), false, "system_advanced_notifications.php");
display_top_tabs($tab_array);

?><div id="container"><?php

$form = new Form;
$section = new Form_Section('웹 구성자');
$group = new Form_Group('프로토콜');

$group->add(new Form_Checkbox(
	'webguiproto',
	'Protocol',
	'HTTP',
	($pconfig['webguiproto'] == 'http'),
	'http'
))->displayAsRadio();

$group->add(new Form_Checkbox(
	'webguiproto',
	'Protocol',
	'HTTPS',
	($pconfig['webguiproto'] == 'https'),
	'https'
))->displayAsRadio();

if (!$certs_available) {
	$group->setHelp('인증서가 정의되지 않았습니다. SSL을 사용하려면 인증서가 있어야합니다. %1$s인증서를%2$s 만들거나 가져옵니다.',
		'<a href="system_certmanager.php">', '</a>');
}

$section->add($group);

$values = array();
foreach ($a_cert as $cert) {
	$values[ $cert['refid'] ] = $cert['descr'];
}

$section->addInput($input = new Form_Select(
	'ssl-certref',
	'SSL Certificate',
	$pconfig['ssl-certref'],
	$values
));

$section->addInput(new Form_Input(
	'webguiport',
	'TCP port',
	'number',
	$config['system']['webgui']['port'],
	['min' => 1, 'max' => 65535]
))->setHelp('위의 webConfigurator에 대한 사용자 정의 포트 번호를 '.
	'입력하여 기본값 (HTTP의 경우 80, HTTPS의 경우 443)을  '.
	'대체하십시오. 변경 사항은 저장 직후에 적용됩니다.');

$section->addInput(new Form_Input(
	'max_procs',
	'Max Processes',
	'number',
	$pconfig['max_procs']
))->setHelp('실행할 WebConfigurator 프로세스 수를 입력하십시오. 이 값을 '.
	'2로 설정하면 더 많은 사용자/ 브라우저가 동시에 GUI에 액세스 '.
	'할 수 있습니다.');

$section->addInput(new Form_Checkbox(
	'webgui-redirect',
	'WebGUI redirect',
	'Disable webConfigurator redirect rule',
	$pconfig['disablehttpredirect']
))->setHelp('이 옵션을 선택하지 않으면 구성된 수신 대기 포트와 상관없이 포트 '.
	'80에서도 항상 WebConfigurator에 대한 액세스가 허용됩니다. '.
	'자동 추가 된 리디렉션 규칙을 사용하지 않으려면이 상자를 선택하십시오.');

$section->addInput(new Form_Checkbox(
	'webgui-hsts',
	'HSTS',
	'Disable HTTP Strict Transport Security',
	$pconfig['disablehsts']
))->setHelp('이 옵션을 선택하지 않으면 Strict-Transport-Security HTTPS 응답 헤더가 webConfigurator에서 브라우저로 전송됩니다. '.
	'이렇게하면 브라우저가 방화벽 FQDN에 대한 이후 요청에 HTTPS 만 사용하게됩니다. HSTS를 사용하지 않으려면이 상자를 '.
	'only HTTPS for future requests to the firewall FQDN. Check this box to disable HSTS. '.
	'선택하십시오. (참고 : HSTS가 활성화되어있는 동안 브라우저가 이미 FQDN을 방문했을 때 비활성화하려면 브라우저 별 단계가 '.
	'필요합니다.)');

$section->addInput(new Form_Checkbox(
	'loginautocomplete',
	'WebGUI Login Autocomplete',
	'Enable webConfigurator login autocomplete',
	$pconfig['loginautocomplete']
))->setHelp('이 옵션을 선택하면 브라우저에서 WebConfigurator의 로그인 자격 증명을 저장할 수 있습니다. 편리하지만 일부 보안 '.
	'표준에서는이 기능을 비활성화해야합니다. 브라우저가 자격 증명을 저장할지 묻는 메시지를 표시하도록 로그인 양식에서 자동 완성을 '.
	'사용하려면이 확인란을 선택하십시오 (참고 : 일부 브라우저는이 옵션을 고려하지 않습니다).');

$section->addInput(new Form_Checkbox(
	'webgui-login-messages',
	'WebGUI login messages',
	'Disable logging of webConfigurator successful logins',
	$pconfig['quietlogin']
))->setHelp('이 옵션을 선택하면 webConfigurator에 성공적으로 로그인 할 수 없습니다.');

if ($config['interfaces']['lan']) {
	$lockout_interface = "LAN";
} else {
	$lockout_interface = "WAN";
}

$section->addInput(new Form_Checkbox(
	'noantilockout',
	'Anti-lockout',
	'Disable webConfigurator anti-lockout rule',
	$pconfig['noantilockout']
))->setHelp('이 옵션을 선택 취소하면 '.
	'사용자 정의 방화벽 규칙 집합과 관계없이 %1$s 인터페이스의 webConfigurator에 대한 액세스가 항상 '.
	'허용됩니다. 자동으로 추가 된이 규칙을 사용하지 않으려면이 상자를 선택하십시오. 따라서 '.
	'따라서 WebConfigurator에 대한 액세스는 사용자 정의 방화벽 규칙에 의해 제어됩니다(액세스를 허용하는 방화벽 규칙이 잠겨 있지 않도록하십시오!).'.
	'%2$sHint: the &quot;인터페이스 IP 주소 설정&quot; '.
	'option in the console menu resets this setting as well.%3$s', $lockout_interface, '<em>', '</em>');

$section->addInput(new Form_Checkbox(
	'nodnsrebindcheck',
	'DNS Rebind Check',
	'Disable DNS Rebinding Checks',
	$pconfig['nodnsrebindcheck']
))->setHelp('이 옵션을 선택 취소하면 시스템이 %1$sDNS Rebinding %2$s 공격으로부터 보호됩니다. '.
	'이렇게하면 구성된 DNS 서버의 개인 IP 응답이 차단됩니다. 환경의 webConfigurator 액세스 또는 이름 확인을 '.
	'방해하는 경우이 보호를 비활성화하려면이 상자를 선택합니다.',
	'<a href="http://en.wikipedia.org/wiki/DNS_rebinding">', '</a>');

$section->addInput(new Form_Input(
	'althostnames',
	'Alternate Hostnames',
	'text',
	htmlspecialchars($pconfig['althostnames'])
))->setHelp('DNS Rebinding 및 HTTP_REFERER 검사를위한 대체 호스트 이름. 라우터가 질의 될 수있는 대체 호스트 이름을 지정하여 DNS  '.
	'Rebinding Attack 검사를 건너 뜁니다. 호스트 이름은 공백으로 구분하십시오.');

$section->addInput(new Form_Checkbox(
	'nohttpreferercheck',
	'Browser HTTP_REFERER enforcement',
	'Disable HTTP_REFERER enforcement check',
	$pconfig['nohttpreferercheck']
))->setHelp('이 옵션을 선택하지 않으면 HTTP_REFERER 리디렉션 시도에 대해 webConfigurator에 대한 액세스가 보호됩니다. 이 시스템과 '.
	'상호 작용하기 위해 외부 스크립트를 사용하는 경우와 같은 특정 경우에 webConfigurator 액세스를 방해하는 경우이 보호를 '.
	'사용하지 않으려면이 상자를 선택하십시오. HTTP_REFERER에 대한 자세한 내용은 %1$s위키 백과%2$s에서 볼 수 있습니다.',
	'<a target="_blank" href="http://en.wikipedia.org/wiki/HTTP_referrer">', '</a>.');

gen_pagenamefirst_field($section, $pconfig['pagenamefirst']);

$form->add($section);
$section = new Form_Section('Secure Shell');

$section->addInput(new Form_Checkbox(
	'enablesshd',
	'Secure Shell Server',
	'Enable Secure Shell',
	isset($pconfig['enablesshd'])
));

$section->addInput(new Form_Checkbox(
	'sshdkeyonly',
	'Authentication Method',
	'Disable password login for Secure Shell (RSA/DSA key only)',
	$pconfig['sshdkeyonly']
))->setHelp('사용하도록 설정하면 보안 쉘 액세스 권한이 부여 된 '.
	'%1$suser%2$s마다 권한 부여 된 키를 구성해야합니다.', '<a href="system_usermanager.php">', '</a>');

$section->addInput(new Form_Input(
	'sshport',
	'SSH port',
	'number',
	$pconfig['sshport'],
	['min' => 1, 'max' => 65535, 'placeholder' => 22]
))->setHelp('Note: 기본값 인 22로 비워 두십시오.');


$form->add($section);
$section = new Form_Section('Serial Communications');

if (!$g['enableserial_force']) {
	$section->addInput(new Form_Checkbox(
		'enableserial',
		'Serial Terminal',
		'Enables the first serial port with 115200/8/N/1 by default, or another speed selectable below.',
		isset($pconfig['enableserial'])
	))->setHelp('Note:	그러면 콘솔 출력과 메시지가 직렬 포트로 리디렉션됩니다. '.
		'콘솔 메뉴는 내부 비디오 카드/키보드에서 계속 액세스 할 수 있습니다. '.
		'직렬 콘솔을 사용하려면 %1$snull모뎀%2$s 직렬 케이블 또는 어댑터가 필요합니다.', '<b>', '</b>');
}

$section->addInput(new Form_Select(
	'serialspeed',
	'Serial Speed',
	$pconfig['serialspeed'],
	array_combine(array(115200, 57600, 38400, 19200, 14400, 9600), array(115200, 57600, 38400, 19200, 14400, 9600))
))->setHelp('직렬 콘솔 포트에 대해 서로 다른 속도를 선택할 수 있습니다.');

if (!$g['enableserial_force'] && !$g['primaryconsole_force']) {
	$section->addInput(new Form_Select(
		'primaryconsole',
		'Primary Console',
		$pconfig['primaryconsole'],
		array(
			'serial' => gettext('시리얼 콘솔'),
			'video' => gettext('VGA 콘솔'),
		)
	))->setHelp('여러 개의 콘솔이있는 경우 기본 콘솔을 선택하십시오. 기본 콘솔에 pfSense 부팅 '.
		'스크립트 출력이 표시됩니다. 모든 콘솔은 OS 부팅 메시지, 콘솔 메시지 '.
		'및 콘솔 메뉴를 표시합니다.');
}

$form->add($section);
$section = new Form_Section('Console Options');

$section->addInput(new Form_Checkbox(
	'disableconsolemenu',
	'Console menu',
	'Password protect the console menu',
	$pconfig['disableconsolemenu']
));

$form->add($section);
print $form;

?>
</div>
<script type="text/javascript">
//<![CDATA[
events.push(function() {

	// ---------- On initial page load ------------------------------------------------------------

	hideInput('ssl-certref', $('input[name=webguiproto]:checked').val() == 'http');
	hideCheckbox('webgui-hsts', $('input[name=webguiproto]:checked').val() == 'http');

	// ---------- Click checkbox handlers ---------------------------------------------------------

	 $('[name=webguiproto]').click(function () {
		hideInput('ssl-certref', $('input[name=webguiproto]:checked').val() == 'http');
		hideCheckbox('webgui-hsts', $('input[name=webguiproto]:checked').val() == 'http');
	});
});
//]]>
</script>

<?php
include("foot.inc");

if ($restart_webgui) {
	echo "<meta http-equiv=\"refresh\" content=\"20;url={$url}\" />";
}

if ($restart_sshd) {
	killbyname("sshd");
	log_error(gettext("secure shell 구성이 변경되었습니다. sshd를 중지하는 중입니다."));

	if ($config['system']['enablesshd']) {
		log_error(gettext("secure shell 구성이 변경되었습니다. sshd를 중지하는 중입니다."));
		send_event("service restart sshd");
	}
}

if ($restart_webgui) {
	ob_flush();
	flush();
	log_error(gettext("웹 구성자 구성이 변경되었습니다. 웹 모니터를 재시작하는 중입니다."));
	send_event("service restart webgui");
}
?>

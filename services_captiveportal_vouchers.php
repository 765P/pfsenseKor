<?php
/*
 * services_captiveportal_vouchers.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2018 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2007 Marcel Wiget <mwiget@mac.com>
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
2018.03.12
한글화 번역 시작
*/

##|+PRIV
##|*IDENT=page-services-captiveportal-vouchers
##|*NAME=Services: Captive Portal Vouchers
##|*DESCR=Allow access to the 'Services: Captive Portal Vouchers' page.
##|*MATCH=services_captiveportal_vouchers.php*
##|-PRIV

if ($_POST['postafterlogin']) {
	$nocsrf= true;
}

require_once("guiconfig.inc");
require_once("functions.inc");
require_once("filter.inc");
require_once("shaper.inc");
require_once("captiveportal.inc");
require_once("voucher.inc");

$cpzone = strtolower(htmlspecialchars($_REQUEST['zone']));

if ($_REQUEST['generatekey']) {
	exec("/usr/bin/openssl genrsa 64 > /tmp/key64.private");
	exec("/usr/bin/openssl rsa -pubout < /tmp/key64.private > /tmp/key64.public");
	$privatekey = str_replace("\n", "\\n", file_get_contents("/tmp/key64.private"));
	$publickey = str_replace("\n", "\\n", file_get_contents("/tmp/key64.public"));
	exec("rm /tmp/key64.private /tmp/key64.public");
	print json_encode(['public' => $publickey, 'private' => $privatekey]);
	exit;
}

if (empty($cpzone)) {
	header("Location: services_captiveportal_zones.php");
	exit;
}

if (!is_array($config['captiveportal'])) {
	$config['captiveportal'] = array();
}

$a_cp =& $config['captiveportal'];

if (!is_array($config['voucher'])) {
	$config['voucher'] = array();
}

if (empty($a_cp[$cpzone])) {
	log_error(sprintf(gettext("알 수 없는 영역 파라미터가있는 captiveportal 페이지에 대한 제출 : %s"), htmlspecialchars($cpzone)));
	header("Location: services_captiveportal_zones.php");
	exit;
}

$pgtitle = array(gettext("Services"), gettext("Captive Portal"), $a_cp[$cpzone]['zone'], gettext("바우처"));
$pglinks = array("", "services_captiveportal_zones.php", "services_captiveportal.php?zone=" . $cpzone, "@self");
$shortcut_section = "captiveportal-vouchers";

if (!is_array($config['voucher'][$cpzone]['roll'])) {
	$config['voucher'][$cpzone]['roll'] = array();
}
if (!isset($config['voucher'][$cpzone]['charset'])) {
	$config['voucher'][$cpzone]['charset'] = '2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
}
if (!isset($config['voucher'][$cpzone]['rollbits'])) {
	$config['voucher'][$cpzone]['rollbits'] = 16;
}
if (!isset($config['voucher'][$cpzone]['ticketbits'])) {
	$config['voucher'][$cpzone]['ticketbits'] = 10;
}
if (!isset($config['voucher'][$cpzone]['checksumbits'])) {
	$config['voucher'][$cpzone]['checksumbits'] = 5;
}
if (!isset($config['voucher'][$cpzone]['magic'])) {
	$config['voucher'][$cpzone]['magic'] = rand();	 // anything slightly random will do
}
if (!isset($config['voucher'][$cpzone]['exponent'])) {
	while (true) {
		while (($exponent = rand()) % 30000 < 5000) {
			continue;
		}
		$exponent = ($exponent * 2) + 1; // Make it odd number
		if ($exponent <= 65537) {
			break;
		}
	}

	$config['voucher'][$cpzone]['exponent'] = $exponent;
	unset($exponent);
}

if (!isset($config['voucher'][$cpzone]['publickey'])) {
	/* generate a random 64 bit RSA key pair using the voucher binary */
	$fd = popen("/usr/local/bin/voucher -g 64 -e " . $config['voucher'][$cpzone]['exponent'], "r");
	if ($fd !== false) {
		$output = fread($fd, 16384);
		pclose($fd);
		list($privkey, $pubkey) = explode("\0", $output);
		$config['voucher'][$cpzone]['publickey'] = base64_encode($pubkey);
		$config['voucher'][$cpzone]['privatekey'] = base64_encode($privkey);
	}
}

// Check for invalid or expired vouchers
if (!isset($config['voucher'][$cpzone]['descrmsgnoaccess'])) {
	$config['voucher'][$cpzone]['descrmsgnoaccess'] = gettext("바우처가 유효하지 않습니다.");
}

if (!isset($config['voucher'][$cpzone]['descrmsgexpired'])) {
	$config['voucher'][$cpzone]['descrmsgexpired'] = gettext("바우처가 만료되었습니다.");
}

$a_roll = &$config['voucher'][$cpzone]['roll'];

if ($_POST['act'] == "del") {
	$id = $_POST['id'];
	if ($a_roll[$id]) {
		$roll = $a_roll[$id]['number'];
		$voucherlck = lock("voucher{$cpzone}");
		unset($a_roll[$id]);
		voucher_unlink_db($roll);
		unlock($voucherlck);
		write_config();
	}
	header("Location: services_captiveportal_vouchers.php?zone={$cpzone}");
	exit;
} else if ($_REQUEST['act'] == "csv") {
	/* print all vouchers of the selected roll */
	$privkey = base64_decode($config['voucher'][$cpzone]['privatekey']);
	if (strstr($privkey, "BEGIN RSA PRIVATE KEY")) {
		$fd = fopen("{$g['varetc_path']}/voucher_{$cpzone}.private", "w");
		if (!$fd) {
			$input_errors[] = gettext("개인 키 파일을 쓸 수 없습니다.") . ".\n";
		} else {
			chmod("{$g['varetc_path']}/voucher_{$cpzone}.private", 0600);
			fwrite($fd, $privkey);
			fclose($fd);
			$a_voucher = &$config['voucher'][$cpzone]['roll'];
			$id = $_REQUEST['id'];
			if (isset($id) && $a_voucher[$id]) {
				$number = $a_voucher[$id]['number'];
				$count = $a_voucher[$id]['count'];
				header("Content-Type: application/octet-stream");
				header("Content-Disposition: attachment; filename=vouchers_{$cpzone}_roll{$number}.csv");
				if (file_exists("{$g['varetc_path']}/voucher_{$cpzone}.cfg")) {
					system("/usr/local/bin/voucher -c {$g['varetc_path']}/voucher_{$cpzone}.cfg -p {$g['varetc_path']}/voucher_{$cpzone}.private $number $count");
				}
				@unlink("{$g['varetc_path']}/voucher_{$cpzone}.private");
			} else {
				header("Location: services_captiveportal_vouchers.php?zone={$cpzone}");
			}
			exit;
		}
	} else {
		$input_errors[] = gettext("바우처를 인쇄하려면 개인 RSA 키가 필요합니다.") . "\n";
	}
}

$pconfig['enable'] = isset($config['voucher'][$cpzone]['enable']);
$pconfig['charset'] = $config['voucher'][$cpzone]['charset'];
$pconfig['rollbits'] = $config['voucher'][$cpzone]['rollbits'];
$pconfig['ticketbits'] = $config['voucher'][$cpzone]['ticketbits'];
$pconfig['checksumbits'] = $config['voucher'][$cpzone]['checksumbits'];
$pconfig['magic'] = $config['voucher'][$cpzone]['magic'];
$pconfig['exponent'] = $config['voucher'][$cpzone]['exponent'];
$pconfig['publickey'] = base64_decode($config['voucher'][$cpzone]['publickey']);
$pconfig['privatekey'] = base64_decode($config['voucher'][$cpzone]['privatekey']);
$pconfig['msgnoaccess'] = $config['voucher'][$cpzone]['descrmsgnoaccess'];
$pconfig['msgexpired'] = $config['voucher'][$cpzone]['descrmsgexpired'];
$pconfig['vouchersyncdbip'] = $config['voucher'][$cpzone]['vouchersyncdbip'];
$pconfig['vouchersyncport'] = $config['voucher'][$cpzone]['vouchersyncport'];
$pconfig['vouchersyncpass'] = $config['voucher'][$cpzone]['vouchersyncpass'];
$pconfig['vouchersyncusername'] = $config['voucher'][$cpzone]['vouchersyncusername'];

if ($_POST['save']) {
	unset($input_errors);

	if ($_POST['postafterlogin']) {
		voucher_expire($_POST['voucher_expire']);
		exit;
	}

	$pconfig = $_POST;

	/* input validation */
	if ($_POST['enable'] == "yes") {
		if (!$_POST['vouchersyncusername']) {
			$reqdfields = explode(" ", "charset rollbits ticketbits checksumbits publickey magic");
			$reqdfieldsn = array(gettext("charset"), gettext("rollbits"), gettext("ticketbits"), gettext("checksumbits"), gettext("publickey"), gettext("magic"));
		} else {
			$reqdfields = explode(" ", "vouchersyncdbip vouchersyncport vouchersyncpass vouchersyncusername");
			$reqdfieldsn = array(gettext("바우처 데이터베이스 IP 동기화"), gettext("Sync 포트"), gettext("Sync 패스워드"), gettext("Sync 유저이름"));
		}

		do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);
	}

	if (!$_POST['vouchersyncusername']) {
		// Check for form errors
		if ($_POST['charset'] && (strlen($_POST['charset']) < 2)) {
			$input_errors[] = gettext("바우처를 만들려면 2문자 이상이 필요합니다.");
		}
		if ($_POST['charset'] && (strpos($_POST['charset'], "\"") > 0)) {
			$input_errors[] = gettext("쌍따옴표는 허용되지 않습니다.");
		}
		if ($_POST['charset'] && (strpos($_POST['charset'], ",") > 0)) {
			$input_errors[] = gettext("','는 허용되지 않습니다.");
		}
		if ($_POST['rollbits'] && (!is_numeric($_POST['rollbits']) || ($_POST['rollbits'] < 1) || ($_POST['rollbits'] > 31))) {
			$input_errors[] = gettext("롤 ID를 저장하는 비트 수는 1에서 31사이 여야합니다.");
		}
		if ($_POST['ticketbits'] && (!is_numeric($_POST['ticketbits']) || ($_POST['ticketbits'] < 1) || ($_POST['ticketbits'] > 16))) {
			$input_errors[] = gettext("티켓 ID를 저장할 비트 카운트는 1에서 16 사이여야합니다.");
		}
		if ($_POST['checksumbits'] && (!is_numeric($_POST['checksumbits']) || ($_POST['checksumbits'] < 1) || ($_POST['checksumbits'] > 31))) {
			$input_errors[] = gettext("체크섬을 저장할 비트 카운트는 1.31 사이 여야합니다.");
		}
		if ($_POST['publickey'] && (!strstr($_POST['publickey'], "BEGIN PUBLIC KEY"))) {
			$input_errors[] = gettext("이것은 RSA 공개 키처럼 보이지 않습니다.");
		}
		if ($_POST['privatekey'] && (!strstr($_POST['privatekey'], "BEGIN RSA PRIVATE KEY"))) {
			$input_errors[] = gettext("이것은 RSA 개인 키처럼 보이지 않습니다.");
		}
		if ($_POST['vouchersyncdbip'] && (is_ipaddr_configured($_POST['vouchersyncdbip']))) {
			$input_errors[] = gettext("바우처 데이터베이스는이 호스트 (자체)와 동기화 될 수 없습니다.");
		}
		if ($_POST['vouchersyncpass'] != $_POST['vouchersyncpass_confirm']) {
			$input_errors[] = gettext("암호와 확인 된 암호가 일치해야합니다.");
		}
	}

	if (!$input_errors) {
		if (empty($config['voucher'][$cpzone])) {
			$newvoucher = array();
		} else {
			$newvoucher = $config['voucher'][$cpzone];
		}
		if ($_POST['enable'] == "yes") {
			$newvoucher['enable'] = true;
		} else {
			unset($newvoucher['enable']);
		}
		if (empty($_POST['vouchersyncusername'])) {
			unset($newvoucher['vouchersyncdbip']);
			unset($newvoucher['vouchersyncport']);
			unset($newvoucher['vouchersyncusername']);
			unset($newvoucher['vouchersyncpass']);
			$newvoucher['charset'] = $_POST['charset'];
			$newvoucher['rollbits'] = $_POST['rollbits'];
			$newvoucher['ticketbits'] = $_POST['ticketbits'];
			$newvoucher['checksumbits'] = $_POST['checksumbits'];
			$newvoucher['magic'] = $_POST['magic'];
			$newvoucher['exponent'] = $_POST['exponent'];
			$newvoucher['publickey'] = base64_encode($_POST['publickey']);
			$newvoucher['privatekey'] = base64_encode($_POST['privatekey']);
			$newvoucher['descrmsgnoaccess'] = $_POST['msgnoaccess'];
			$newvoucher['descrmsgexpired'] = $_POST['msgexpired'];
			$config['voucher'][$cpzone] = $newvoucher;
			write_config();
			voucher_configure_zone();
		} else {
			$newvoucher['vouchersyncdbip'] = $_POST['vouchersyncdbip'];
			$newvoucher['vouchersyncport'] = $_POST['vouchersyncport'];
			$newvoucher['vouchersyncusername'] = $_POST['vouchersyncusername'];
			if ($_POST['vouchersyncpass'] != DMYPWD ) {
				$newvoucher['vouchersyncpass'] = $_POST['vouchersyncpass'];
			} else {
				$newvoucher['vouchersyncpass'] = $config['voucher'][$cpzone]['vouchersyncpass'];
			}
			if ($newvoucher['vouchersyncpass'] && $newvoucher['vouchersyncusername'] &&
			    $newvoucher['vouchersyncport'] && $newvoucher['vouchersyncdbip']) {

				// Synchronize the voucher DB from the master node
				$execcmd = <<<EOF
				global \$config;
				\$toreturn = array();
				\$toreturn['voucher'] = \$config['voucher']['$cpzone'];
				unset(\$toreturn['vouchersyncport'], \$toreturn['vouchersyncpass'], \$toreturn['vouchersyncusername'], \$toreturn['vouchersyncdbip']);

EOF;
				require_once("xmlrpc_client.inc");
				$rpc_client = new pfsense_xmlrpc_client();
				$rpc_client->setConnectionData(
						$newvoucher['vouchersyncdbip'], $newvoucher['vouchersyncport'],
						$newvoucher['vouchersyncusername'], $newvoucher['vouchersyncpass']);
				$rpc_client->set_noticefile("CaptivePortalVoucherSync");
				$resp = $rpc_client->xmlrpc_exec_php($execcmd);
				if ($resp == null) {
					$input_errors[] = $rpc_client->get_error();
				}

				if (!$input_errors) {
					if (is_array($resp)) {
						log_error(sprintf(gettext("전속 포털 바우처 데이터베이스가 %s(pfsense.exec_php)와 동기화되었습니다."), $url));
						// If we received back the voucher roll and other information then store it.
						if ($resp['voucher']['roll']) {
							$newvoucher['roll'] = $resp['voucher']['roll'];
						}
						if ($resp['voucher']['rollbits']) {
							$newvoucher['rollbits'] = $resp['voucher']['rollbits'];
						}
						if ($resp['voucher']['ticketbits']) {
							$newvoucher['ticketbits'] = $resp['voucher']['ticketbits'];
						}
						if ($resp['voucher']['checksumbits']) {
							$newvoucher['checksumbits'] = $resp['voucher']['checksumbits'];
						}
						if ($resp['voucher']['magic']) {
							$newvoucher['magic'] = $resp['voucher']['magic'];
						}
						if ($resp['voucher']['exponent']) {
							$newvoucher['exponent'] = $resp['voucher']['exponent'];
						}
						if ($resp['voucher']['publickey']) {
							$newvoucher['publickey'] = $resp['voucher']['publickey'];
						}
						if ($resp['voucher']['privatekey']) {
							$newvoucher['privatekey'] = $resp['voucher']['privatekey'];
						}
						if ($resp['voucher']['descrmsgnoaccess']) {
							$newvoucher['descrmsgnoaccess'] = $resp['voucher']['descrmsgnoaccess'];
						}
						if ($resp['voucher']['descrmsgexpired']) {
							$newvoucher['descrmsgexpired'] = $resp['voucher']['descrmsgexpired'];
						}
						$savemsg = sprintf(gettext('바우처 데이터베이스가 %1$s에서 동기화되었습니다.'), $url);

						$config['voucher'][$cpzone] = $newvoucher;
						write_config();
						voucher_configure_zone(true);
					}
				}
			}
		}

		if (!$input_errors) {
			header("Location: services_captiveportal_vouchers.php?zone={$cpzone}");
			exit;
		}
	}
}

include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

if ($savemsg) {
	print_info_box($savemsg, 'success');
}

$tab_array = array();
$tab_array[] = array(gettext("구성"), false, "services_captiveportal.php?zone={$cpzone}");
$tab_array[] = array(gettext("MACs"), false, "services_captiveportal_mac.php?zone={$cpzone}");
$tab_array[] = array(gettext("허용된 IP주소"), false, "services_captiveportal_ip.php?zone={$cpzone}");
$tab_array[] = array(gettext("허용된 호스트이름"), false, "services_captiveportal_hostname.php?zone={$cpzone}");
$tab_array[] = array(gettext("바우처"), true, "services_captiveportal_vouchers.php?zone={$cpzone}");
$tab_array[] = array(gettext("파일 매니저"), false, "services_captiveportal_filemanager.php?zone={$cpzone}");
display_top_tabs($tab_array, true);

// We draw a simple table first, then present the controls to work with it
?>
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext("바우처 롤");?></h2></div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed table-rowdblclickedit sortable-theme-bootstrap" data-sortable>
				<thead>
					<tr>
						<th><?=gettext("롤 #")?></th>
						<th><?=gettext("분/티켓")?></th>
						<th><?=gettext("티켓 수")?></th>
						<th><?=gettext("코멘트")?></th>
						<th><?=gettext("Actions")?></th>
					</tr>
				</thead>
				<tbody>
<?php
$i = 0;
foreach ($a_roll as $rollent):
?>
					<tr>
						<td><?=htmlspecialchars($rollent['number']); ?></td>
						<td><?=htmlspecialchars($rollent['minutes'])?></td>
						<td><?=htmlspecialchars($rollent['count'])?></td>
						<td><?=htmlspecialchars($rollent['descr']); ?></td>
						<td>
							<!-- These buttons are hidden/shown on checking the 'enable' checkbox -->
							<a class="fa fa-pencil"		title="<?=gettext("바우처 롤 수정"); ?>" href="services_captiveportal_vouchers_edit.php?zone=<?=$cpzone?>&amp;id=<?=$i; ?>"></a>
							<a class="fa fa-trash"		title="<?=gettext("바우처 롤 삭제")?>" href="services_captiveportal_vouchers.php?zone=<?=$cpzone?>&amp;act=del&amp;id=<?=$i; ?>" usepost></a>
							<a class="fa fa-file-excel-o"	title="<?=gettext("이 롤에 대한 바우처를 .csv 파일로 내보내기")?>" href="services_captiveportal_vouchers.php?zone=<?=$cpzone?>&amp;act=csv&amp;id=<?=$i; ?>"></a>
						</td>
					</tr>
<?php
	$i++;
endforeach;
?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<?php

if ($pconfig['enable']) : ?>
	<nav class="action-buttons">
		<a href="services_captiveportal_vouchers_edit.php?zone=<?=$cpzone?>" class="btn btn-success">
			<i class="fa fa-plus icon-embed-btn"></i>
			<?=gettext("추가")?>
		</a>
	</nav>
<?php
endif;

$form = new Form();

$section = new Form_Section('Create, Generate and Activate Rolls with Vouchers');

$section->addInput(new Form_Checkbox(
	'enable',
	'Enable',
	'Enable the creation, generation and activation of rolls with vouchers',
	$pconfig['enable']
	));

$form->add($section);

$section = new Form_Section('Create, Generate and Activate Rolls with Vouchers');
$section->addClass('rolledit');

$section->addInput(new Form_Textarea(
	'publickey',
	'Voucher Public Key',
	$pconfig['publickey']
))->setHelp('Paste an RSA public key (64 Bit or smaller) in PEM format here. This key is used to decrypt vouchers.');

$section->addInput(new Form_Textarea(
	'privatekey',
	'Voucher Private Key',
	$pconfig['privatekey']
))->setHelp('Paste an RSA private key (64 Bit or smaller) in PEM format here. This key is only used to generate encrypted vouchers and doesn\'t need to be available if the vouchers have been generated offline.');

$section->addInput(new Form_Input(
	'charset',
	'Character set',
	'text',
	$pconfig['charset']
))->setHelp('Tickets are generated with the specified character set. It should contain printable characters (numbers, lower case and upper case letters) that are hard to confuse with others. Avoid e.g. 0/O and l/1.');

$section->addInput(new Form_Input(
	'rollbits',
	'# of Roll bits',
	'text',
	$pconfig['rollbits']
))->setHelp('Reserves a range in each voucher to store the Roll # it belongs to. Allowed range: 1..31. Sum of Roll+Ticket+Checksum bits must be one Bit less than the RSA key size.');

$section->addInput(new Form_Input(
	'ticketbits',
	'# of Ticket bits',
	'text',
	$pconfig['ticketbits']
))->setHelp('Reserves a range in each voucher to store the Ticket# it belongs to. Allowed range: 1..16. ' .
					'Using 16 bits allows a roll to have up to 65535 vouchers. ' .
					'A bit array, stored in RAM and in the config, is used to mark if a voucher has been used. A bit array for 65535 vouchers requires 8 KB of storage. ');

$section->addInput(new Form_Input(
	'checksumbits',
	'# of Checksum bits',
	'text',
	$pconfig['checksumbits']
))->setHelp('Reserves a range in each voucher to store a simple checksum over Roll # and Ticket#. Allowed range is 0..31.');

$section->addInput(new Form_Input(
	'magic',
	'Magic number',
	'text',
	$pconfig['magic']
))->setHelp('Magic number stored in every voucher. Verified during voucher check. ' .
					'Size depends on how many bits are left by Roll+Ticket+Checksum bits. If all bits are used, no magic number will be used and checked.');

$section->addInput(new Form_Input(
	'msgnoaccess',
	'Invalid voucher message',
	'text',
	$pconfig['msgnoaccess']
))->setHelp('Error message displayed for invalid vouchers on captive portal error page ($PORTAL_MESSAGE$).');


$section->addInput(new Form_Input(
	'msgexpired',
	'Expired voucher message',
	'text',
	$pconfig['msgexpired']
))->setHelp('Error message displayed for expired vouchers on captive portal error page ($PORTAL_MESSAGE$).');

$form->add($section);

$section = new Form_Section('Voucher Database Synchronization');
$section->addClass('rolledit');

$section->addInput(new Form_IpAddress(
	'vouchersyncdbip',
	'Synchronize Voucher Database IP',
	$pconfig['vouchersyncdbip']
))->setHelp('IP address of master nodes webConfigurator to synchronize voucher database and used vouchers from.%1$s' .
			'NOTE: this should be setup on the slave nodes and not the primary node!', '<br />');

$section->addInput(new Form_Input(
	'vouchersyncport',
	'Voucher sync port',
	'text',
	$pconfig['vouchersyncport']
))->setHelp('The port of the master voucher node\'s webConfigurator. Example: 443 ');

$section->addInput(new Form_Input(
	'vouchersyncusername',
	'Voucher sync username',
	'text',
	$pconfig['vouchersyncusername']
))->setHelp('This is the username of the master voucher nodes webConfigurator.');

$section->addPassword(new Form_Input(
	'vouchersyncpass',
	'Voucher sync password',
	'password',
	$pconfig['vouchersyncpass']
))->setHelp('This is the password of the master voucher nodes webConfigurator.');

$section->addInput(new Form_Input(
	'zone',
	null,
	'hidden',
	$cpzone
));

$section->addInput(new Form_Input(
	'exponent',
	null,
	'hidden',
	$pconfig['exponent']
));

$form->add($section);
print($form);
?>
<div class="rolledit">
<?php
	print_info_box(gettext('이 페이지에서 롤의 목록을 관리하는 것과는 별도로 바우처 매개 변수를 변경하면 기존 바우처가 다른 설정으로 생성 된 경우 쓸모 없게됩니다. ' .
							'바우처 데이터베이스 지정 동기화 옵션은 다른 옵션의 다른 값을 기록하지 않습니다. 마스터로부터 검색/동기화 됩니다.'), 'info');
?>
</div>

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

	function setShowHide (show) {
		hideClass('rolledit', !show);

		if (show) {
			$('td:nth-child(5),th:nth-child(5)').show();
		} else {
			$('td:nth-child(5),th:nth-child(5)').hide();
		}
	}

	// Show/hide on checkbox change
	$('#enable').click(function() {
		setShowHide($('#enable').is(":checked"));
	})

	// Set initial state
	setShowHide($('#enable').is(":checked"));

	var generateButton = $('<a class="btn btn-xs btn-warning"><i class="fa fa-refresh icon-embed-btn"></i><?=gettext("새 키 생성");?></a>');
	generateButton.on('click', function() {
		$.ajax({
			type: 'get',
			url: 'services_captiveportal_vouchers.php?generatekey=true',
			dataType: 'json',
			success: function(data) {
				$('#publickey').val(data.public.replace(/\\n/g, '\n'));
				$('#privatekey').val(data.private.replace(/\\n/g, '\n'));
			}
		});
	});
	generateButton.appendTo($('#publickey + .help-block')[0]);
});
//]]>
</script>
<?php include("foot.inc");

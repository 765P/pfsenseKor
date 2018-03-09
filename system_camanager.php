<?php
/*
 * system_camanager.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2018 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2008 Shrew Soft Inc
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
2018.03.08
한글화 번역 시작
*/

##|+PRIV
##|*IDENT=page-system-camanager
##|*NAME=System: CA Manager
##|*DESCR=Allow access to the 'System: CA Manager' page.
##|*MATCH=system_camanager.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("certs.inc");
require_once("pfsense-utils.inc");

$ca_methods = array(
	"existing" => gettext("기존 인증 기관 가져 오기"),
	"internal" => gettext("내부 인증 기관 만들기"),
	"intermediate" => gettext("중간 인증 기관 만들기"));

$ca_keylens = array("512", "1024", "2048", "3072", "4096", "7680", "8192", "15360", "16384");
global $openssl_digest_algs;

if (isset($_REQUEST['id']) && is_numericint($_REQUEST['id'])) {
	$id = $_REQUEST['id'];
}

if (!is_array($config['ca'])) {
	$config['ca'] = array();
}

$a_ca =& $config['ca'];

if (!is_array($config['cert'])) {
	$config['cert'] = array();
}

$a_cert =& $config['cert'];

if (!is_array($config['crl'])) {
	$config['crl'] = array();
}

$a_crl =& $config['crl'];

if ($_REQUEST['act']) {
	$act = $_REQUEST['act'];
}

if ($_POST['act'] == "del") {

	if (!isset($a_ca[$id])) {
		pfSenseHeader("system_camanager.php");
		exit;
	}

	/* Only remove CA reference when deleting. It can be reconnected if a new matching CA is imported */
	$index = count($a_cert) - 1;
	for (;$index >= 0; $index--) {
		if ($a_cert[$index]['caref'] == $a_ca[$id]['refid']) {
			unset($a_cert[$index]['caref']);
		}
	}

	/* Remove any CRLs for this CA, there is no way to recover the connection once the CA has been removed. */
	$index = count($a_crl) - 1;
	for (;$index >= 0; $index--) {
		if ($a_crl[$index]['caref'] == $a_ca[$id]['refid']) {
			unset($a_crl[$index]);
		}
	}

	$name = $a_ca[$id]['descr'];
	unset($a_ca[$id]);
	write_config();
	$savemsg = sprintf(gettext("인증 기관 %s 및 해당 CRL(있는 경우)이 성공적으로 삭제되었습니다."), htmlspecialchars($name));
	pfSenseHeader("system_camanager.php");
	exit;
}

if ($act == "edit") {
	if (!$a_ca[$id]) {
		pfSenseHeader("system_camanager.php");
		exit;
	}
	$pconfig['descr']  = $a_ca[$id]['descr'];
	$pconfig['refid']  = $a_ca[$id]['refid'];
	$pconfig['cert']   = base64_decode($a_ca[$id]['crt']);
	$pconfig['serial'] = $a_ca[$id]['serial'];
	if (!empty($a_ca[$id]['prv'])) {
		$pconfig['key'] = base64_decode($a_ca[$id]['prv']);
	}
}

if ($act == "new") {
	$pconfig['method'] = $_POST['method'];
	$pconfig['keylen'] = "2048";
	$pconfig['digest_alg'] = "sha256";
	$pconfig['lifetime'] = "3650";
	$pconfig['dn_commonname'] = "internal-ca";
}

if ($act == "exp") {

	if (!$a_ca[$id]) {
		pfSenseHeader("system_camanager.php");
		exit;
	}

	$exp_name = urlencode("{$a_ca[$id]['descr']}.crt");
	$exp_data = base64_decode($a_ca[$id]['crt']);
	$exp_size = strlen($exp_data);

	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename={$exp_name}");
	header("Content-Length: $exp_size");
	echo $exp_data;
	exit;
}

if ($act == "expkey") {

	if (!$a_ca[$id]) {
		pfSenseHeader("system_camanager.php");
		exit;
	}

	$exp_name = urlencode("{$a_ca[$id]['descr']}.key");
	$exp_data = base64_decode($a_ca[$id]['prv']);
	$exp_size = strlen($exp_data);

	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename={$exp_name}");
	header("Content-Length: $exp_size");
	echo $exp_data;
	exit;
}

if ($_POST['save']) {

	unset($input_errors);
	$input_errors = array();
	$pconfig = $_POST;

	/* input validation */
	if ($pconfig['method'] == "existing") {
		$reqdfields = explode(" ", "descr cert");
		$reqdfieldsn = array(
			gettext("기술적인 이름"),
			gettext("인증서 데이터"));
		if ($_POST['cert'] && (!strstr($_POST['cert'], "BEGIN CERTIFICATE") || !strstr($_POST['cert'], "END CERTIFICATE"))) {
			$input_errors[] = gettext("이 인증서는 유효하지 않습니다.");
		}
		if ($_POST['key'] && strstr($_POST['key'], "ENCRYPTED")) {
			$input_errors[] = gettext("암호화 된 개인 키는 아직 지원되지 않습니다.");
		}
		if (!$input_errors && !empty($_POST['key']) && cert_get_publickey($_POST['cert'], false) != cert_get_publickey($_POST['key'], false, 'prv')) {
			$input_errors[] = gettext("제출 된 개인 키가 제출 된 인증서 데이터와 일치하지 않습니다.");
		}
		/* we must ensure the certificate is capable of acting as a CA
		 * https://redmine.pfsense.org/issues/7885
		 */
		if (!$input_errors) {
			$purpose = cert_get_purpose($_POST['cert'], false);
			if ($purpose['ca'] != 'Yes') {
				$input_errors[] = gettext("제출 된 인증서가 인증 기관이 아닌 것으로 나타나면 대신 인증서 탭에 가져 오십시오.");
			}
		}
	}
	if ($pconfig['method'] == "internal") {
		$reqdfields = explode(" ",
			"descr keylen lifetime dn_country dn_state dn_city ".
			"dn_organization dn_email dn_commonname");
		$reqdfieldsn = array(
			gettext("기술적인 이름"),
			gettext("키 길이"),
			gettext("유효기간"),
			gettext("국가 코드"),
			gettext("주 또는 도 이름"),
			gettext("도시 이름"),
			gettext("소속"),
			gettext("이메일 주소"),
			gettext("이름"));
	}
	if ($pconfig['method'] == "intermediate") {
		$reqdfields = explode(" ",
			"descr caref keylen lifetime dn_country dn_state dn_city ".
			"dn_organization dn_email dn_commonname");
		$reqdfieldsn = array(
			gettext("기술적인 이름"),
			gettext("서명 인증 기관"),
			gettext("키 길이"),
			gettext("유효기간"),
			gettext("국가 코드"),
			gettext("주 또는 도 이름"),
			gettext("도시 이름"),
			gettext("소속"),
			gettext("이메일 주소"),
			gettext("이름"));
	}

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);
	if ($pconfig['method'] != "existing") {
		/* Make sure we do not have invalid characters in the fields for the certificate */
		if (preg_match("/[\?\>\<\&\/\\\"\']/", $_POST['descr'])) {
			array_push($input_errors, gettext("'기술적인 이름'필드에 잘못된 문자가 있습니다."));
		}

		for ($i = 0; $i < count($reqdfields); $i++) {
			if ($reqdfields[$i] == 'dn_email') {
				if (preg_match("/[\!\#\$\%\^\(\)\~\?\>\<\&\/\\\,\"\']/", $_POST["dn_email"])) {
					array_push($input_errors, gettext("'이메일 주소'필드에 유효하지 않은 문자가 포함되어 있습니다."));
				}
			}
		}
		if (!in_array($_POST["keylen"], $ca_keylens)) {
			array_push($input_errors, gettext("올바른 키 길이를 선택하십시오."));
		}
		if (!in_array($_POST["digest_alg"], $openssl_digest_algs)) {
			array_push($input_errors, gettext("유효한 알고리즘을 선택하십시오."));
		}
	}

	/* save modifications */
	if (!$input_errors) {
		$ca = array();
		if (!isset($pconfig['refid']) || empty($pconfig['refid'])) {
			$ca['refid'] = uniqid();
		} else {
			$ca['refid'] = $pconfig['refid'];
		}

		if (isset($id) && $a_ca[$id]) {
			$ca = $a_ca[$id];
		}

		$ca['descr'] = $pconfig['descr'];

		if ($act == "edit") {
			$ca['descr']  = $pconfig['descr'];
			$ca['refid']  = $pconfig['refid'];
			$ca['serial'] = $pconfig['serial'];
			$ca['crt']	  = base64_encode($pconfig['cert']);
			if (!empty($pconfig['key'])) {
				$ca['prv']	  = base64_encode($pconfig['key']);
			}
		} else {
			$old_err_level = error_reporting(0); /* otherwise openssl_ functions throw warnings directly to a page screwing menu tab */
			if ($pconfig['method'] == "existing") {
				ca_import($ca, $pconfig['cert'], $pconfig['key'], $pconfig['serial']);
			} else if ($pconfig['method'] == "internal") {
				$dn = array(
					'countryName' => $pconfig['dn_country'],
					'stateOrProvinceName' => cert_escape_x509_chars($pconfig['dn_state']),
					'localityName' => cert_escape_x509_chars($pconfig['dn_city']),
					'organizationName' => cert_escape_x509_chars($pconfig['dn_organization']),
					'emailAddress' => cert_escape_x509_chars($pconfig['dn_email']),
					'commonName' => cert_escape_x509_chars($pconfig['dn_commonname']));
				if (!empty($pconfig['dn_organizationalunit'])) {
					$dn['organizationalUnitName'] = cert_escape_x509_chars($pconfig['dn_organizationalunit']);
				}
				if (!ca_create($ca, $pconfig['keylen'], $pconfig['lifetime'], $dn, $pconfig['digest_alg'])) {
					$input_errors = array();
					while ($ssl_err = openssl_error_string()) {
						if (strpos($ssl_err, 'NCONF_get_string:no value') === false) {
							array_push($input_errors, "openssl library returns: " . $ssl_err);
						}
					}
				}
			} else if ($pconfig['method'] == "intermediate") {
				$dn = array(
					'countryName' => $pconfig['dn_country'],
					'stateOrProvinceName' => cert_escape_x509_chars($pconfig['dn_state']),
					'localityName' => cert_escape_x509_chars($pconfig['dn_city']),
					'organizationName' => cert_escape_x509_chars($pconfig['dn_organization']),
					'emailAddress' => cert_escape_x509_chars($pconfig['dn_email']),
					'commonName' => cert_escape_x509_chars($pconfig['dn_commonname']));
				if (!empty($pconfig['dn_organizationalunit'])) {
					$dn['organizationalUnitName'] = cert_escape_x509_chars($pconfig['dn_organizationalunit']);
				}
				if (!ca_inter_create($ca, $pconfig['keylen'], $pconfig['lifetime'], $dn, $pconfig['caref'], $pconfig['digest_alg'])) {
					$input_errors = array();
					while ($ssl_err = openssl_error_string()) {
						if (strpos($ssl_err, 'NCONF_get_string:no value') === false) {
							array_push($input_errors, "openssl library returns: " . $ssl_err);
						}
					}
				}
			}
			error_reporting($old_err_level);
		}

		if (isset($id) && $a_ca[$id]) {
			$a_ca[$id] = $ca;
		} else {
			$a_ca[] = $ca;
		}

		if (!$input_errors) {
			write_config();
			pfSenseHeader("system_camanager.php");
		}
	}
}

$pgtitle = array(gettext("System"), gettext("인증서 매니저"), gettext("CAs"));
$pglinks = array("", "system_camanager.php", "system_camanager.php");

if ($act == "new" || $act == "edit" || $act == gettext("저장") || $input_errors) {
	$pgtitle[] = gettext('편집');
	$pglinks[] = "@self";
}
include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

if ($savemsg) {
	print_info_box($savemsg, 'success');
}

// Load valid country codes
$dn_cc = array();
if (file_exists("/etc/ca_countries")) {
	$dn_cc_file=file("/etc/ca_countries");
	foreach ($dn_cc_file as $line) {
		if (preg_match('/^(\S*)\s(.*)$/', $line, $matches)) {
			$dn_cc[$matches[1]] = $matches[1];
		}
	}
}

$tab_array = array();
$tab_array[] = array(gettext("CAs"), true, "system_camanager.php");
$tab_array[] = array(gettext("Certificates"), false, "system_certmanager.php");
$tab_array[] = array(gettext("Certificate Revocation"), false, "system_crlmanager.php");
display_top_tabs($tab_array);

if (!($act == "new" || $act == "edit" || $act == gettext("저장") || $input_errors)) {
?>
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext('인증 기관')?></h2></div>
	<div class="panel-body">
		<div class="table-responsive">
		<table class="table table-striped table-hover table-rowdblclickedit">
			<thead>
				<tr>
					<th><?=gettext("이름")?></th>
					<th><?=gettext("내부")?></th>
					<th><?=gettext("발행자")?></th>
					<th><?=gettext("인증서")?></th>
					<th><?=gettext("Distinguished Name")?></th>
					<th><?=gettext("In Use")?></th>
					<th><?=gettext("Actions")?></th>
				</tr>
			</thead>
			<tbody>
<?php
$pluginparams = array();
$pluginparams['type'] = 'certificates';
$pluginparams['event'] = 'used_ca';
$certificates_used_by_packages = pkg_call_plugins('plugin_certificates', $pluginparams);

foreach ($a_ca as $i => $ca):
	$name = htmlspecialchars($ca['descr']);
	$subj = cert_get_subject($ca['crt']);
	$issuer = cert_get_issuer($ca['crt']);
	list($startdate, $enddate) = cert_get_dates($ca['crt']);
	if ($subj == $issuer) {
		$issuer_name = gettext("자가 서명");
	} else {
		$issuer_name = gettext("외부");
	}
	$subj = htmlspecialchars(cert_escape_x509_chars($subj, true));
	$issuer = htmlspecialchars($issuer);
	$certcount = 0;

	$issuer_ca = lookup_ca($ca['caref']);
	if ($issuer_ca) {
		$issuer_name = $issuer_ca['descr'];
	}

	foreach ($a_cert as $cert) {
		if ($cert['caref'] == $ca['refid']) {
			$certcount++;
		}
	}

	foreach ($a_ca as $cert) {
		if ($cert['caref'] == $ca['refid']) {
			$certcount++;
		}
	}
?>
				<tr>
					<td><?=$name?></td>
					<td><i class="fa fa-<?= (!empty($ca['prv'])) ? "check" : "times" ; ?>"></i></td>
					<td><i><?=$issuer_name?></i></td>
					<td><?=$certcount?></td>
					<td>
						<?=$subj?>
						<br />
						<small>
							<?=gettext("유효기간 시작")?>: <b><?=$startdate ?></b><br /><?=gettext("유효기간 만료")?>: <b><?=$enddate ?></b>
						</small>
					</td>
					<td class="text-nowrap">
						<?php if (is_openvpn_server_ca($ca['refid'])): ?>
							<?=gettext("OpenVPN 서버")?><br/>
						<?php endif?>
						<?php if (is_openvpn_client_ca($ca['refid'])): ?>
							<?=gettext("OpenVPN 클라이언트")?><br/>
						<?php endif?>
						<?php if (is_ipsec_peer_ca($ca['refid'])): ?>
							<?=gettext("IPsec 터널")?><br/>
						<?php endif?>
						<?php if (is_ldap_peer_ca($ca['refid'])): ?>
							<?=gettext("LDAP 서버")?>
						<?php endif?>
						<?php echo cert_usedby_description($ca['refid'], $certificates_used_by_packages); ?>
					</td>
					<td class="text-nowrap">
						<a class="fa fa-pencil"	title="<?=gettext("CA 편집")?>"	href="system_camanager.php?act=edit&amp;id=<?=$i?>"></a>
						<a class="fa fa-certificate"	title="<?=gettext("CA 내보내기")?>"	href="system_camanager.php?act=exp&amp;id=<?=$i?>"></a>
					<?php if ($ca['prv']): ?>
						<a class="fa fa-key"	title="<?=gettext("key 내보내기")?>"	href="system_camanager.php?act=expkey&amp;id=<?=$i?>"></a>
					<?php endif?>
					<?php if (!ca_in_use($ca['refid'])): ?>
						<a class="fa fa-trash" 	title="<?=gettext("CA 및 해당 CRL 삭제")?>"	href="system_camanager.php?act=del&amp;id=<?=$i?>" usepost ></a>
					<?php endif?>
					</td>
				</tr>
<?php endforeach; ?>
			</tbody>
		</table>
		</div>
	</div>
</div>

<nav class="action-buttons">
	<a href="?act=new" class="btn btn-success btn-sm">
		<i class="fa fa-plus icon-embed-btn"></i>
		<?=gettext("추가")?>
	</a>
</nav>
<?php
	include("foot.inc");
	exit;
}

$form = new Form;
//$form->setAction('system_camanager.php?act=edit');
if (isset($id) && $a_ca[$id]) {
	$form->addGlobal(new Form_Input(
		'id',
		null,
		'hidden',
		$id
	));
}

if ($act == "edit") {
	$form->addGlobal(new Form_Input(
		'refid',
		null,
		'hidden',
		$pconfig['refid']
	));
}

$section = new Form_Section('Create / Edit CA');

$section->addInput(new Form_Input(
	'descr',
	'*Descriptive name',
	'text',
	$pconfig['descr']
));

if (!isset($id) || $act == "edit") {
	$section->addInput(new Form_Select(
		'method',
		'*Method',
		$pconfig['method'],
		$ca_methods
	))->toggles();
}

$form->add($section);

$section = new Form_Section('Existing Certificate Authority');
$section->addClass('toggle-existing collapse');

$section->addInput(new Form_Textarea(
	'cert',
	'*Certificate data',
	$pconfig['cert']
))->setHelp('Paste a certificate in X.509 PEM format here.');

$section->addInput(new Form_Textarea(
	'key',
	'Certificate Private Key (optional)',
	$pconfig['key']
))->setHelp('Paste the private key for the above certificate here. This is '.
	'optional in most cases, but is required when generating a '.
	'Certificate Revocation List (CRL).');

$section->addInput(new Form_Input(
	'serial',
	'Serial for next certificate',
	'number',
	$pconfig['serial']
))->setHelp('Enter a decimal number to be used as the serial number for the next '.
	'certificate to be created using this CA.');

$form->add($section);

$section = new Form_Section('Internal Certificate Authority');
$section->addClass('toggle-internal', 'toggle-intermediate', 'collapse');

$allCas = array();
foreach ($a_ca as $ca) {
	if (!$ca['prv']) {
			continue;
	}

	$allCas[ $ca['refid'] ] = $ca['descr'];
}

$group = new Form_Group('*Signing Certificate Authority');
$group->addClass('toggle-intermediate', 'collapse');
$group->add(new Form_Select(
	'caref',
	null,
	$pconfig['caref'],
	$allCas
));
$section->add($group);

$section->addInput(new Form_Select(
	'keylen',
	'*Key length (bits)',
	$pconfig['keylen'],
	array_combine($ca_keylens, $ca_keylens)
));

$section->addInput(new Form_Select(
	'digest_alg',
	'*Digest Algorithm',
	$pconfig['digest_alg'],
	array_combine($openssl_digest_algs, $openssl_digest_algs)
))->setHelp('NOTE: It is recommended to use an algorithm stronger than SHA1 '.
	'when possible.');

$section->addInput(new Form_Input(
	'lifetime',
	'*Lifetime (days)',
	'number',
	$pconfig['lifetime']
));

$section->addInput(new Form_Select(
	'dn_country',
	'*Country Code',
	$pconfig['dn_country'],
	$dn_cc
));

$section->addInput(new Form_Input(
	'dn_state',
	'*State or Province',
	'text',
	$pconfig['dn_state'],
	['placeholder' => 'e.g. Texas']
));

$section->addInput(new Form_Input(
	'dn_city',
	'*City',
	'text',
	$pconfig['dn_city'],
	['placeholder' => 'e.g. Austin']
));

$section->addInput(new Form_Input(
	'dn_organization',
	'*Organization',
	'text',
	$pconfig['dn_organization'],
	['placeholder' => 'e.g. My Company Inc']
));

$section->addInput(new Form_Input(
	'dn_organizationalunit',
	'Organizational Unit',
	'text',
	$pconfig['dn_organizationalunit'],
	['placeholder' => 'e.g. My Department Name (optional)']
));

$section->addInput(new Form_Input(
	'dn_email',
	'*Email Address',
	'email',
	$pconfig['dn_email'],
	['placeholder' => 'e.g. admin@mycompany.com']
));

$section->addInput(new Form_Input(
	'dn_commonname',
	'*Common Name',
	'text',
	$pconfig['dn_commonname'],
	['placeholder' => 'e.g. internal-ca']
));

$form->add($section);

print $form;

$internal_ca_count = 0;
foreach ($a_ca as $ca) {
	if ($ca['prv']) {
		$internal_ca_count++;
	}
}

include('foot.inc');
?>

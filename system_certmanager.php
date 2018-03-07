<?php
/*
 * system_certmanager.php
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
2018.03.07
한글화 번역 추가
*/

##|+PRIV
##|*IDENT=page-system-certmanager
##|*NAME=System: Certificate Manager
##|*DESCR=Allow access to the 'System: Certificate Manager' page.
##|*MATCH=system_certmanager.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("certs.inc");
require_once("pfsense-utils.inc");

$cert_methods = array(
	"import" => gettext("기존 인증서 가져 오기"),
	"internal" => gettext("내부 인증서 만들기"),
	"external" => gettext("인증서 서명 요청 만들기"),
	"sign" => gettext("인증서 서명 요청 서명")
);

$cert_keylens = array("512", "1024", "2048", "3072", "4096", "7680", "8192", "15360", "16384");
$cert_types = array(
	"server" => "Server Certificate",
	"user" => "User Certificate");

global $cert_altname_types;
global $openssl_digest_algs;

if (isset($_REQUEST['userid']) && is_numericint($_REQUEST['userid'])) {
	$userid = $_REQUEST['userid'];
}

if (isset($userid)) {
	$cert_methods["existing"] = gettext("기존 인증서 선택");
	if (!is_array($config['system']['user'])) {
		$config['system']['user'] = array();
	}
	$a_user =& $config['system']['user'];
}

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

$internal_ca_count = 0;
foreach ($a_ca as $ca) {
	if ($ca['prv']) {
		$internal_ca_count++;
	}
}

$act = $_REQUEST['act'];

if ($_POST['act'] == "del") {

	if (!isset($a_cert[$id])) {
		pfSenseHeader("system_certmanager.php");
		exit;
	}

	unset($a_cert[$id]);
	write_config();
	$savemsg = sprintf(gettext("인증서 %s이(가) 성공적으로 삭제되었습니다."), htmlspecialchars($a_cert[$id]['descr']));
	pfSenseHeader("system_certmanager.php");
	exit;
}

if ($act == "new") {
	$pconfig['method'] = $_POST['method'];
	$pconfig['keylen'] = "2048";
	$pconfig['digest_alg'] = "sha256";
	$pconfig['csr_keylen'] = "2048";
	$pconfig['csr_digest_alg'] = "sha256";
	$pconfig['csrsign_digest_alg'] = "sha256";
	$pconfig['type'] = "user";
	$pconfig['lifetime'] = "3650";
}

if ($act == "exp") {

	if (!$a_cert[$id]) {
		pfSenseHeader("system_certmanager.php");
		exit;
	}

	$exp_name = urlencode("{$a_cert[$id]['descr']}.crt");
	$exp_data = base64_decode($a_cert[$id]['crt']);
	$exp_size = strlen($exp_data);

	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename={$exp_name}");
	header("Content-Length: $exp_size");
	echo $exp_data;
	exit;
}

if ($act == "req") {

	if (!$a_cert[$id]) {
		pfSenseHeader("system_certmanager.php");
		exit;
	}

	$exp_name = urlencode("{$a_cert[$id]['descr']}.req");
	$exp_data = base64_decode($a_cert[$id]['csr']);
	$exp_size = strlen($exp_data);

	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename={$exp_name}");
	header("Content-Length: $exp_size");
	echo $exp_data;
	exit;
}

if ($act == "key") {

	if (!$a_cert[$id]) {
		pfSenseHeader("system_certmanager.php");
		exit;
	}

	$exp_name = urlencode("{$a_cert[$id]['descr']}.key");
	$exp_data = base64_decode($a_cert[$id]['prv']);
	$exp_size = strlen($exp_data);

	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename={$exp_name}");
	header("Content-Length: $exp_size");
	echo $exp_data;
	exit;
}

if ($act == "p12") {
	if (!$a_cert[$id]) {
		pfSenseHeader("system_certmanager.php");
		exit;
	}

	$exp_name = urlencode("{$a_cert[$id]['descr']}.p12");
	$args = array();
	$args['friendly_name'] = $a_cert[$id]['descr'];

	$ca = lookup_ca($a_cert[$id]['caref']);

	if ($ca) {
		$args['extracerts'] = openssl_x509_read(base64_decode($ca['crt']));
	}

	$res_crt = openssl_x509_read(base64_decode($a_cert[$id]['crt']));
	$res_key = openssl_pkey_get_private(array(0 => base64_decode($a_cert[$id]['prv']) , 1 => ""));

	$exp_data = "";
	openssl_pkcs12_export($res_crt, $exp_data, $res_key, null, $args);
	$exp_size = strlen($exp_data);

	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename={$exp_name}");
	header("Content-Length: $exp_size");
	echo $exp_data;
	exit;
}

if ($act == "csr") {
	if (!$a_cert[$id]) {
		pfSenseHeader("system_certmanager.php");
		exit;
	}

	$pconfig['descr'] = $a_cert[$id]['descr'];
	$pconfig['csr'] = base64_decode($a_cert[$id]['csr']);
}

if ($_POST['save']) {

	if ($_POST['save'] == gettext("저장")) {
		$input_errors = array();
		$pconfig = $_POST;

		/* input validation */
		if ($pconfig['method'] == "sign") {
			$reqdfields = explode(" ",
				"descr catosignwith");
			$reqdfieldsn = array(
				gettext("기술적인 이름"),
				gettext("서명할 CA"));

			if (($_POST['csrtosign'] === "new") &&
			    ((!strstr($_POST['csrpaste'], "BEGIN CERTIFICATE REQUEST") || !strstr($_POST['csrpaste'], "END CERTIFICATE REQUEST")) &&
			    (!strstr($_POST['csrpaste'], "BEGIN NEW CERTIFICATE REQUEST") || !strstr($_POST['csrpaste'], "END NEW CERTIFICATE REQUEST")))) {
				$input_errors[] = gettext("이 서명 요청이 유효하지 않습니다.");
			}

			if ( (($_POST['csrtosign'] === "new") && (strlen($_POST['keypaste']) > 0)) && (!strstr($_POST['keypaste'], "BEGIN PRIVATE KEY") || !strstr($_POST['keypaste'], "END PRIVATE KEY"))) {
				$input_errors[] = gettext("이 비공개는 유효하지 않은 것으로 보입니다.");
				$input_errors[] = gettext("키 데이터 필드는 공백이거나 유효한 x509 개인 키여야합니다.");
			}

		}

		if ($pconfig['method'] == "import") {
			$reqdfields = explode(" ",
				"descr cert key");
			$reqdfieldsn = array(
				gettext("기술적인 이름"),
				gettext("인증서 데이터"),
				gettext("키 데이터"));
			if ($_POST['cert'] && (!strstr($_POST['cert'], "BEGIN CERTIFICATE") || !strstr($_POST['cert'], "END CERTIFICATE"))) {
				$input_errors[] = gettext("이 인증서는 유효하지 않습니다.");
			}

			if (cert_get_publickey($_POST['cert'], false) != cert_get_publickey($_POST['key'], false, 'prv')) {
				$input_errors[] = gettext("제출 된 개인 키가 제출 된 인증서 데이터와 일치하지 않습니다.");
			}
		}

		if ($pconfig['method'] == "internal") {
			$reqdfields = explode(" ",
				"descr caref keylen type lifetime dn_country dn_state dn_city ".
				"dn_organization dn_email dn_commonname");
			$reqdfieldsn = array(
				gettext("기술적인 이름"),
				gettext("인증기관"),
				gettext("키 길이"),
				gettext("인증서 타입"),
				gettext("유효기간"),
				gettext("고유 이름 국가 코드"),
				gettext("주 또는 도 이름"),
				gettext("소속 시"),
				gettext("소속 조직"),
				gettext("이메일 주소"),
				gettext("성함"));
		}

		if ($pconfig['method'] == "external") {
			$reqdfields = explode(" ",
				"descr csr_keylen csr_dn_country csr_dn_state csr_dn_city ".
				"csr_dn_organization csr_dn_email csr_dn_commonname");
			$reqdfieldsn = array(
				gettext("기술적인 이름"),
				gettext("키 길이"),
				gettext("고유 이름 국가 코드"),
				gettext("주 또는 도 이름"),
				gettext("소속 시"),
				gettext("소속 조직"),
				gettext("이메일 주소"),
				gettext("성함"));
		}

		if ($pconfig['method'] == "existing") {
			$reqdfields = array("certref");
			$reqdfieldsn = array(gettext("기존 인증서 "));
		}

		$altnames = array();
		do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

		if ($pconfig['method'] != "import" && $pconfig['method'] != "existing") {
			/* subjectAltNames */
			$san_typevar = 'altname_type';
			$san_valuevar = 'altname_value';
			// This is just the blank alternate name that is added for display purposes. We don't want to validate/save it
			if ($_POST["{$san_valuevar}0"] == "") {
				unset($_POST["{$san_typevar}0"]);
				unset($_POST["{$san_valuevar}0"]);
			}
			foreach ($_POST as $key => $value) {
				$entry = '';
				if (!substr_compare($san_typevar, $key, 0, strlen($san_typevar))) {
					$entry = substr($key, strlen($san_typevar));
					$field = 'type';
				} elseif (!substr_compare($san_valuevar, $key, 0, strlen($san_valuevar))) {
					$entry = substr($key, strlen($san_valuevar));
					$field = 'value';
				}

				if (ctype_digit($entry)) {
					$entry++;	// Pre-bootstrap code is one-indexed, but the bootstrap code is 0-indexed
					$altnames[$entry][$field] = $value;
				}
			}

			$pconfig['altnames']['item'] = $altnames;

			/* Input validation for subjectAltNames */
			foreach ($altnames as $idx => $altname) {
				switch ($altname['type']) {
					case "DNS":
						if (!is_hostname($altname['value'], true) || is_ipaddr($altname['value'])) {
							array_push($input_errors, "DNS subjectAltName values must be valid hostnames, FQDNs or wildcard domains.");
						}
						break;
					case "IP":
						if (!is_ipaddr($altname['value'])) {
							array_push($input_errors, "IP subjectAltName values must be valid IP Addresses");
						}
						break;
					case "email":
						if (empty($altname['value'])) {
							array_push($input_errors, "An e-mail address must be provided for this type of subjectAltName");
						}
						if (preg_match("/[\!\#\$\%\^\(\)\~\?\>\<\&\/\\\,\"\']/", $altname['value'])) {
							array_push($input_errors, "The e-mail provided in a subjectAltName contains invalid characters.");
						}
						break;
					case "URI":
						/* Close enough? */
						if (!is_URL($altname['value'])) {
							$input_errors[] = "URI subjectAltName types must be a valid URI";
						}
						break;
					default:
						$input_errors[] = "Unrecognized subjectAltName type.";
				}
			}

			/* Make sure we do not have invalid characters in the fields for the certificate */

			if (preg_match("/[\?\>\<\&\/\\\"\']/", $_POST['descr'])) {
				array_push($input_errors, "The field 'Descriptive Name' contains invalid characters.");
			}

			for ($i = 0; $i < count($reqdfields); $i++) {
				if (preg_match('/email/', $reqdfields[$i])) { /* dn_email or csr_dn_name */
					if (preg_match("/[\!\#\$\%\^\(\)\~\?\>\<\&\/\\\,\"\']/", $_POST[$reqdfields[$i]])) {
						array_push($input_errors, gettext("'고유 이름 이메일 주소'필드에 잘못된 문자가 있습니다."));
					}
				}
			}

			if (($pconfig['method'] != "external") && isset($_POST["keylen"]) && !in_array($_POST["keylen"], $cert_keylens)) {
				array_push($input_errors, gettext("유효한 키 길이를 설정하십시오."));
			}
			if (($pconfig['method'] != "external") && !in_array($_POST["digest_alg"], $openssl_digest_algs)) {
				array_push($input_errors, gettext("유효한 알고리즘을 설정하십시오."));
			}

			if (($pconfig['method'] == "external") && isset($_POST["csr_keylen"]) && !in_array($_POST["csr_keylen"], $cert_keylens)) {
				array_push($input_errors, gettext("유효한 키 길이를 설정하십시오."));
			}
			if (($pconfig['method'] == "external") && !in_array($_POST["csr_digest_alg"], $openssl_digest_algs)) {
				array_push($input_errors, gettext("유효한 알고리즘을 설정하십시오."));
			}
			if (($pconfig['method'] == "sign") && !in_array($_POST["csrsign_digest_alg"], $openssl_digest_algs)) {
				array_push($input_errors, gettext("유효한 알고리즘을 설정하십시오."));
			}
		}

		/* save modifications */
		if (!$input_errors) {

			if ($pconfig['method'] == "existing") {
				$cert = lookup_cert($pconfig['certref']);
				if ($cert && $a_user) {
					$a_user[$userid]['cert'][] = $cert['refid'];
				}
			} else if ($pconfig['method'] == "sign") { // Sign a CSR
				$csrid = lookup_cert($pconfig['csrtosign']);
				$ca = & lookup_ca($pconfig['catosignwith']);

				// Read the CSR from $config, or if a new one, from the textarea
				if ($pconfig['csrtosign'] === "new") {
					$csr = $pconfig['csrpaste'];
				} else {
					$csr = base64_decode($csrid['csr']);
				}
				if (count($altnames)) {
					foreach ($altnames as $altname) {
						$altnames_tmp[] = "{$altname['type']}:" . cert_escape_x509_chars($altname['value']);
					}
					$altname_str = implode(",", $altnames_tmp);
				}

				$n509 = csr_sign($csr, $ca, $pconfig['csrsign_lifetime'], $pconfig['type'], $altname_str, $pconfig['csrsign_digest_alg']);

				if ($n509) {
					// Gather the details required to save the new cert
					$newcert = array();
					$newcert['refid'] = uniqid();
					$newcert['caref'] = $pconfig['catosignwith'];
					$newcert['descr'] = $pconfig['descr'];
					$newcert['type'] = $pconfig['type'];
					$newcert['crt'] = base64_encode($n509);

					if ($pconfig['csrtosign'] === "new") {
						$newcert['prv'] = base64_encode($pconfig['keypaste']);
					} else {
						$newcert['prv'] = $csrid['prv'];
					}

					// Add it to the config file
					$config['cert'][] = $newcert;
				}

			} else {
				$cert = array();
				$cert['refid'] = uniqid();
				if (isset($id) && $a_cert[$id]) {
					$cert = $a_cert[$id];
				}

				$cert['descr'] = $pconfig['descr'];

				$old_err_level = error_reporting(0); /* otherwise openssl_ functions throw warnings directly to a page screwing menu tab */

				if ($pconfig['method'] == "import") {
					cert_import($cert, $pconfig['cert'], $pconfig['key']);
				}

				if ($pconfig['method'] == "internal") {
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

					$altnames_tmp = array();
					$cn_altname = cert_add_altname_type($pconfig['dn_commonname']);
					if (!empty($cn_altname)) {
						$altnames_tmp[] = $cn_altname;
					}
					if (count($altnames)) {
						foreach ($altnames as $altname) {
							// The CN is added as a SAN automatically, do not add it again.
							if ($altname['value'] != $pconfig['dn_commonname']) {
								$altnames_tmp[] = "{$altname['type']}:" . cert_escape_x509_chars($altname['value']);
							}
						}
					}
					if (!empty($altnames_tmp)) {
						$dn['subjectAltName'] = implode(",", $altnames_tmp);
					}

					if (!cert_create($cert, $pconfig['caref'], $pconfig['keylen'], $pconfig['lifetime'], $dn, $pconfig['type'], $pconfig['digest_alg'])) {
						$input_errors = array();
						while ($ssl_err = openssl_error_string()) {
							if (strpos($ssl_err, 'NCONF_get_string:no value') === false) {
								array_push($input_errors, "openssl library returns: " . $ssl_err);
							}
						}
					}
				}

				if ($pconfig['method'] == "external") {
					$dn = array(
						'countryName' => $pconfig['csr_dn_country'],
						'stateOrProvinceName' => cert_escape_x509_chars($pconfig['csr_dn_state']),
						'localityName' => cert_escape_x509_chars($pconfig['csr_dn_city']),
						'organizationName' => cert_escape_x509_chars($pconfig['csr_dn_organization']),
						'emailAddress' => cert_escape_x509_chars($pconfig['csr_dn_email']),
						'commonName' => cert_escape_x509_chars($pconfig['csr_dn_commonname']));
					if (!empty($pconfig['csr_dn_organizationalunit'])) {
						$dn['organizationalUnitName'] = cert_escape_x509_chars($pconfig['csr_dn_organizationalunit']);
					}

					$altnames_tmp = array();
					$cn_altname = cert_add_altname_type($pconfig['csr_dn_commonname']);
					if (!empty($cn_altname)) {
						$altnames_tmp[] = $cn_altname;
					}
					if (count($altnames)) {
						foreach ($altnames as $altname) {
							// The CN is added as a SAN automatically, do not add it again.
							if ($altname['value'] != $pconfig['csr_dn_commonname']) {
								$altnames_tmp[] = "{$altname['type']}:" . cert_escape_x509_chars($altname['value']);
							}
						}
					}
					if (!empty($altnames_tmp)) {
						$dn['subjectAltName'] = implode(",", $altnames_tmp);
					}

					if (!csr_generate($cert, $pconfig['csr_keylen'], $dn, $pconfig['type'], $pconfig['csr_digest_alg'])) {
						$input_errors = array();
						while ($ssl_err = openssl_error_string()) {
							if (strpos($ssl_err, 'NCONF_get_string:no value') === false) {
								array_push($input_errors, "openssl library returns: " . $ssl_err);
							}
						}
					}
				}

				error_reporting($old_err_level);

				if (isset($id) && $a_cert[$id]) {
					$a_cert[$id] = $cert;
				} else {
					$a_cert[] = $cert;
				}

				if (isset($a_user) && isset($userid)) {
					$a_user[$userid]['cert'][] = $cert['refid'];
				}
			}

			if (!$input_errors) {
				write_config();
			}

			if ($userid && !$input_errors) {
				post_redirect("system_usermanager.php", array('act' => 'edit', 'userid' => $userid));
				exit;
			}
		}
	}

	if ($_POST['save'] == gettext("Update")) {
		unset($input_errors);
		$pconfig = $_POST;

		/* input validation */
		$reqdfields = explode(" ", "descr cert");
		$reqdfieldsn = array(
			gettext("기술적인 이름"),
			gettext("최종 인증서 데이터"));

		do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

		if (preg_match("/[\?\>\<\&\/\\\"\']/", $_POST['descr'])) {
			array_push($input_errors, "The field 'Descriptive Name' contains invalid characters.");
		}

//		old way
		/* make sure this csr and certificate subjects match */
//		$subj_csr = csr_get_subject($pconfig['csr'], false);
//		$subj_cert = cert_get_subject($pconfig['cert'], false);
//
//		if (!isset($_POST['ignoresubjectmismatch']) && !($_POST['ignoresubjectmismatch'] == "yes")) {
//			if (strcmp($subj_csr, $subj_cert)) {
//				$input_errors[] = sprintf(gettext("The certificate subject '%s' does not match the signing request subject."), $subj_cert);
//				$subject_mismatch = true;
//			}
//		}
		$mod_csr = cert_get_publickey($pconfig['csr'], false, 'csr');
		$mod_cert = cert_get_publickey($pconfig['cert'], false);

		if (strcmp($mod_csr, $mod_cert)) {
			// simply: if the moduli don't match, then the private key and public key won't match
			$input_errors[] = sprintf(gettext("인증서 공개 키가 서명 요청 공개 키와 일치하지 않습니다."), $subj_cert);
			$subject_mismatch = true;
		}

		/* save modifications */
		if (!$input_errors) {

			$cert = $a_cert[$id];

			$cert['descr'] = $pconfig['descr'];

			csr_complete($cert, $pconfig['cert']);

			$a_cert[$id] = $cert;

			write_config();

			pfSenseHeader("system_certmanager.php");
		}
	}
}

$pgtitle = array(gettext("System"), gettext("Certificate Manager"), gettext("Certificates"));
$pglinks = array("", "system_camanager.php", "system_certmanager.php");

if (($act == "new" || ($_POST['save'] == gettext("저장") && $input_errors)) || ($act == "csr" || ($_POST['save'] == gettext("업데이트") && $input_errors))) {
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

$tab_array = array();
$tab_array[] = array(gettext("CAs"), false, "system_camanager.php");
$tab_array[] = array(gettext("Certificates"), true, "system_certmanager.php");
$tab_array[] = array(gettext("Certificate Revocation"), false, "system_crlmanager.php");
display_top_tabs($tab_array);

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

if ($act == "new" || (($_POST['save'] == gettext("저장")) && $input_errors)) {
	$form = new Form();
	$form->setAction('system_certmanager.php?act=edit');

	if (isset($userid) && $a_user) {
		$form->addGlobal(new Form_Input(
			'userid',
			null,
			'hidden',
			$userid
		));
	}

	if (isset($id) && $a_cert[$id]) {
		$form->addGlobal(new Form_Input(
			'id',
			null,
			'hidden',
			$id
		));
	}

	$section = new Form_Section('새 인증서 추가/서명');

	if (!isset($id)) {
		$section->addInput(new Form_Select(
			'method',
			'*Method',
			$pconfig['method'],
			$cert_methods
		))->toggles();
	}

	$section->addInput(new Form_Input(
		'descr',
		'*Descriptive name',
		'text',
		($a_user && empty($pconfig['descr'])) ? $a_user[$userid]['name'] : $pconfig['descr']
	))->addClass('toggle-existing');

	$form->add($section);

	// Return an array containing the IDs od all CAs
	function list_cas() {
		global $a_ca;
		$allCas = array();

		foreach ($a_ca as $ca) {
			if ($ca['prv']) {
				$allCas[$ca['refid']] = $ca['descr'];
			}
		}

		return $allCas;
	}

	// Return an array containing the IDs od all CSRs
	function list_csrs() {
		global $config;
		$allCsrs = array();

		foreach ($config['cert'] as $cert) {
			if ($cert['csr']) {
				$allCsrs[$cert['refid']] = $cert['descr'];
			}
		}

		return ['new' => gettext('새 CSR (아래에 붙여 넣기)')] + $allCsrs;
	}

	$section = new Form_Section('CSR 서명');
	$section->addClass('toggle-sign collapse');

	$section->AddInput(new Form_Select(
		'catosignwith',
		'*CA to sign with',
		$pconfig['catosignwith'],
		list_cas()
	));

	$section->AddInput(new Form_Select(
		'csrtosign',
		'*CSR to sign',
		isset($pconfig['csrtosign']) ? $pconfig['csrtosign'] : 'new',
		list_csrs()
	));

	$section->addInput(new Form_Textarea(
		'csrpaste',
		'CSR data',
		$pconfig['csrpaste']
	))->setHelp('Paste a Certificate Signing Request in X.509 PEM format here.');

	$section->addInput(new Form_Textarea(
		'keypaste',
		'Key data',
		$pconfig['keypaste']
	))->setHelp('Optionally paste a private key here. The key will be associated with the newly signed certificate in pfSense');

	$section->addInput(new Form_Input(
		'csrsign_lifetime',
		'*Certificate Lifetime (days)',
		'number',
		$pconfig['csrsign_lifetime'] ? $pconfig['csrsign_lifetime']:'3650'
	));
	$section->addInput(new Form_Select(
		'csrsign_digest_alg',
		'*Digest Algorithm',
		$pconfig['csrsign_digest_alg'],
		array_combine($openssl_digest_algs, $openssl_digest_algs)
	))->setHelp('NOTE: It is recommended to use an algorithm stronger than '.
		'SHA1 when possible');

	$form->add($section);

	$section = new Form_Section('인증서 가져오기');
	$section->addClass('toggle-import collapse');

	$section->addInput(new Form_Textarea(
		'cert',
		'*Certificate data',
		$pconfig['cert']
	))->setHelp('Paste a certificate in X.509 PEM format here.');

	$section->addInput(new Form_Textarea(
		'key',
		'*Private key data',
		$pconfig['key']
	))->setHelp('Paste a private key in X.509 PEM format here.');

	$form->add($section);
	$section = new Form_Section('내부 인증서');
	$section->addClass('toggle-internal collapse');

	if (!$internal_ca_count) {
		$section->addInput(new Form_StaticText(
			'*Certificate authority',
			gettext('내부 인증 기관이 정의되지 않았습니다. ') .
			gettext('내부 인증서를 만들려면 내부 CA를 정의하십시오. ') .
			sprintf(gettext('내부 CA를 %1$s만듭니다%2$s.'), '<a href="system_camanager.php?act=new&amp;method=internal"> ', '</a>')
		));
	} else {
		$allCas = array();
		foreach ($a_ca as $ca) {
			if (!$ca['prv']) {
				continue;
			}

			$allCas[ $ca['refid'] ] = $ca['descr'];
		}

		$section->addInput(new Form_Select(
			'caref',
			'*Certificate authority',
			$pconfig['caref'],
			$allCas
		));
	}

	$section->addInput(new Form_Select(
		'keylen',
		'*Key length',
		$pconfig['keylen'],
		array_combine($cert_keylens, $cert_keylens)
	));

	$section->addInput(new Form_Select(
		'digest_alg',
		'*Digest Algorithm',
		$pconfig['digest_alg'],
		array_combine($openssl_digest_algs, $openssl_digest_algs)
	))->setHelp('NOTE: It is recommended to use an algorithm stronger than '.
		'SHA1 when possible.');

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
		'text',
		$pconfig['dn_email'],
		['placeholder' => 'e.g. admin@mycompany.com']
	));

	$section->addInput(new Form_Input(
		'dn_commonname',
		'*Common Name',
		'text',
		$pconfig['dn_commonname'],
		['placeholder' => 'e.g. www.example.com']
	));

	$form->add($section);
	$section = new Form_Section('External Signing Request');
	$section->addClass('toggle-external collapse');

	$section->addInput(new Form_Select(
		'csr_keylen',
		'*Key length',
		$pconfig['csr_keylen'],
		array_combine($cert_keylens, $cert_keylens)
	));

	$section->addInput(new Form_Select(
		'csr_digest_alg',
		'*Digest Algorithm',
		$pconfig['csr_digest_alg'],
		array_combine($openssl_digest_algs, $openssl_digest_algs)
	))->setHelp('NOTE: It is recommended to use an algorithm stronger than '.
		'SHA1 when possible');

	$section->addInput(new Form_Select(
		'csr_dn_country',
		'*Country Code',
		$pconfig['csr_dn_country'],
		$dn_cc
	));

	$section->addInput(new Form_Input(
		'csr_dn_state',
		'*State or Province',
		'text',
		$pconfig['csr_dn_state'],
		['placeholder' => 'e.g. Texas']
	));

	$section->addInput(new Form_Input(
		'csr_dn_city',
		'*City',
		'text',
		$pconfig['csr_dn_city'],
		['placeholder' => 'e.g. Austin']
	));

	$section->addInput(new Form_Input(
		'csr_dn_organization',
		'*Organization',
		'text',
		$pconfig['csr_dn_organization'],
		['placeholder' => 'e.g. My Company Inc']
	));

	$section->addInput(new Form_Input(
		'csr_dn_organizationalunit',
		'Organizational Unit',
		'text',
		$pconfig['csr_dn_organizationalunit'],
		['placeholder' => 'e.g. My Department Name (optional)']
	));

	$section->addInput(new Form_Input(
		'csr_dn_email',
		'*Email Address',
		'text',
		$pconfig['csr_dn_email'],
		['placeholder' => 'e.g. admin@mycompany.com']
	));

	$section->addInput(new Form_Input(
		'csr_dn_commonname',
		'*Common Name',
		'text',
		$pconfig['csr_dn_commonname'],
		['placeholder' => 'e.g. internal-ca']
	));

	$form->add($section);
	$section = new Form_Section('Choose an Existing Certificate');
	$section->addClass('toggle-existing collapse');

	$existCerts = array();

	foreach ($config['cert'] as $cert)	{
		if (is_array($config['system']['user'][$userid]['cert'])) { // Could be MIA!
			if (isset($userid) && in_array($cert['refid'], $config['system']['user'][$userid]['cert'])) {
				continue;
			}
		}

		$ca = lookup_ca($cert['caref']);
		if ($ca) {
			$cert['descr'] .= " (CA: {$ca['descr']})";
		}

		if (cert_in_use($cert['refid'])) {
			$cert['descr'] .= " (In Use)";
		}
		if (is_cert_revoked($cert)) {
			$cert['descr'] .= " (Revoked)";
		}

		$existCerts[ $cert['refid'] ] = $cert['descr'];
	}

	$section->addInput(new Form_Select(
		'certref',
		'*Existing Certificates',
		$pconfig['certref'],
		$existCerts
	));

	$form->add($section);

	$section = new Form_Section('인증서 속성');
	$section->addClass('toggle-external toggle-internal toggle-sign collapse');

	$section->addInput(new Form_StaticText(
		gettext('속성 노트'),
		'<span class="help-block">'.
		gettext('인증서 또는 요청이 만들어 지거나 서명될 때 다음 ' .
		'특성이 인증서와 요청에 추가됩니다. 이러한 ' .
		'속성은 선택한 모드에 따라 다르게 동작합니다.') .
		'<br/><br/>' .
		'<span class="toggle-internal collapse">' . gettext('내부 인증서의 경우 이러한 특성은 그림과 같이 인증서에 직접 추가됩니다.') . '</span>' .
		'<span class="toggle-external collapse">' .
		gettext('인증서 서명 요청의 경우 이러한 특성은 요청에 추가되지만 요청을 서명 한 CA는이를 무시하거나 변경할 수 있습니다. ') .
		'<br/><br/>' .
		gettext('이 CSR에서이 방화벽의 인증서 관리자를 사용하여 서명하는 경우 이월 할 수 없으므로 서명 할 때 속성을 설정하십시오.') . '</span>' .
		'<span class="toggle-sign collapse">' . gettext('인증서 요청에 서명 할 때 요청의 기존 특성을 복사 할 수 없습니다. 아래 속성은 결과 인증서에 적용됩니다.') . '</span>' .
		'</span>'
	));

	$section->addInput(new Form_Select(
		'type',
		'*Certificate Type',
		$pconfig['type'],
		$cert_types
	))->setHelp('Add type-specific usage attributes to the signed certificate.' .
		' Used for placing usage restrictions on, or granting abilities to, ' .
		'the signed certificate.');

	if (empty($pconfig['altnames']['item'])) {
		$pconfig['altnames']['item'] = array(
			array('type' => null, 'value' => null)
		);
	}

	$counter = 0;
	$numrows = count($pconfig['altnames']['item']) - 1;

	foreach ($pconfig['altnames']['item'] as $item) {

		$group = new Form_Group($counter == 0 ? 'Alternative Names':'');

		$group->add(new Form_Select(
			'altname_type' . $counter,
			'Type',
			$item['type'],
			$cert_altname_types
		))->setHelp(($counter == $numrows) ? 'Type':null);

		$group->add(new Form_Input(
			'altname_value' . $counter,
			null,
			'text',
			$item['value']
		))->setHelp(($counter == $numrows) ? 'Value':null);

		$group->add(new Form_Button(
			'deleterow' . $counter,
			'Delete',
			null,
			'fa-trash'
		))->addClass('btn-warning');

		$group->addClass('repeatable');

		$group->setHelp('Enter additional identifiers for the certificate ' .
			'in this list. The Common Name field is automatically ' .
			'added to the certificate as an Alternative Name. ' .
			'The signing CA may ignore or change these values.');

		$section->add($group);

		$counter++;
	}

	$section->addInput(new Form_Button(
		'addrow',
		'Add',
		null,
		'fa-plus'
	))->addClass('btn-success');

	$form->add($section);


	print $form;

} else if ($act == "csr" || (($_POST['save'] == gettext("업데이트")) && $input_errors)) {
	$form = new Form(false);
	$form->setAction('system_certmanager.php?act=csr');

	$section = new Form_Section("Complete Signing Request for " . $pconfig['descr']);

	$section->addInput(new Form_Input(
		'descr',
		'*Descriptive name',
		'text',
		$pconfig['descr']
	));

	$section->addInput(new Form_Textarea(
		'csr',
		'Signing request data',
		$pconfig['csr']
	))->setReadonly()
	  ->setWidth(7)
	  ->setHelp('Copy the certificate signing data from here and forward it to a certificate authority for signing.');

	$section->addInput(new Form_Textarea(
		'cert',
		'*Final certificate data',
		$pconfig['cert']
	))->setWidth(7)
	  ->setHelp('Paste the certificate received from the certificate authority here.');

	 if (isset($id) && $a_cert[$id]) {
		 $section->addInput(new Form_Input(
			'id',
			null,
			'hidden',
			$id
		 ));

		 $section->addInput(new Form_Input(
			'act',
			null,
			'hidden',
			'csr'
		 ));
	 }

	$form->add($section);

	$form->addGlobal(new Form_Button(
		'save',
		'Update',
		null,
		'fa-save'
	))->addClass('btn-primary');

	print($form);
} else {
?>
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext('인증서')?></h2></div>
	<div class="panel-body">
		<div class="table-responsive">
		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th><?=gettext("이름")?></th>
					<th><?=gettext("Issuer")?></th>
					<th><?=gettext("Distinguished Name")?></th>
					<th><?=gettext("In Use")?></th>

					<th class="col-sm-2"><?=gettext("Actions")?></th>
				</tr>
			</thead>
			<tbody>
<?php

$pluginparams = array();
$pluginparams['type'] = 'certificates';
$pluginparams['event'] = 'used_certificates';
$certificates_used_by_packages = pkg_call_plugins('plugin_certificates', $pluginparams);
$i = 0;
foreach ($a_cert as $i => $cert):
	$name = htmlspecialchars($cert['descr']);
	$sans = array();
	if ($cert['crt']) {
		$subj = cert_get_subject($cert['crt']);
		$issuer = cert_get_issuer($cert['crt']);
		$purpose = cert_get_purpose($cert['crt']);
		$sans = cert_get_sans($cert['crt']);
		list($startdate, $enddate) = cert_get_dates($cert['crt']);

		if ($subj == $issuer) {
			$caname = '<i>'. gettext("자체 서명") .'</i>';
		} else {
			$caname = '<i>'. gettext("외부").'</i>';
		}

		$subj = htmlspecialchars(cert_escape_x509_chars($subj, true));
	} else {
		$subj = "";
		$issuer = "";
		$purpose = "";
		$startdate = "";
		$enddate = "";
		$caname = "<em>" . gettext("개인키 전용") . "</em>";
	}

	if ($cert['csr']) {
		$subj = htmlspecialchars(cert_escape_x509_chars(csr_get_subject($cert['csr']), true));
		$sans = cert_get_sans($cert['crt']);
		$caname = "<em>" . gettext("external - signature pending") . "</em>";
	}

	$ca = lookup_ca($cert['caref']);
	if ($ca) {
		$caname = $ca['descr'];
	}
?>
				<tr>
					<td>
						<?=$name?><br />
						<?php if ($cert['type']): ?>
							<i><?=$cert_types[$cert['type']]?></i><br />
						<?php endif?>
						<?php if (is_array($purpose)): ?>
							CA: <b><?=$purpose['ca']?></b><br/>
							<?=gettext("서버")?>: <b><?=$purpose['server']?></b><br/>
						<?php endif?>
					</td>
					<td><?=$caname?></td>
					<td>
						<?=$subj?>
						<?php
						$certextinfo = "";
						$certserial = cert_get_serial($cert['crt']);
						if (!empty($certserial)) {
							$certextinfo .= '<b>' . gettext("시리얼: ") . '</b> ';
							$certextinfo .= htmlspecialchars(cert_escape_x509_chars($certserial, true));
							$certextinfo .= '<br/>';
						}
						$certsig = cert_get_sigtype($cert['crt']);
						if (is_array($certsig) && !empty($certsig) && !empty($certsig['shortname'])) {
							$certextinfo .= '<b>' . gettext("서명 요약: ") . '</b> ';
							$certextinfo .= htmlspecialchars(cert_escape_x509_chars($certsig['shortname'], true));
							$certextinfo .= '<br/>';
						}
						if (is_array($sans) && !empty($sans)) {
							$certextinfo .= '<b>' . gettext("SAN: ") . '</b> ';
							$certextinfo .= htmlspecialchars(implode(', ', cert_escape_x509_chars($sans, true)));
							$certextinfo .= '<br/>';
						}
						if (is_array($purpose) && !empty($purpose['ku'])) {
							$certextinfo .= '<b>' . gettext("KU: ") . '</b> ';
							$certextinfo .= htmlspecialchars(implode(', ', $purpose['ku']));
							$certextinfo .= '<br/>';
						}
						if (is_array($purpose) && !empty($purpose['eku'])) {
							$certextinfo .= '<b>' . gettext("EKU: ") . '</b> ';
							$certextinfo .= htmlspecialchars(implode(', ', $purpose['eku']));
						}
						?>
						<?php if (!empty($certextinfo)): ?>
							<div class="infoblock">
							<? print_info_box($certextinfo, 'info', false); ?>
							</div>
						<?php endif?>

						<?php if (!empty($startdate) || !empty($enddate)): ?>
						<br />
						<small>
							<?=gettext("유효")?>: <b><?=$startdate ?></b><br /><?=gettext(" 까지 유효")?>: <b><?=$enddate ?></b>
						</small>
						<?php endif?>
					</td>
					<td>
						<?php if (is_cert_revoked($cert)): ?>
							<i><?=gettext("취소됨")?></i>
						<?php endif?>
						<?php if (is_webgui_cert($cert['refid'])): ?>
							<?=gettext("웹 구성자")?>
						<?php endif?>
						<?php if (is_user_cert($cert['refid'])): ?>
							<?=gettext("사용자 인증서")?>
						<?php endif?>
						<?php if (is_openvpn_server_cert($cert['refid'])): ?>
							<?=gettext("OpenVPN 서버")?>
						<?php endif?>
						<?php if (is_openvpn_client_cert($cert['refid'])): ?>
							<?=gettext("OpenVPN 클라이언트")?>
						<?php endif?>
						<?php if (is_ipsec_cert($cert['refid'])): ?>
							<?=gettext("IPsec 터널")?>
						<?php endif?>
						<?php if (is_captiveportal_cert($cert['refid'])): ?>
							<?=gettext("전속 포털")?>
						<?php endif?>
						<?php echo cert_usedby_description($cert['refid'], $certificates_used_by_packages); ?>
					</td>
					<td>
						<?php if (!$cert['csr']): ?>
							<a href="system_certmanager.php?act=exp&amp;id=<?=$i?>" class="fa fa-certificate" title="<?=gettext("인증서 내보내기")?>"></a>
							<?php if ($cert['prv']): ?>
								<a href="system_certmanager.php?act=key&amp;id=<?=$i?>" class="fa fa-key" title="<?=gettext("인증서 키")?>"></a>
							<?php endif?>
							<a href="system_certmanager.php?act=p12&amp;id=<?=$i?>" class="fa fa-archive" title="<?=gettext("인증서 P12")?>"></a>
						<?php else: ?>
							<a href="system_certmanager.php?act=csr&amp;id=<?=$i?>" class="fa fa-pencil" title="<?=gettext("업데이트 CSR")?>"></a>
							<a href="system_certmanager.php?act=req&amp;id=<?=$i?>" class="fa fa-sign-in" title="<?=gettext("익스포트 요청")?>"></a>
							<a href="system_certmanager.php?act=key&amp;id=<?=$i?>" class="fa fa-key" title="<?=gettext("익스포트 키")?>"></a>
						<?php endif?>
						<?php if (!cert_in_use($cert['refid'])): ?>
							<a href="system_certmanager.php?act=del&amp;id=<?=$i?>" class="fa fa-trash" title="<?=gettext("인증서 삭제")?>" usepost></a>
						<?php endif?>
					</td>
				</tr>
<?php
	$i++;
	endforeach; ?>
			</tbody>
		</table>
		</div>
	</div>
</div>

<nav class="action-buttons">
	<a href="?act=new" class="btn btn-success btn-sm">
		<i class="fa fa-plus icon-embed-btn"></i>
		<?=gettext("추가/")?>
	</a>
</nav>
<?php
	include("foot.inc");
	exit;
}


?>
<script type="text/javascript">
//<![CDATA[
events.push(function() {

<?php if ($internal_ca_count): ?>
	function internalca_change() {

		caref = $('#caref').val();

		switch (caref) {
<?php
			foreach ($a_ca as $ca):
				if (!$ca['prv']) {
					continue;
				}

				$subject = cert_get_subject_array($ca['crt']);
?>
				case "<?=$ca['refid'];?>":
					$('#dn_country').val(<?=json_encode(cert_escape_x509_chars($subject[0]['v'], true));?>);
					$('#dn_state').val(<?=json_encode(cert_escape_x509_chars($subject[1]['v'], true));?>);
					$('#dn_city').val(<?=json_encode(cert_escape_x509_chars($subject[2]['v'], true));?>);
					$('#dn_organization').val(<?=json_encode(cert_escape_x509_chars($subject[3]['v'], true));?>);
					$('#dn_email').val(<?=json_encode(cert_escape_x509_chars($subject[4]['v'], true));?>);
					$('#dn_organizationalunit').val(<?=json_encode(cert_escape_x509_chars($subject[6]['v'], true));?>);
					break;
<?php
			endforeach;
?>
		}
	}

	function set_csr_ro() {
		var newcsr = ($('#csrtosign').val() == "new");

		$('#csrpaste').attr('readonly', !newcsr);
		$('#keypaste').attr('readonly', !newcsr);
		setRequired('csrpaste', newcsr);
	}

	// ---------- Click checkbox handlers ---------------------------------------------------------

	$('#caref').on('change', function() {
		internalca_change();
	});

	$('#csrtosign').change(function () {
		set_csr_ro();
	});

	// ---------- On initial page load ------------------------------------------------------------

	internalca_change();
	set_csr_ro();

	// Suppress "Delete row" button if there are fewer than two rows
	checkLastRow();

<?php endif; ?>


});
//]]>
</script>
<?php
include('foot.inc');

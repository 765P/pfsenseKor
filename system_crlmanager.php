<?php
/*
 * system_crlmanager.php
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
##|*IDENT=page-system-crlmanager
##|*NAME=System: CRL Manager
##|*DESCR=Allow access to the 'System: CRL Manager' page.
##|*MATCH=system_crlmanager.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("certs.inc");
require_once("openvpn.inc");
require_once("pfsense-utils.inc");
require_once("vpn.inc");

global $openssl_crl_status;

$crl_methods = array(
	"internal" => gettext("내부 인증서 해지 목록 생성"),
	"existing" => gettext("기존 인증서 해지 목록 가져오기"));

if (isset($_REQUEST['id']) && ctype_alnum($_REQUEST['id'])) {
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

foreach ($a_crl as $cid => $acrl) {
	if (!isset($acrl['refid'])) {
		unset ($a_crl[$cid]);
	}
}

$act = $_REQUEST['act'];


if (!empty($id)) {
	$thiscrl =& lookup_crl($id);
}

// If we were given an invalid crlref in the id, no sense in continuing as it would only cause errors.
if (!$thiscrl && (($act != "") && ($act != "new"))) {
	pfSenseHeader("system_crlmanager.php");
	$act="";
	$savemsg = gettext("CRL참조가 잘못되었습니다.");
	$class = "danger";
}

if ($_POST['act'] == "del") {
	$name = htmlspecialchars($thiscrl['descr']);
	if (crl_in_use($id)) {
		$savemsg = sprintf(gettext("인증서 해지 목록%s이(가) 사용중이므로 삭제할 수 없습니다."), $name);
		$class = "danger";
	} else {
		foreach ($a_crl as $cid => $acrl) {
			if ($acrl['refid'] == $thiscrl['refid']) {
				unset($a_crl[$cid]);
			}
		}
		write_config("Deleted CRL {$name}.");
		$savemsg = sprintf(gettext("인증서 해지 목록%s을(를) 삭제했습니다."), $name);
		$class = "success";
	}
}

if ($act == "new") {
	$pconfig['method'] = $_REQUEST['method'];
	$pconfig['caref'] = $_REQUEST['caref'];
	$pconfig['lifetime'] = "9999";
	$pconfig['serial'] = "0";
}

if ($act == "exp") {
	crl_update($thiscrl);
	$exp_name = urlencode("{$thiscrl['descr']}.crl");
	$exp_data = base64_decode($thiscrl['text']);
	$exp_size = strlen($exp_data);

	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename={$exp_name}");
	header("Content-Length: $exp_size");
	echo $exp_data;
	exit;
}

if ($act == "addcert") {

	unset($input_errors);
	$pconfig = $_REQUEST;

	if (!$pconfig['crlref'] || !$pconfig['certref']) {
		pfSenseHeader("system_crlmanager.php");
		exit;
	}

	// certref, crlref
	$crl =& lookup_crl($pconfig['crlref']);
	$cert = lookup_cert($pconfig['certref']);

	if (!$crl['caref'] || !$cert['caref']) {
		$input_errors[] = gettext("인증서와 CRL을 모두 지정해야 합니다.");
	}

	if ($crl['caref'] != $cert['caref']) {
		$input_errors[] = gettext("CA가 인증서와 CRL이 일치하지 않습니다. 취소할 수 없습니다.");
	}
	if (!is_crl_internal($crl)) {
		$input_errors[] = gettext("가져온 CRL의 인증서를 해지할 수 없습니다.");
	}

	if (!$input_errors) {
		$reason = (empty($pconfig['crlreason'])) ? OCSP_REVOKED_STATUS_UNSPECIFIED : $pconfig['crlreason'];
		cert_revoke($cert, $crl, $reason);
		// refresh IPsec and OpenVPN CRLs
		openvpn_refresh_crls();
		vpn_ipsec_configure();
		write_config("Revoked cert {$cert['descr']} in CRL {$crl['descr']}.");
		pfSenseHeader("system_crlmanager.php");
		exit;
	}
}

if ($act == "delcert") {
	if (!is_array($thiscrl['cert'])) {
		pfSenseHeader("system_crlmanager.php");
		exit;
	}
	$found = false;
	foreach ($thiscrl['cert'] as $acert) {
		if ($acert['refid'] == $_REQUEST['certref']) {
			$found = true;
			$thiscert = $acert;
		}
	}
	if (!$found) {
		pfSenseHeader("system_crlmanager.php");
		exit;
	}
	$certname = htmlspecialchars($thiscert['descr']);
	$crlname = htmlspecialchars($thiscrl['descr']);
	if (cert_unrevoke($thiscert, $thiscrl)) {
		$savemsg = sprintf(gettext('CRL %2$s에서 인증서 %1$s을(를) 삭제했습니다.'), $certname, $crlname);
		$class = "success";
		// refresh IPsec and OpenVPN CRLs
		openvpn_refresh_crls();
		vpn_ipsec_configure();
		write_config($savemsg);
	} else {
		$savemsg = sprintf(gettext('CRL %2$s에서 인증서 %1$s을(를) 삭제하지못했습니다.'), $certname, $crlname);
		$class = "danger";
	}
	$act="edit";
}

if ($_POST['save']) {
	$input_errors = array();
	$pconfig = $_POST;

	/* input validation */
	if (($pconfig['method'] == "existing") || ($act == "editimported")) {
		$reqdfields = explode(" ", "descr crltext");
		$reqdfieldsn = array(
			gettext("기술적인 이름"),
			gettext("인증서 해지 목록 데이터"));
	}
	if ($pconfig['method'] == "internal") {
		$reqdfields = explode(" ", "descr caref");
		$reqdfieldsn = array(
			gettext("기술적인 이름"),
			gettext("인증 기관"));
	}

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

	if (preg_match("/[\?\>\<\&\/\\\"\']/", $pconfig['descr'])) {
		array_push($input_errors, "The field 'Descriptive Name' contains invalid characters.");
	}

	/* save modifications */
	if (!$input_errors) {
		$result = false;

		if ($thiscrl) {
			$crl =& $thiscrl;
		} else {
			$crl = array();
			$crl['refid'] = uniqid();
		}

		$crl['descr'] = $pconfig['descr'];
		if ($act != "editimported") {
			$crl['caref'] = $pconfig['caref'];
			$crl['method'] = $pconfig['method'];
		}

		if (($pconfig['method'] == "existing") || ($act == "editimported")) {
			$crl['text'] = base64_encode($pconfig['crltext']);
		}

		if ($pconfig['method'] == "internal") {
			$crl['serial'] = empty($pconfig['serial']) ? 9999 : $pconfig['serial'];
			$crl['lifetime'] = empty($pconfig['lifetime']) ? 9999 : $pconfig['lifetime'];
			$crl['cert'] = array();
		}

		if (!$thiscrl) {
			$a_crl[] = $crl;
		}

		write_config("CRL 저장 {$crl['descr']}");
		// refresh IPsec and OpenVPN CRLs
		openvpn_refresh_crls();
		vpn_ipsec_configure();
		pfSenseHeader("system_crlmanager.php");
	}
}

$pgtitle = array(gettext("시스템"), gettext("인증서 매니저"), gettext("인증서 폐지"));
$pglinks = array("", "system_camanager.php", "system_crlmanager.php");

if ($act == "new" || $act == gettext("저장") || $input_errors || $act == "edit") {
	$pgtitle[] = gettext('편집');
	$pglinks[] = "@self";
}
include("head.inc");
?>

<script type="text/javascript">
//<![CDATA[

function method_change() {

	method = document.iform.method.value;

	switch (method) {
		case "internal":
			document.getElementById("기존의").style.display="none";
			document.getElementById("내부").style.display="";
			break;
		case "existing":
			document.getElementById("기존의").style.display="";
			document.getElementById("내부").style.display="none";
			break;
	}
}

//]]>
</script>

<?php

function build_method_list() {
	global $_POST, $crl_methods;

	$list = array();

	foreach ($crl_methods as $method => $desc) {
		if (($_POST['importonly'] == "yes") && ($method != "existing")) {
			continue;
		}

		$list[$method] = $desc;
	}

	return($list);
}

function build_ca_list() {
	global $a_ca;

	$list = array();

	foreach ($a_ca as $ca) {
		$list[$ca['refid']] = $ca['descr'];
	}

	return($list);
}

function build_cacert_list() {
	global $ca_certs;

	$list = array();

	foreach ($ca_certs as $cert) {
		$list[$cert['refid']] = $cert['descr'];
	}

	return($list);
}

if ($input_errors) {
	print_input_errors($input_errors);
}

if ($savemsg) {
	print_info_box($savemsg, $class);
}

$tab_array = array();
$tab_array[] = array(gettext("CAs"), false, "system_camanager.php");
$tab_array[] = array(gettext("인증서"), false, "system_certmanager.php");
$tab_array[] = array(gettext("인증서 폐지"), true, "system_crlmanager.php");
display_top_tabs($tab_array);

if ($act == "new" || $act == gettext("저장") || $input_errors) {
	if (!isset($id)) {
		$form = new Form();

		$section = new Form_Section('새 해지 목록 만들기');

		$section->addInput(new Form_Select(
			'method',
			'*Method',
			$pconfig['method'],
			build_method_list()
		));

	}

	$section->addInput(new Form_Input(
		'descr',
		'*Descriptive name',
		'text',
		$pconfig['descr']
	));

	$section->addInput(new Form_Select(
		'caref',
		'*Certificate Authority',
		$pconfig['caref'],
		build_ca_list()
	));

	$form->add($section);

	$section = new Form_Section('Existing Certificate Revocation List');
	$section->addClass('existing');

	$section->addInput(new Form_Textarea(
		'crltext',
		'*CRL data',
		$pconfig['crltext']
		))->setHelp('Paste a Certificate Revocation List in X.509 CRL format here.');

	$form->add($section);

	$section = new Form_Section('Internal Certificate Revocation List');
	$section->addClass('internal');

	$section->addInput(new Form_Input(
		'lifetime',
		'Lifetime (Days)',
		'number',
		$pconfig['lifetime'],
		[max => '9999']
	));

	$section->addInput(new Form_Input(
		'serial',
		'Serial',
		'number',
		$pconfig['serial'],
		['min' => '0', 'max' => '9999']
	));

	$form->add($section);

	if (isset($id) && $thiscrl) {
		$section->addInput(new Form_Input(
			'id',
			null,
			'hidden',
			$id
		));
	}

	print($form);

} elseif ($act == "editimported") {

	$form = new Form();

	$section = new Form_Section('Edit Imported Certificate Revocation List');

	$section->addInput(new Form_Input(
		'descr',
		'*Descriptive name',
		'text',
		$pconfig['descr']
	));

	$section->addInput(new Form_Textarea(
		'crltext',
		'*CRL data',
		$pconfig['crltext']
	))->setHelp('Paste a Certificate Revocation List in X.509 CRL format here.');

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
		'editimported'
	));

	$form->add($section);

	print($form);

} elseif ($act == "edit") {
	$crl = $thiscrl;

	$form = new Form(false);
?>

	<div class="panel panel-default">
		<div class="panel-heading"><h2 class="panel-title"><?=gettext("현재 CRL에 대해 해지된 인증서") . ': ' . $crl['descr']?></h2></div>
		<div class="panel-body table-responsive">
<?php
	if (!is_array($crl['cert']) || (count($crl['cert']) == 0)) {
		print_info_box(gettext("이 CRL에 대한 인증서를 찾을 수 없습니다."), 'danger');
	} else {
?>
			<table class="table table-striped table-hover table-condensed">
				<thead>
					<tr>
						<th><?=gettext("인증서 이름")?></th>
						<th><?=gettext("폐지 사유")?></th>
						<th><?=gettext("다음 시간에 폐지됨")?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
<?php
		foreach ($crl['cert'] as $i => $cert):
			$name = htmlspecialchars($cert['descr']);
?>
					<tr>
						<td class="listlr">
							<?=$name; ?>
						</td>
						<td class="listlr">
							<?=$openssl_crl_status[$cert["reason"]]; ?>
						</td>
						<td class="listlr">
							<?=date("D M j G:i:s T Y", $cert["revoke_time"]); ?>
						</td>
						<td class="list">
							<a href="system_crlmanager.php?act=delcert&amp;id=<?=$crl['refid']; ?>&amp;certref=<?=$cert['refid']; ?>" usepost>
								<i class="fa fa-trash" title="<?=gettext("CRL에서 이 인증서 삭제")?>" alt="<?=gettext("CRL에서 이 인증서 삭제")?>"></i>
							</a>
						</td>
					</tr>
<?php
		endforeach;
?>
				</tbody>
			</table>
<?php
	}
?>
		</div>
	</div>
<?php

	$ca_certs = array();
	foreach ($a_cert as $cert) {
		if ($cert['caref'] == $crl['caref'] && !is_cert_revoked($cert, $id)) {
			$ca_certs[] = $cert;
		}
	}

	if (count($ca_certs) == 0) {
		print_info_box(gettext("이 CA에 대한 인증서를 찾을 수 없습니다."), 'danger');
	} else {
		$section = new Form_Section('Choose a Certificate to Revoke');
		$group = new Form_Group(null);

		$group->add(new Form_Select(
			'certref',
			null,
			$pconfig['certref'],
			build_cacert_list()
			))->setWidth(4)->setHelp('Certificate');

		$group->add(new Form_Select(
			'crlreason',
			null,
			-1,
			$openssl_crl_status
			))->setHelp('Reason');

		$group->add(new Form_Button(
			'submit',
			'Add',
			null,
			'fa-plus'
			))->addClass('btn-success btn-sm');

		$section->add($group);

		$section->addInput(new Form_Input(
			'id',
			null,
			'hidden',
			$crl['refid']
		));

		$section->addInput(new Form_Input(
			'act',
			null,
			'hidden',
			'addcert'
		));

		$section->addInput(new Form_Input(
			'crlref',
			null,
			'hidden',
			$crl['refid']
		));

		$form->add($section);
	}

	print($form);
} else {
?>

	<div class="panel panel-default">
		<div class="panel-heading"><h2 class="panel-title"><?=gettext("추가 인증서 해지 목록")?></h2></div>
		<div class="panel-body table-responsive">
			<table class="table table-striped table-hover table-condensed table-rowdblclickedit">
				<thead>
					<tr>
						<th><?=gettext("이름")?></th>
						<th><?=gettext("내부")?></th>
						<th><?=gettext("인증서")?></th>
						<th><?=gettext("사용중")?></th>
						<th><?=gettext("Actions")?></th>
					</tr>
				</thead>
				<tbody>
<?php
	$pluginparams = array();
	$pluginparams['type'] = 'certificates';
	$pluginparams['event'] = 'used_crl';
	$certificates_used_by_packages = pkg_call_plugins('plugin_certificates', $pluginparams);
	// Map CRLs to CAs in one pass
	$ca_crl_map = array();
	foreach ($a_crl as $crl) {
		$ca_crl_map[$crl['caref']][] = $crl['refid'];
	}

	$i = 0;
	foreach ($a_ca as $ca):
		$name = htmlspecialchars($ca['descr']);

		if ($ca['prv']) {
			$cainternal = "YES";
		} else {
			$cainternal = "NO";
		}
?>
					<tr>
						<td colspan="4">
							<?=$name?>
						</td>
						<td>
<?php
		if ($cainternal == "YES"):
?>
							<a href="system_crlmanager.php?act=new&amp;caref=<?=$ca['refid']; ?>" class="btn btn-xs btn-success">
								<i class="fa fa-plus icon-embed-btn"></i>
								<?=gettext("CRL추가 또는 가져오기")?>
							</a>
<?php
		else:
?>
							<a href="system_crlmanager.php?act=new&amp;caref=<?=$ca['refid']; ?>&amp;importonly=yes" class="btn btn-xs btn-success">
								<i class="fa fa-plus icon-embed-btn"></i>
								<?=gettext("CRL추가 또는 가져오기")?>
							</a>
<?php
		endif;
?>
						</td>
					</tr>
<?php
		if (is_array($ca_crl_map[$ca['refid']])):
			foreach ($ca_crl_map[$ca['refid']] as $crl):
				$tmpcrl = lookup_crl($crl);
				$internal = is_crl_internal($tmpcrl);
				$inuse = crl_in_use($tmpcrl['refid']);
?>
					<tr>
						<td><?=$tmpcrl['descr']; ?></td>
						<td><i class="fa fa-<?=($internal) ? "check" : "times"; ?>"></i></td>
						<td><?=($internal) ? count($tmpcrl['cert']) : "Unknown (imported)"; ?></td>
						<td><i class="fa fa-<?=($inuse) ? "check" : "times"; ?>"></i>
						<?php echo cert_usedby_description($tmpcrl['refid'], $certificates_used_by_packages); ?>
						</td>
						<td>
							<a href="system_crlmanager.php?act=exp&amp;id=<?=$tmpcrl['refid']?>" class="fa fa-download" title="<?=gettext("CRL 내보내기")?>" ></a>
<?php
				if ($internal): ?>
							<a href="system_crlmanager.php?act=edit&amp;id=<?=$tmpcrl['refid']?>" class="fa fa-pencil" title="<?=gettext("CRL 편집")?>"></a>
<?php
				else:
?>
							<a href="system_crlmanager.php?act=editimported&amp;id=<?=$tmpcrl['refid']?>" class="fa fa-pencil" title="<?=gettext("CRL 편집")?>"></a>
<?php			endif;
				if (!$inuse):
?>
							<a href="system_crlmanager.php?act=del&amp;id=<?=$tmpcrl['refid']?>" class="fa fa-trash" title="<?=gettext("CRL ")?>" usepost></a>
<?php
				endif;
?>
						</td>
					</tr>
<?php
				$i++;
				endforeach;
			endif;
			$i++;
		endforeach;
?>
				</tbody>
			</table>
		</div>
	</div>


<?php
}
?>

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

	// When the 'method" selector is changed, we show/hide certain sections
	$('#method').on('change', function() {
		hideClass('internal', ($('#method').val() == 'existing'));
		hideClass('existing', ($('#method').val() == 'internal'));
	});

	hideClass('internal', ($('#method').val() == 'existing'));
	hideClass('existing', ($('#method').val() == 'internal'));
});
//]]>
</script>

<?php include("foot.inc");


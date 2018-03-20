<?php
/*
 * services_unbound_acls.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2018 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2014 Warren Baker (warren@pfsense.org)
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
2018.03.15
한글화 번역 시작
*/

##|+PRIV
##|*IDENT=page-services-dnsresolver-acls
##|*NAME=Services: DNS Resolver: Access Lists
##|*DESCR=Allow access to the 'Services: DNS Resolver: Access Lists' page.
##|*MATCH=services_unbound_acls.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("pfsense-utils.inc");
require_once("unbound.inc");

if (!is_array($config['unbound']['acls'])) {
	$config['unbound']['acls'] = array();
}

$a_acls = &$config['unbound']['acls'];

$id = $_REQUEST['id'];

if (isset($_POST['aclid'])) {
	$id = $_POST['aclid'];
}

if (!empty($id) && !is_numeric($id)) {
	pfSenseHeader("services_unbound_acls.php");
	exit;
}

$act = $_REQUEST['act'];

if ($_POST['act'] == "del") {
	if (!$a_acls[$id]) {
		pfSenseHeader("services_unbound_acls.php");
		exit;
	}

	unset($a_acls[$id]);
	write_config(gettext("액세스 목록이 DNS 확인자에서 삭제되었습니다."));
	mark_subsystem_dirty('unbound');
}

if ($act == "new") {
	$id = unbound_get_next_id();
}

if ($act == "edit") {
	if (isset($id) && $a_acls[$id]) {
		$pconfig = $a_acls[$id];
		$networkacl = $a_acls[$id]['row'];
	}
}

if (!is_array($networkacl)) {
	$networkacl = array();
}

// Add a row to the networks table
if ($act == 'new') {
	$networkacl = array('0' => array('acl_network' => '', 'mask' => '', 'description' => ''));
}

if ($_POST['apply']) {
	$retval = 0;
	$retval |= services_unbound_configure();
	if ($retval == 0) {
		clear_subsystem_dirty('unbound');
	}
}
if ($_POST['save']) {
	unset($input_errors);
	$pconfig = $_POST;
	$deleting = false;

	// input validation - only allow 50 entries in a single ACL
	for ($x = 0; $x < 50; $x++) {
		if (isset($pconfig["acl_network{$x}"])) {
			$networkacl[$x] = array();
			$networkacl[$x]['acl_network'] = $pconfig["acl_network{$x}"];
			$networkacl[$x]['mask'] = $pconfig["mask{$x}"];
			$networkacl[$x]['description'] = $pconfig["description{$x}"];
			if (!is_ipaddr($networkacl[$x]['acl_network'])) {
				$input_errors[] = gettext("네트워크 아래의 각 행에 유효한 IP 주소를 입력해야합니다.");
			}

			if (is_ipaddr($networkacl[$x]['acl_network'])) {
				if (!is_subnet($networkacl[$x]['acl_network']."/".$networkacl[$x]['mask'])) {
					$input_errors[] = gettext("네트워크 아래의 각 IPv4 행에 유효한 IPv4 넷 마스크를 입력해야합니다.");
				}
			} else if (function_exists("is_ipaddrv6")) {
				if (!is_ipaddrv6($networkacl[$x]['acl_network'])) {
					$input_errors[] = gettext("{$networkacl[$x]['acl_network']}에 유효한 IPv6 주소를 입력해야합니다.");
				} else if (!is_subnetv6($networkacl[$x]['acl_network']."/".$networkacl[$x]['mask'])) {
					$input_errors[] = gettext("네트워크 아래의 각 IPv6 행에 유효한 IPv6 넷 마스크를 입력해야합니다.");
				}
			} else {
				$input_errors[] = gettext("네트워크 아래의 각 행에 유효한 IP 주소를 입력해야합니다.");
			}
		} else if (isset($networkacl[$x])) {
			unset($networkacl[$x]);
		}
	}

	if (!$input_errors) {
		if (strtolower($pconfig['save']) == gettext("저장")) {
			$acl_entry = array();
			$acl_entry['aclid'] = $pconfig['aclid'];
			$acl_entry['aclname'] = $pconfig['aclname'];
			$acl_entry['aclaction'] = $pconfig['aclaction'];
			$acl_entry['description'] = $pconfig['description'];
			$acl_entry['aclid'] = $pconfig['aclid'];
			$acl_entry['row'] = array();

			foreach ($networkacl as $acl) {
				$acl_entry['row'][] = $acl;
			}

			if (isset($id) && $a_acls[$id]) {
				$a_acls[$id] = $acl_entry;
			} else {
				$a_acls[] = $acl_entry;
			}

			mark_subsystem_dirty("unbound");
			write_config(gettext("DNS 확인자에 대해 구성된 액세스 목록"));

			pfSenseHeader("/services_unbound_acls.php");
			exit;
		}
	}
}

$actionHelp =
					sprintf(gettext('%1$sDeny:%2$s 아래 정의 된 넷 블록 내의 호스트에서 쿼리를 중지합니다.%3$s'), '<span class="text-success"><strong>', '</strong></span>', '<br />') .
					sprintf(gettext('%1$sRefuse:%2$s 아래에 정의 된 넷 블록 내의 호스트에서 쿼리를 중지하지만 DNS rcode REFUSED 오류 메시지를 클라이언트에 보냅니다.%3$s'), '<span class="text-success"><strong>', '</strong></span>', '<br />') .
					sprintf(gettext('%1$sAllow:%2$s 아래에 정의 된 넷 블록 내의 호스트로부터의 쿼리를 허용합니다.%3$s'), '<span class="text-success"><strong>', '</strong></span>', '<br />') .
					sprintf(gettext('%1$sAllow Snoop:%2$s  아래에 정의 된 넷 블록 내의 호스트로부터 재귀 및 비 재귀 액세스를 허용합니다. 캐시 스누핑에 사용되며 이상적으로는 관리 호스트에 대해서만 구성되어야합니다.%3$s'), '<span class="text-success"><strong>', '</strong></span>', '<br />') .
					sprintf(gettext('%1$sDeny Nonlocal:%2$s 아래에 정의 된 넷 블록 내의 호스트로부터 신뢰할 수있는 로컬 데이터 쿼리 만 허용합니다. 허용되지 않는 메시지는 삭제됩니다.%3$s'), '<span class="text-success"><strong>', '</strong></span>', '<br />') .
					sprintf(gettext('%1$sRefuse Nonlocal:%2$s 아래에 정의 된 넷 블록 내의 호스트로부터 신뢰할 수있는 로컬 데이터 쿼리 만 허용합니다. DNS rcode REFUSED 오류 메시지를 클라이언트에 보내서 허용되지 않는 메시지를 다시 보냅니다.'), '<span class="text-success"><strong>', '</strong></span>');

$pgtitle = array(gettext("Services"), gettext("DNS 확인자"), gettext("액세스리스트"));
$pglinks = array("", "services_unbound.php", "@self");

if ($act == "new" || $act == "edit") {
	$pgtitle[] = gettext('편집');
}
$shortcut_section = "resolver";
include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

if ($_POST['apply']) {
	print_apply_result_box($retval);
}

if (is_subsystem_dirty('unbound')) {
	print_apply_box(gettext("DNS확인자 구성이 변경되었습니다.") . "<br />" . gettext("변경사항을 저장하시면 적용됩니다."));
}

$tab_array = array();
$tab_array[] = array(gettext("일반설정"), false, "/services_unbound.php");
$tab_array[] = array(gettext("고급설정"), false, "services_unbound_advanced.php");
$tab_array[] = array(gettext("액세스 목록"), true, "/services_unbound_acls.php");
display_top_tabs($tab_array, true);

if ($act == "new" || $act == "edit") {

	$form = new Form();

	$section = new Form_Section('New Access List');

	$section->addInput(new Form_Input(
		'aclid',
		null,
		'hidden',
		$id
	));

	$section->addInput(new Form_Input(
		'act',
		null,
		'hidden',
		$act
	));

	$section->addInput(new Form_Input(
		'aclname',
		'Access List name',
		'text',
		$pconfig['aclname']
	))->setHelp('액세스 목록 이름을 제공하십시오.');

	$section->addInput(new Form_Select(
		'aclaction',
		'*Action',
		strtolower($pconfig['aclaction']),
		array('allow' => gettext('허가'), 'deny' => gettext('Deny'), 'refuse' => gettext('거부'), 'allow snoop' => gettext('Allow Snoop'), 'deny nonlocal' => gettext('Deny Nonlocal'), 'refuse nonlocal' => gettext('Refuse Nonlocal'))
	))->setHelp($actionHelp);

	$section->addInput(new Form_Input(
		'description',
		'Description',
		'text',
		$pconfig['description']
	))->setHelp('A description may be entered here for administrative reference.');

	$numrows = count($networkacl) - 1;
	$counter = 0;

	foreach ($networkacl as $item) {
		$network = $item['acl_network'];
		$cidr = $item['mask'];
		$description = $item['description'];

		$group = new Form_Group($counter == 0 ? '*Networks':'');

		$group->add(new Form_IpAddress(
			'acl_network'.$counter,
			null,
			$network
		))->addMask('mask' . $counter, $cidr, 128, 0)->setWidth(4)->setHelp(($counter == $numrows) ? 'Network/mask':null);

		$group->add(new Form_Input(
			'description' . $counter,
			null,
			'text',
			$description
		))->setHelp(($counter == $numrows) ? 'Description':null);

		$group->add(new Form_Button(
			'deleterow' . $counter,
			'Delete',
			null,
			'fa-trash'
		))->addClass('btn-warning');

		$group->addClass('repeatable');
		$section->add($group);

		$counter++;
	}

	$form->addGlobal(new Form_Button(
		'addrow',
		'Add Network',
		null,
		'fa-plus'
	))->addClass('btn-success');

	$form->add($section);
	print($form);
} else {
	// NOT 'edit' or 'add'
?>
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext('DNS확인자에 대한 액세스 제어')?></h2></div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed sortable-theme-bootstrap" data-sortable>
				<thead>
					<tr>
						<th><?=gettext("액세스 목록 이름")?></th>
						<th><?=gettext("Action")?></th>
						<th><?=gettext("설명")?></th>
						<th><?=gettext("Actions")?></th>
					</tr>
				</thead>
				<tbody>
<?php
	$i = 0;
	foreach ($a_acls as $acl):
?>
					<tr ondblclick="document.location='services_unbound_acls.php?act=edit&amp;id=<?=$i?>'">
						<td>
							<?=htmlspecialchars($acl['aclname'])?>
						</td>
						<td>
							<?=htmlspecialchars($acl['aclaction'])?>
						</td>
						<td>
							<?=htmlspecialchars($acl['description'])?>
						</td>
						<td>
							<a class="fa fa-pencil"	title="<?=gettext('ACL 편집')?>" href="services_unbound_acls.php?act=edit&amp;id=<?=$i?>"></a>
							<a class="fa fa-trash"	title="<?=gettext('ACL 삭제')?>" href="services_unbound_acls.php?act=del&amp;id=<?=$i?>" usepost></a>
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

<nav class="action-buttons">
	<a href="services_unbound_acls.php?act=new" class="btn btn-sm btn-success">
		<i class="fa fa-plus icon-embed-btn"></i>
		<?=gettext("추가")?>
	</a>
</nav>

<?php
}

?>
<script type="text/javascript">
//<![CDATA[
events.push(function() {

	// Suppress "Delete row" button if there are fewer than two rows
	checkLastRow();

});
//]]>
</script>

<?php
include("foot.inc");

<?php
/*
 * services_wol.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2018 Rubicon Communications, LLC (Netgate)
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
##|*IDENT=page-services-wakeonlan
##|*NAME=Services: Wake-on-LAN
##|*DESCR=Allow access to the 'Services: Wake-on-LAN' page.
##|*MATCH=services_wol.php*
##|-PRIV

require_once("guiconfig.inc");
if (!is_array($config['wol']['wolentry'])) {
	$config['wol']['wolentry'] = array();
}
$a_wol = &$config['wol']['wolentry'];

if ($_REQUEST['wakeall'] != "") {
	$i = 0;
	$savemsg = "";
	foreach ($a_wol as $wolent) {
		$mac = $wolent['mac'];
		$if = $wolent['interface'];
		$description = $wolent['descr'];
		$ipaddr = get_interface_ip($if);
		if (!is_ipaddr($ipaddr)) {
			continue;
		}
		$bcip = gen_subnet_max($ipaddr, get_interface_subnet($if));
		/* Execute wol command and check return code. */
		if (!mwexec("/usr/local/bin/wol -i {$bcip} {$mac}")) {
			$savemsg .= sprintf(gettext('매직 패킷을 %1$s(%2$s)(으)로 보냈습니다.'), $mac, $description) . "<br />";
			$class = 'success';
		} else {
			$savemsg .= sprintf(gettext('%1$s 시스템 로그 %2$s를 확인하십시오. %3$s (%4$s)의 wol 명령이 성공적으로 완료되지 않았습니다.'), '<a href="/status_logs.php">', '</a>', $description, $mac) . "<br />";
			$class = 'warning';
		}
	}
}

if ($_POST['Submit'] || $_POST['mac']) {
	unset($input_errors);

	if ($_POST['mac']) {
		/* normalize MAC addresses - lowercase and convert Windows-ized hyphenated MACs to colon delimited */
		$mac = strtolower(str_replace("-", ":", $_POST['mac']));
		$if = $_POST['if'];
	}

	/* input validation */
	if (!$mac || !is_macaddr($mac)) {
		$input_errors[] = gettext("유효한 MAC 주소를 지정해야합니다.");
	}

	if (!$if) {
		$input_errors[] = gettext("유효한 인터페이스를 지정해야합니다.");
	}

	if (!$input_errors) {
		/* determine broadcast address */
		$ipaddr = get_interface_ip($if);
		if (!is_ipaddr($ipaddr)) {
			$input_errors[] = gettext("A valid ip could not be found!");
		} else {
			$bcip = gen_subnet_max($ipaddr, get_interface_subnet($if));
			/* Execute wol command and check return code. */
			if (!mwexec("/usr/local/bin/wol -i {$bcip} " . escapeshellarg($mac))) {
				$savemsg .= sprintf(gettext("%s에 매직 패킷을 보냈습니다."), $mac);
				$class = 'success';
			} else {
				$savemsg .= sprintf(gettext('%1$s 시스템 로그 %2$s를 확인하십시오. %3$s의 wol 명령이 성공적으로 완료되지 않았습니다.'), '<a href="/status_logs.php">', '</a>', $mac) . "<br />";
				$class = 'warning';
			}
		}
	}
}

if ($_POST['act'] == "del") {
	if ($a_wol[$_POST['id']]) {
		unset($a_wol[$_POST['id']]);
		write_config(gettext("WOL 구성에서 장치를 삭제했습니다."));
		header("Location: services_wol.php");
		exit;
	}
}

$pgtitle = array(gettext("서비스"), gettext("Wake-on-LAN"));
include("head.inc");
?>
<div class="infoblock blockopen">
<?php
print_info_box(gettext('이 서비스는 특별한 "매직 패킷"을 보내 컴퓨터를 깨우거나 (켜기 위해) 사용할 수 있습니다.') . '<br />' .
			   gettext('깨우는 컴퓨터의 NIC는 WOL(Wake-on-LAN)을 지원해야하며 제대로 구성되어야합니다 (WOL 케이블, BIOS 설정).'),
			   'info', false);

?>
</div>
<?php

if ($input_errors) {
	print_input_errors($input_errors);
}

if ($savemsg) {
	print_info_box($savemsg, $class);
}

$form = new Form(false);

$section = new Form_Section('Wake-on-LAN');

$section->addInput(new Form_Select(
	'if',
	'*Interface',
	(link_interface_to_bridge($if) ? null : $if),
	get_configured_interface_with_descr()
))->setHelp('Choose which interface the host to be woken up is connected to.');

$section->addInput(new Form_Input(
	'mac',
	'*MAC address',
	'text',
	$mac
))->setHelp('Enter a MAC address in the following format: xx:xx:xx:xx:xx:xx');

$form->add($section);

$form->addGlobal(new Form_Button(
	'Submit',
	'Send',
	null,
	'fa-power-off'
))->addClass('btn-primary');

print $form;
?>

<div class="panel panel-default">
	<div class="panel-heading">
		<h2 class="panel-title"><?=gettext("Wake-on-LAN 디바이스");?></h2>
	</div>

	<div class="panel-body">
		<p><?=gettext("개별 장치를 깨우려면 MAC 주소를 클릭하십시오.")?></p>
		<div class="table-responsive">
			<table class="table table-striped table-hover table-rowdblclickedit">
				<thead>
					<tr>
						<th><?=gettext("인터페이스")?></th>
						<th><?=gettext("MAC 주소")?></th>
						<th><?=gettext("Description")?></th>
						<th><?=gettext("Actions")?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($a_wol as $i => $wolent): ?>
						<tr>
							<td>
								<?=convert_friendly_interface_to_friendly_descr($wolent['interface']);?>
							</td>
							<td>
								<a href="?mac=<?=$wolent['mac'];?>&amp;if=<?=$wolent['interface'];?>" usepost><?=strtolower($wolent['mac']);?></a>
							</td>
							<td>
								<?=htmlspecialchars($wolent['descr']);?>
							</td>
							<td>
								<a class="fa fa-pencil"	title="<?=gettext('디바이스 편집')?>"	href="services_wol_edit.php?id=<?=$i?>"></a>
								<a class="fa fa-trash"	title="<?=gettext('디바이스 삭제')?>" href="services_wol.php?act=del&amp;id=<?=$i?>" usepost></a>
								<a class="fa fa-power-off" title="<?=gettext('디바이스 깨우기')?>" href="?mac=<?=$wolent['mac'];?>&amp;if=<?=$wolent['interface'];?>" usepost></a>
							</td>
						</tr>
					<?php endforeach?>
				</tbody>
			</table>
		</div>
	</div>
	<div class="panel-footer">
		<a class="btn btn-success" href="services_wol_edit.php">
			<i class="fa fa-plus icon-embed-btn"></i>
			<?=gettext("추가");?>
		</a>

		<a href="services_wol.php?wakeall=true" role="button" class="btn btn-primary">
			<i class="fa fa-power-off icon-embed-btn"></i>
			<?=gettext("모든 디바이스 ")?>
		</a>
	</div>
</div>

<?php

include("foot.inc");

<?php
/*
 * services_igmpproxy.php
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
2018.03.12
한글화 번역 시작
*/

##|+PRIV
##|*IDENT=page-services-igmpproxy
##|*NAME=Services: IGMP Proxy
##|*DESCR=Allow access to the 'Services: IGMP Proxy' page.
##|*MATCH=services_igmpproxy.php*
##|-PRIV

require_once("guiconfig.inc");

if (!is_array($config['igmpproxy']['igmpentry'])) {
	$config['igmpproxy']['igmpentry'] = array();
}

//igmpproxy_sort();
$a_igmpproxy = &$config['igmpproxy']['igmpentry'];

if ($_POST['apply']) {
	$pconfig = $_POST;

	$changes_applied = true;
	$retval = 0;
	/* reload all components that use igmpproxy */
	$retval |= services_igmpproxy_configure();

	clear_subsystem_dirty('igmpproxy');
}

if ($_POST['act'] == "del") {
	if ($a_igmpproxy[$_POST['id']]) {
		unset($a_igmpproxy[$_POST['id']]);
		write_config();
		mark_subsystem_dirty('igmpproxy');
		header("Location: services_igmpproxy.php");
		exit;
	}
}

$pgtitle = array(gettext("Services"), gettext("IGMP Proxy"));
include("head.inc");

if ($changes_applied) {
	print_apply_result_box($retval);
}

if (is_subsystem_dirty('igmpproxy')) {
	print_apply_box(gettext('IGMP 항목 목록이 변경되었습니다.') . '<br />' . gettext('변경사항을 저장하면 적용됩니다.'));
}
?>

<form action="services_igmpproxy.php" method="post">
	<div class="panel panel-default">
		<div class="panel-heading"><h2 class="panel-title"><?=gettext('IGMP 프록시')?></h2></div>
		<div class="panel-body">
			<div class="table-responsive">
				<table class="table table-striped table-hover table-condensed table-rowdblclickedit">
					<thead>
						<tr>
							<th><?=gettext("이름")?></th>
							<th><?=gettext("유형")?></th>
							<th><?=gettext("요소 값")?></th>
							<th><?=gettext("Description")?></th>
							<th><?=gettext("Actions")?></th>
						</tr>
					</thead>
					<tbody>
<?php
$i = 0;
foreach ($a_igmpproxy as $igmpentry):
?>
						<tr>
							<td>
								<?=htmlspecialchars(convert_friendly_interface_to_friendly_descr($igmpentry['ifname']))?>
							</td>
							<td>
								<?=htmlspecialchars($igmpentry['type'])?>
							</td>
							<td>
<?php
	$addresses = implode(", ", array_slice(explode(" ", $igmpentry['address']), 0, 10));
	print($addresses);

	if (count($addresses) < 10) {
		print(' ');
	} else {
		print('...');
	}
?>
							</td>
							<td>
								<?=htmlspecialchars($igmpentry['descr'])?>&nbsp;
							</td>
							<td>
								<a class="fa fa-pencil"	title="<?=gettext('IGMP 항목 편집')?>" href="services_igmpproxy_edit.php?id=<?=$i?>"></a>
								<a class="fa fa-trash"	title="<?=gettext('IGMP 항목 삭제')?>" href="services_igmpproxy.php?act=del&amp;id=<?=$i?>" usepost></a>
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
</form>

<nav class="action-buttons">
	<a href="services_igmpproxy_edit.php" class="btn btn-success btn-sm">
		<i class="fa fa-plus icon-embed-btn"></i>
		<?=gettext('추가')?>
	</a>
</nav>

<div class="infoblock">
<?php print_info_box(gettext('프록시가 허용 할 업스트림, 허용 된 서브넷 및 다운 스트림 인터페이스에 대한 인터페이스를 추가하십시오. 하나의 ' .
					   '"업스트림"인터페이스 만 구성 할 수 있습니다.'), 'info', false); ?>
</div>
<?php
include("foot.inc");

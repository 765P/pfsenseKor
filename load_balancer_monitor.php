<?php
/*
 * load_balancer_monitor.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2018 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2005-2008 Bill Marquette <bill.marquette@gmail.com>
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
##|*IDENT=page-services-loadbalancer-monitor
##|*NAME=Services: Load Balancer: Monitors
##|*DESCR=Allow access to the 'Services: Load Balancer: Monitors' page.
##|*MATCH=load_balancer_monitor.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("filter.inc");

if (!is_array($config['load_balancer']['monitor_type'])) {
	$config['load_balancer']['monitor_type'] = array();
}
$a_monitor = &$config['load_balancer']['monitor_type'];

$pconfig = $_POST;

if ($_POST['apply']) {
	$retval = 0;
	$retval |= filter_configure();
	$retval |= relayd_configure();

	clear_subsystem_dirty('loadbalancer');
}


if ($_POST['act'] == "del") {
	if (array_key_exists($_POST['id'], $a_monitor)) {
		/* make sure no pools reference this entry */
		if (is_array($config['load_balancer']['lbpool'])) {
			foreach ($config['load_balancer']['lbpool'] as $pool) {
				if ($pool['monitor'] == $a_monitor[$_POST['id']]['name']) {
					$input_errors[] = gettext("이 항목은 적어도 하나 이상의 풀에서 계속 참조되므로 삭제할 수 없습니다.");
					break;
				}
			}
		}

		if (!$input_errors) {
			unset($a_monitor[$_POST['id']]);
			write_config();
			mark_subsystem_dirty('loadbalancer');
			header("Location: load_balancer_monitor.php");
			exit;
		}
	}
}

$pgtitle = array(gettext("Services"), gettext("로드 밸런스"), gettext("모니터"));
$pglinks = array("", "load_balancer_pool.php", "@self");
$shortcut_section = "relayd";

include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

if ($_POST['apply']) {
	print_apply_result_box($retval);
}

if (is_subsystem_dirty('loadbalancer')) {
	print_apply_box(gettext("로드 밸런서 구성이 변경되었습니다.") . "<br />" . gettext("변경 사항을 적용해야 적용됩니다."));
}

/* active tabs */
$tab_array = array();
$tab_array[] = array(gettext("풀"), false, "load_balancer_pool.php");
$tab_array[] = array(gettext("가상 서버"), false, "load_balancer_virtual_server.php");
$tab_array[] = array(gettext("모니터"), true, "load_balancer_monitor.php");
$tab_array[] = array(gettext("설정"), false, "load_balancer_setting.php");
display_top_tabs($tab_array);
?>

<form action="load_balancer_monitor.php" method="post">
	<div class="panel panel-default">
		<div class="panel-heading"><h2 class="panel-title"><?=gettext('모니터')?></h2></div>
		<div class="panel-body table-responsive">
			<table class="table table-striped table-hover table-condensed table-rowdblclickedit">
				<thead>
					<tr>
						<th><?=gettext('이름')?></th>
						<th><?=gettext('유형')?></th>
						<th><?=gettext('Description')?></th>
						<th><?=gettext('Actions')?></th>
					</tr>
				</thead>
				<tbody>
<?php
$idx = 0;
foreach ($a_monitor as $monitor) {
?>
					<tr>
						<td>
							<?=$monitor['name']?>
						</td>
						<td>
							<?=$monitor['type']?>
						</td>
						<td>
							<?=$monitor['descr']?>
						</td>
						<td>
							<a class="fa fa-pencil"	title="<?=gettext('모니터 편집')?>"	href="load_balancer_monitor_edit.php?id=<?=$idx?>"></a>
							<a class="fa fa-clone"	title="<?=gettext('모니터 복사')?>"	href="load_balancer_monitor_edit.php?act=dup&amp;id=<?=$idx?>"></a>
							<a class="fa fa-trash"	title="<?=gettext('모니터 삭제')?>"	href="load_balancer_monitor.php?act=del&amp;id=<?=$idx?>" usepost></a>
						</td>
					</tr>
<?php
	$idx++;
}
?>
				</tbody>
			</table>
		</div>
	</div>
</form>

<nav class="action-buttons">
	<a href="load_balancer_monitor_edit.php" class="btn btn-success btn-sm">
		<i class="fa fa-plus icon-embed-btn"></i>
		<?=gettext('추가')?>
	</a>
</nav>

<?php

include("foot.inc");

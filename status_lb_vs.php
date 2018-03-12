<?php
/*
 * status_lb_vs.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2018 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2010 Seth Mos <seth.mos@dds.nl>
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
##|*IDENT=page-status-loadbalancer-virtualserver
##|*NAME=Status: Load Balancer: Virtual Server
##|*DESCR=Allow access to the 'Status: Load Balancer: Virtual Server' page.
##|*MATCH=status_lb_vs.php*
##|-PRIV

define('COLOR', true);

require_once("guiconfig.inc");
require_once("vslb.inc");

if (!is_array($config['load_balancer']['lbpool'])) {
	$config['load_balancer']['lbpool'] = array();
}
if (!is_array($config['load_balancer']['virtual_server'])) {
	$config['load_balancer']['virtual_server'] = array();
}
$a_vs = &$config['load_balancer']['virtual_server'];
$a_pool = &$config['load_balancer']['lbpool'];
$rdr_a = get_lb_redirects();

$pgtitle = array(gettext("Status"), gettext("로드 밸런서"), gettext("가상서버"));
$pglinks = array("", "status_lb_pool.php", "@self");
include("head.inc");

/* active tabs */
$tab_array = array();
$tab_array[] = array(gettext("풀"), false, "status_lb_pool.php");
$tab_array[] = array(gettext("가상서버"), true, "status_lb_vs.php");
display_top_tabs($tab_array);

if (empty($a_vs)) {
	print_info_box(gettext("로드 밸런서가 구성되지 않았습니다."), 'danger', false);
} else {
?>
<div class="table-responsive"></div>
	<table class="table table-striped table-hover table-condensed sortable-theme-bootstrap" data-sortable>
		<thead>
			<tr>
				<th><?=gettext("이름"); ?></th>
				<th><?=gettext("주소"); ?></th>
				<th><?=gettext("서버"); ?></th>
				<th><?=gettext("Status"); ?></th>
				<th><?=gettext("Description"); ?></th>
			</tr>
		</thead>
		<tbody>
<?php
			$i = 0;
			foreach ($a_vs as $vsent): ?>
			<tr>
				<td>
					<?=$vsent['name']?>
				</td>
				<td>
					<?=$vsent['ipaddr']." : ".$vsent['port']?><br />
				</td>
				<td>

					<?php
					foreach ($a_pool as $vipent) {
						if ($vipent['name'] == $vsent['poolname']) {
							foreach ((array) $vipent['servers'] as $server) { ?>
								<?=$server?> <br />
<?php
							}
						}
					}
?>
				</td>
				<?php
				switch (trim($rdr_a[$vsent['name']]['status'])) {
					case 'active':
					  $bgcolor = "bg-success";
					  $rdr_a[$vsent['name']]['status'] = gettext("액티브");
					  break;
					case 'down':
					  $bgcolor = "bg-danger";
					  $rdr_a[$vsent['name']]['status'] = gettext("다운");
					  break;
					default:
					  $bgcolor = "bg-info";
					  $rdr_a[$vsent['name']]['status'] = gettext('알 수 없음 - 릴레이가 실행되고 있지 않습니까?');
				  }

				if (!COLOR) {
					$bgcolor = "";
				}
?>
				<td class="<?=$bgcolor?>">
					<?=$rdr_a[$vsent['name']]['status']?>

<?php
					if (!empty($rdr_a[$vsent['name']]['total'])) {
						echo sprintf(gettext("총 세션: %s"), $rdr_a[$vsent['name']]['total'] . "<br />");
					}
					if (!empty($rdr_a[$vsent['name']]['last'])) {
						echo sprintf(gettext("마지막: %s"), $rdr_a[$vsent['name']]['last'] . "<br />");
					}
					if (!empty($rdr_a[$vsent['name']]['average'])) {
						echo sprintf(gettext("Average: %s"), $rdr_a[$vsent['name']]['average']);
					}
?>
				</td>
				<td>
					<?=htmlspecialchars($vsent['descr'])?>
				</td>
			</tr>

<?php		$i++; endforeach; ?>
		</tbody>
	</table>
</div>

<?php }

include("foot.inc"); ?>

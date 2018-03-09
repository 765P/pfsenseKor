<?php
/*
 * status_captiveportal.php
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
2018.03.09
한글화 번역 시작
*/

##|+PRIV
##|*IDENT=page-status-captiveportal
##|*NAME=Status: Captive Portal
##|*DESCR=Allow access to the 'Status: Captive Portal' page.
##|*MATCH=status_captiveportal.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("functions.inc");
require_once("filter.inc");
require_once("shaper.inc");
require_once("captiveportal.inc");

function print_details($cpent) {
	global $config, $cpzone, $cpzoneid;

	printf("<a data-toggle=\"popover\" data-trigger=\"hover focus\" title=\"%s\" data-content=\" ", gettext("세션 정보"));

	/* print the duration of the session */
	$session_time = time() - $cpent[0];
	printf(gettext("세션 기간: %s") . "<br>", convert_seconds_to_dhms($session_time));

	/* print the time left before session timeout or session terminate time or the closer of the two if both are set */
	if (!empty($cpent[7]) && !empty($cpent[9])) {
		$session_time_left = min($cpent[0] + $cpent[7] - time(),$cpent[9] - time());
		printf(gettext("세션 종료: %s") . "<br>", convert_seconds_to_dhms($session_time_left));
	} elseif (!empty($cpent[7]) && empty($cpent[9])) {
		$session_time_left = $cpent[0] + $cpent[7] - time();
		printf(gettext("세션 종료: %s") . "<br>", convert_seconds_to_dhms($session_time_left));
	} elseif (empty($cpent[7]) && !empty($cpent[9])) {
		$session_time_left = $cpent[9] - time();
		printf(gettext("세션 종료: %s") . "<br>", convert_seconds_to_dhms($session_time_left));
	}

	/* print idle time and time left before disconnection if idle timeout is set */
	if ($_REQUEST['showact']) {
		$last_act = captiveportal_get_last_activity($cpent[2]);

		/* if the user never sent traffic, set last activity time to the login time */
		$last_act = $last_act ? $last_act : $cpent[0];

		$idle_time = time() - $last_act;
		printf(gettext("유휴기간: %s") . "<br>", convert_seconds_to_dhms((int)$idle_time));

		if (!empty($cpent[8])) {
			$idle_time_left = $last_act + $cpent[8] - time();
			printf(gettext("유휴기간 종료: %s") . "<br>", convert_seconds_to_dhms((int)$idle_time_left));
		}
	}

	/* print bytes sent and received, invert the values if reverse accounting is enabled */
	$volume = getVolume($cpent[2]);
	$reverse = isset($config['captiveportal'][$cpzone]['reverseacct']) ? true : false;
	if ($reverse) {
		printf(gettext("바이트 전송: %s") . "<br>" . gettext("받은 바이트: %s") . "\" data-html=\"true\">", format_bytes($volume['output_bytes']), format_bytes($volume['input_bytes']));
	} else {
		printf(gettext("바이트 전송: %s") . "<br>" . gettext("받은 바이트: %s") . "\" data-html=\"true\">", format_bytes($volume['input_bytes']), format_bytes($volume['output_bytes']));
	}

	/* print username */
	printf("%s</a>", htmlspecialchars($cpent[4]));
}

$cpzone = strtolower($_REQUEST['zone']);

if (!is_array($config['captiveportal'])) {
	$config['captiveportal'] = array();
}

$a_cp =& $config['captiveportal'];

if (count($a_cp) == 1) {
	$cpzone = current(array_keys($a_cp));
}

/* If the zone does not exist, do not display the invalid zone */
if (!array_key_exists($cpzone, $a_cp)) {
	$cpzone = "";
}

if (isset($cpzone) && !empty($cpzone) && isset($a_cp[$cpzone]['zoneid'])) {
	$cpzoneid = $a_cp[$cpzone]['zoneid'];
}

if ($_POST['act'] == "del" && !empty($cpzone) && isset($cpzoneid) && isset($_POST['id'])) {
	captiveportal_disconnect_client($_POST['id'], 6);
	/* keep displaying last activity times */
	if ($_POST['showact']) {
		header("Location: status_captiveportal.php?zone={$cpzone}&showact=1");
	} else {
		header("Location: status_captiveportal.php?zone={$cpzone}");
	}
	exit;
}

if ($_POST['deleteall'] && !empty($cpzone) && isset($cpzoneid)) {
	captiveportal_disconnect_all();
	header("Location: status_captiveportal.php?zone={$cpzone}");
	exit;
}

$pgtitle = array(gettext("Status"), gettext("Captive Portal"));
$pglinks = array("", "status_captiveportal.php");

if (!empty($cpzone)) {
	$cpdb = captiveportal_read_db();

	$pgtitle[] = htmlspecialchars($a_cp[$cpzone]['zone']);
	$pglinks[] = "status_captiveportal.php?zone=" . $cpzone;

	if (isset($config['voucher'][$cpzone]['enable'])) {
		$pgtitle[] = gettext("Active Users");
		$pglinks[] = "status_captiveportal.php?zone=" . $cpzone;
	}
}
$shortcut_section = "captiveportal";

include("head.inc");

if (!empty($cpzone) && isset($config['voucher'][$cpzone]['enable'])):
	$tab_array = array();
	$tab_array[] = array(gettext("활성 사용자"), true, "status_captiveportal.php?zone=" . htmlspecialchars($cpzone));
	$tab_array[] = array(gettext("활성 바우처"), false, "status_captiveportal_vouchers.php?zone=" . htmlspecialchars($cpzone));
	$tab_array[] = array(gettext("바우처 롤"), false, "status_captiveportal_voucher_rolls.php?zone=" . htmlspecialchars($cpzone));
	$tab_array[] = array(gettext("바우처 테스트"), false, "status_captiveportal_test.php?zone=" . htmlspecialchars($cpzone));
	$tab_array[] = array(gettext("만료 바우처"), false, "status_captiveportal_expire.php?zone=" . htmlspecialchars($cpzone));
	display_top_tabs($tab_array);
endif;

// Load MAC-Manufacturer table
$mac_man = load_mac_manufacturer_table();

if (count($a_cp) > 1) {
	$form = new Form(false);

	$section = new Form_Section('Captive Portal Zone');

	$zonelist = array("" => 'None');

	foreach ($a_cp as $cpkey => $cp) {
		$zonelist[$cpkey] = $cp['zone'];
	}

	$section->addInput(new Form_Select(
		'zone',
		'Display Zone',
		$cpzone,
		$zonelist
	))->setOnchange('this.form.submit()');

	$form->add($section);

	print($form);
}

if (!empty($cpzone)): ?>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=sprintf(gettext("로그인한 유저 (%d)"), count($cpdb))?></h2></div>
	<div class="panel-body table-responsive">
		<table class="table table-striped table-hover table-condensed sortable-theme-bootstrap" data-sortable>
			<thead>
				<tr>
					<th><?=gettext("IP 주소")?></th>
<?php
	if (!isset($config['captiveportal'][$cpzone]['nomacfilter'])):
?>
					<th><?=gettext("MAC 주소")?></th>
<?php
	endif;
?>
					<th><?=gettext("유저이름")?></th>
					<th><?=gettext("세션시작")?></th>
<?php
	if ($_REQUEST['showact']):
?>
					<th><?=gettext("마지막 작동")?></th>
<?php
	endif;
?>
					<th data-sortable="false"><?=gettext("Actions")?></th>
				</tr>
			</thead>
			<tbody>
<?php

	foreach ($cpdb as $cpent): ?>
				<tr>
					<td><?=htmlspecialchars($cpent[2])?></td>
<?php
		if (!isset($config['captiveportal'][$cpzone]['nomacfilter'])) {
?>
					<td>
<?php
			$mac=trim($cpent[3]);
			if (!empty($mac)) {
				$mac_hi = strtoupper($mac[0] . $mac[1] . $mac[3] . $mac[4] . $mac[6] . $mac[7]);
				print htmlentities($mac);
				if (isset($mac_man[$mac_hi])) {
					print "<br /><font size=\"-2\"><i>" . htmlspecialchars($mac_man[$mac_hi]) . "</i></font>";
				}
			}
?>
					</td>
<?php
		}
?>
					<td><?php print_details($cpent); ?></td>
<?php
		if ($_REQUEST['showact']):
			$last_act = captiveportal_get_last_activity($cpent[2]);
			/* if the user never sent traffic, set last activity time to the login time */
			$last_act = $last_act ? $last_act : $cpent[0];
?>
					<td><?=htmlspecialchars(date("m/d/Y H:i:s", $cpent[0]))?></td>
					<td>
<?php
			echo htmlspecialchars(date("m/d/Y H:i:s", $last_act));
?>
					</td>
<?php
		else:
?>
					<td><?=htmlspecialchars(date("m/d/Y H:i:s", $cpent[0]))?></td>
<?php
		endif;
?>
					<td>
						<a href="?zone=<?=htmlspecialchars($cpzone)?>&amp;showact=<?=htmlspecialchars($_REQUEST['showact'])?>&amp;act=del&amp;id=<?=htmlspecialchars($cpent[5])?>" usepost><i class="fa fa-trash" title="<?=gettext("해당 유저 연결끊기")?>"></i></a>
					</td>
				</tr>
<?php
	endforeach;
?>
			</tbody>
		</table>
	</div>
</div>
<?php
else:
	if (empty($a_cp)) {
		// If no zones have been defined
		print_info_box(sprintf(gettext('포털 포털 영역이 구성되어 있지 않습니다. 여기에 새 영역을 추가 할 수 있습니다 : %1$sServices> 전속 포털 %2$s.'), '<a href="services_captiveportal_zones.php">', '</a>'), 'warning', false);
	}
endif;
?>

<nav class="action-buttons">
<?php
if (!empty($cpzone)):
	if ($_REQUEST['showact']): ?>
	<a href="status_captiveportal.php?zone=<?=htmlspecialchars($cpzone)?>&amp;showact=0" role="button" class="btn btn-info" title="<?=gettext("마지막 활동 표시 안 함")?>">
		<i class="fa fa-minus-circle icon-embed-btn"></i>
		<?=gettext("마지막 작동 숨기기")?>
	</a>
<?php
	else:
?>
	<a href="status_captiveportal.php?zone=<?=htmlspecialchars($cpzone)?>&amp;showact=1" role="button" class="btn btn-info" title="<?=gettext("마지막 활동 표시")?>">
		<i class="fa fa-plus-circle icon-embed-btn"></i>
		<?=gettext("마지막 작동 보기")?>
	</a>
<?php
	endif;
?>
	<a href="status_captiveportal.php?zone=<?=htmlspecialchars($cpzone)?>&amp;deleteall=1" role="button" class="btn btn-danger" title="<?=gettext("모든 활성 사용자 연결 끊기")?>" usepost>
		<i class="fa fa-trash icon-embed-btn"></i>
		<?=gettext("모든 유저 연결해제")?>
	</a>
<?php
endif;
?>
</nav>
<?php include("foot.inc");

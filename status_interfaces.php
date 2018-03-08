<?php
/*
 * status_interfaces.php
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
2018.03.08
한글화 번역 시작
*/

##|+PRIV
##|*IDENT=page-status-interfaces
##|*NAME=Status: Interfaces
##|*DESCR=Allow access to the 'Status: Interfaces' page.
##|*MATCH=status_interfaces.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("interfaces.inc");
require_once("pfsense-utils.inc");
require_once("util.inc");

if ($_POST['ifdescr'] && $_POST['submit']) {
	$interface = $_POST['ifdescr'];
	if ($_POST['status'] == "up") {
		if ($_POST['relinquish_lease']) {
			dhcp_relinquish_lease($_POST['if'], $_POST['ifdescr'], $_POST['ipv']);
		}
		interface_bring_down($interface);
	} else {
		interface_configure($interface);
	}
	header("Location: status_interfaces.php");
	exit;
}

$formtemplate = '<form name="%s" action="status_interfaces.php" method="post">' .
					'<input type="hidden" name="ifdescr" value="%s" />' .
					'<input type="hidden" name="status" value="%s" />' .
					'%s' .
					'<button type="submit" name="submit" class="btn btn-warning btn-xs" value="%s">' .
					'<i class="fa fa-refresh icon-embed-btn"></i>' .
					'%s' .
					'</button>' .
					'%s' .
					'</form>';

// Display a term/definition pair
function showDef($show, $term, $def) {
	if ($show) {
		print('<dt>' . $term . '</dt>');
		print('<dd>' . htmlspecialchars($def) . '</dd>');
	}
}

// Display a term/definition pair with a button
function showDefBtn($show, $term, $def, $ifdescr, $btnlbl, $chkbox_relinquish_lease) {
	global $formtemplate;

	if ($show) {
		print('<dt>' . $term . '</dt>');
		print('<dd>');
		printf($formtemplate, $term, $ifdescr, $show, htmlspecialchars($def)	. ' ', $btnlbl, $btnlbl, $chkbox_relinquish_lease);
		print('</dd>');
	}
}

// Relinquish the DHCP lease from the server.
function dhcp_relinquish_lease($if, $ifdescr, $ipv) {
	$leases_db = '/var/db/dhclient.leases.' . $if;
	$conf_file = '/var/etc/dhclient_'.$ifdescr.'.conf';
	$script_file = '/usr/local/sbin/pfSense-dhclient-script';

	if (file_exists($leases_db) && file_exists($script_file)) {
		mwexec('/usr/local/sbin/dhclient -'.$ipv.' -d -r -lf '.$leases_db.' -cf '.$conf_file.' -sf '.$script_file);
	}
}

$pgtitle = array(gettext("Status"), gettext("Interfaces"));
$shortcut_section = "interfaces";
include("head.inc");

$ifdescrs = get_configured_interface_with_descr(true);

foreach ($ifdescrs as $ifdescr => $ifname):
	$ifinfo = get_interface_info($ifdescr);
	$mac_man = load_mac_manufacturer_table();

	$chkbox_relinquish_lease = 	'&nbsp;&nbsp;&nbsp;' .
								'<input type="checkbox" name="relinquish_lease" value="true" title="' . gettext("DHCP 릴리스 패킷을 서버에 보내십시오.") . '" /> ' . gettext("리스 포기") .
								'<input type="hidden" name="if" value='.$ifinfo['if'].' />';
	$chkbox_relinquish_lease_v4 = $chkbox_relinquish_lease . '<input type="hidden" name="ipv" value=4 />';
	$chkbox_relinquish_lease_v6 = $chkbox_relinquish_lease . '<input type="hidden" name="ipv" value=6 />';
?>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=htmlspecialchars($ifname)?><?=gettext(" 인터페이스 "); ?>(<?=htmlspecialchars($ifdescr)?>, <?=htmlspecialchars($ifinfo['hwif'])?>)</h2></div>
	<div class="panel-body">
		<dl class="dl-horizontal">
<?php
		showDef(true, gettext("Status"), $ifinfo['enable'] ? $ifinfo['status'] : gettext('비활성화'));
		showDefBtn($ifinfo['dhcplink'], 'DHCP', $ifinfo['dhcplink'], $ifdescr, $ifinfo['dhcplink'] == "up" ? gettext("릴리즈") : gettext("재개"), $ifinfo['dhcplink'] == "up" ? $chkbox_relinquish_lease_v4 : '');
		showDefBtn($ifinfo['dhcp6link'], 'DHCP6', $ifinfo['dhcp6link'], $ifdescr, $ifinfo['dhcp6link'] == "up" ? gettext("릴리즈") : gettext("재개"), $ifinfo['dhcp6link'] == "up" ? $chkbox_relinquish_lease_v6 : '');
		showDefBtn($ifinfo['pppoelink'], 'PPPoE', $ifinfo['pppoelink'], $ifdescr, $ifinfo['pppoelink'] == "up" ? gettext("연결종료") : gettext("연결"), '');
		showDefBtn($ifinfo['pptplink'], 'PPTP', $ifinfo['pptplink'], $ifdescr, $ifinfo['pptplink'] == "up" ? gettext("연결종료") : gettext("연결"), '');
		showDefBtn($ifinfo['l2tplink'], 'L2TP', $ifinfo['l2tplink'], $ifdescr, $ifinfo['l2tplink'] == "up" ? gettext("연결종료") : gettext("연결"), '');
		showDefBtn($ifinfo['ppplink'], 'PPP', $ifinfo['ppplink'], $ifdescr, ($ifinfo['ppplink'] == "up" && !$ifinfo['nodevice']) ? gettext("연결종료") : gettext("연결"), '');
		showDef($ifinfo['ppp_uptime'] || $ifinfo['ppp_uptime_accumulated'], gettext("가동시간") . ' ' . ($ifinfo['ppp_uptime_accumulated'] ? gettext('(기록)'):''), $ifinfo['ppp_uptime'] . $ifinfo['ppp_uptime_accumulated']);
		showDef($ifinfo['cell_rssi'], gettext("셀 신호(RSSI)"), $ifinfo['cell_rssi']);
		showDef($ifinfo['cell_mode'], gettext("셀 모드"), $ifinfo['cell_mode']);
		showDef($ifinfo['cell_simstate'], gettext("셀 SIM상태"), $ifinfo['cell_simstate']);
		showDef($ifinfo['cell_service'], gettext("셀 서비스"), $ifinfo['cell_service']);
		showDef($ifinfo['cell_bwupstream'], gettext("셀 업스트림"), $ifinfo['cell_bwupstream']);
		showDef($ifinfo['cell_bwdownstream'], gettext("셀 다운스트림"), $ifinfo['cell_bwdownstream']);
		showDef($ifinfo['cell_upstream'], gettext("셀 흐름 상승"), $ifinfo['cell_upstream']);
		showDef($ifinfo['cell_downstream'], gettext("셀 흐름 하락"), $ifinfo['cell_downstream']);

		if ($ifinfo['macaddr']) {
			$mac=$ifinfo['macaddr'];
			$mac_hi = strtoupper($mac[0] . $mac[1] . $mac[3] . $mac[4] . $mac[6] . $mac[7]);
			showDef( $ifinfo['macaddr'], gettext('MAC주소'), $mac . (isset($mac_man[$mac_hi]) ? ' - ' . $mac_man[$mac_hi] : ''));
		}

		if ($ifinfo['status'] != "down") {
			if ($ifinfo['dhcplink'] != "down" && $ifinfo['pppoelink'] != "down" && $ifinfo['pptplink'] != "down") {
				showDef($ifinfo['ipaddr'], gettext('IPv4 주소'), $ifinfo['ipaddr']);
				showDef($ifinfo['subnet'], gettext('IPv4 서브넷 마스크'), $ifinfo['subnet']);
				showDef($ifinfo['gateway'], gettext('IPv4 게이트웨이'), $ifinfo['gateway']);
				showDef($ifinfo['linklocal'], gettext('IPv6 링크 로컬'), $ifinfo['linklocal']);
				showDef($ifinfo['ipaddrv6'], gettext('IPv6 주소'), $ifinfo['ipaddrv6']);
				showDef($ifinfo['subnetv6'], gettext('IPv6 서브넷 마스크'), $ifinfo['subnetv6']);
				showDef($ifinfo['gatewayv6'], gettext("IPv6 게이트웨이"), $config['interfaces'][$ifdescr]['gatewayv6'] . " " . $ifinfo['gatewayv6']);

				if ($ifdescr == "wan" && file_exists("{$g['etc_path']}/resolv.conf")) {
					$dns_servers = get_dns_servers();
					$dnscnt = 0;
					foreach ($dns_servers as $dns) {
						showDef(true, $dnscnt == 0 ? gettext('DNS 서버'):'', $dns);
						$dnscnt++;
					}
				}
			}

			showDef($ifinfo['mtu'], gettext("MTU"), $ifinfo['mtu']);
			showDef($ifinfo['media'], gettext("미디어"), $ifinfo['media']);
			showDef($ifinfo['laggproto'], gettext("LAGG 프로토콜"), $ifinfo['laggproto']);
			showDef($ifinfo['laggport'], gettext("LAGG 포트"), $laggport);
			showDef($ifinfo['channel'], gettext("채널"), $ifinfo['channel']);
			showDef($ifinfo['ssid'], gettext("SSID"), $ifinfo['ssid']);
			showDef($ifinfo['bssid'], gettext("BSSID"), $ifinfo['bssid']);
			showDef($ifinfo['rate'], gettext("Rate"), $ifinfo['rate']);
			showDef($ifinfo['rssi'], gettext("RSSI"), $ifinfo['rssi']);
			showDef(true, gettext("In/out packets"),
			    $ifinfo['inpkts'] . '/' . $ifinfo['outpkts'] . " (" . format_bytes($ifinfo['inbytes']) . "/" . format_bytes($ifinfo['outbytes']) . ")");
			showDef(true, gettext("In/out 패킷 (pass)"),
			    $ifinfo['inpktspass'] . '/' . $ifinfo['outpktspass'] . " (" . format_bytes($ifinfo['inbytespass']) . "/" . format_bytes($ifinfo['outbytespass']) . ")");
			showDef(true, gettext("In/out 패킷 (block)"),
			    $ifinfo['inpktsblock'] . '/' . $ifinfo['outpktsblock'] . " (" . format_bytes($ifinfo['inbytesblock']) . "/" . format_bytes($ifinfo['outbytesblock']) . ")");
			showDef(isset($ifinfo['inerrs']), gettext("In/out errors"), $ifinfo['inerrs'] . "/" . $ifinfo['outerrs']);
			showDef(isset($ifinfo['collisions']), gettext("충돌"), $ifinfo['collisions']);
		} // e-o-if ($ifinfo['status'] != "down")

		showDef($ifinfo['bridge'], sprintf(gettext('브리지 (%1$s)'), $ifinfo['bridgeint']), $ifinfo['bridge']);

		if (file_exists("/usr/bin/vmstat")) {
			$real_interface = "";
			$interrupt_total = "";
			$interrupt_sec = "";
			$real_interface = $ifinfo['hwif'];
			$interrupt_total = `vmstat -i | grep $real_interface | awk '{ print $3 }'`;
			$interrupt_sec = `vmstat -i | grep $real_interface | awk '{ print $4 }'`;

			if (strstr($interrupt_total, "hci")) {
				$interrupt_total = `vmstat -i | grep $real_interface | awk '{ print $4 }'`;
				$interrupt_sec = `vmstat -i | grep $real_interface | awk '{ print $5 }'`;
			}

			unset($interrupt_total);

			showDef($interrupt_total, gettext('총 인터럽트'), $interrupt_total);
			showDef($interrupt_total, '', $interrupt_sec . " " . $interrupt_total);
		}
?>
		</dl>
	</div>
</div>

<?php
	endforeach;

print_info_box(sprintf(gettext('dial-on-demand를 사용하면 패킷이 트리거될 때 연결이 다시 활성화됩니다. ' .
	    '이 점을 구체화하기 위해 수동으로 연결을 끊더라도 전화 접속을 이용해 외부에 연결을 시도할 수 없습니다. ' .
	    '회선을 분리 한 채로 두려면 전화 접속을 사용하지 마십시오.'), '<strong>', '</strong>'), 'warning', false);
include("foot.inc");
?>

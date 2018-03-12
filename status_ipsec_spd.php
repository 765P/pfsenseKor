<?php
/*
 * status_ipsec_spd.php
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
##|*IDENT=page-status-ipsec-spd
##|*NAME=Status: IPsec: SPD
##|*DESCR=Allow access to the 'Status: IPsec: SPD' page.
##|*MATCH=status_ipsec_spd.php*
##|-PRIV

define('RIGHTARROW', '&#x25ba;');
define('LEFTARROW',  '&#x25c4;');

require_once("guiconfig.inc");
require_once("ipsec.inc");

$pgtitle = array(gettext("Status"), gettext("IPsec"), gettext("SPDs"));
$pglinks = array("", "status_ipsec.php", "@self");
$shortcut_section = "ipsec";
include("head.inc");

$spd = ipsec_dump_spd();

$tab_array = array();
$tab_array[0] = array(gettext("개요"), false, "status_ipsec.php");
$tab_array[1] = array(gettext("리스"), false, "status_ipsec_leases.php");
$tab_array[2] = array(gettext("SADs"), false, "status_ipsec_sad.php");
$tab_array[3] = array(gettext("SPDs"), true, "status_ipsec_spd.php");
display_top_tabs($tab_array);

if (count($spd)) {
?>
	<div class="table-responsive">
		<table class="table table-striped table-condensed table-hover sortable-theme-bootstrap" data-sortable>
			<thead>
				<tr>
					<th><?= gettext("발신지"); ?></th>
					<th><?= gettext("수신지"); ?></th>
					<th><?= gettext("방향"); ?></th>
					<th><?= gettext("프로토콜"); ?></th>
					<th><?= gettext("터널 엔드포인트"); ?></th>
				</tr>
			</thead>

			<tbody>
<?php
		foreach ($spd as $sp) {
			if ($sp['dir'] == 'in') {
				$dirstr = LEFTARROW . gettext(' 인바운드');
			} else {
				$dirstr = RIGHTARROW . gettext(' 아웃바운드');
			}
?>
				<tr>
					<td>
						<?=htmlspecialchars($sp['srcid'])?>
					</td>
					<td>
						<?=htmlspecialchars($sp['dstid'])?>
					</td>
					<td>
						<?=$dirstr ?>
					</td>
					<td>
						<?=htmlspecialchars(strtoupper($sp['proto']))?>
					</td>
					<td>
						<?=htmlspecialchars($sp['src'])?> -&gt; <?=htmlspecialchars($sp['dst'])?>
					</td>
				</tr>
<?php
		}
?>
			</tbody>
		</table>
	</div>
<?php
} else {
	print_info_box(gettext('IPsec 보안 정책이 구성되지 않았습니다.'));
}

if (ipsec_enabled()) {
?>
<div class="infoblock">
<?php
} else {
?>
<div class="infoblock blockopen">
<?php
}
print_info_box(sprintf(gettext('IPsec을 %1$s여기%2$s에서 구성 할 수 있습니다.'), '<a href="vpn_ipsec.php">', '</a>'), 'info', false);
?>
</div>
<?php
include("foot.inc");

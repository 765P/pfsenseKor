<?php
/*
 * diag_halt.php
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

##|+PRIV
##|*IDENT=page-diagnostics-haltsystem
##|*NAME=Diagnostics: Halt system
##|*DESCR=Allow access to the 'Diagnostics: Halt system' page.
##|*MATCH=diag_halt.php*
##|-PRIV

// Set DEBUG to true to prevent the system_halt() function from being called
define("DEBUG", false);

require_once("guiconfig.inc");
require_once("functions.inc");
require_once("captiveportal.inc");

if ($_POST['save'] == 'No') {
	header("Location: index.php");
	exit;
}

$pgtitle = array(gettext("진단"), gettext("시스템 정지"));
include('head.inc');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
?>
	<meta http-equiv="refresh" content="70;url=/">
<?php
	print_info_box(gettext("시스템이 지금 정지 중입니다. 1 분 정도 걸릴 수 있습니다."), 'success', false);

	if (DEBUG) {
	   printf(gettext("실제로 멈추지 않음(DEBUG가 true로 설정 됨)%s"), "<br />");
	} else {
		print('<pre>');
		system_halt();
		print('</pre>');
	}
} else {
?>

<div class="panel panel-default">
	<div class="panel-heading">
		<h2 class="panel-title"><?=gettext('시스템 정지 확인')?></h2>
	</div>
	<div class="panel-body">
		<div class="content">
			<p><?=gettext('시스템을 즉시 중지하려면 "중지"를 클릭하고, 시스템 대시 보드로 이동하려면 "취소"를 클릭하십시오. 대시 보드가 표시되기까지 잠깐 지연 될 것입니다.')?></p>
			<form action="diag_halt.php" method="post">
				<button type="submit" class="btn btn-danger pull-center" name="save" value="<?=gettext("중지")?>" title="<?=gettext("시스템 정지 및 전원 끄기")?>">
					<i class="fa fa-stop-circle"></i>
					<?=gettext("중지")?>
				</button>
				<a href="/" class="btn btn-info">
					<i class="fa fa-undo"></i>
					<?=gettext("취소")?>
				</a>
			</form>
		</div>
	</div>
</div>



<?php
}

include("foot.inc");

<?php
/*
 * status_filter_reload.php
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
2018.03.15
한글화 번역 시작
*/

##|+PRIV
##|*IDENT=page-status-filterreloadstatus
##|*NAME=Status: Filter Reload Status
##|*DESCR=Allow access to the 'Status: Filter Reload Status' page.
##|*MATCH=status_filter_reload.php*
##|-PRIV

require_once("globals.inc");
require_once("guiconfig.inc");
require_once("functions.inc");

$pgtitle = array(gettext("상태"), gettext("필터 리로드"));
$shortcut_section = "firewall";

if (file_exists("{$g['varrun_path']}/filter_reload_status")) {
	$status = file_get_contents("{$g['varrun_path']}/filter_reload_status");
}

if ($_REQUEST['getstatus']) {
	echo "|{$status}|";
	exit;
}
if ($_POST['reloadfilter']) {
	send_event("filter reload");
	header("Location: status_filter_reload.php");
	exit;
}
if ($_POST['syncfilter']) {
	send_event("filter sync");
	header("Location: status_filter_reload.php");
	exit;
}

include("head.inc");
?>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext("필터 리로드");?></h2></div>
	<div class="panel-body">
		<div class="content">
			<form action="status_filter_reload.php" method="post" name="filter">
				<button type="submit" class="btn btn-success" value="<?=gettext("필터 리로드")?>" name="reloadfilter" id="reloadfilter"><i class="fa fa-refresh icon-embed-btn"></i><?=gettext("필터 리로드")?></button>
<?php
if ($config['hasync'] && $config['hasync']["synchronizetoip"] != ""): ?>
				<button type="submit" class="btn btn-info" value="<?=gettext("강제 구성 동기화")?>" name="syncfilter" id="syncfilter"><i class="fa fa-clone icon-embed-btn"></i><?=gettext("강제 구성 동기화")?></button>
<?php
endif;
?>
			</form>
			<br />
			<div id="doneurl"></div>
			<br />
			<div class="panel panel-default">
				<div class="panel-heading"><h2 class="panel-title"><?=gettext("새로 고침 상태")?></h2></div>
				<div class="panel-body" id="status">
				</div>
			</div>
			<br/>

<?php if (!$_REQUEST['user']) { ?>
			<div id="reloadinfo"><?=gettext("이 페이지는 필터가 다시로드 될 때까지 3 초마다 자동으로 새로 고침됩니다."); ?></div>
<?php } ?>

		</div>
	</div>
</div>

<script type="text/javascript">
//<![CDATA[
/* init update "thread */
function update_status_thread() {
	getURL('status_filter_reload.php?getstatus=true', update_data);
}

function update_data(obj) {
	var result_text = obj.content;
	var result_text_split = result_text.split("|");
	result_text = result_text_split[1];

	if (result_text) {
		$('#status').html('<pre>' + result_text + '</pre>');
	} else {
		$('#status').html('<pre>' + '<?=gettext("필터 상태 가져 오는 중...");?>' + '</pre>');
	}

	if (result_text.endsWith("Done\n")) {
		$('#reloadinfo').css("visibility", "hidden");
		$('#doneurl').css("visibility", "visible");
		$('#doneurl').html("<p><a href='status_queues.php'><?=gettext("대기열 상태");?><\/a><\/p>");
		$('#reloadinfo').html("");
	}  else {
		window.setTimeout('update_status_thread()', 1500);
	}
}
//]]>
</script>

<script type="text/javascript">
//<![CDATA[
/*
 * getURL is a proprietary Adobe function, but it's simplicity has made it very
 * popular. If getURL is undefined we spin our own by wrapping XMLHttpRequest.
 */
if (typeof getURL == 'undefined') {
	getURL = function(url, callback) {
		if (!url) {
			throw 'No URL for getURL';
		}

		try {
			if (typeof callback.operationComplete == 'function') {
				callback = callback.operationComplete;
			}
		} catch (e) {}
		if (typeof callback != 'function') {
			throw 'No callback function for getURL';
		}

		var http_request = null;
		if (typeof XMLHttpRequest != 'undefined') {
			http_request = new XMLHttpRequest();
		} else if (typeof ActiveXObject != 'undefined') {
			try {
				http_request = new ActiveXObject('Msxml2.XMLHTTP');
			} catch (e) {
				try {
					http_request = new ActiveXObject('Microsoft.XMLHTTP');
				} catch (e) {}
			}
		}
		if (!http_request) {
			throw 'Both getURL and XMLHttpRequest are undefined';
		}

		http_request.onreadystatechange = function() {
			if (http_request.readyState == 4) {
				callback( { success : true,
					content : http_request.responseText,
					contentType : http_request.getResponseHeader("Content-Type") } );
			}
		}

		http_request.open('REQUEST', url, true);
		http_request.send(null);
	}
}

if ("<?=htmlspecialchars($_REQUEST['user'])?>" != "true") {
 	window.setTimeout('update_status_thread()', 1500);
 }
//]]>
</script>

<?php include("foot.inc");

<?php
/*
 * system_user_settings.php
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
2018.03.07
한글화 번역 시작
*/

##|+PRIV
##|*IDENT=page-system-user-settings
##|*NAME=System: User Settings
##|*DESCR=Allow access to the 'System: User Settings' page.
##|*MATCH=system_user_settings.php*
##|-PRIV

 require_once("auth.inc");
 require_once("guiconfig.inc");

$pgtitle = array(gettext("시스템"), gettext("유저 설정"));

$a_user = &$config['system']['user'];

if (isset($_SESSION['Username']) && isset($userindex[$_SESSION['Username']])) {
	$id = $userindex[$_SESSION['Username']];
}

if (isset($id) && $a_user[$id]) {
	$pconfig['webguicss'] = $a_user[$id]['webguicss'];
	$pconfig['webguifixedmenu'] = $a_user[$id]['webguifixedmenu'];
	$pconfig['webguihostnamemenu'] = $a_user[$id]['webguihostnamemenu'];
	$pconfig['dashboardcolumns'] = $a_user[$id]['dashboardcolumns'];
	$pconfig['interfacessort'] = isset($a_user[$id]['interfacessort']);
	$pconfig['dashboardavailablewidgetspanel'] = isset($a_user[$id]['dashboardavailablewidgetspanel']);
	$pconfig['systemlogsfilterpanel'] = isset($a_user[$id]['systemlogsfilterpanel']);
	$pconfig['systemlogsmanagelogpanel'] = isset($a_user[$id]['systemlogsmanagelogpanel']);
	$pconfig['statusmonitoringsettingspanel'] = isset($a_user[$id]['statusmonitoringsettingspanel']);
	$pconfig['webguileftcolumnhyper'] = isset($a_user[$id]['webguileftcolumnhyper']);
	$pconfig['disablealiaspopupdetail'] = isset($a_user[$id]['disablealiaspopupdetail']);
	$pconfig['pagenamefirst'] = isset($a_user[$id]['pagenamefirst']);
} else {
	echo gettext("로컬사용자가 아닌 경우 설정을 관리 할 수 없습니다.");
	include("foot.inc");
	exit;
}

if (isset($_POST['save'])) {
	unset($input_errors);
	/* input validation */

	$reqdfields = explode(" ", "webguicss dashboardcolumns");
	$reqdfieldsn = array(gettext("테마"), gettext("대시보드 "));
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

	$userent = $a_user[$id];

	if (!$input_errors) {
		$pconfig['webguicss'] = $userent['webguicss'] = $_POST['webguicss'];

		if ($_POST['webguifixedmenu']) {
			$pconfig['webguifixedmenu'] = $userent['webguifixedmenu'] = $_POST['webguifixedmenu'];
		} else {
			$pconfig['webguifixedmenu'] = "";
			unset($userent['webguifixedmenu']);
		}

		if ($_POST['webguihostnamemenu']) {
			$pconfig['webguihostnamemenu'] = $userent['webguihostnamemenu'] = $_POST['webguihostnamemenu'];
		} else {
			$pconfig['webguihostnamemenu'] = "";
			unset($userent['webguihostnamemenu']);
		}

		$pconfig['dashboardcolumns'] = $userent['dashboardcolumns'] = $_POST['dashboardcolumns'];

		if ($_POST['interfacessort']) {
			$pconfig['interfacessort'] = $userent['interfacessort'] = true;
		} else {
			$pconfig['interfacessort'] = false;
			unset($userent['interfacessort']);
		}

		if ($_POST['dashboardavailablewidgetspanel']) {
			$pconfig['dashboardavailablewidgetspanel'] = $userent['dashboardavailablewidgetspanel'] = true;
		} else {
			$pconfig['dashboardavailablewidgetspanel'] = false;
			unset($userent['dashboardavailablewidgetspanel']);
		}

		if ($_POST['systemlogsfilterpanel']) {
			$pconfig['systemlogsfilterpanel'] = $userent['systemlogsfilterpanel'] = true;
		} else {
			$pconfig['systemlogsfilterpanel'] = false;
			unset($userent['systemlogsfilterpanel']);
		}

		if ($_POST['systemlogsmanagelogpanel']) {
			$pconfig['systemlogsmanagelogpanel'] = $userent['systemlogsmanagelogpanel'] = true;
		} else {
			$pconfig['systemlogsmanagelogpanel'] = false;
			unset($userent['systemlogsmanagelogpanel']);
		}

		if ($_POST['statusmonitoringsettingspanel']) {
			$pconfig['statusmonitoringsettingspanel'] = $userent['statusmonitoringsettingspanel'] = true;
		} else {
			$pconfig['statusmonitoringsettingspanel'] = false;
			unset($userent['statusmonitoringsettingspanel']);
		}

		if ($_POST['webguileftcolumnhyper']) {
			$pconfig['webguileftcolumnhyper'] = $userent['webguileftcolumnhyper'] = true;
		} else {
			$pconfig['webguileftcolumnhyper'] = false;
			unset($userent['webguileftcolumnhyper']);
		}

		if ($_POST['disablealiaspopupdetail']) {
			$pconfig['disablealiaspopupdetail'] = $userent['disablealiaspopupdetail'] = true;
		} else {
			$pconfig['disablealiaspopupdetail'] = false;
			unset($userent['disablealiaspopupdetail']);
		}

		if ($_POST['pagenamefirst']) {
			$pconfig['pagenamefirst'] = $userent['pagenamefirst'] = true;
		} else {
			$pconfig['pagenamefirst'] = false;
			unset($userent['pagenamefirst']);
		}

		$a_user[$id] = $userent;
		$savemsg = sprintf(gettext("사용자 %s에 대한 사용자 설정이 변경되었습니다."), $_SESSION['Username']);
		write_config($savemsg);
	}
}

include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

if ($savemsg) {
	print_info_box($savemsg, 'success');
}

$form = new Form();

$section = new Form_Section('사용자 설정: ' . $_SESSION['Username']);

gen_user_settings_fields($section, $pconfig);

$form->add($section);
print($form);
$csswarning = sprintf(gettext("%s 사용자가 만든 테마는 지원되지 않으므로 사용에 따른 모든 책임은 사용자에게 있습니다."), "<br />");
?>
<script type="text/javascript">
//<![CDATA[
events.push(function() {

	// Handle displaying a warning message if a user-created theme is selected.
	function setThemeWarning() {
		if ($('#webguicss').val().startsWith("pfSense")) {
			$('#csstxt').html("").addClass("text-default");
		} else {
			$('#csstxt').html("<?=$csswarning?>").addClass("text-danger");
		}
	}

	$('#webguicss').change(function() {
		setThemeWarning();
	});

	// ---------- On initial page load ------------------------------------------------------------
	setThemeWarning();

});
//]]>
</script>
<?php
include("foot.inc");

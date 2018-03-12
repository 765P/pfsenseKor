<?php
/*
 * services_captiveportal.php
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
##|*IDENT=page-services-captiveportal
##|*NAME=Services: Captive Portal
##|*DESCR=Allow access to the 'Services: Captive Portal' page.
##|*MATCH=services_captiveportal.php*
##|-PRIV

/*
2018.03.12
한글화 번역 시작
*/

require_once("functions.inc");
require_once("filter.inc");
require_once("shaper.inc");
require_once("captiveportal.inc");

if (substr($_REQUEST['act'], 0, 3) == "get") {
	$nocsrf = true;
}

require_once("guiconfig.inc");

global $cpzone;
global $cpzoneid;

$cpzoneid = 1; /* Just a default */
$cpzone = $_REQUEST['zone'];

$cpzone = strtolower($cpzone);

if (empty($cpzone) || empty($config['captiveportal'][$cpzone])) {
	header("Location: services_captiveportal_zones.php");
	exit;
}

if (!is_array($config['captiveportal'])) {
	$config['captiveportal'] = array();
}

$a_cp =& $config['captiveportal'];

$pgtitle = array(gettext("Services"), gettext("Captive Portal"), $a_cp[$cpzone]['zone'], gettext("Configuration"));
$pglinks = array("", "services_captiveportal_zones.php", "@self", "@self");
$shortcut_section = "captiveportal";

if ($_REQUEST['act'] == "viewhtml") {
	if ($a_cp[$cpzone] && $a_cp[$cpzone]['page']['htmltext']) {
		echo base64_decode($a_cp[$cpzone]['page']['htmltext']);
	}
	exit;
} else if ($_REQUEST['act'] == "gethtmlhtml" && $a_cp[$cpzone] && $a_cp[$cpzone]['page']['htmltext']) {
	$file_data = base64_decode($a_cp[$cpzone]['page']['htmltext']);
	$file_size = strlen($file_data);

	header("Content-Type: text/html");
	header("Content-Disposition: attachment; filename=portal.html");
	header("Content-Length: $file_size");
	echo $file_data;

	exit;
} else if ($_REQUEST['act'] == "delhtmlhtml" && $a_cp[$cpzone] && $a_cp[$cpzone]['page']['htmltext']) {
	unset($a_cp[$cpzone]['page']['htmltext']);
	write_config(sprintf(gettext("Captive Portal: zone %s: Restore default portal page"), $cpzone));
	header("Location: services_captiveportal.php?zone={$cpzone}");
	exit;
} else if ($_REQUEST['act'] == "viewerrhtml") {
	if ($a_cp[$cpzone] && $a_cp[$cpzone]['page']['errtext']) {
		echo base64_decode($a_cp[$cpzone]['page']['errtext']);
	}
	exit;
} else if ($_REQUEST['act'] == "geterrhtml" && $a_cp[$cpzone] && $a_cp[$cpzone]['page']['errtext']) {
	$file_data = base64_decode($a_cp[$cpzone]['page']['errtext']);
	$file_size = strlen($file_data);

	header("Content-Type: text/html");
	header("Content-Disposition: attachment; filename=err.html");
	header("Content-Length: $file_size");
	echo $file_data;

	exit;
} else if ($_REQUEST['act'] == "delerrhtml" && $a_cp[$cpzone] && $a_cp[$cpzone]['page']['errtext']) {
	unset($a_cp[$cpzone]['page']['errtext']);
	write_config(sprintf(gettext("Captive Portal: zone %s: Restore default error page"), $cpzone));
	header("Location: services_captiveportal.php?zone={$cpzone}");
	exit;
} else if ($_REQUEST['act'] == "viewlogouthtml") {
	if ($a_cp[$cpzone] && $a_cp[$cpzone]['page']['logouttext']) {
		echo base64_decode($a_cp[$cpzone]['page']['logouttext']);
	}
	exit;
} else if ($_REQUEST['act'] == "getlogouthtml" && $a_cp[$cpzone] && $a_cp[$cpzone]['page']['logouttext']) {
	$file_data = base64_decode($a_cp[$cpzone]['page']['logouttext']);
	$file_size = strlen($file_data);

	header("Content-Type: text/html");
	header("Content-Disposition: attachment; filename=logout.html");
	header("Content-Length: $file_size");
	echo $file_data;

	exit;
} else if ($_REQUEST['act'] == "dellogouthtml" && $a_cp[$cpzone] && $a_cp[$cpzone]['page']['logouttext']) {
	unset($a_cp[$cpzone]['page']['logouttext']);
	write_config(sprintf(gettext("Captive Portal: zone %s: Restore default logout page"), $cpzone));
	header("Location: services_captiveportal.php?zone={$cpzone}");
	exit;
}

if (!is_array($config['ca'])) {
	$config['ca'] = array();
}

$a_ca =& $config['ca'];

if (!is_array($config['cert'])) {
	$config['cert'] = array();
}

$a_cert =& $config['cert'];

if ($a_cp[$cpzone]) {
	$cpzoneid = $pconfig['zoneid'] = $a_cp[$cpzone]['zoneid'];
	$pconfig['cinterface'] = $a_cp[$cpzone]['interface'];
	$pconfig['maxproc'] = $a_cp[$cpzone]['maxproc'];
	$pconfig['maxprocperip'] = $a_cp[$cpzone]['maxprocperip'];
	$pconfig['timeout'] = $a_cp[$cpzone]['timeout'];
	$pconfig['idletimeout'] = $a_cp[$cpzone]['idletimeout'];
	$pconfig['freelogins_count'] = $a_cp[$cpzone]['freelogins_count'];
	$pconfig['freelogins_resettimeout'] = $a_cp[$cpzone]['freelogins_resettimeout'];
	$pconfig['freelogins_updatetimeouts'] = isset($a_cp[$cpzone]['freelogins_updatetimeouts']);
	$pconfig['enable'] = isset($a_cp[$cpzone]['enable']);
	$pconfig['auth_method'] = $a_cp[$cpzone]['auth_method'];
	$pconfig['localauth_priv'] = isset($a_cp[$cpzone]['localauth_priv']);
	$pconfig['radacct_enable'] = isset($a_cp[$cpzone]['radacct_enable']);
	$pconfig['radmac_enable'] = isset($a_cp[$cpzone]['radmac_enable']);
	$pconfig['radmac_secret'] = $a_cp[$cpzone]['radmac_secret'];
	$pconfig['reauthenticate'] = isset($a_cp[$cpzone]['reauthenticate']);
	$pconfig['reauthenticateacct'] = $a_cp[$cpzone]['reauthenticateacct'];
	$pconfig['httpslogin_enable'] = isset($a_cp[$cpzone]['httpslogin']);
	$pconfig['httpsname'] = $a_cp[$cpzone]['httpsname'];
	$pconfig['preauthurl'] = strtolower($a_cp[$cpzone]['preauthurl']);
	$pconfig['blockedmacsurl'] = strtolower($a_cp[$cpzone]['blockedmacsurl']);
	$pconfig['certref'] = $a_cp[$cpzone]['certref'];
	$pconfig['nohttpsforwards'] = isset($a_cp[$cpzone]['nohttpsforwards']);
	$pconfig['logoutwin_enable'] = isset($a_cp[$cpzone]['logoutwin_enable']);
	$pconfig['peruserbw'] = isset($a_cp[$cpzone]['peruserbw']);
	$pconfig['bwdefaultdn'] = $a_cp[$cpzone]['bwdefaultdn'];
	$pconfig['bwdefaultup'] = $a_cp[$cpzone]['bwdefaultup'];
	$pconfig['nomacfilter'] = isset($a_cp[$cpzone]['nomacfilter']);
	$pconfig['noconcurrentlogins'] = isset($a_cp[$cpzone]['noconcurrentlogins']);
	$pconfig['radius_protocol'] = $a_cp[$cpzone]['radius_protocol'];
	$pconfig['redirurl'] = $a_cp[$cpzone]['redirurl'];
	$pconfig['radiusip'] = $a_cp[$cpzone]['radiusip'];
	$pconfig['radiusip2'] = $a_cp[$cpzone]['radiusip2'];
	$pconfig['radiusip3'] = $a_cp[$cpzone]['radiusip3'];
	$pconfig['radiusip4'] = $a_cp[$cpzone]['radiusip4'];
	$pconfig['radiusport'] = $a_cp[$cpzone]['radiusport'];
	$pconfig['radiusport2'] = $a_cp[$cpzone]['radiusport2'];
	$pconfig['radiusport3'] = $a_cp[$cpzone]['radiusport3'];
	$pconfig['radiusport4'] = $a_cp[$cpzone]['radiusport4'];
	$pconfig['radiusacctport'] = $a_cp[$cpzone]['radiusacctport'];
	$pconfig['radiuskey'] = $a_cp[$cpzone]['radiuskey'];
	$pconfig['radiuskey2'] = $a_cp[$cpzone]['radiuskey2'];
	$pconfig['radiuskey3'] = $a_cp[$cpzone]['radiuskey3'];
	$pconfig['radiuskey4'] = $a_cp[$cpzone]['radiuskey4'];
	$pconfig['radiusvendor'] = $a_cp[$cpzone]['radiusvendor'];
	$pconfig['radiussession_timeout'] = isset($a_cp[$cpzone]['radiussession_timeout']);
	$pconfig['radiussrcip_attribute'] = $a_cp[$cpzone]['radiussrcip_attribute'];
	$pconfig['passthrumacadd'] = isset($a_cp[$cpzone]['passthrumacadd']);
	$pconfig['passthrumacaddusername'] = isset($a_cp[$cpzone]['passthrumacaddusername']);
	$pconfig['radmac_format'] = $a_cp[$cpzone]['radmac_format'];
	$pconfig['reverseacct'] = isset($a_cp[$cpzone]['reverseacct']);
	$pconfig['includeidletime'] = isset($a_cp[$cpzone]['includeidletime']);
	$pconfig['radiusnasid'] = $a_cp[$cpzone]['radiusnasid'];
	$pconfig['page'] = array();
	if ($a_cp[$cpzone]['page']['htmltext']) {
		$pconfig['page']['htmltext'] = $a_cp[$cpzone]['page']['htmltext'];
	}
	if ($a_cp[$cpzone]['page']['errtext']) {
		$pconfig['page']['errtext'] = $a_cp[$cpzone]['page']['errtext'];
	}
	if ($a_cp[$cpzone]['page']['logouttext']) {
		$pconfig['page']['logouttext'] = $a_cp[$cpzone]['page']['logouttext'];
	}
}

if ($_POST['save']) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	if ($_POST['enable']) {
		$reqdfields = explode(" ", "zone cinterface auth_method");
		$reqdfieldsn = array(gettext("Zone name"), gettext("Interface"), gettext("Authentication method"));

		if (isset($_POST['auth_method']) && $_POST['auth_method'] == "radius") {
			$reqdfields[] = "radius_protocol";
			$reqdfieldsn[] = gettext("RADIUS Protocol");
			$reqdfields[] = "radiusip";
			$reqdfieldsn[] = gettext("Primary RADIUS server IP address");
		}

		do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

		/* make sure no interfaces are bridged or used on other zones */
		if (is_array($_POST['cinterface'])) {
			foreach ($pconfig['cinterface'] as $cpbrif) {
				if (link_interface_to_bridge($cpbrif)) {
					$input_errors[] = sprintf(gettext("The captive portal cannot be used on interface %s since it is part of a bridge."), $cpbrif);
				}
				foreach ($a_cp as $cpkey => $cp) {
					if ($cpkey != $cpzone || empty($cpzone)) {
						if (in_array($cpbrif, explode(",", $cp['interface']))) {
							$input_errors[] = sprintf(gettext('The captive portal cannot be used on interface %1$s since it is used already on %2$s instance.'), $cpbrif, $cp['zone']);
						}
					}
				}
			}
		}

		if ($_POST['auth_method'] && !in_array($_POST['auth_method'], array('none', 'local', 'radius'))) {
			$input_errors[] = sprintf(gettext("Authentication method %s is invalid."), $_POST['auth_method']);
		}

		if ($_POST['httpslogin_enable']) {
			if (!$_POST['certref']) {
				$input_errors[] = gettext("Certificate must be specified for HTTPS login.");
			}
			if (!$_POST['httpsname'] || !is_domain($_POST['httpsname'])) {
				$input_errors[] = gettext("The HTTPS server name must be specified for HTTPS login.");
			}
		}
	}

	if ($_POST['timeout']) {
		if (!is_numeric($_POST['timeout']) || ($_POST['timeout'] < 1)) {
			$input_errors[] = gettext("The timeout must be at least 1 minute.");
		} else if (isset($config['dhcpd']) && is_array($config['dhcpd'])) {
			foreach ($config['dhcpd'] as $dhcpd_if => $dhcpd_data) {
				if (!isset($dhcpd_data['enable'])) {
					continue;
				}
				if (!is_array($_POST['cinterface']) || !in_array($dhcpd_if, $_POST['cinterface'])) {
					continue;
				}

				$deftime = 7200; // Default lease time
				if (isset($dhcpd_data['defaultleasetime']) && is_numeric($dhcpd_data['defaultleasetime'])) {
					$deftime = $dhcpd_data['defaultleasetime'];
				}

				if ($_POST['timeout'] > $deftime) {
					$input_errors[] = gettext("Hard timeout must be less than or equal to the Default lease time set on DHCP Server");
				}
			}
		}
	}

	if ($_POST['idletimeout'] && (!is_numeric($_POST['idletimeout']) || ($_POST['idletimeout'] < 1))) {
		$input_errors[] = gettext("The idle timeout must be at least 1 minute.");
	}

	if ($_POST['freelogins_count'] && (!is_numeric($_POST['freelogins_count']))) {
		$input_errors[] = gettext("The pass-through credit count must be a number or left blank.");
	} else if ($_POST['freelogins_count'] && is_numeric($_POST['freelogins_count']) && ($_POST['freelogins_count'] >= 1)) {
		if (empty($_POST['freelogins_resettimeout']) || !is_numeric($_POST['freelogins_resettimeout']) || ($_POST['freelogins_resettimeout'] <= 0)) {
			$input_errors[] = gettext("The waiting period to restore pass-through credits must be above 0 hours.");
		}
	}

	if (($_POST['radiusip'] && !is_ipaddr($_POST['radiusip']))) {
		$input_errors[] = sprintf(gettext("A valid IP address must be specified. [%s]"), $_POST['radiusip']);
	}

	if (($_POST['radiusip2'] && !is_ipaddr($_POST['radiusip2']))) {
		$input_errors[] = sprintf(gettext("A valid IP address must be specified. [%s]"), $_POST['radiusip2']);
	}

	if (($_POST['radiusip3'] && !is_ipaddr($_POST['radiusip3']))) {
		$input_errors[] = sprintf(gettext("A valid IP address must be specified. [%s]"), $_POST['radiusip3']);
	}

	if (($_POST['radiusip4'] && !is_ipaddr($_POST['radiusip4']))) {
		$input_errors[] = sprintf(gettext("A valid IP address must be specified. [%s]"), $_POST['radiusip4']);
	}

	if (($_POST['radiusport'] && !is_port($_POST['radiusport']))) {
		$input_errors[] = sprintf(gettext("A valid port number must be specified. [%s]"), $_POST['radiusport']);
	}

	if (($_POST['radiusport2'] && !is_port($_POST['radiusport2']))) {
		$input_errors[] = sprintf(gettext("A valid port number must be specified. [%s]"), $_POST['radiusport2']);
	}

	if (($_POST['radiusport3'] && !is_port($_POST['radiusport3']))) {
		$input_errors[] = sprintf(gettext("A valid port number must be specified. [%s]"), $_POST['radiusport3']);
	}

	if (($_POST['radiusport4'] && !is_port($_POST['radiusport4']))) {
		$input_errors[] = sprintf(gettext("A valid port number must be specified. [%s]"), $_POST['radiusport4']);
	}

	if (($_POST['radiusacctport'] && !is_port($_POST['radiusacctport']))) {
		$input_errors[] = sprintf(gettext("A valid port number must be specified. [%s]"), $_POST['radiusacctport']);
	}

	if ($_POST['maxproc'] && (!is_numeric($_POST['maxproc']) || ($_POST['maxproc'] < 4) || ($_POST['maxproc'] > 100))) {
		$input_errors[] = gettext("The maximum number of concurrent connections per client IP address may not be larger than the global maximum.");
	}

	if (trim($_POST['radiusnasid']) !== "" && !preg_match("/^[\x21-\x7e]{3,253}$/i", trim($_POST['radiusnasid']))) {
		$input_errors[] = gettext("The NAS-Identifier must be 3-253 characters long and should only contain ASCII characters.");
	}

	if (!$input_errors) {
		$newcp =& $a_cp[$cpzone];
		//$newcp['zoneid'] = $a_cp[$cpzone]['zoneid'];
		if (empty($newcp['zoneid'])) {
			$newcp['zoneid'] = 2;
			foreach ($a_cp as $keycpzone => $cp) {
				if ($cp['zoneid'] == $newcp['zoneid'] && $keycpzone != $cpzone) {
					$newcp['zoneid'] += 2; /* Reserve space for SSL config if needed */
				}
			}

			$cpzoneid = $newcp['zoneid'];
		}
		if (is_array($_POST['cinterface'])) {
			$newcp['interface'] = implode(",", $_POST['cinterface']);
		}
		$newcp['maxproc'] = $_POST['maxproc'];
		$newcp['maxprocperip'] = $_POST['maxprocperip'] ? $_POST['maxprocperip'] : false;
		$newcp['timeout'] = $_POST['timeout'];
		$newcp['idletimeout'] = $_POST['idletimeout'];
		$newcp['freelogins_count'] = $_POST['freelogins_count'];
		$newcp['freelogins_resettimeout'] = $_POST['freelogins_resettimeout'];
		$newcp['freelogins_updatetimeouts'] = $_POST['freelogins_updatetimeouts'] ? true : false;
		if ($_POST['enable']) {
			$newcp['enable'] = true;
		} else {
			unset($newcp['enable']);
		}
		$newcp['auth_method'] = $_POST['auth_method'];
		$newcp['localauth_priv'] = isset($_POST['localauth_priv']);
		$newcp['radacct_enable'] = $_POST['radacct_enable'] ? true : false;
		$newcp['reauthenticate'] = $_POST['reauthenticate'] ? true : false;
		$newcp['radmac_enable'] = $_POST['radmac_enable'] ? true : false;
		$newcp['radmac_secret'] = $_POST['radmac_secret'] ? $_POST['radmac_secret'] : false;
		$newcp['reauthenticateacct'] = $_POST['reauthenticateacct'];
		if ($_POST['httpslogin_enable']) {
			$newcp['httpslogin'] = true;
		} else {
			unset($newcp['httpslogin']);
		}
		$newcp['httpsname'] = $_POST['httpsname'];
		$newcp['preauthurl'] = $_POST['preauthurl'];
		$newcp['blockedmacsurl'] = $_POST['blockedmacsurl'];
		$newcp['peruserbw'] = $_POST['peruserbw'] ? true : false;
		if (isset($_POST['bwdefaultdn'])) {
			$newcp['bwdefaultdn'] = $_POST['bwdefaultdn'];
		} else {
			unset($newcp['bwdefaultdn']);
		}
		if (isset($_POST['bwdefaultup'])) {
			$newcp['bwdefaultup'] = $_POST['bwdefaultup'];
		} else {
			unset($newcp['bwdefaultup']);
		}
		$newcp['certref'] = $_POST['certref'];
		$newcp['nohttpsforwards'] = $_POST['nohttpsforwards'] ? true : false;
		$newcp['logoutwin_enable'] = $_POST['logoutwin_enable'] ? true : false;
		$newcp['nomacfilter'] = $_POST['nomacfilter'] ? true : false;
		$newcp['noconcurrentlogins'] = $_POST['noconcurrentlogins'] ? true : false;
		$newcp['radius_protocol'] = $_POST['radius_protocol'];
		$newcp['redirurl'] = $_POST['redirurl'];
		if (isset($_POST['radiusip'])) {
			$newcp['radiusip'] = $_POST['radiusip'];
		} else {
			unset($newcp['radiusip']);
		}
		if (isset($_POST['radiusip2'])) {
			$newcp['radiusip2'] = $_POST['radiusip2'];
		} else {
			unset($newcp['radiusip2']);
		}
		if (isset($_POST['radiusip3'])) {
			$newcp['radiusip3'] = $_POST['radiusip3'];
		} else {
			unset($newcp['radiusip3']);
		}
		if (isset($_POST['radiusip4'])) {
			$newcp['radiusip4'] = $_POST['radiusip4'];
		} else {
			unset($newcp['radiusip4']);
		}
		$newcp['radiusport'] = $_POST['radiusport'];
		$newcp['radiusport2'] = $_POST['radiusport2'];
		if (isset($_POST['radiusport3'])) {
			$newcp['radiusport3'] = $_POST['radiusport3'];
		}
		if (isset($_POST['radiusport4'])) {
			$newcp['radiusport4'] = $_POST['radiusport4'];
		}
		$newcp['radiusacctport'] = $_POST['radiusacctport'];
		$newcp['radiuskey'] = $_POST['radiuskey'];
		$newcp['radiuskey2'] = $_POST['radiuskey2'];
		$newcp['radiuskey3'] = $_POST['radiuskey3'];
		$newcp['radiuskey4'] = $_POST['radiuskey4'];
		$newcp['radiusvendor'] = $_POST['radiusvendor'] ? $_POST['radiusvendor'] : false;
		$newcp['radiussession_timeout'] = $_POST['radiussession_timeout'] ? true : false;
		$newcp['radiussrcip_attribute'] = $_POST['radiussrcip_attribute'];
		$newcp['passthrumacadd'] = $_POST['passthrumacadd'] ? true : false;
		$newcp['passthrumacaddusername'] = $_POST['passthrumacaddusername'] ? true : false;
		$newcp['radmac_format'] = $_POST['radmac_format'] ? $_POST['radmac_format'] : false;
		$newcp['reverseacct'] = $_POST['reverseacct'] ? true : false;
		$newcp['includeidletime'] = $_POST['includeidletime'] ? true : false;
		$newcp['radiusnasid'] = trim($_POST['radiusnasid']);

		if (!is_array($newcp['page'])) {
			$newcp['page'] = array();
		}

		/* file upload? */
		if (is_uploaded_file($_FILES['htmlfile']['tmp_name'])) {
			$newcp['page']['htmltext'] = base64_encode(file_get_contents($_FILES['htmlfile']['tmp_name']));
		}
		if (is_uploaded_file($_FILES['errfile']['tmp_name'])) {
			$newcp['page']['errtext'] = base64_encode(file_get_contents($_FILES['errfile']['tmp_name']));
		}
		if (is_uploaded_file($_FILES['logoutfile']['tmp_name'])) {
			$newcp['page']['logouttext'] = base64_encode(file_get_contents($_FILES['logoutfile']['tmp_name']));
		}

		write_config();

		captiveportal_configure_zone($newcp);
		unset($newcp);
		filter_configure();
		header("Location: services_captiveportal_zones.php");
		exit;
	} else {
		if (is_array($_POST['cinterface'])) {
			$pconfig['cinterface'] = implode(",", $_POST['cinterface']);
		}
	}
}

function build_radiusnas_list() {
	global $config;
	$list = array();

	$iflist = get_configured_interface_with_descr();
	foreach ($iflist as $ifdesc => $ifdescr) {
		$ipaddr = get_interface_ip($ifdesc);
		if (is_ipaddr($ipaddr)) {
			$list[$ifdesc] = $ifdescr . ' - ' . $ipaddr;
		}
	}

	if (is_array($config['virtualip']['vip'])) {
		foreach ($config['virtualip']['vip'] as $sn) {
			if ($sn['mode'] == "proxyarp" && $sn['type'] == "network") {
				$start = ip2long32(gen_subnet($sn['subnet'], $sn['subnet_bits']));
				$end = ip2long32(gen_subnet_max($sn['subnet'], $sn['subnet_bits']));
				$len = $end - $start;

				for ($i = 0; $i <= $len; $i++) {
					$snip = long2ip32($start+$i);
					$list[$snip] = $sn['descr'] . ' - ' . $snip;
				}
			} else {
				$list[$sn['subnet']] = $sn['descr'] . ' - ' . $sn['subnet'];
			}
		}
	}

	return($list);
}

function build_cert_list() {
	global $a_cert;

	$list = array();

	foreach ($a_cert as $cert) {
		$list[$cert['refid']] = $cert['descr'];
	}

	return($list);
}

include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

$tab_array = array();
$tab_array[] = array(gettext("Configuration"), true, "services_captiveportal.php?zone={$cpzone}");
$tab_array[] = array(gettext("MACs"), false, "services_captiveportal_mac.php?zone={$cpzone}");
$tab_array[] = array(gettext("Allowed IP Addresses"), false, "services_captiveportal_ip.php?zone={$cpzone}");
$tab_array[] = array(gettext("Allowed Hostnames"), false, "services_captiveportal_hostname.php?zone={$cpzone}");
$tab_array[] = array(gettext("Vouchers"), false, "services_captiveportal_vouchers.php?zone={$cpzone}");
$tab_array[] = array(gettext("File Manager"), false, "services_captiveportal_filemanager.php?zone={$cpzone}");
display_top_tabs($tab_array, true);

$form = new Form();
$form->setMultipartEncoding();

$section = new Form_Section('Captive Portal Configuration');

$section->addInput(new Form_Checkbox(
	'enable',
	'Enable',
	'Enable Captive Portal',
	$pconfig['enable']
));

$section->addInput(new Form_Select(
	'cinterface',
	'*Interfaces',
	explode(",", $pconfig['cinterface']),
	get_configured_interface_with_descr(),
	true
))->addClass('general')->setHelp('Select the interface(s) to enable for captive portal.');

$section->addInput(new Form_Input(
	'maxprocperip',
	'Maximum concurrent connections',
	'number',
	$pconfig['maxprocperip'],
	['min' => '0', 'max' => '100']
))->setHelp('Limits the number of concurrent connections to the captive portal HTTP(S) server. This does not set how many users can be logged in ' .
			'to the captive portal, but rather how many connections a single IP can establish to the portal web server.');

$section->addInput(new Form_Input(
	'idletimeout',
	'Idle timeout (Minutes)',
	'number',
	$pconfig['idletimeout']
))->setHelp('Clients will be disconnected after this amount of inactivity. They may log in again immediately, though. Leave this field blank for no idle timeout.');

$section->addInput(new Form_Input(
	'timeout',
	'Hard timeout (Minutes)',
	'number',
	$pconfig['timeout']
))->setHelp('Clients will be disconnected after this amount of time, regardless of activity. They may log in again immediately, though. ' .
			'Leave this field blank for no hard timeout (not recommended unless an idle timeout is set).');

$section->addInput(new Form_Input(
	'freelogins_count',
	'Pass-through credits per MAC address.',
	'number',
	$pconfig['freelogins_count']
))->setHelp('Allows passing through the captive portal without authentication a limited number of times per MAC address. Once used up, ' .
			'the client can only log in with valid credentials until the waiting period specified below has expired. Recommended to set ' .
			'a hard timeout and/or idle timeout when using this for it to be effective.');

$section->addInput(new Form_Input(
	'freelogins_resettimeout',
	'Waiting period to restore pass-through credits. (Hours)',
	'number',
	$pconfig['freelogins_resettimeout']
))->setHelp('Clients will have their available pass-through credits restored to the original count after this amount of time since using the first one. ' .
			'This must be above 0 hours if pass-through credits are enabled.');

$section->addInput(new Form_Checkbox(
	'freelogins_updatetimeouts',
	'Reset waiting period',
	'Enable waiting period reset on attempted access',
	$pconfig['freelogins_updatetimeouts']
))->setHelp('If enabled, the waiting period is reset to the original duration if access is attempted when all pass-through credits have already been exhausted.');

$section->addInput(new Form_Checkbox(
	'logoutwin_enable',
	'Logout popup window',
	'Enable logout popup window',
	$pconfig['logoutwin_enable']
))->setHelp('If enabled, a popup window will appear when clients are allowed through the captive portal. ' .
			'This allows clients to explicitly disconnect themselves before the idle or hard timeout occurs.');

$section->addInput(new Form_Input(
	'preauthurl',
	'Pre-authentication redirect URL',
	'text',
	$pconfig['preauthurl']
))->setHelp('Use this field to set $PORTAL_REDIRURL$ variable which can be accessed using the custom captive portal index.php page or error pages.');

$section->addInput(new Form_Input(
	'redirurl',
	'After authentication Redirection URL',
	'text',
	$pconfig['redirurl']
))->setHelp('Clients will be redirected to this URL instead of the one they initially tried to access after they\'ve authenticated.');

$section->addInput(new Form_Input(
	'blockedmacsurl',
	'Blocked MAC address redirect URL',
	'text',
	$pconfig['blockedmacsurl']
))->setHelp('Blocked MAC addresses will be redirected to this URL when attempting access.');

$section->addInput(new Form_Checkbox(
	'noconcurrentlogins',
	'Concurrent user logins',
	'Disable Concurrent user logins',
	$pconfig['noconcurrentlogins']
))->setHelp('If enabled only the most recent login per username will be active. Subsequent logins will cause machines previously logged in with the ' .
			'same username to be disconnected.');

$section->addInput(new Form_Checkbox(
	'nomacfilter',
	'MAC filtering',
	'Disable MAC filtering',
	$pconfig['nomacfilter']
))->setHelp('If enabled no attempts will be made to ensure that the MAC address of clients stays the same while they are logged in. ' .
			'This is required when the MAC address of the client cannot be determined (usually because there are routers between pfSense and the clients). ' .
			'If this is enabled, RADIUS MAC authentication cannot be used.');

$section->addInput(new Form_Checkbox(
	'passthrumacadd',
	'Pass-through MAC Auto Entry',
	'Enable Pass-through MAC automatic additions',
	$pconfig['passthrumacadd']
))->setHelp('When enabled, a MAC passthrough entry is automatically added after the user has successfully authenticated. Users of that MAC address will ' .
			'never have to authenticate again. To remove the passthrough MAC entry either log in and remove it manually from the ' .
			'%1$sMAC tab%2$s or send a POST from another system. '  .
			'If this is enabled, RADIUS MAC authentication cannot be used. Also, the logout window will not be shown.', "<a href=\"services_captiveportal_mac.php?zone={$cpzone}\">", '</a>');

$section->addInput(new Form_Checkbox(
	'passthrumacaddusername',
	null,
	'Enable Pass-through MAC automatic addition with username',
	$pconfig['passthrumacaddusername']
))->setHelp('If enabled with the automatically MAC passthrough entry created, the username used during authentication will be saved. ' .
			'To remove the passthrough MAC entry either log in and remove it manually from the %1$sMAC tab%2$s or send a POST from another system.',
			"<a href=\"services_captiveportal_mac.php?zone={$cpzone}\">", '</a>');

$section->addInput(new Form_Checkbox(
	'peruserbw',
	'Per-user bandwidth restriction',
	'Enable per-user bandwidth restriction',
	$pconfig['peruserbw']
));

$section->addInput(new Form_Input(
	'bwdefaultdn',
	'Default download (Kbit/s)',
	'number',
	$pconfig['bwdefaultdn']
));

$section->addInput(new Form_Input(
	'bwdefaultup',
	'Default upload (Kbit/s)',
	'number',
	$pconfig['bwdefaultup']
))->setHelp('If this option is set, the captive portal will restrict each user who logs in to the specified default bandwidth. ' .
			'RADIUS can override the default settings. Leave empty for no limit.');

$form->add($section);

$section = new Form_Section('Authentication');
$section->addClass('Authentication');

$group = new Form_Group('*Authentication Method');

$group->add(new Form_Checkbox(
	'auth_method',
	null,
	'No Authentication',
	$pconfig['auth_method'] == 'none' || empty($pconfig['auth_method']),
	'none'
))->displayasRadio();

$group->add(new Form_Checkbox(
	'auth_method',
	null,
	'Local User Manager / Vouchers',
	$pconfig['auth_method'] == 'local',
	'local'
))->displayasRadio();

$group->add(new Form_Checkbox(
	'auth_method',
	null,
	'RADIUS Authentication',
	$pconfig['auth_method'] == 'radius',
	'radius'
))->displayasRadio();

$group->setHelp('Select an Authentication Method to use for this zone. One method must be selected.');

$section->add($group);

$section->addInput(new Form_Checkbox(
	'localauth_priv',
	null,
	'Allow only users/groups with "Captive portal login" privilege set',
	$pconfig['localauth_priv']
));

$group = new Form_Group('*RADIUS protocol');
$group->addClass("radiusproto");

$group->add(new Form_Checkbox(
	'radius_protocol',
	null,
	'PAP',
	$pconfig['radius_protocol'] == 'PAP',
	'PAP'
))->displayasRadio();

$group->add(new Form_Checkbox(
	'radius_protocol',
	null,
	'CHAP-MD5',
	$pconfig['radius_protocol'] == 'CHAP_MD5',
	'CHAP_MD5'
))->displayasRadio();

$group->add(new Form_Checkbox(
	'radius_protocol',
	null,
	'MSCHAPv1',
	$pconfig['radius_protocol'] == 'MSCHAPv1',
	'MSCHAPv1'
))->displayasRadio();

$group->add(new Form_Checkbox(
	'radius_protocol',
	null,
	'MSCHAPv2',
	$pconfig['radius_protocol'] == 'MSCHAPv2',
	'MSCHAPv2'
))->displayasRadio();

$section->add($group);

$form->add($section);

$section = new Form_Section('Primary Authentication Source');
$section->addClass('Primary');

$group = new Form_Group('*Primary RADIUS server');

$group->add(new Form_IpAddress(
	'radiusip',
	null,
	$pconfig['radiusip']
));

$group->add(new Form_Input(
	'radiusport',
	null,
	'number',
	$pconfig['radiusport']
));

$group->add(new Form_Input(
	'radiuskey',
	null,
	'text',
	$pconfig['radiuskey']
));

$section->add($group);

$group = new Form_Group('Secondary RADIUS server');

$group->add(new Form_IpAddress(
	'radiusip2',
	null,
	$pconfig['radiusip2']
))->setHelp('IP address of the RADIUS server to authenticate against.');

$group->add(new Form_Input(
	'radiusport2',
	null,
	'number',
	$pconfig['radiusport2']
))->setHelp('RADIUS port. Leave blank for default (1812)');

$group->add(new Form_Input(
	'radiuskey2',
	null,
	'text',
	$pconfig['radiuskey2']
))->setHelp('RADIUS shared secret. Leave blank to not use a shared secret (not recommended)');

$section->add($group);

$form->add($section);

$section = new Form_Section('Secondary Authentication Source');
$section->addClass('Secondary');

$group = new Form_Group('Primary RADIUS server');

$group->add(new Form_IpAddress(
	'radiusip3',
	null,
	$pconfig['radiusip3']
));

$group->add(new Form_Input(
	'radiusport3',
	null,
	'number',
	$pconfig['radiusport3']
));

$group->add(new Form_Input(
	'radiuskey3',
	null,
	'text',
	$pconfig['radiuskey3']
));

$section->add($group);

$group = new Form_Group('Secondary RADIUS server');

$group->add(new Form_IpAddress(
	'radiusip4',
	null,
	$pconfig['radiusip4']
))->setHelp('인증 할 RADIUS 서버의 IP 주소입니다.');

$group->add(new Form_Input(
	'radiusport4',
	null,
	'number',
	$pconfig['radiusport4']
))->setHelp('기본 포트 (1813)를 사용하려면 비워 두십시오.');

$group->add(new Form_Input(
	'radiuskey4',
	null,
	'text',
	$pconfig['radiuskey4']
))->setHelp('RADIUS 공유 암호. 공유 암호를 사용하지 않으려면 비워 두십시오 (권장하지 않음).');

$section->add($group);
$form->add($section);

$section = new Form_Section('Accounting');
$section->addClass('Accounting');

$section->addInput(new Form_Checkbox(
	'radacct_enable',
	'RADIUS',
	'Send RADIUS accounting packets to the primary RADIUS server.',
	$pconfig['radacct_enable']
));

$section->addInput(new Form_Input(
	'radiusacctport',
	'Accounting Port',
	'text',
	$pconfig['radiusacctport']
))->setHelp('기본 포트 (1813)를 사용하려면 비워 두십시오.');

$group = new Form_Group('Accounting updates');

$group->add(new Form_Checkbox(
	'reauthenticateacct',
	null,
	'No updates',
	$pconfig['reauthenticateacct'] == "",
	""
))->displayasRadio();

$group->add(new Form_Checkbox(
	'reauthenticateacct',
	null,
	'Stop/Start',
	$pconfig['reauthenticateacct'] == 'stopstart',
	"stopstart"
))->displayasRadio();

$group->add(new Form_Checkbox(
	'reauthenticateacct',
	null,
	'Stop/Start (FreeRADIUS)',
	$pconfig['reauthenticateacct'] == 'stopstartfreeradius',
	"stopstartfreeradius"
))->displayasRadio();

$group->add(new Form_Checkbox(
	'reauthenticateacct',
	null,
	'Interim',
	$pconfig['reauthenticateacct'] == 'interimupdate',
	"interimupdate"
))->displayasRadio();

$section->add($group);

$form->add($section);

$section = new Form_Section('RADIUS Options');
$section->addClass('Radius');

$section->addInput(new Form_Checkbox(
	'reauthenticate',
	'Reauthentication',
	'Reauthenticate connected users every minute',
	$pconfig['reauthenticate']
))->setHelp('재 인증을 사용하면 매분마다 로그인 한 각 사용자의 액세스 요청이 RADIUS 서버로 전송됩니다. ' .
			'사용자에 대해 액세스 거부가 수신되면 해당 사용자는 포털과 즉시 연결이 끊어집니다. ' .
			'재 인증을 사용하려면 사용자가 로그인 한 상태에서 포털 데이터베이스에 사용자 자격 증명을 캐시해야합니다. ' .
			'캐시 된 자격 증명은 포털이 자동 재 인증 요청을 수행하는 데 필요합니다.');

$section->addInput(new Form_Checkbox(
	'radmac_enable',
	'RADIUS MAC Authentication',
	'Enable RADIUS MAC authentication',
	$pconfig['radmac_enable']
))->setHelp('활성화 된 경우, 종속 포털은 사용자의 MAC 주소를 아래에 입력 한 사용자 이름과 암호로 RADIUS 서버에 보내 사용자를 인증하려고 시도합니다.');

$section->addInput(new Form_Input(
	'radmac_secret',
	'MAC authentication secret',
	'text',
	$pconfig['radmac_secret']
));

$section->addInput(new Form_Select(
	'radiussrcip_attribute',
	'RADIUS NAS IP Attribute',
	$pconfig['radiussrcip_attribute'],
	build_radiusnas_list()
))->setHelp('호출 스테이션 속성에 사용할 IP를 선택하십시오.');

$section->addInput(new Form_Checkbox(
	'radiussession_timeout',
	'Session timeout',
	'Use RADIUS Session-Timeout attributes',
	$pconfig['radiussession_timeout']
))->setHelp('활성화 된 경우, RADIUS 세션 - 시간 초과 속성에서 설정한 시간이 지나면 클라이언트의 연결이 끊어집니다.');

$section->addInput(new Form_Select(
	'radiusvendor',
	'Type',
	$pconfig['radiusvendor'],
	['default' => gettext('default'), 'cisco' => 'cisco']
))->setHelp('RADIUS 유형이 Cisco로 설정된 경우, Access-Requests에서 Calling-Station-ID의 값은 클라이언트의 IP 주소로 설정되고 Called-Station-Id는 클라이언트의 MAC 주소로 설정됩니다.' .
			'기본 동작은 Calling-Station-Id = 클라이언트의 MAC 주소 및 호출 된 스테이션 ID = pfSense의 WAN IP 주소입니다.');

$section->addInput(new Form_Checkbox(
	'reverseacct',
	'Accounting style',
	'Invert Acct-Input-Octets and Acct-Output-Octets',
	$pconfig['reverseacct']
))->setHelp('활성화 된 경우, RADIUS 계정 패킷의 데이터 수는 NAS가 아닌 클라이언트 관점에서 취합니다. ' .
			'Acct-Input-Octets은 다운로드를 나타내고 Acct-Output-Octets은 업로드를 나타냅니다.');

$section->addInput(new Form_Checkbox(
	'includeidletime',
	'Idle time accounting',
	'Include idle time in session time',
	$pconfig['includeidletime']
))->setHelp('활성화 된 경우, 유휴 시간을 초과하여 클라이언트의 연결이 끊어지면 유휴 시간이 전체 세션 시간에 포함됩니다. ' .
			'그렇지 않은 경우 RADIUS 서버에 보고 된 세션 시간은 세션이 시작된 시간과 마지막 작업이 기록 된 시간 사이의 값입니다.');

$section->addInput(new Form_Input(
	'radiusnasid',
	'NAS Identifier',
	'text',
	$pconfig['radiusnasid']
))->setHelp('NAS 식별자를 지정하여 기본값을 무시 (pfSense.localdomain)');

$section->addInput(new Form_Select(
	'radmac_format',
	'MAC address format',
	$pconfig['radmac_format'],
	['default' => 'Default', 'singledash' => gettext('Single dash'), 'ietf' => 'IETF', 'cisco' => 'Cisco', 'unformatted' => gettext('Unformatted')]
))->setHelp('이 옵션은 전체 RADIUS 시스템에서 사용되는 MAC 주소 형식을 변경합니다. 사용자 이름 형식도 변경해야하는 경우 변경하십시오. ' .
			'RADIUS MAC authentication. %1$s' .
			'Default: 00:11:22:33:44:55 %1$s' .
			'Single dash: 001122-334455 %1$s' .
			'IETF: 00-11-22-33-44-55 %1$s' .
			'Cisco: 0011.2233.4455 %1$s' .
			'Unformatted: 001122334455', '<br />');

$form->add($section);

$section = new Form_Section('HTTPS Options');
$section->addClass('HTTPS');

$section->addInput(new Form_Checkbox(
	'httpslogin_enable',
	'Login',
	'Enable HTTPS login',
	$pconfig['httpslogin_enable']
))->setHelp('사용하도록 설정하면 사용자 이름과 비밀번호를 보호하기 위해 HTTPS 연결을 통해 전송됩니다 ' .
			'서버 이름과 인증서도 아래에 지정해야합니다.');

$section->addInput(new Form_Input(
	'httpsname',
	'*HTTPS server name',
	'text',
	$pconfig['httpsname']
))->setHelp('이 이름은 HTTPS POST에 대한 양식 작업에 사용되며 인증서의 일반 이름 (CN)과 일치해야합니다. ' .
			'(그렇지 않으면 클라이언트 브라우저가 보안 경고를 표시 할 가능성이 큽니다.). ' .
			'종속 포털 클라이언트가 DNS에서 이름을 확인할 수 있고 클라이언트가 IP가 pfSense의 올바른 인터페이스 IP로 확인되는지 혹은 확인할 수 있는지 확인하십시오.');

$section->addInput(new Form_Select(
	'certref',
	'*SSL Certificate',
	$pconfig['certref'],
	build_cert_list()
))->setHelp('정의된 인증서가 없으면, 여기서 인증할 수 있습니다: %1$sSystem &gt; Cert. Manager%2$s', '<a href="system_certmanager.php">', '</a>');

$section->addInput(new Form_Checkbox(
	'nohttpsforwards',
	'HTTPS Forwards',
	'Disable HTTPS Forwards',
	$pconfig['nohttpsforwards']
))->setHelp('이 옵션을 설정하면 SSL / HTTPS (포트 443) 사이트에 연결하려는 시도가 종속 포털로 전달되지 않습니다. ' .
			'이렇게하면, HTTPS 로그인을 사용할 수있는 경우에도 인증서 오류가 사용자에게 표시되지 않습니다. ' .
			'즉, 사용자는 종속 포털로 전달하기 위해 HTTP (포트 80) 사이트에 연결을 시도해야합니다. ' .
			'HTTPS 로그인을 사용하면 사용자가 HTTPS 로그인 페이지로 리디렉션됩니다.');

$form->add($section);

$section = new Form_Section('HTML Page Contents');
$section->addClass('HTML');

$section->addInput(new Form_Input(
	'htmlfile',
	'Portal page contents',
	'file',
	$pconfig['htmlfile']
))->setHelp('포털 페이지에 대한 HTML / PHP 파일을 여기에 업로드하십시오. (현재 페이지를 유지하려면 비워 두십시오) ' .
			'제출 버튼 (name = "accept")과 name = "redirurl" 및 value = "$PORTAL_REDIRURL$ 인 숨겨진 필드를 포함한 양식 (POST를 "$PORTAL_ACTION$"에 포함)을 포함 시키십시오. ' .
			'"auth_user "및" auth_pass "및 / 또는"auth_voucher "입력 필드를 사용해야합니다. 그렇지 않으면 항상 실패합니다.%1$s' .
			'양식 예제 코드: %1$s' .
			'&lt;form method=&quot;post&quot; action=&quot;$PORTAL_ACTION$&quot;&gt;%1$s' .
			'&nbsp;&nbsp;&nbsp;&lt;input name=&quot;auth_user&quot; type=&quot;text&quot;&gt;%1$s' .
			'&nbsp;&nbsp;&nbsp;&lt;input name=&quot;auth_pass&quot; type=&quot;password&quot;&gt;%1$s' .
			'&nbsp;&nbsp;&nbsp;&lt;input name=&quot;auth_voucher&quot; type=&quot;text&quot;&gt;%1$s' .
			'&nbsp;&nbsp;&nbsp;&lt;input name=&quot;redirurl&quot; type=&quot;hidden&quot; value=&quot;$PORTAL_REDIRURL$&quot;&gt;%1$s' .
			'&nbsp;&nbsp;&nbsp;&lt;input name=&quot;zone&quot; type=&quot;hidden&quot; value=&quot;$PORTAL_ZONE$&quot;&gt;%1$s' .
			'&nbsp;&nbsp;&nbsp;&lt;input name=&quot;accept&quot; type=&quot;submit&quot; value=&quot;Continue&quot;&gt;%1$s' .
			'&lt;/form&gt;', '<br />')->addClass('btn btn-info btn-sm');

list($host) = explode(":", $_SERVER['HTTP_HOST']);
$zoneid = $pconfig['zoneid'] ? $pconfig['zoneid'] : 8000;
if ($pconfig['httpslogin_enable']) {
	$port = $pconfig['listenporthttps'] ? $pconfig['listenporthttps'] : ($zoneid + 8001);
	$href = "https://{$host}:{$port}/?zone={$cpzone}";
} else {
	$port = $pconfig['listenporthttp'] ? $pconfig['listenporthttp'] : ($zoneid + 8000);
	$href = "http://{$host}:{$port}/?zone={$cpzone}";
}

if ($pconfig['page']['htmltext']) {
	$group = new Form_Group('Current Portal Page');
	$group->add(new Form_Button(
		'btnliveview',
		'Live View',
		$href,
		'fa-file-text-o'
	))->addClass('btn btn-info btn-xs')->setAttribute("target", "_blank");

	$group->add(new Form_Button(
		'btnview',
		'View Page Contents',
		'?zone=' . $cpzone . '&act=viewhtml',
		'fa-file-text-o'
	))->addClass('btn btn-info btn-xs')->setAttribute("target", "_blank");

	$group->add(new Form_Button(
		'btndownload',
		'Download',
		'?zone=' . $cpzone . '&act=gethtmlhtml',
		'fa-download'
	))->addClass('btn btn-primary btn-xs')->setAttribute("target", "_blank");

	$group->add(new Form_Button(
		'btndownload',
		'Restore Default Page',
		'?zone=' . $cpzone . '&act=delhtmlhtml',
		'fa-undo'
	))->addClass('btn btn-danger btn-xs')->setAttribute("target", "_blank");
	$section->add($group);
}

$section->addInput(new Form_Input(
	'errfile',
	'Auth error page contents',
	'file',
	$pconfig['errfile']
))->setHelp('여기에 업로드 된 HTML / PHP 파일의 내용은 인증 오류가 발생할 때 표시됩니다. ' .
			'"$PORTAL_MESSAGE$"이(가) 포함될 수 있으며 RADIUS 서버(있는 경우)의 오류 또는 회신 메시지로 대체됩니다. ')->addClass('btn btn-info btn-sm');

if ($pconfig['page']['errtext']) {
	$group = new Form_Group('Current Auth Error Page');
	$group->add(new Form_Button(
		'btnview',
		'View Page Contents',
		'?zone=' . $cpzone . '&act=viewerrhtml',
		'fa-file-text-o'
	))->addClass('btn btn-info btn-xs')->setAttribute("target", "_blank");

	$group->add(new Form_Button(
		'btndownload',
		'Download',
		'?zone=' . $cpzone . '&act=geterrhtml',
		'fa-download'
	))->addClass('btn btn-primary btn-xs')->setAttribute("target", "_blank");

	$group->add(new Form_Button(
		'btndownload',
		'Restore Default Page',
		'?zone=' . $cpzone . '&act=delerrhtml',
		'fa-undo'
	))->addClass('btn btn-danger btn-xs')->setAttribute("target", "_blank");
	$section->add($group);
}

$section->addInput(new Form_Input(
	'logoutfile',
	'Logout page contents',
	'file',
	$pconfig['logoutfile']
))->setHelp('여기에 업로드 된 HTML / PHP 파일의 내용은 로그 아웃 팝업이 활성화되었을 때 인증 성공시 표시됩니다.')->addClass('btn btn-info btn-sm');

if ($pconfig['page']['logouttext']) {
	$group = new Form_Group('Current Logout Page');
	$group->add(new Form_Button(
		'btnview',
		'View Page Contents',
		'?zone=' . $cpzone . '&act=viewlogouthtml',
		'fa-file-text-o'
	))->addClass('btn btn-info btn-xs')->setAttribute("target", "_blank");

	$group->add(new Form_Button(
		'btndownload',
		'Download',
		'?zone=' . $cpzone . '&act=getlogouthtml',
		'fa-download'
	))->addClass('btn btn-primary btn-xs')->setAttribute("target", "_blank");

	$group->add(new Form_Button(
		'btndownload',
		'Restore Default Page',
		'?zone=' . $cpzone . '&act=dellogouthtml',
		'fa-undo'
	))->addClass('btn btn-danger btn-xs')->setAttribute("target", "_blank");
	$section->add($group);
}
$section->addInput(new Form_Input(
	'zone',
	null,
	'hidden',
	$cpzone
));

$form->add($section);
print($form);

print_info_box(gettext('포털 인터페이스에서 DHCP 서버를 활성화하는 것을 잊지 마십시오! ' .
					   '기본 / 최대 DHCP 임대 시간이 페이지에 입력 된 제한 시간보다 높은지 확인하십시오. ' .
					   '또한 DNS 전달자 또는 확인자가 인증되지 않은 클라이언트의 DNS 조회를 사용하도록 설정되어 있어야합니다.'));

?>

<script type="text/javascript">
//<![CDATA[
events.push(function() {

	// ------- Show/hide sections based on checkbox settings --------------------------------------
	function hideSections(hide) {
		hideClass('Authentication', hide);
		hideRadius();
		hideHTTPS();
		hideClass('HTTPS', hide);
		hideClass('HTML', hide);
		hideGeneral(hide)
	}

	function hideRadius() {
		hide = (!$('#enable').prop('checked') || (!($('input[name="auth_method"]:checked').val() == 'radius')));

		hideClass('Primary', hide);
		hideClass('Secondary', hide);
		hideClass('Accounting', hide);
		hideClass('Radius', hide);

		disableInput('localauth_priv', !($('input[name="auth_method"]:checked').val() == 'local'));
		hideCheckbox('localauth_priv', !($('input[name="auth_method"]:checked').val() == 'local'));
		hideClass("radiusproto", !($('input[name="auth_method"]:checked').val() == 'radius'));
	}

	function hideHTTPS() {
		hide = (!$('#httpslogin_enable').prop('checked') || !$('#enable').prop('checked'));

		hideInput('httpsname', hide);
		hideInput('certref', hide);
		hideCheckbox('nohttpsforwards', hide);
	}

	function hideGeneral(hide) {
		hideMultiClass('general', hide);
		hideInput('maxprocperip', hide);
		hideInput('idletimeout', hide);
		hideInput('timeout', hide);
		hideInput('freelogins_count', hide);
		hideInput('freelogins_resettimeout', hide);
		hideCheckbox('freelogins_updatetimeouts', hide);
		hideCheckbox('logoutwin_enable', hide);
		hideInput('preauthurl', hide);
		hideInput('redirurl', hide);
		hideInput('blockedmacsurl', hide);
		hideCheckbox('noconcurrentlogins', hide);
		hideCheckbox('nomacfilter', hide);
		hideCheckbox('passthrumacadd', hide);
		hideCheckbox('passthrumacaddusername', hide);
		hideCheckbox('peruserbw', hide);
		hideInput('bwdefaultdn', hide);
		hideInput('bwdefaultup', hide);
	}

	// ---------- Click checkbox handlers ---------------------------------------------------------
	$("#enable").click(function() {
		hideSections(!this.checked);
	});

	$('input[name="auth_method"]').on('change', function() {
		hideRadius();
	});


	$("#httpslogin_enable").click(function() {
		hideHTTPS(!this.checked);
	});

	// ---------- On initial page load ------------------------------------------------------------
	hideSections(!$('#enable').prop('checked'));
	disableInput('localauth_priv', !($('input[name="auth_method"]:checked').val() == 'local'));
});
//]]>
</script>

<?php include("foot.inc");

<?

namespace Xapi;

function Halt($E) {
	header('HTTP/1.0 400 '.$E);
	echo $E;
	exit;
}

//
//	Validation

if (empty($_GET['api'])) Halt('No api');
$Xapi = $_GET['api']; unset($_GET['api']);
if (count($Xapi) != 2) Halt('Bad api');
if (empty($Xapi['kind'])) Halt('No kind');
if (empty($Xapi['path'])) Halt('No path');
$Xapi['path'] = trim($Xapi['path'], '/ ');
if ((empty($Xapi['path'])) || (strpos($Xapi['path'], '..'))) Halt('Bad path');

//
//	Routines

function Rident(&$S, &$I) {
	$P = strrpos($S, '/');
	if ($P) {
		$I = substr($S, $P + 1);
		$S = substr($S, 0, $P);
	} elseif (empty($S)) return false; else {
		$I = $S;
		$S = '';
	}
	return true;
}

function Instance($Kind, $Path, $Module) {
	$File = $Kind.'/';
	if (!empty($Path)) $File .= $Path.'/';
	$File .= $Module.'.php';
	if (!file_exists($File)) Halt('File ['.htmlspecialchars($File).'] does not exists');
	include 'api/'.$File;
	$Module = 'X'.$Module;
	if (!class_exists($Module)) Halt('Class ['.htmlspecialchars($Module).'] does not exists');
	return new $Module();
}

//
//	Handlers

function DoData($Path, &$Output) {
	if (!Rident($Path, $Module)) Halt('No module');
	if (ctype_digit($Module)) {
		$Recoid = $Module;
		if (!Rident($Path, $Module)) Halt('No module');
	}
	$Method = $_SERVER['REQUEST_METHOD'];
/*  Cache lookup
	if (($Method == 'GET') && empty($Recid) && file_exists('../cache'.$Path.'/'.$Module.'.json')) {
		echo '{"success":true,"data":'.file_get_contents('../cad'.$Path.'/'.$Module.'.json').'}';
		exit;
	}
*/	
	if (file_exists('data'.$Path.'/'.$Module.'/default.php')) {
		$Path .= $Module;
		$Module = 'default';
	}
	include 'lib/waxapi/xrest.php';
	$Object = Instance('data', $Path, $Module);
	$Object->Blink = $GLOBALS['BC']; // BASE_LINK
	if (isset($Recoid)) $Object->Recoid = $Recoid;
	if (method_exists($Object, 'Initialize')) {
		$Result = $Object->Initialize($Output);
		if (!isset($Result)) $Result = true;
		if (!$Result) return $Result;
	}
	switch ($Method) {
		case 'GET':
			$Result = $Object->Select($Output);
			break;
		case 'POST':
			$Result = $Object->Insert($Output);
			break;
		case 'PUT':
			$Result = $Object->Update($Output);
			break;
		case 'DELETE':
			$Result = $Object->Delete($Output);
			break;
		default: Halt('Bad request');
	}
	return $Result;
}

function DoAjax($Path, &$Output) {
	if (!Rident($Path, $Method)) Halt('No module');
	if (!Rident($Path, $Module)) Halt('No method');
	$Object = Instance('ajax', $Path, $Module);
	if (!method_exists($Object, $Method)) Halt('Method ['.htmlspecialchars($Method).'] does not exists');
	return $Object->{$Method}($Output);
}

function DoForm($Path, &$Output) {
	if (!Rident($Path, $Module)) Halt('No module');
	$Object = Instance('form', $Path, $Module);
	return $Object->Execute($Output);
}

//
//	Router

switch ($Xapi['kind']) {
	case 'data':
		$Result = DoData($Xapi['path'], $Output);
		break;
	case 'ajax': 
		$Result = DoAjax($Xapi['path'], $Output);
		break;
	case 'form':
		$Result = DoForm($Xapi['path'], $Output);
		break;
	default : Halt('Bad kind');
}

if (!isset($Result)) $Result = true;
$Result = array('success' => $Result);
if (isset($Output)) $Result = array_merge($Result, $Output);
echo json_encode($Result, JSON_UNESCAPED_UNICODE);

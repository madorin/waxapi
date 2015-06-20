<?

abstract class XRest {

public $Blink;
public $Recid;

private function Query(&$H) {
	$Q = DB\Query($H['query'], isset($H['params']) ? $H['params'] : NULL, IBASE_TEXT);
	return $Q;
}

public function Count(&$O) {
	if (!method_exists($this, 'ToCount')) return;
	$this->ToCount($H);
	$Q = $this->Query($H);
	$C = $Q->Fetchra();
	if (isset($C['total'])) $O['total'] = $C['total'];
}

protected function ToSelect(&$H, &$O) {}

public function Select(&$O) {
	if (empty($this->Recid)) $this->Count($O);
	$R = $this->ToSelect($H, $O);
	if (!isset($R)) $R = true;
	if (!$R || !empty($H['break'])) return $R;
	if (!$Q = $this->Query($H)) return false;
	$O['data'] = array();
	$F = &$Q->Field;
	while ($Q->NextAssoc())
		$O['data'][] = $F;
}

protected function ToInsert(&$H, &$O) {}

public function Insert(&$O) {
	$R = $this->ToInsert($H, $O);
	if (!isset($R)) $R = true;
	if (!$R || !empty($H['break'])) return $R;
	return $this->Query($H) ? true : false;
}

protected function ToUpdate(&$H, &$O) {}

public function Update(&$O) {
	$R = $this->ToUpdate($H, $O);
	if (!isset($R)) $R = true;
	if (!$R || !empty($H['break'])) return $R;
	return $this->Query($H) ? true : false;
}

protected function ToDelete(&$H, &$O) {}

public function Delete(&$O) {
	$R = $this->ToDelete($H, $O);
	if (!isset($R)) $R = true;
	if (!$R || !empty($H['break'])) return $R;
	return $this->Query($H) ? true : false;
}

}

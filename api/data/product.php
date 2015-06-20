<?php

class XProduct extends XRest {

public function ToSelect(&$H, &$O) {
	$H['query'] = 'select P.ID, P.NAME from PRODUCTS P';
}

}
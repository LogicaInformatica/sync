<?php
class Template {
	const clear_pattern = '/%[A-Z_]+%/';

	public static $base_path = '';
	
	private $tmpl;
	private $txt;
	
	function __construct($path, array $sost = NULL) {
		$this->tmpl = @file_get_contents(self::$base_path.$path);
		if ($this->tmpl === FALSE)
			$this->tmpl = '';
		else
			if (!is_null($sost)) {
				$this->tmpl = str_replace(array_keys($sost),array_values($sost),$this->tmpl);
			}
	}
	
	function replace(array $sost, $clear_others = true) {
		$this->txt = str_replace(array_keys($sost),array_values($sost),$this->tmpl);
		
		if ($clear_others) {
			$this->txt = preg_replace(self::clear_pattern, '', $this->txt);
		}
		return $this->txt;
	}

	function placeholders() {
		preg_match_all(self::clear_pattern, $this->tmpl, $placeholders);
		return $placeholders;
	}

	function getTemplate() {
		return $this->tmpl;
	}

	function getText() {
		return $this->txt;
	}
}

Template::$base_path = 'C:/Progetti/IPhone/geomago/Comuni/Templates/';
$test = new Template('altro.htm'); //,array('%BASE%' => 'b_a_s_e'));
print_r($test->placeholders());

echo $test->replace(array('%NOME_DOPPIO%' => 'Roccella ionica'));
echo $test->replace(array('%NOME_DOPPIO%' => 'Roma'), false);
?>
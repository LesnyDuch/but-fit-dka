#!/usr/bin/php
<?php
#DKA:xorsza00
/* Pole obsahujuce mozne argumenty,
 * sluzi na zjednodusenie parsovania
 */
$arguments = array(
		'input'	=> FALSE,
		'i_addr' => '',
		'output'=> FALSE,
		'o_addr' => '',
		'help' => FALSE,
		'no-eps'=> FALSE,
		'determ'=> FALSE,
		'casein'=> FALSE
		);

/* Trieda popisujuca konecny automat
 */
class Fsm {
	
	public $states = array();
	public $alphabet = array();
	public $rules = array();
	public $begin = array();
	public $finish = array();

	public $eUz = array();

	/* Inicializacia pola pravidiel
	 */
	function init(){
		$this->rules[0] = array();
		$this->rules[1] = array();
		$this->rules[2] = array();
	}

	/* Vypis automatu
	 */
	function print_fsm() {
		print_r($this->states);
		print_r($this->alphabet);
		print_r($this->rules);
		print_r($this->begin);
		print_r($this->finish);
	}

	/* Metoda vracajuca jedno pravidlo v poli
	 */
	function getRule($id) {
		$tmp = array (
				$this->rules[0][$id],
				$this->rules[1][$id],
				$this->rules[2][$id]
			);
		return $tmp;
	}

	/* Pomocna metoda na vlozenie pravidla
	 */
	function makeRule($a,$b,$c) {
		array_push($this->rules[0], $a);
		array_push($this->rules[1], $b);
		array_push($this->rules[2], $c);
	}

	/* Pomocna metoda na vytvorenie pravidla
	 */
	function addState($state) {
		array_push($this->states,$state);
	}

}

$input = new Fsm();
$input->init();
$output = new Fsm();
$output->init();

/* Funkcia parsujuca argumenty z prikazovej riadky
 */
function ParseArguments($argc, $argv) {
	global $arguments;	

	/* Cyklus, ktory prechadza mnozinou argumentov a pomocou pola $arguments
	 * ich spracovava
	 */
	for ($i = 1; $i < $argc; $i++) {
		if ($arguments['help']) {
			exit(1);
		}

		# Help
		if ($argv[$i] === '--help') {
			if ($arguments['help'] === TRUE) 
				exit(1);
			$arguments['help'] = TRUE;
			}

		# Determinizacia
		else if (($argv[$i] === '-d') || ($argv[$i] === '--determinization')) {
			if (($arguments['no-eps'] === TRUE) || ($arguments['determ'] === TRUE)) 
				exit(1);
			$arguments['determ'] = TRUE;
		}

		else if (($argv[$i] === '-e') || ($argv[$i] === '--no-epsilon-rules')) {
			if (($arguments['no-eps'] === TRUE) || ($arguments['determ'] === TRUE)) 
				exit(1);
			$arguments['no-eps'] = TRUE;
		}

		else if (($argv[$i] === '-i') || ($argv[$i] === '--case-insensitive')) {
			if ($arguments['casein'] === TRUE) 
				exit(1);;
			$arguments['casein'] = TRUE;
		}
			
		# Spracovanie argumentu input
		else if (substr($argv[$i], 0, 7) === '--input')  {
			if ($arguments['input'] === TRUE) {
				exit(1);
			}
			$arguments['input'] = TRUE;
			$arguments['i_addr'] = substr($argv[$i], 8);
		}

		# Spracovanie argumentu output
		else if (substr($argv[$i], 0, 8) === '--output') {
			if ($arguments['output'] === TRUE) {
				exit(1);
			}
			$arguments['output'] = TRUE;
			$arguments['o_addr'] = substr($argv[$i], 9);
			printf("%s\n", $arguments['output'] );
		}
		else 
			exit(1);
	}
}

/* Funkcia, ktora spracovava a konstroluje vstupny subor
 */
function ParseSource() {
	global $arguments;
	global $input;
	global $source;
	
	# Najprv si odstranim komentare 
	$comment = FALSE;
	$tmp_var = '';
	
	for($i = 0; $i < strlen($source); $i++) {
		if (($source[$i] === '#') && ($i > 0)) {
			if (($source[$i-1] !== '\'') && ($source[$i-1] !== '\'')) {
				$comment = TRUE;
			}
		} 
		else if ($source[$i] === '#') {
			$comment = TRUE;
		} 
		if ($source[$i] === "\n") {
			$comment = FALSE;
		}
		if ($comment === FALSE) {
			$tmp_var .= $source[$i];
		}
	}
	$source = $tmp_var;


	# Nasledne si whitespaces premenim na medzery pre zjednodusenie
	$source = preg_replace('/\'\t\'/u', 'TAB_IN_TRANSIT', $source);
	$source = preg_replace('/\'\n\'/u', 'NL_IN_TRANSIT', $source);

	$whitespaces = '/\s+/u';
	$source = preg_replace($whitespaces, ' ', $source);

	$source = preg_replace('/TAB_IN_TRANSIT/u', '	', $source);
	$source = preg_replace('/NL_IN_TRANSIT/u', "\n", $source);



	$i = 0;
	while ($source[$i] != '(') {
		if ($source[$i] !== ' ') {
			exit(40);
		}
		$i++;
		if ($i+2 >= strlen($source)) {
			exit(40);
		}
	}
	$i++;

	$pattern = '/(\s*{.*}\s*,\s*{.*}\s*,\s*{.*}\s*,\s*\w+\s*,\s*{.*}\s*)\s*/u';
	if (!preg_match($pattern, $source)) {

		exit(40);
	}


	#---------------------------------- STAVY ---------------------------------#
	# Stavy // TOTO ZMEN NA IBA WHITESPACES	
	while ($source[$i] !== '{') {
		if ($source[$i] !== ' ') {
			exit(40);
		}
		$i++;
		if ($i+2 === strlen($source)) {
			exit(40);
		}
	}

	$b = $i+1;
	while ($source[$i] !== '}') {
		$i++;
		if ($i+2 === strlen($source)) {
			exit(40);
		}

	}
	$e = $i;

	$states = substr($source, $b, $e-$b);

	unset($matches);
	$matches = array();

	$pattern = '/\w+/u';
	preg_match_all($pattern,$states,$matches);
	$input->states = $matches[0];

	$states = preg_replace($pattern, '', $states);
	$states = preg_replace('/(,)*|( )/u', '', $states);

	if ( strlen($states) > 0 ) {
		exit(40);
	}

	if (empty($input->states)) {
		exit(41);
	}

	#--------------------------------- ABECEDA --------------------------------#

	$i++;
	$comma = FALSE;
	while ($source[$i] !== '{') {
		if (($source[$i] !== ',') && ($source[$i] !== ' ')) {
			exit(40);
		}
		if ($source[$i] === ',') {
			if ($comma === TRUE) {
				echo "Chyba -ABECEDA\n";
			}
			else {
				$comma = TRUE;
			}
		}
		$i++;
		if ($i+2 === strlen($source)) {
			exit(40);
		}
	}
	$b = $i+1;
	for ($i; $i <= strlen($source); $i++) {
		if ($i+2 === strlen($source)) {
			exit(40);
		}
		if ($source[$i] === '}') {
			if (($source[$i-1] !== '\'') || ($source[$i+1] !== '\'')) {
				$e = $i - $b;
				break;
			}
		}
	}
	$states = substr($source, $b, $e);

	unset($matches);
	$matches = array();

	$pattern = '/(\'\'\'\')|(\'.\')/u';
	preg_match_all($pattern, $states, $matches);
	$input->alphabet = $matches[0];

	# k vstupnej abecede si natvrdo vlozim prazdny retazec
	$input->alphabet[count($input->alphabet)] = '';

	#-------------------------------- PRAVIDLA -------------------------------#
	$i++;
	$comma = FALSE;
	while ($source[$i] !== '{') {
		if (($source[$i] !== ',') && ($source[$i] !== ' ')) {
			exit(40);
		}

		if ($source[$i] === ',') {
			if ($comma === TRUE) {
				exit(40);
			}
			else {
				$comma = TRUE;
			}
		}
		$i++;
		if ($i+2 === strlen($source)) {
			exit(40);
		}
	}
	$b = $i+1;
	

	for ($i; $i <= strlen($source); $i++) {
		if ($source[$i] === '}') {
			if (($source[$i-1] !== '\'') ||($source[$i+1] !== '\'')) {
				break;
			}
		}
	}
	$e = $i;


	$rules = substr($source, $b, $e-$b);

	# Vytiahnem si pravidla z popisu
	$pattern = '/(\w+\s*\'.\'\s*->\s*\w+)|(\w+\s*\'\'\'\'\s*->\s*\w+)|(\w+\s*\'\'\s*->\s*\w+)/u';
	preg_match_all($pattern, $rules, $matches);
	$rules = $matches[0];

	foreach ($rules as $rule) {
		$rule=preg_replace('/ /', '', $rule);
	}
	
	# Vstupny stav
	unset($matches);
	$matches = array();	
	$i = 0;
	foreach ($rules as $rule) {
		preg_match('/^\w+/', $rule, $matches[$i]);
		$i++;
	}
	$tmp = count($matches);
	for ($i = 0;$i < $tmp; $i++) {
		if (empty($matches[$i])) {
			unset($matches[$i]);
		}
	}



	//print_r($matches);
	if (!empty($matches)) {
		for ($i = 0; $i<count($matches);$i++) {
			$input->rules[0][$i] = $matches[$i][0];
		}

	}	

	# Prechod
	unset($matches);
	$matches = array();

	$i=0;
	$pattern = '/(?:\').(?:\')/u';
	$pattern = '/(\'\'\'\')|(\'.\')/u';
	foreach ($rules as $rule) {
		preg_match($pattern, $rule, $matches[$i]);
		$i++;
	}

	for ($i = 0; $i<count($matches);$i++) {
		if (!empty($matches[$i])) {
			$input->rules[1][$i] = $matches[$i][0];
		}
		else {
			$input->rules[1][$i] = '';
		}
	}

	# Vystupny stav
	unset($matches);
	$matches = array();

	$i=0;
	$pattern = '/\w+$/';

	foreach ($rules as $rule) {
		preg_match($pattern, $rule, $matches[$i]);
		$i++;
	}

	for ($i = 0; $i < count($matches); $i++) {
		$input->rules[2][$i] = $matches[$i][0];
	}



	# KONTROLA PRAVIDIEL A ABECEDY

	foreach ($input->rules[0] as $state) {
		if (!in_array($state, $input->states)) {
			exit(41);
		}
	}

	foreach ($input->rules[2] as $state) {
		if (!in_array($state, $input->states)) {
			exit(41);
		}
	}

	foreach ($input->rules[1] as $state) {
		if (!in_array($state, $input->alphabet)) {
			exit(41);
		}
	}



	#---------------------------------- Pociatocny stav --------------------------------#

	$comma = 0;
	$i = $e+1; 

	

	while ($source[$i] !== '{') {
		if ($source[$i] === ',') {
			if ($comma === 3) {
				exit(40);
			}
			else {
				$comma += 1;
			}
		}
		$i++;
		if ($i+2 === strlen($source)) {
			exit(40);
		}
	}
	$e++;

	$bgn = substr($source, $e, $i-$e);
	
	unset($matches);
	$matches = array();

	$pattern = '/\w+/u';
	preg_match($pattern, $bgn, $matches);
	
	$found = FALSE;
	$input->begin = $matches[0];
	
	# kontrola
	if (!in_array($input->begin, $input->states)) 
		exit(41);


	#---------------------------------- Koncove stavy ----------------------------------#

	while ($source[$i] !== '{') {
		if (($source[$i] !== ',') && ($source[$i] !== ' ')) {
			//echo $source[$i];
			exit(40);
		
		}
		$i++;
		if ($i+2 === strlen($source)) {
			exit(40);
		}
	}
	$b = $i+1;

	while ($source[$i] !== '}') {
		$i++;
		if ($i+1 === strlen($source)) {
			exit(40);
		}
	}
	$e = $i;

	$states = substr($source, $b, $e-$b);

	$invalid_pattern = '/([^a-zA-Z0-9\s,])|([a-zA-Z0-9]+ [a-zA-Z0-9]+)|(,\s*,)/u';
	if (preg_match($invalid_pattern, $states)) {
		exit(40);
	}

	unset($matches);
	$matches = array();

	$pattern = '/\w+/u';
	preg_match_all($pattern,$states,$matches);
	$input->finish = $matches[0];
	foreach ($input->finish as $state) {
		if (!in_array($state, $input->states)) {
			exit(41);
		}
	}

	$i++;
	while ($source[$i] !== ')') {
		if ($source[$i] !== ' ') {
			echo $source[$i];
			exit(40);
		
		}
		$i++;
		if ($i+1 === strlen($source)) {
			exit(40);
		}
	}

	$i++;

	while($i+1 <= strlen($source)) {
		if ($source[$i] !== ' ' ) {
			exit(40);
		}
		$i++;
	}


	if (empty($input->alphabet)) 
		exit(41);
}

/* Pomocna funkcia pre odstranovanie epsilon prechodov
 */
function findNext($original,$current) {
	global $input;
	# Osetrenie zacyklenia
	if (in_array($current, $input->eUz[$original])) 
		return;
	array_push($input->eUz[$original],$current);

	for($i = 0; $i < count($input->rules[0]); $i++) {
		$rule = $input->getRule($i);
		
		if((empty($rule[1])) && ($rule[0] === $current)) {
			findNext($original, $rule[2]);
		}
	}
}

/* Pomocna funkcia pre spracovanie pravidiel po nacitani 
 * zo suboru
 */
function fixRules($output){
	if(empty($output->rules)){
		return array();
	}
	$tmp_arr = array();
	for($i = 0; $i < count($output->rules[0]); $i++) {
		array_push($tmp_arr, $output->getRule($i));
	}
	$output->rules = $tmp_arr;
}

/* Pomocna funkcia sluziaca na zoradenie pravidiel podla
 * viacerych klucov
 */
function sortRules($output) {
	if(empty($output->rules)){
		return array();
	}


	$tmp_arr = $output->rules;
	$sort = array();
	foreach ($tmp_arr as $k=>$v) {
		$sort[0][$k] = $v[0];
		$sort[1][$k] = $v[1];
		$sort[2][$k] = $v[2];
	}

	array_multisort($sort[0], SORT_ASC, $sort[1], SORT_ASC, $sort[2], SORT_ASC, $tmp_arr);
	return $tmp_arr;
}

/* Funkcia na odstranenie epsilon prechodov
 */
function noEps(){
	global $input;
	global $output;

	# Najdem e-uzavery
	foreach ($input->states as $state) {
		$input->eUz[$state] = array();
		findNext($state,$state);
	}

	foreach ($input->states as $state) {
		foreach ($input->eUz[$state] as $eUz ) {
			for ($i=0; $i < count($input->rules[0]); $i++) {
				if(($input->rules[0][$i] === $eUz) && (!empty($input->rules[1][$i]) )) {
					$output->makeRule($state,$input->rules[1][$i],$input->rules[2][$i]);
				}
			}
		}		
	}

	$output->states = $input->states;
	$output->alphabet = $input->alphabet;
	$output->begin = $input->begin;
	$output->finish = $input->finish;
}

/* Funkcia sluziaca na determinizaciu konecneho automatu
 */
function determinize(){
	global $output;
	global $determ;

	$determ->begin = $output->begin;
	
	$Q_new = array();
	array_push($Q_new, array($determ->begin));

	$qq = array();
	$Qd = array();


	while (count($Q_new) !== 0) {

		$i = count($Q_new) - 1;
		$qq = (is_array($Q_new[$i]) ? $Q_new[$i] : array($Q_new[$i]));
		array_pop($Q_new);
		array_push($Qd, $qq);

		// print_r($Q_new);

		foreach ($output->alphabet as $a) {
			$qqq = array();
			
			# Vsetky stavy dostupne z aktualneho, cez a, zlucim do jedneho
			foreach ($output->rules as $rule) {
				foreach ($qq as $qq_) {
					if (($rule[1] === $a) && ($rule[0] === $qq_) && (!in_array($rule[2],$qqq))) {
						array_push($qqq, $rule[2]);	
					}
				}
			}
			
			# Vytvorim pravidlo
			if (!empty($qqq)) {
				$rule = array($qq, $a, $qqq);
				array_push($determ->rules, $rule);
			}
			
			# Vytvorim stav v novom automate
			if (!in_array($qqq, $Qd)) {
				array_push($Q_new, $qqq);
			}
		}

		foreach ($qq as $qq_) {

			if (in_array($qq_, $output->finish)) {
				array_push($determ->finish, $qq);
				break;
			}
		}
	

	}

	$Qd = array_filter($Qd);
	$Qd = array_values($Qd);


	for($i=0;$i<count($Qd);$i++) {
		sort($Qd[$i]);
		
		$Qd[$i] = implode('_', $Qd[$i]);
	}
	
	$determ->states = $Qd;

	$determ->rules = array_filter($determ->rules);
	$determ->rules = array_values($determ->rules);
	

	for($i = 0; $i < count($determ->rules); $i++){
		
		sort($determ->rules[$i][0]);
		sort($determ->rules[$i][2]);
		$determ->rules[$i][0] = implode('_',$determ->rules[$i][0]);
		$determ->rules[$i][2] = implode('_',$determ->rules[$i][2]);
	}

	$determ->rules = array_unique($determ->rules, SORT_REGULAR);
	
	for($i = 0; $i < count($determ->finish); $i++){
		$determ->finish[$i] = implode('_',$determ->finish[$i]);
	}

	$determ->alphabet = $output->alphabet;
}

/* Spracovanie automatu na normalny vystup a jeho ulozenie
 */
function saveFsm($output) {

	if (($key = array_search('', $output->alphabet)) !== false) {
    	unset($output->alphabet[$key]);
	}

	# Zaciatok
	$tmp_string ="(\n";
	$tmp_string .='{';
	
	# Stavy
	sort($output->states,SORT_LOCALE_STRING);
	$tmp_string .= implode(', ', $output->states);
	$tmp_string .= "},\n{";

	# Abeceda
	$tmp_string .= implode(', ', $output->alphabet);
	sort($output->alphabet,SORT_LOCALE_STRING);
	$tmp_string .= "},\n{\n";

	# Pravidla
	for ($i=0; $i < count($output->rules); $i++) {
		$rule = $output->rules[$i];
		if (empty($rule[1]))
			$rule[1] = '\'\'';
		if ($i === (count($output->rules) - 1)) {
			$tmp_string .= $rule[0] . ' ' . $rule[1] . " -> " . $rule[2]."\n";
		}
		else {
			$tmp_string .= $rule[0] . ' ' . $rule[1] . " -> " . $rule[2] . ",\n";
		}
	}
	
	# Koncove stavy
	$tmp_string .= "},\n" . $output->begin . ",\n{";
	sort($output->finish,SORT_LOCALE_STRING);
	$tmp_string .= implode(', ', $output->finish);
	
	# Koniec
	$tmp_string .= "}\n)";
	echo $tmp_string;
}

/* Help
 */
function help(){
	echo("dka.php - Determinizacia, alebo odstranenie epsilon prechodov konecneho automatu\n\n");
	echo("\t--input=filename\n\t\t\tvstupny textovy subor na adrese filename, ak nie je zadany, ocakava sa vstup z STDIN\n\n");
	echo("\t--output=filename\n\t\t\tvystupny textovy subor so spracovanym automatom, ak nie je zadany, vystup je vypisany na STDOUT\n\n");
	echo("\t-e, --no-epsilon-rules\n\t\t\todstranenie epsilon prechodov vstupneho automatu, nemozno kombinovat s -d\n\n");
	echo("\t-d, --determinization\n\t\t\tvykona determinizaciu vstupneho automatu bez generovania nedostupnych stavov, nemozno kombinovat s -e\n\n");
	echo("\tPozn.: Pokial nie je zadany ani jeden z prepinacov -d a -e, je vstupny automat validovany a prevedeny na normalny vystup\n\n");
	echo("\t-i, --case-insensitive\n\t\t\tneberie ohlad na velkost znakov, na vystupe su velke pismena prevedene na male\n\n");
}


ParseArguments($argc, $argv, $arguments);

if ($arguments['help'] === TRUE) {
	help();
	exit(0);
}

if ($arguments['input'] === TRUE) {
	if (!file_exists($arguments['i_addr'])){
		exit(2);
	}
	$source = file_get_contents($arguments['i_addr'], FILE_USE_INCLUDE_PATH);
	if ($source === FALSE) {
		exit(2);
	}
}

else {
	$source = '';
	while (($source .= fgetc(STDIN)) && (!feof(STDIN))) {

	}
}

if ($arguments['casein'] === TRUE) {
	$source = mb_strtolower($source, 'UTF-8');
}

ParseSource();

if ($arguments['no-eps'] === TRUE) {
	noEps();
	fixRules($output);
	$output->rules = sortRules($output);

}

if ($arguments['determ'] === TRUE) {
	noEps();
	fixRules($output);

	$determ = new Fsm();
	$determ->init();
	determinize();

	$determ->rules = sortRules($determ);

	saveFsm($determ);
	exit(0);
}

if ($arguments['output'] === TRUE) {
	fclose(STDOUT);
	if (!file_exists($arguments['o_addr'])){
		exit(3);
	}
	if ( ($STDOUT = fopen($arguments['o_addr'], 'w')) === FALSE)
		exit(3);
}
if ((!$arguments['no-eps']) && (!$arguments['determ'])) {
	fixRules($input);
	$input->rules = sortRules($input);
	saveFsm($input);
	exit(0);
} 
else saveFsm($output);

?>
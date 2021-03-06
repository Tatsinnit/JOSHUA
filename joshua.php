<?php // joshua engine <alexander@binaerpilot.no>
include 'inc.global.php';
if (!empty($_POST['command'])) $command = strtolower(strip_tags(trim($_POST['command'])));
if (!empty($_POST['option'])) $option = strip_tags(trim($_POST['option']));
if (!empty($_POST['dump'])) $dump = strip_tags(trim($_POST['dump']));
if (!empty($option) && $option == "undefined") unset($option);
if (!empty($dump) && $dump == "undefined") unset($dump);
if (isset($command) && isset($dump)) {
	$pos = strpos($dump, $command);
	if ($pos !== false) $input = substr_replace($dump, '', $pos, strlen($command));
}
if (empty($input)) unset($input);
unset($output);

// functions
function error($id, $inline=null) {
	global $error, $command, $prompt, $joshua;
	if (!$inline) print $prompt;
	print '<p class="joshua error">'.$joshua.$error[$id].'</p>';
	die();
}
function output($response) {
	global $output, $command, $option, $input, $prompt;
	if (stristr($response,'<p') || stristr($response,'<table')|| stristr($response,'<ul')) print $prompt.$response;
	else print $prompt.'<p>'.$response.'</p>';
	$output = 1;
}
function run($cmd, $opt=null) {
	$timeout = 10;
	return trim(utf8_encode(shell_exec('timeout '.$timeout.' '.$cmd.' '.$opt)));
}
function microtimer($timestamp) {
	return round(microtime(true)-$timestamp, 5);
}
function dbFile($file) {
	if (file_exists($file)) {
		$file = file($file);
		$data = array();
		$sep = '^';
		foreach ($file as $lineNum => $line) {
			if (!empty($line)) {
				if (strpos($line, $sep) !== false) $data[$lineNum] = explode($sep, trim($line));
				else $data[$lineNum] = trim($line);
			}
			else return false;
		}
		return $data;		
	}
	else error('localcache');
}
function implodeHuman($a, $command=false) {
	$last = array_pop($a); 
	if ($command) {
		if (!count($a)) return '<span class="command">'.$last.'</span>';
		return '<span class="command">'.implode('</span>, <span class="command">', $a).'</span> and <span class="command">'.$last.'</span>';
	}
	else {
		if (!count($a)) return $last;
		return implode(', ', $a).' and '.$last; 	
	}
}
function deleteCookie($cookie) {
	setcookie($cookie, '', time()-60*60*24*365, '/');
}
function deCamel($s) {
	return ucfirst(preg_replace( '/([a-z0-9])([A-Z])/', "$1 $2", $s));
}
function cakeDay($date) {
	$cake = (strtotime(date("Ymd")) > strtotime(date("Y/$date"))) ? strtotime(date("Y/$date", strtotime("+1 year"))) : strtotime(date("Y/$date"));
	return round(($cake-strtotime(date("Ymd")))/86400);
}

// errors	
$error = array(
	'404' => 'I couldn\'t find that.',
	'invalid' => 'I don\'t understand. Do you need some <span class="command">help</span>?',
	'blocked' => 'Invalid input. This shouldn\'t get triggered anymore so not sure what you\'re doing.',
	'notip' => 'That\'s not an IP. Regex never lies.',
	'notdomain' => 'You call that a domain name?',
	'noreturn' => 'Executing <span class="command">'.$command.'</span> on this system returned nothing. I am disappoint.',
	'timeout' => 'I\'m asking ever so nicely but the server did not respond.',
	'empty' => 'That API is not responding. Or I have been throttled. Either way it sucks.',
	'invalidxml' => 'API returned malformed XML. XML sucks.',
	'invalidjson' => 'API returned malformed JSON. How can you mess up JSON?',
	'invalidhtml' => 'API returned malformed HTML. Is there even such a thing as well-formed HTML?',
	'invalidrequest' => 'API threw an error. This is bad and I should feel bad.',
	'localcache' => 'Local cache does not exist. IT\'S GONE! ALL GONE!',
	'auth' => 'You are not authorized. Go away.',
	'password' => 'Wrong password.',
	'outdatedapi' => 'API returned something, but not what I expected. Probably needs an update.'
);

// prompt
if (!empty($command)) {
	$noReturn = array('sudo');
	if (!empty($input) and !in_array($command, $noReturn)) {
		$prompt = '<div class="prompt">'.$command.' <b>'.$input.'</b></div>';
	}
	else $prompt = '<div class="prompt">'.$command.'</div>';
}

// output
if (empty($output)) {
	// we need to load the brain
	include('brain.php');
	// quotes, bash
	if ($command == "bash" || $command == "quote" || $command == "quotes") {
		if ($command == "bash") $array = $bash;
		elseif ($command == "quote" || $command == "quotes") $array = $quotes;
		$count = count($array)-1; $rand = rand(0,$count);
		if (!empty($option) && $option == "all") {
			foreach($array as $quote) {
				if ($command == "bash") $quote = '<div class="pre">'.$quote.'</div>';
				output($quote);
			}
		}
		elseif (isset($option) && $option == "clean") {
			print $array[$rand]; $output = 1;
		}
		else {
			$quote = $array[$rand];
			if ($command == "bash") $quote = '<div class="pre">'.$quote.'</div>';
			output($quote);
		}
	}
	
	// motd 
	if ($command == "motd") {
		$count = count($motd)-1; $rand = rand(0,$count);
		if (isset($option) && $option == "inline") {
			print '<p class="dark motd">'.$motd[$rand].'</p><p class="joshua">'.$joshua.'Please enter <span class="command">help</span> for commands.</p>';
			$output = 1;
		}
		else {
			output($motd[$rand]);
		}
	}

	// uptime and date
	if ($command == "uptime" || $command == "date") {
		$return = run($command);
		if (!empty($return))	output($return);
		else error('noreturn');
	}

	// whois and ping
	if ($command == "whois" || $command == "ping") {
		if (isset($option)) {
			$pattern = "/^[a-zA-Z0-9._-]+\.[a-zA-Z.]{2,4}$/";
			if (preg_match($pattern, $option)) {
				if ($command == "ping") {
					$return = run('ping', '-c1 '.$option);
				}
				elseif ($command == "whois") {
					$return = run('whois', $option);
				}
				if (!empty($return)) {
					output('<pre>'.$return.'</pre>');
				}
				else error('noreturn');
			}
			else error('notdomain');
		}
		else output('<p class="error">'.$joshua.'I need a domain to lookup.</p><p class="example">'.$command.' binaerpilot.no</p>');
	}

	// prime number
	if ($command == "prime") {
		if (!empty($option)) {
			if (strlen($option) <= 5) {
				$i = 0; $unary = '';
				while($i++ < $option) {
					$unary = $unary.'1';
				}
				$pattern = '/^1?$|^(11+?)\1+$/';
				if (preg_match($pattern, $unary)) {
					output($option.' is not a prime number.');
				}
				else output($option.' is a prime number.');				
			}
			else output('<p class="error">'.$joshua.'The number is too damn high (for regex).</p>');
		}
		else output('<p class="error">'.$joshua.'You have to tell me a number.</p><p class="example">prime 13</p>');
	}

	// locate
	if ($command == "locate") {
		$lookup = 'http://api.hostip.info/get_html.php?position=true&ip=';
		if (!empty($option)) $ip = $option;
		else $ip = $_SERVER['REMOTE_ADDR'];
		if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
			$request = $lookup.$ip;
			$output = get($request);
			// google maps link
			$latitude = trim(array_shift(explode('Longitude', array_pop(explode('Latitude:', $output)))));
			$longitude = trim(array_shift(explode('IP', array_pop(explode('Longitude:', $output)))));
			if (!empty($latitude) && !empty($longitude)) {
				output('<pre>'.$output.'</pre><p><a class="external" href="http://maps.google.com/maps?q='.$latitude.'+'.$longitude.'">View at Google Maps.</a></p>');
			}
			else output('<pre>'.$output.'</pre>');
		}
		else error('notip');
	}

	// numbers
	if ($command == "numbers" || $command == "number") {
		$levels = count($numbers);
		if (empty($_SESSION['numbers'])) $_SESSION['numbers'] = 0;
		if (isset($option) && $option == "reset") {
			unset($_SESSION['numbers']);
			output('<p class="joshua">'.$joshua.'Game reset.</p>');
		}
		else if ($_SESSION['numbers'] == $levels) {
			output('<p class="joshua">'.$joshua.'You have beaten the game! Use <span class="command">idkfa</span> to list all the static keys.</p>');
		}
		else {
			if (empty($option)) {
				$level = $_SESSION['numbers']+1;
				if ($level != 1) output('<p>Level '.$level.': '.$numbers[$_SESSION['numbers']][0].'</p>');
				else output('<p>Level '.$level.': '.$numbers[$_SESSION['numbers']][0].'</p><p class="joshua">'.$joshua.'Type <b>number (x)</b> to answer the riddle.</p><p class="example">number 1</p>');
			}
			else {
				if ($option == $numbers[$_SESSION['numbers']][1]) {
					$_SESSION['numbers'] = $_SESSION['numbers']+1;
					$level = $_SESSION['numbers']+1;
					output('<p>Level '.$level.': '.$numbers[$_SESSION['numbers']][0].'</p>');
				}
				else output('<p class="error">'.$joshua.'Wrong answer. Try again.</p><p>'.$numbers[$_SESSION['numbers']][0].'</p>');
			}
		}
	}

	// msg
	if ($command == "msg") {
		$storage = "msg.data";
		if (isset($input)) {
			if ($option != "list" && $option != "listall") {
				if (strlen($input) < 10) $msgTooShort = true;
				else {
					if (!file_exists($storage)) touch($storage);
					$fp = fopen($storage, 'a');
					fwrite($fp, gmdate("d/m/y").'^'.$input.'^'.$_SERVER['REMOTE_ADDR']."\n");
					fclose($fp);					
				}
			}
			$db = dbFile($storage);
			$messages = array();
			foreach ($db as $entry => $message) {
				$messages[$entry]['timestamp'] = $message[0];
				$messages[$entry]['message'] = $message[1];
				if (!empty($message[2])) {
					$messages[$entry]['ip'] = $message[2];
				}
			}
			$messages = array_reverse($messages);
			$output = '<table class="fluid msg">';
			$limit = 20;
			if ($option == "listall") $limit = count($messages);
			for ($i = 0; $i < $limit; $i++) {
				$output .= '<tr><td class="dark fixed-width">'.$messages[$i]['timestamp'].'</td><td>'.stripslashes($messages[$i]['message']).'</td><td></td></tr>';
			}
			$output .= '</table>';
			if (isset($msgTooShort)) output('<p class="error">'.$joshua.'Message is too short.</p>');
			else output($output);
		}
		else output('<p class="error">'.$joshua.'Message can\'t be empty.</p><p class="example">msg joshua needs more ultraviolence</p>');
	}

	// yoda
	if ($command == "yoda") {
		$yoda = '<div class="pixelPerson"><img src="images/iconYoda.png" width="27" height="28"></div>';
		if (isset($input)) {
			$count = count($yodaQuotes)-1; $rand = rand(0,$count);
			output('<div class="speechBubble">'.$yodaQuotes[$rand].'</div>'.$yoda);
		}
		else output('<p class="speechBubble">Ask a question you must.</p>'.$yoda);
	}

	// fml
	if ($command == "fml") {
		$url = "http://feeds.feedburner.com/fmylife?format=xml";
		$cache = "fml.xml";
		get($url, $cache);
		$xml = load($cache);
		output($xml->entry[rand(0,9)]->content);
	}

	// cheat
	if ($command == "idkfa") {
		foreach ($static as $key => $value) $commands[] .= $key;
		sort($commands); $commands = implodeHuman($commands, true);
		output($commands);
	}

	// lastfm
	if ($command == "last.fm" || $command == "lastfm") {
		$output = '';
		if (!empty($option) && $option == "loved") {
			// loved tracks
			$url = 'http://ws.audioscrobbler.com/2.0/?method=user.getlovedtracks&user=astoever&api_key=a2b73335d53c05871eb50607e5df5466';
			$count = 10; $cache = 'lastfm.loved.xml';
			get($url, $cache);
			$xml = load($cache);
			for ($i = 0; $i < $count; $i++) {
				$track = $xml->lovedtracks->track[$i]->name;
				$artist = $xml->lovedtracks->track[$i]->artist->name;
				$output .= $artist.' - '.$track.'<br>'."\r";
			}	
		}
		else {
			// recent tracks
			$url = 'http://ws.audioscrobbler.com/2.0/?method=user.getrecenttracks&user=astoever&api_key=a2b73335d53c05871eb50607e5df5466';
			$count = 10; $cache = 'lastfm.xml';
			get($url, $cache);
			$xml = load($cache);
			for ($i = 0; $i < $count; $i++) {
				$track = $xml->recenttracks->track[$i]->name;
				$artist =$xml->recenttracks->track[$i]->artist;
				$output .= $artist.' - '.$track.'<br>'."\r";
			}
		}
		$output .= '<a class="external" href="http://last.fm/user/astoever/" title="Alexander Støver on Last.FM">More useless data.</a></p>';
		output($output);
	}

	// wtfig
	if ($command == "wtfig" || $command == "figlet") {
		if (!isset($option)) {
			output('<p class="error">'.$joshua.'Tell me which font to use and what you want to figletize. (See available fonts with <span class="command">wtfig list</span>.)</p><p class="example">wtfig chunky w00t!</p>');
		}
		else {
			if (file_exists("wtfig/fonts/$option.flf")) {
				$font =  $option.'.flf';
				$string = trim(str_replace($option, '', $input));
				if (strlen($string) > 0) {
					// load class
					require("wtfig/class.figlet.php");
					$phpFiglet = new phpFiglet();
					if ($phpFiglet->loadFont("wtfig/fonts/".$font)) {
						$wtFIG = $phpFiglet->fetch($string);
						output('<pre class="ascii">'.$wtFIG.'</pre>');
					}
				}
				else {
					output('<p class="error">'.$joshua.'You didn\'t write anything to figletize.</p>');
				}
			}
			else {
				$fontList = array();
				$dir = scandir("wtfig/fonts/");
				foreach($dir as $file) {
					if (strpos($file,".flf")) {
						$fontName = str_replace('.flf', '', $file);
						$fontList[] = $fontName;
					}
				}
				sort($fontList); $fonts = implodeHuman($fontList);
				$output = '<p>'.$fonts.'.</p>';
				if ($option != "list") {
					$output = '<p class="error">'.$joshua.'I don\'t have that font. See list below.</p>'.$output;
				}
				output($output);
			}
		}
	}

	// get (torrents)
	if ($command == "get" || $command == "torrent") {
		if (isset($option)) {
			$rows = 25; $url = 'http://ca.isohunt.com/js/json.php?ihq='.urlencode($input).'&start=0&sort=seeds&rows='.$rows;
			$content = get($url);
			if ($content) {
				print '<div class="prompt">'.$command.' <b>'.$input.'</b></div>';
				$c = json_decode($content, true);
				$hits = $c['total_results'];
				if ($hits > 0) {
					print '<table class="torrents">';
					if ($rows > $hits) $rows = $hits;
					for ($i = 0; $i < $rows; $i++) {
						$name = $c['items']['list'][$i]['title'];
						$link = $c['items']['list'][$i]['link'];
						$size = $c['items']['list'][$i]['size'];
						$seeds = $c['items']['list'][$i]['Seeds'];
						$leechers = $c['items']['list'][$i]['leechers'];
						if (!$seeds) $seeds = 0;
						if (!$leechers) $leechers = 0;						
						if (strlen($name) > 70) $name = substr($name, 0, 67).'...';
						if (!empty($link)) {
							print '<tr><td class="torrent"><a href="'.$link.'">'.$name.'</a></td><td>'.$size.'</td><td class="dark">'.$seeds.'/'.$leechers.'</td></tr>';
						}
					}
					print '</table>'; $output = 1;
				}
				else output('<p class="error">'.$joshua.'<b>'.$input.'</b> returned nothing.</p>');
			}
			else {
				error('timeout');
			}
		}
		else output('<p class="error">'.$joshua.'You need to tell me something to look for.</p><p class="example">get binaerpilot</p>');
	}

	// themes
	if ($command == "theme" || $command == "themes") {
		$themes = array();
		if (isset($_COOKIE['konami'])) $themes[] = 'contra';
		foreach(scandir("themes") as $file) {
			if (stristr($file, '.css')) {
				$theme = str_replace('.css', '', $file);
				if ($theme != "contra") $themes[] = $theme;
			}
		}
		sort($themes);
		if (isset($option) && in_array($option, $themes)) {
			setcookie('theme', $option, $expires, '/');
			output('<script>location.reload();</script>');
		}
		else output('<p class="error">'.$joshua.'Choose between '.implodeHuman($themes).'.</p><p class="example">'.$command.' '.$themes[rand(0,count($themes)-1)].'</p>');
	}

	// presets
	if ($command == "preset" || $command == "presets") {
		$presets = array('alexander', 'rachael', 'gamer', 'tron');
		sort($presets);
		if (isset($option) && in_array($option, $presets)) {
			if ($option == "gamer") {
				setcookie('theme', 'carolla', $expires, '/');
				setcookie('background', 'atari', $expires, '/');
				setcookie('fx', 'sparks', $expires, '/');
				deleteCookie('opacity');
			}
			else if ($option == "rachael") {
				setcookie('theme', 'penguin', $expires, '/');
				setcookie('background', 'rachael', $expires, '/');
				deleteCookie('fx');
				deleteCookie('opacity');
			}
			else if ($option == "tron") {
				setcookie('theme', 'tron', $expires, '/');
				deleteCookie('background');
				setcookie('fx', 'sparks', $expires, '/');
				setcookie('tron.team', 'purple', $expires, '/');
			}
			else if ($option == "alexander") {
				setcookie('theme', 'mono', $expires, '/');
				deleteCookie('background');
				setcookie('fx', 'pulsar', $expires, '/');
			}
			output('<meta http-equiv="refresh" content="0">');
		}
		else output('<p class="error">'.$joshua.'Choose between '.implodeHuman($presets).'.</p><p class="example">'.$command.' '.$presets[rand(0,count($presets)-1)].'</p>');
	}

	// superplastic scores
	if ($command == "scores") {
		if (!empty($_POST['name'])) $name = strip_tags(trim($_POST['name']));
		if (!empty($_POST['score'])) $score = strip_tags(trim($_POST['score']));
		if (!is_numeric($score)) unset($score);
		$storage = "superplastic.data";
		if (!empty($name) && !empty($score)) {
			if (!file_exists($storage)) touch($storage);
			$fp = fopen($storage, 'a');
			fwrite($fp, $score.'^'.$name."\n");
			fclose($fp);
		}
		$db = dbFile($storage);
		$scores = array();
		foreach ($db as $entry => $score) {
			$scores[$entry]['score'] = $score[0];
			$scores[$entry]['name'] = $score[1];
		}
		rsort($scores);
		print '<h2>Season X High Scores</h2><ul>';
		for ($i = 0; $i<30; $i++) {
			$pos = $i+1;
			if ($pos < 10) $pos = '0'.$pos;
			print '<li><span class="pos">'.$pos.'.</span><b>'.$scores[$i]['name'].'</b> <span class="score">'.$scores[$i]['score'].'</span></li>';
		}
		print '</ul>';
		$output = 1;
	}

	// calc
	if ($command == "calc") {
		if (isset($option)) {
			if (preg_match('/^([0-9]+[+-\/*%][0-9]+)*$/', $option)) {
				$return = shell_exec("awk 'BEGIN {print $option}'");
				if (!empty($return)) {
					output($return);
				}
				else error('noreturn');
			}
			else output('<p class="error">'.$joshua.'Does not compute.</p>');
		}
		else output('<p class="error">'.$joshua.'There\'s nothing to calculate.</p><p class="example">calc 6*9</p>');
	}
	
	// hashing
	if ($command == "hash" || $command == "crypt" || $command == "md5" || $command == "sha1") {
		$example = '<p class="example">hash md5 joshua</p>';
		if (isset($option)) {
			$string = trim(str_replace($option, '', $input));
			if (strlen($string) > 0) {
				output('<p>'.hash($option, $string).'</p>');
			}
			else {
				output('<p class="error">'.$joshua.'You need to specify both an algorithm and a string.</p>'.$example);
			}
		}
		else output('<p class="error">'.$joshua.'Can\'t hash an empty string.</p>'.$example);
	}

	// reviews
	if ($command == "reviews" || $command == "review" || $command == "r") {
		if (empty($option)) {
			print $prompt.'<p>One day we had a great idea: '.
				'"Let\'s watch all the worst movies in the world!"</i><br> '.
				'In retrospect, it might not have been the greatest of ideas. ';
			print '<table class="reviews fluid">';
			foreach ($reviews as $key => $value) {
				print '<tr><td class="light">'.($key+1).'</td><td>'.$value['title'].'</td><td class="dark">'.$value['year'].'</td><td>'.$value['rating'].'/10</td></tr>';
			}
			print '</table>';
			print '<p class="joshua">'.$joshua.'Type <b>review (x)</b> to read a review.</p><p class="example">review '.rand(0,count($reviews)-1).'</p>';
			$output = 1;
		}
		else {
			$pattern = "/^[0-9]+$/";
			if (preg_match($pattern, $option)) {
				$id = $option-1;
				if (!empty($reviews[$id])) {
					print $prompt.'<p><b>'.$reviews[$id]['title'].'</b> ('.$reviews[$id]['year'].') <span class="dark">'.$reviews[$id]['rating'].'/10</span></p>'.
						$reviews[$id]['review'].
						'<p><a class="external" href="http://www.imdb.com/find?s=all;q='.urlencode($reviews[$id]['title'].' '.$reviews[$id]['year']).'">View movie on IMDb.</a></p>';
					$output = 1;
				}
				else error("404");
			}
			else error("blocked");
		}
	}

	// stats
	if ($command == "stats") {
		$timestamp = microtime(true);
		$brainCells = 0; $themes = 0; $bytes = 0; $lines = 0;
		$dir = '.'; $scan = scandir($dir);
		foreach ($scan as $file) {
			if (!stristr($file, '.xml') && !stristr($file, '.data') && !is_dir($file)) {
				$bytes = $bytes + filesize($file);
				$lines = $lines + count(file($file));
			}
			if (stristr($file, 'cell.'))	$brainCells = $brainCells+1;
			else if (stristr($file, '.xml')) $brainCells = $brainCells+1;
		}
		$dir = 'themes/'; $scan = scandir($dir);
		foreach ($scan as $file) {
			if (!is_dir($file)) {
				$bytes = $bytes + filesize($dir.$file);
				$lines = $lines + count(file($dir.$file));
			}
			if (stristr($file, '.css')) $themes = $themes+1;
		}
		if (file_exists('msg.data')) $messages = count(explode("\n", file_get_contents('msg.data')));
		else $messages = 0;
		if (file_exists('superplastic.data')) $scores = count(explode("\n", file_get_contents('superplastic.data')))+2828; // from season 1-4
		else $scores = 0;
		$commands = count($static)+30; // guesstimate
		$quotes = count($motd)+count($bash)+count($quotes);
		$reviews = count($reviews);
		$stats = '<table class="stats">'.
			'<tr><td class="light">Commands</td><td>'.$commands.'</td><td class="dark">Yes, there are at least that many</td></tr>'.
			'<tr><td class="light">Brain cells</td><td>'.$brainCells.'</td><td class="dark">All external files loaded by the brain</td></tr>'.
			'<tr><td class="light">Themes</td><td>'.$themes.'</td><td class="dark">Some themes have to be unlocked...</td></tr>'.
			'<tr><td class="light">Bytes</td><td>'.$bytes.'</td><td class="dark">Everything hand-coded with Notepad++ and TextMate</td></tr>'.
			'<tr><td class="light">Lines</td><td>'.$lines.'</td><td class="dark">Lines of code (no externals)</td></tr>'.
			'<tr><td class="light">Messages</td><td>'.$messages.'</td><td class="dark">Left with the msg command</td></tr>'.
			'<tr><td class="light">Reviews</td><td>'.$reviews.'</td><td class="dark">Reviews of terrible movies</td></tr>'.
			'<tr><td class="light">Scores</td><td>'.$scores.'</td><td class="dark">Superplastic record attempts</td></tr>'.
			'<tr><td class="light">Quotes</td><td>'.$quotes.'</td><td class="dark">Includes MOTD\'s and bash.org quotes</td></tr>'.
			'<tr><td class="light">Timer</td><td>'.microtimer($timestamp).'</td><td class="dark">The seconds it took to compile these stats</td></tr>'.
			'</table>';
		output($stats);
	}

	// hi reddit
	if ($command == "let\'s" || $command == "lets" || $command == "how") {
		if (preg_match('/thermonuclear/i', $dump)) {
			$prompt = '<div class="prompt">how about global thermonuclear war?</div>';
			output('<p class="joshua">'.$joshua.'Wouldn\'t you prefer a nice game of chess?</p>');		
		}
	}
	
	// say
	if ($command == "say" || $command == "talk" || $command == "speak") {
		if (isset($input)) {
			print '<div class="prompt">'.$command.' <b>'.$input.'</b></div>'.
				'<script>speak(\''.$input.'\', { pitch:50, speed:120 });</script>';
			$output = 1;
		}
		else output('<p class="error">'.$joshua.'What do you want me to say?</p><p class="example">say hello</p>');
	}

	// rate
	if ($command == "rate" || $command == "rating" || $command == "imdb") {
		if (isset($input)) {
			$output;
			$query = urlencode($input);
			$limit = 10;
			// imdb
			$imdb = 'http://mymovieapi.com/?title='.$query.'&limit='.$limit.'&lang=en-US';
			$imdb = json_decode(get($imdb));
			if ($imdb) {
				$output .= '<table class="ratings fluid">';
				foreach ($imdb as $movie) {
					if (isset($movie->title)) {
						if (isset($movie->rating)) $rating = $movie->rating; else $rating = 'N/A';
						$output .= '<tr><td><a href="'.$movie->imdb_url.'">'.$movie->title.'</a></td><td class="dark">'.$movie->year.'</td><td class="light">'.$rating.'</td></tr>';
					}
				}
				$output .= '</table>';
			}
			// rt
			$rt = 'http://api.rottentomatoes.com/api/public/v1.0/movies.json?q='.$query.'&page_limit='.$limit.'&apikey=m4x7r8qu99bsamd9era6qqzb';
			$rt = json_decode(get($rt));
			if ($rt && $rt->movies) {
				$output .= '<table class="ratings fluid">';
				foreach ($rt->movies as $movie) {
					if (isset($movie->title)) {
						$critics = $movie->ratings->critics_score;
						if ($critics >= 0) $critics .= '%'; else $critics = 'N/A';
						$audience = $movie->ratings->audience_score;
						if ($audience >= 0) $audience .= '%'; else $audience = 'N/A';
						$output .= '<tr><td><a href="'.$movie->links->alternate.'">'.$movie->title.'</a></td><td class="dark">'.$movie->year.'</td><td class="light">'.$critics.'</td><td>'.$audience.'</td></tr>';						
					}
				}
				$output .= '</table>';
			}
			if (!empty($output)) output($output);
			else output('<p class="error">'.$joshua.'IMDb and Rotten Tomatoes returned nothing for that title.</p>');
		}
		else output('<p class="error">'.$joshua.'What am I looking for?</p><p class="example">'.$command.' blade runner</p>');		
	}
	
	// img
	if ($command == "img" || $command == "image" || $command == "images") {
		if (isset($input)) {
			$tag = str_replace(' ','', $input);
			$instagram = 'https://api.instagram.com/v1/tags/'.$tag.'/media/recent?client_id=c0f8f9f1e63a4e1c8a45846bb5db52db&count=30';
			$instagram = get($instagram);
			if ($instagram) {
				$output = '';
				$result = json_decode($instagram);
				if ($result->data) {
					$resultCount = count($result->data);
					$thumbnails = ($resultCount > 18) ? 18 : $resultCount;
					for ($i = 0; $i<$thumbnails; $i++) {
						$image = $result->data[$i];
						$output .= '<div class="slide"><img src="'.$image->images->standard_resolution->url.'" width="468" height="468"></div>';
					}
					print $prompt.'<script>$("#slick .slideshow").html(\''.$output.'\'); galleryInit(); $("#gallery:hidden").fadeIn(fade); $("#galleryOpen").addClass("active");</script>';
					$output = 1;
				}
				else output('<p class="error">'.$joshua.'Found nothing tagged with '.$input.'. (Instagram filters the API rigorously.)</p>');
			}
			else error('empty');
		}
		else output('<p class="error">'.$joshua.'Give me something to search for.</p><p class="example">'.$command.' daft punk</p>');					
	}
	
	// rand
	if ($command == "rand") {
		if (isset($input)) {
			if (is_numeric($input)) {
				output(rand(1, $input));
			}
			else output('<p class="error">'.$joshua.' '.$input.' is not numeric. Come on man.');
		}
		else output('<p class="error">'.$joshua.'Between how many numbers?</p><p class="example">'.$command.' '.rand(0,10).'</p>');					
	}
	

	// window management
	$jsCommands = array('clear', 'cls', 'exit', 'quit', 'logout', 'customize', 'music', 'videos', 'superplastic', 'reset');
	if (in_array($command, $jsCommands)) {
		if ($command == "clear" || $command == "cls") {
			$js = 'clearScreen();';
		}
		else if ($command == "exit" || $command == "quit" || $command == "logout") {
			$js = 'location.href = "http://binaerpilot.no";';
		}
		else if ($command == "superplastic") {
			$js = 'loadSuperplastic();';
		}
		else if ($command == "videos") {
			$js = 'loadVideos();';
		}
		else if ($command == "reset") {
			$js = 'reset();';
		}
		else if ($command == "customize" || $command == "music") {
			setcookie($command, true, $expires, '/');
			$js = '$("#'.$command.':hidden").fadeIn(fade); $("#'.$command.'Open").addClass("active");';
			if ($command == "music") {
				$js .= 'mute();';
			}
		}
		$js .= 'systemReady();';
		print '<script>'.$js.'</script>';
		$output = 1;
	}
	
	// fallback
	if (empty($output)) {
		foreach ($static as $key => $value) {
			if ($key == $command) output($value);
		}
		if (empty($output)) {
			// lets store commands that fail and populate them
			$storage = "invalid.data";
			if (!file_exists($storage)) touch($storage);
			$fp = fopen($storage, 'a');
			fwrite($fp, $dump."\n");
			fclose($fp);
			// feedback
			error('invalid');
		}
	}
}

?>
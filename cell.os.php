<?php // random os-related garbage

// sudo
if($command == "sudo"){
	if(empty($option)) error('password');
	else {
		if($option == "iddqd"){
			$_SESSION['sudo'] = 1;
			output('<p class="joshua">'.$joshua.'Authentification successful.</p>');
		}
		else {
			unset($_SESSION['sudo']);
			error('password');
		}
	}
}

// various *nix commands for lulz
if($command == "ls" || $command == "cd" || $command == "top" || $command == "rm" || $command == "top" || $command == "who"){
	if(isset($_SESSION['sudo'])){
		if($command == "ls") $return = shell_exec("ls");
		elseif($command == "who") $return = shell_exec("who");
		if(isset($return) && !empty($return)){
			output('<pre>'.$return.'</pre>');
		}
		else error('noreturn');		
	}
	else error('auth');
}

// motd 
if($command == "motd"){
	$count = count($motd)-1; $rand = rand(0,$count);
	if(isset($option) && $option == "clean"){
		print '<p class="dark motd">'.$motd[$rand].'</p><p class="joshua">'.$joshua.'Please enter <b>help</b> for commands.</p>'; $output = 1;
	}
	else {
		output($motd[$rand]);
	}
}

// uptime and date
if($command == "uptime" || $command == "date"){
	$return = trim(exec($command));
	if(!empty($return))	output($return);
	else error('noreturn');
}

?>
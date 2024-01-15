<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * @todo this file has to be set in a static class
 */
function is_exim_id($id) {
    $tmp = array();
	if (preg_match('/^[a-z,A-Z,0-9]{6}\-[a-z,A-Z,0-9]{6,11}\-[a-z,A-Z,0-9]{2,4}$/',$id, $tmp)) {
		return true;
	}
	return false;
}

function is_email($a) {
	if (filter_var($a, FILTER_VALIDATE_EMAIL)) {
        	return true;
        }
        return false;
}

function isname($s) {
    $tmp = array();
	if (preg_match('/\S+/', $s, $tmp)) {
		return true;
	}
	return false;
}

function extractSRSAddress($sender) {
        $sep = '[=+-]';
        if (preg_match('/^srs0.*/i', $sender)) {
                $segments = preg_split("/$sep/", $sender);
                $tag = array_shift($segments);
                $hash = array_shift($segments);
                $time = array_shift($segments);
                $domain = array_shift($segments);
                $remove = "$tag$sep$hash$sep$time$sep$domain$sep";
                $remove = preg_replace('/\//', '\\\/', $remove);
                $sender = preg_replace("/^$remove(.*)\@[^\@]*$/", '$1', $sender) . '@' . $domain;
        } elseif (preg_match('/^srs1.*/i', $sender)) {
                $blocks = preg_split("/=$sep/", $sender);
                $segments = preg_split("/$sep/", $blocks[0]);
                $domain = $segments[sizeof($segments)-1];
                $segments = preg_split("/$sep/", $blocks[sizeof($blocks)-1]);
                $hash = array_shift($segments);
                $time = array_shift($segments);
                $relay = array_shift($segments);
                $remove = "$hash$sep$time$sep$relay$sep";
                $remove = preg_replace('/\//', '\\\/', $remove);
                $sender = preg_replace("/^$remove(.*)\@[^\@]*$/", '$1', $blocks[sizeof($blocks)-1]) . '@' . $domain;
        }
        return $sender;
}

function extractVERP($sender) {
        if (preg_match('/^[^\+]+\+.+=[a-z0-9\-\.]+\.[a-z]+/i', $sender)) {
                return preg_replace('/([^\+]+)\+.+=[a-z0-9\-]{2,}\.[a-z]{2,}@([a-z0-9\-]{2,}\.[a-z]{2,})/i', '$1@$2', $sender);
        }
        return $sender;
}

function extractSubAddress($sender) {
        if (preg_match('/\+/', $sender)) {
                return preg_replace('/([^\+]+)\+.+@([a-z0-9\-]{2,}\.[a-z]{2,})/i', '$1@$2', $sender);
        }
        return $sender;
}

function extractSender($sender) {
	$orig = $sender;
	$sender = extractSRSAddress($sender);
	$sender = extractVERP($sender);
	$sender = extractSubAddress($sender);
	if ($orig == $sender) {
		return False;
	}
        return $sender;
}

function detectSingleUseAddress($sender) {
	if (preg_match('/[\.\-=_][^@]*@/', $sender)) {
		return True;
	}
	if (preg_match('/[0-9a-fA-F]{6}[^@]*@/', $sender)) {
		return True;
	}
	if (preg_match('/[0-9]+[a-zA-Z]+[0-9]+[^@]*@/', $sender)) {
		return True;
	}
	return False;
}
?>

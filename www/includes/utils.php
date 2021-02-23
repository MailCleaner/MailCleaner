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
	if (preg_match('/^[a-z,A-Z,0-9]{6}\-[a-z,A-Z,0-9]{6}\-[a-z,A-Z,0-9]{2}$/',$id, $tmp)) {
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
?>

<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 * 
 * This is the controler for the add address page
 */

if ($_SERVER["REQUEST_METHOD"] == "HEAD") {
  return 200;
}
 
/**
 * require valid session
 */ 
require_once('objects.php');
require_once("user/AliasRequest.php");
require_once("view/Template.php");
global $user_;

// first save user in order to have it on database
$user_->save();
// then create the request
$request = new AliasRequest();
if (isset($_POST['address']) && is_string($_POST['address'])) {
  // do the request
  $message = $request->requestForm($_POST['address']);
}

// create view
$template_ = new Template('add_address.tmpl');
// prepare replacements
$replace = array(
        '__MESSAGE__' => $message,
        '__BEGIN_FORM__' => "<form name=\"add_alias\" method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">",
        '__ADDRESS_FIELD__' => "<input type=\"text\" size=\"30\" name=\"address\" value=\"\" />",
        '__INPUT_BUTTON__' => "<input type=\"submit\" value=\"".$lang_->print_txt('ADD')."\" />",
        '__CLOSE_FORM__' => "</form>"
       );
// display page
$template_->output($replace);
?>

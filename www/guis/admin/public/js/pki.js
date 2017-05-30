/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 */

function generatePKI(type, length) {
	url = baseurl+"/pki/createkey/t/"+type+"/l/"+length;
	request = $.ajax({
	  	  type: "GET",
	  	  url: url,
	  	  dataType: "html",
		  async: false,
	  	  success: function(msg){
            setupFields(msg);
	      },
	      error: function() {
	      }
	  });
}

function setupFields(msg) {
	obj = jQuery.parseJSON(msg);
	$(".pki_privatekey").val(obj.privateKey);
}

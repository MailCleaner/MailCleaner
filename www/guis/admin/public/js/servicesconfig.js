/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 */

$(document).ready(function(){
	 
	 $("#use_ssl").click(function(event){
		 if ($("#use_ssl").is(':checked')) {
			 $(".tls_row").show();
			 $("#urlsheme").html('https');
			 if ($("#https_port").val() != 443) {
			   set_url_port($("#https_port").val());
			 } else {
				 set_url_port(); 
			 }
			 
		 } else {
			 $(".tls_row").hide();
			 $("#urlsheme").html('http');
			 if ($("#http_port").val() != 80) {
				   set_url_port($("#http_port").val());
		     } else {
					 set_url_port(); 
			 }
		 }
	 });
	 
	 $("#http_port").keyup(function(event) {
		do_url_port();
	 });
	 $("#https_port").keyup(function(event) {
			do_url_port();
     });
	 
	 $("#http_tls_cert_infos").mouseover(function(event) {
		 display_hover('http_tls_cert_infos', event, 1);
	 });
	 $("#http_tls_cert_infos").mouseout(function(event) {
		 display_hover('http_tls_cert_infos', event, 0);
	 });
});

function do_url_port() {
	port = 80;
    field = $("#http_port");
	
    if ($("#use_ssl").is(':checked')) {
    	port = 443;
    	field = $("#https_port");
    }
	if ( field.val() != port) {
		set_url_port(field.val());
	} else {
		set_url_port();
	}
}

function set_url_port(port) {
	str = $("#servername").val();
	pos = str.indexOf(":");
	if (pos > 0) {
		str = str.substring(0, pos);
	}
	if (port) {
    	$("#servername").val( str+":"+port);
	} else {
		$("#servername").val( str);
	}
}
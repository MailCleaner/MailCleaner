/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 */
var clearcalout_text = '';

$(document).ready(function(){
	 
	 $("#ratelimit_enable").click(function(event){
		 if ($("#ratelimit_enable").is(':checked')) {
			 $("#rate_rule_row").show();
			 $("#rate_delay_row").show();
		 } else {
			 $("#rate_rule_row").hide();
			 $("#rate_delay_row").hide();
		 }
		 setNoRateLimitHosts();
	 });
	 $("#trusted_ratelimit_enable").click(function(event){
		 if ($("#trusted_ratelimit_enable").is(':checked')) {
			 $("#trusted_rate_rule_row").show();
			 $("#trusted_rate_delay_row").show();
		 } else {
			 $("#trusted_rate_rule_row").hide();
			 $("#trusted_rate_delay_row").hide();
		 }
		 setNoRateLimitHosts();
	 });
	 setNoRateLimitHosts();
	 
	 $("#use_incoming_tls").click(function(event){
		 if ($("#use_incoming_tls").is(':checked')) {
			 $(".tls_row").show();
		 } else {
			 $(".tls_row").hide();
		 }
	 });
	 
	 $(".max_conn_field").keyup(function(event) {
		if ( (parseInt($("#smtp_accept_max").val()) < parseInt($("#smtp_accept_max_per_host").val())) |
				(parseInt($("#smtp_accept_max").val()) < parseInt($("#smtp_accept_max_per_trusted_host").val())) ) {
			$("#smtp_accept_max").val(Math.max($("#smtp_accept_max_per_host").val(), $("#smtp_accept_max_per_trusted_host").val()));
		}
		if ( parseInt($("#smtp_reserve").val()) > parseInt($("#smtp_accept_max").val())) {
			$("#smtp_accept_max").val($("#smtp_reserve").val());
		}
	 });
	 
	 $("#smtp_tls_cert_infos").mouseover(function(event) {
		 display_hover('smtp_tls_cert_infos', event, 1);
	 });
	 $("#smtp_tls_cert_infos").mouseout(function(event) {
		 display_hover('smtp_tls_cert_infos', event, 0);
	 });
	 
	 $('#clearcalloutbutton').click(function(evet) {
		 clearcalout_text = $('#clearcalloutbutton').attr('value');
		 $('#clearcalloutbutton').attr("disabled", "disabled");
		 $('#clearcalloutbutton').attr('value', 'Clearing...');
		 url = baseurl+'/smtp/clearcallout';
		 clearing = $.ajax({
				type: "GET",
				url: url,
				dataType: "html",
				success: function(msg){
					$('#clearcalloutbutton').attr('value', clearcalout_text);
                    $('#clearcalloutbutton').removeAttr("disabled");
			    },
			    error: function(XMLHttpRequest, textStatus, errorThrown) {
			    	$("#clearcalloutbutton").attr('value', errorThrown);
			    }
			});
	 });
});

function setNoRateLimitHosts() {
	if ($("#ratelimit_enable").is(':checked') || $("#trusted_ratelimit_enable").is(':checked') ) {
		$("#no_ratelimit").show();
	} else {
		$("#no_ratelimit").hide();
    }
}

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 */

$(document).ready(function(){
	
	 $("#enable_whitelists").click(function(event){
		 if ($("#enable_whitelists").is(':checked')) {
             $("#whitelist_list").show();
             $(".whitelist_options").show();
		 } else {
        	 $("#whitelist_list").hide();
             $(".whitelist_options").hide();
		 }
	 });

	$("#enable_blacklists").click(function(event){
                 if ($("#enable_blacklists").is(':checked')) {
             $("#blacklist_list").show();
             $(".blacklist_options").show();
                 } else {
                 $("#blacklist_list").hide();
             $(".blacklist_options").hide();
                 }
         });
	 
	 $("#enable_warnlists").click(function(event){
		 if ($("#enable_warnlists").is(':checked')) {
             $("#warnlist_list").show();
		 } else {
             $("#warnlist_list").hide();
		 }
	 });
	
	 $("#use_bayes").click(function(event){
		 if ($("#use_bayes").is(':checked')) {
	        $("#bayes_autolearn").removeAttr('disabled');
		 } else {
		    $("#bayes_autolearn").attr("disabled", "disabled");
		 }
	 });
	 
	 $("#use_rbls").click(function(event){
		 if ($("#use_rbls").is(':checked')) {
		     $("#rbls_timeout").removeAttr('disabled');
			 $("#iprbls").show();
			 $("#urirbls").show();
		 } else {
			 $("#rbls_timeout").attr("disabled", "disabled");
			 $("#iprbls").hide();
			 $("#urirbls").hide();
		 }
	 });
	 
	 $("#use_dcc").click(function(event){
		 if ($("#use_dcc").is(':checked')) {
		        $("#dcc_timeout").removeAttr('disabled');
		 } else {
			    $("#dcc_timeout").attr("disabled", "disabled");
		 }
	 });
	 
	 $("#use_razor").click(function(event){
		 if ($("#use_razor").is(':checked')) {
		        $("#razor_timeout").removeAttr('disabled');
		 } else {
			    $("#razor_timeout").attr("disabled", "disabled");
		 }
	 });
	 
	 $("#use_pyzor").click(function(event){
		 if ($("#use_pyzor").is(':checked')) {
		        $("#pyzor_timeout").removeAttr('disabled');
		 } else {
			    $("#pyzor_timeout").attr("disabled", "disabled");
		 }
	 });
	 
	 $("#use_spf").click(function(event){
		 if ($("#use_spf").is(':checked')) {
		        $("#spf_timeout").removeAttr('disabled');
		 } else {
			    $("#spf_timeout").attr("disabled", "disabled");
		 }
	 });
	 
	 $("#use_dkim").click(function(event){
		 if ($("#use_dkim").is(':checked')) {
		        $("#dkim_timeout").removeAttr('disabled');
		 } else {
			    $("#dkim_timeout").attr("disabled", "disabled");
		 }
	 });
	 
	 $("#use_domainkeys").click(function(event){
		 if ($("#use_domainkeys").is(':checked')) {
		        $("#domainkeys_timeout").removeAttr('disabled');
		 } else {
			    $("#domainkeys_timeout").attr("disabled", "disabled");
		 }
	 });
	 
	 $(".disabled").attr("disabled", "disabled");
});

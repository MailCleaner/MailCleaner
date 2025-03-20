/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 */

$(document).ready(function(){
	 
	 $("#max_attach_size_enable").click(function(event){
		 if ($("#max_attach_size_enable").is(':checked')) {
			$("#max_attach_size").attr("disabled", "disabled");
			$("#max_attach_size").val('');
		 } else {
		    $("#max_attach_size").removeAttr('disabled');
		 }
	 });
	 
	 $("#max_archive_depth_disable").click(function(event){
		 if ($("#max_archive_depth_disable").is(':checked')) {
			$("#max_archive_depth").attr("disabled", "disabled");
			$("#max_archive_depth").val('');
		 } else {
		    $("#max_archive_depth").removeAttr('disabled');
		 }
	 });
	 
	 $("#expand_tnef").click(function(event){
		 if ($("#expand_tnef").is(':checked')) {
			    $("#deliver_bad_tnef").removeAttr('disabled');
			    $("#usetnefcontent").removeAttr('disabled');
		 } else {
				$("#deliver_bad_tnef").attr("disabled", "disabled");
				$("#usetnefcontent").attr("disabled", "disabled");
		 }
	 });
	 
	 $("#send_notices").click(function(event){
		 if ($("#send_notices").is(':checked')) {
			    $("#notices_to").removeAttr('disabled');
		 } else {
				$("#notices_to").attr("disabled", "disabled");
		 }
	 });
	 
	 $(".disabled").attr("disabled", "disabled");
});

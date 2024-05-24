/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 */

var statusrequest;
var hostreloadtime = 5000;
var graphStatus = new Array();

$(document).ready(function(){
	$("#statusreloadimg").click(function(event){
        loading();
        event.preventDefault();
      });
	
	$("a.menubutton").click(function(event){
		abortStatus();
		//event.preventDefault();
	});
	loading();
});

function loading() {
	if (slave) {
            $("#statuspanel").html("not running on slave");
		return;
	}
	
	$("#statuspanel").html(loadinghtml);
	statusrequest = $.ajax({
		  type: "GET",
		  url: quickstatusurl,
		  dataType: "html",
		  timeout: 5000,
		  success: function(msg){
            $("#statuspanel").html(msg);
    	    setTimeout("loading()", statusreload);
          },
          error: function() {
        	$("#statuspanel").html();
            setTimeout("loading()", statusreload)
          }
		});
}

function abortStatus() {
	statusrequest.abort();
	delete statusrequest;
}

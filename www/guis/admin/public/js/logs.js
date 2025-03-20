/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 */

controller='monitorlogs';
quarantineblock='logsbloc';
$(document).ready(function(){	
	launchSearch();
});

function showHideSearch() {
	if ($("#domain").val() == '') {
	   $("#searchfields").hide();
	} else {
		$("#searchfields").show();
	}
}
reloaddelay = 3000;
resubmit = 0;
function launchSearch() {

	setLoading();
    url = baseurl+"/"+controller+"/search";
    url += '/fd/'+$("#fd").val();
    url += '/fm/'+$("#fm").val();
    if (resubmit) {
        url += '/submit/1';
        resubmit = 0;
    }

    //alert(url);
    statusrequest = $.ajax({
		  type: "GET",
		  url: url,
		  async: false,
		  dataType: "html",
		  timeout: 120000,
		  success: function(msg){
    	    $("#"+quarantineblock).html(msg);
          },
          error: function() {
        	$("#"+quarantineblock).html('jserror');
          }
    });
    
    if ($("#dataloading").html()) {
    	setTimeout("launchSearch()", reloaddelay);
    }
}


function setLoading() {
	var url = baseurl+"/"+controller+"/search/load/only";
    if (resubmit) {
        url += '/submit/1';
    }
 	statusrequest = $.ajax({
  	  type: "GET",
  	  url: url,
  	  dataType: "html",
	  async: false,
  	  success: function(msg){
      $("#"+quarantineblock).html(msg);
    },
    error: function() {
  	  $("#"+quarantineblock).html('jserror');
    }
  });
}

var logviewWidth=1050;
var logviewHeight=500;
function openLog(url) {
	window.open(url, '', 'width=' + logviewWidth
			+ ',height=' + logviewHeight
			+ ',toolbar=0,resizable=1,status=0,scrollbars=0');
}
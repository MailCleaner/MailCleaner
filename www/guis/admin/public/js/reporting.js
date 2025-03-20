/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 */

controller='monitorreporting';
$(document).ready(function(){
	$("#domain").change(function(event){
	      showHideSearch();
	});
	showHideSearch();
	
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

    url = baseurl+"/"+controller+"/search";
    url += '/search/'+$("#search").val();
    url += '/domain/'+$("#domain").val();
    url += '/td/'+$("#td").val();
    url += '/tm/'+$("#tm").val();
    url += '/fd/'+$("#fd").val();
    url += '/fm/'+$("#fm").val();
    url += '/sort/'+$("#sort").val();
    url += '/top/'+$("#top").val();
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
            $("#statsbloc").html(msg);
          },
          error: function() {
        	$("#statsbloc").html('jserror');
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

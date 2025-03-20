/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 */

var isauthexhaustive = 0;

$(document).ready(function(){
	
	$("#type").change(function(event){
	  if ($("#type").val() == 'email') {
	    $("#usersearchlabel").hide();
	    $("#emailsearchlabel").show();
	  } else {
		$("#emailsearchlabel").hide();
		$("#usersearchlabel").show();
      }
	  loadsearch(1);
    });
	
	$("#domain").change(function(event) {
		url = baseurl+'/domain/isauthexhaustive/name/'+$("#domain").val();
		loadIsExhaustive(url);
		loadsearch(1);
	});


	function delay(callback, ms) {
		var timer = 0;
		return function() {
			var context = this, args = arguments;
			clearTimeout(timer);
			timer = setTimeout(function () {
				callback.apply(context, args);
			}, ms || 0);
		};
	}


	$('#search').keypress(delay(function (e) {
		showhideadd();
		loadsearch(1);
	}, 500));
	
	$("#searchform").submit(function(event) {
		return false;
	});
	
	$("#search").attr('autocomplete', 'off');
	
	$("#userpanel").change(function(event) {
		loadUserPanel($("#userpanel").val());
	});
	
	$("#emailpanel").change(function(event) {
		loadUserPanel($("#emailpanel").val());
	});
	
	$(".listelement").click(function(event){
	   activateEnableButton();
	});
	
	$("#delivery_type").change(function(event) {
		showHideSpamTag();
	});
	
	url = baseurl+'/domain/isauthexhaustive/name/'+$("#domain").val();
    loadIsExhaustive(url);
	activateEnableButton();
	showHideSpamTag();
});

function loadsearch(page) {
	  url =  domainsearchurl+'/search/'+$("#search").val()+'/type/'+$("#type").val()+"/domain/"+$("#domain").val()+"/page/"+page;
	  loadsearchurl(url);
}

function loadIsExhaustive(url) {
	statusrequest = $.ajax({
		  type: "GET",
		  url: url,
		  dataType: "html",
		  success: function(msg){
            if(msg == "YES") {
            	isauthexhaustive = 1;
            	showhideadd();
            } else {
            	isauthexhaustive = 0;
            	showhideadd();
            }
          },
          error: function() {
          }
		});
	   setTimeout("", 5000);
}

function showhideadd() {
	if (isauthexhaustive || $("#search").val() == '') {
		$(".useraddsubmit").hide();
    	//$("#searchpanel").height('100px !important');
    	//$("#searchpanel").css('height', '100px !important');
    	$("#searchpanel").addClass('noaddbutton');
    	return;
	}
	if ($("#search").val() != '') {
        $(".useraddsubmit").show();
        //$("#searchpanel").height('120px !important');
    	//$("#searchpanel").css('height', '120px !important');
	    $("#searchpanel").removeClass('noaddbutton');
	}
	return;
}

function loadUserPanel(panel) {
	$("#domainformcontent").slideUp(500);
	statusrequest = $.ajax({
		  type: "GET",
		  url: thisurl+'/panel/'+panel,
		  dataType: "html",
		  success: function(msg){
            $("#domainformcontent").html(msg);
            $("#domainformcontent").slideDown(500);
    		activateEnableButton();
    		$(".listelement").click(function(event){
    			   activateEnableButton();
    	    });
    		$("#delivery_type").change(function(event) {
    			showHideSpamTag();
    		});
    		showHideSpamTag();
          },
          error: function() {
        	  $("#domainformcontent").html('jserror');
          }
    });
}


function activateEnableButton() {
	toenable = 0;
	jQuery.each($(".listelement"),
	    function( e ){
	       //if ($(this).hasClass("listelementdisabled")) {
	    	   cb = $(this).children('.unchecked');
	    	   if ($(cb).is(':checked')) {
                  $('#disable').removeAttr('disabled');
                  toenable = 1;
                  return;
	    	   }
	       //}
	    }
	);
	if (!toenable) {
        $('#disable').attr('disabled', 'disabled');
	}
}

function showHideSpamTag() {
  if ($("#delivery_type").val() == 1) {
	  $(".spam_tag_row").show();
  } else {
	  $(".spam_tag_row").hide();
  }
}
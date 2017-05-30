/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 */

$(document).ready(function(){
	var onsub = 0;
	var t;
	
	$("li.menubutton").hover(
	  function(){
		 var id=$(this).attr('id');
		 clearTimeout(t);
		 
		 var doit=1;
			$(".submenu:visible").each( function() {
			  if (this.id == "sub"+id) {
			  		doit=0;
			  }
		 });
		 if (doit) {	
    		 $("div.submenu").hide();
	    	 $("a.menuselected").attr('class', 'menubutton');
             $("a#link"+id).attr('class', 'menuselected');
	         $("div#sub"+id).fadeIn();
		 }
	  }
	);
	
	$("div.submenu").hover(function() { clearTimeout(t); });
	
	$("#main_navigation").hover(
	    function(){
	    	clearTimeout(t);
	    },
	    
	    function(){
	    	t = setTimeout('resetMenu()', 1000);
	    }
	    
	);
	
	$("#flashmessage").click(
	   function() {
		   $("#flashmessage").stop();
		   $("#flashmessage").fadeOut(1);
	   }
	);
	if (message && message != "") {
		$("#flashmessage").fadeIn(50);
		if ($("#flashmessage").hasClass('fmNOK')) {
			$("#flashmessage").fadeOut(20000);
		} else if ($("#flashmessage").hasClass('fmWAIT')) {
			loadwaiting(waiturl);
			
		} else {
			$("#flashmessage").fadeOut(4000);
		}
	}
        if (typeof slave === 'undefined') {
           slave = 1;
        }
        if (!slave) {
	    $("#informational").click(
	        function() {
			$('#informational').slideToggle();
	        }
	    );
	    getInformationalMessage();
        }
});

$(window).bind('beforeunload', function(){ 
	$('#informational').slideUp('fast');
});


function resetMenu() {
	var doit=1;
	$(".submenu:visible").each( function() {
	  if (this.id == "submenu_"+selectedMenu) {
	  		doit=0;
	  }
	});
	if (doit) {
        $("div.submenu").hide();
	    $("a.menuselected").attr('class', 'menubutton');
	    $("a#linkmenu_"+selectedMenu).attr('class', 'menuselected');
	    $("div#submenu_"+selectedMenu).fadeIn();
	}
}

function resizePanel(toresize, fromresize) {
	   var h = 0;
	   var marginbottom = 23;
	   var margintop = 0;
	      
	   for (el in fromresize) {
	     h = h + document.getElementById(fromresize[el]).offsetHeight -1;
	   }
	   h = h + marginbottom;

	   document.getElementById(toresize).style.height = h+'px';
	}
	 
function goTo(link) {
	document.location = link;
}

var stopwaiting = 0;

function loadwaiting() {
	statusrequest = $.ajax({
		  type: "GET",
		  url: waiturl,
		  dataType: "html",
		  success: function(msg){
            $("#flashmessage").html(waitloadingimg+'<br />'+msg);
            mstr = new RegExp(finishedwait,"g");
            if (msg.match(mstr)) {
                $("#flashmessage").html(msg);
            	stopwaiting = 1;
            }
          },
          error: function() {
        	  $("#flashmessage").html('timed out');
          }
		});
	if (stopwaiting < 1) {
        setTimeout("loadwaiting()", 4000);
	}
}

function getInformationalMessage() {
        mcinformational = $.ajax({
                  type: "GET",
                  url: baseurl+'/status/informational',
                  dataType: "html",
                   success: function(msg) { 
                           if (msg != '') {  
                              $('#informational').html(msg);
                              $('#informational').slideToggle();
                           }
                   },
                   error: function(jqXHR, textStatus, errorThrown) {
                         $('#informational').html(textStatus+' ('+errorThrown+')');
                         $('#informational').slideToggle();
                   },
        });
}

function updateInformationalMessages() {
	mcinformational = $.ajax({
		  type: "GET",
		  url: baseurl+'/status/informational',
		  dataType: "html",
		  success: function(msg) {
			  if (msg != '') {
			    $('#informational').html(msg);
			  }
                  }
	});
}

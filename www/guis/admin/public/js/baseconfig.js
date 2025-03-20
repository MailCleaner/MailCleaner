/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 */

$(document).ready(function(){
	$("#selectinterface").change(function(event){
        $("#interfaceformcontent").slideUp(500, function() {
		statusrequest = $.ajax({
			  type: "GET",
			  url: thisurl+'/interface/'+$("#selectinterface").val(),
			  dataType: "html",
			  success: function(msg){
	            $("#interfaceformcontent").html(msg);
                $("#interfaceformcontent").slideDown(500);
                setIPhandlers();
	          },
	          error: function() {
	        	  $("#interfaceformcontent").html('jserror');
	          }
			});
        });
      });

	 $("#reloadnetbutton").mousedown(
	   function(event) {
		   $("#reloadnetbutton").attr('value', reloading_text);
		   $("#reloadnetworkform").submit();
	   }
	 );
	 
	 $("#zone").change(function(event){
		 $("#subzoneset").html($("#subzonesetwaiting").html());
		 statusrequest = $.ajax({
			  type: "GET",
			  url: thisurl+'/zone/'+$("#zone").val(),
			  dataType: "html",
			  success: function(msg){
			       $("#subzoneset").html(msg);
		      },
		      error:  function() {
	        	  $("#subzoneset").html('jserror');
	          }
			});
	    
	 });
	 
	 $("#usentp").click(function(event){
		 if ($("#usentp").is(':checked')) {
           $("#ntpserver").removeAttr('disabled');
		   $("#saveandsync").val('Save and sync now')
		 } else {
	       $("#ntpserver").attr("disabled", "disabled");
		   $("#saveandsync").val('Save')
		 }
	 });
	 
	 $("#use_syslog").click(function(event) {
		 setSyslogField();
	 });
	 
	 $(".unchecked").attr('checked', false);
	 
	 $(".listhoverable").hover(
	  function(event) {
		 showToolTip(event);
	  },
	  function(event) {
	     hideToolTip(event);
	  }
	 );

    setSyslogField();
    
    $('#archiving_type').change(function(event) {
    	setArchiverFields();
    });
    setArchiverFields();
    
    $("#role").change( function(event) {
    	setRoleFields();
    });
    setRoleFields();
    
    setIPhandlers();
});

function setSyslogField() {
	if (!$("#use_syslog").is(':checked')) {
		 $("#syslog_host").attr("disabled", "disabled");
	 } else {
		 $("#syslog_host").removeAttr('disabled');
	 }
}
function setArchiverFields() {
	$('.archiver_type_field').hide();
	selectedvalue = $('#archiving_type').val();
	$('.'+selectedvalue+'_archiver_type_field').show();
}
function hideToolTip() {
	$("#tooltip").hide();
}
function showToolTip(e) {
	elem = $("#tooltip");
	
	var offsety = 0;
    var posx = 0;
    var posy = 0;

    if (!e) var e = window.event;
	   if (e.pageX || e.pageY) 	{
	      posx = e.pageX;
	      posy = e.pageY;
	   } else if (e.clientX || e.clientY) {
	      posx = e.clientX + document.body.scrollLeft + document.documentElement.scrollLeft;
	      posy = e.clientY + document.body.scrollTop + document.documentElement.scrollTop;
	   }

     text = $(e.target).parent().next().html();
	  
	 elem.innerHTML = text;
	 rExp = /&[a-z]+;/gi;
	 textcpt = text.replace(rExp, '_');
	 sl = textcpt.length * 6;
	 offsety = 5;
	  
	 elem.css({top: posy + 10 +"px", left: posx + offsety +"px"})
	 elem.html(text);
     elem.show();
}

function showFieldError(e, st) {
    var elem = document.getElementById('tooltip');
	var offsety = 0;

	if (! elem) { return; }

    var posx = 0;
    var posy = 0;

	if (!e) var e = window.event;
	if (e.pageX || e.pageY) 	{
		posx = e.pageX;
		posy = e.pageY;
	} else if (e.clientX || e.clientY) {
		posx = e.clientX + document.body.scrollLeft + document.documentElement.scrollLeft;
		posy = e.clientY + document.body.scrollTop + document.documentElement.scrollTop;
	}

    text = st;
		   
	elem.innerHTML = text;
	rExp = /&[a-z]+;/gi;
	textcpt = text.replace(rExp, '_');
	sl = textcpt.length * 6;
	offsety = 5;
		   
	elem.style.top = posy +"px";
	elem.style.left = posx + offsety +"px";
	elem.innerHTML = text;
	
	$("#tooltip").addClass('ferrortooltip');
	$("#tooltip").show();
}
function hideFieldError() {
	var elem = document.getElementById('tooltip');
	if (! elem) { return; }
	
	$("#tooltip").removeClass('ferrortooltip');
	$("#tooltip").hide();
}

function setRoleFields() {
	if ($('#role').val() == 'administrator') {
        $('.advanced_admin').hide();
	} else {
        $('.advanced_admin').show();
	}
}

function setIPhandlers() {
	$("#ipv4mode").change( function(event) {
    	setIPv4Fields();
    });
    setIPv4Fields();
    $("#ipv6mode").change( function(event) {
    	setIPv6Fields();
    });
    setIPv6Fields();
}
function setIPv4Fields() {
	$('.ipv4autoaddress').hide();
	if ($("#ipv4mode").val() == 'disabled' || $("#ipv4mode").val() == 'dhcp') {
		$('.ipv4configs').hide();
		if ($("#ipv4mode").val() == 'dhcp') {
			$('.ipv4autoaddress').show();
		}
	} else {
		$('.ipv4configs').show();
	}
}

function setIPv6Fields() {
	$('.ipv6autoaddress').hide();
	if ($("#ipv6mode").val() == 'disabled' || $("#ipv6mode").val() == 'manual') {
		$('.ipv6configs').hide();
		if ($("#ipv6mode").val() == 'manual') {
			$('.ipv6autoaddress').show();
		}
	} else {
		$('.ipv6configs').show();
	}	
}

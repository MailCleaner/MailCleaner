/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 */

var reloadtime = 5000;
var newparams = new Array();
var offset = 0;
var request;

$(document).ready(function(){
   autoloadSpool();
});

function autoloadSpool(newparams) {
	loadSpool(newparams);
	setTimeout("autoloadSpool()", reloadtime);
}
function loadSpool(newparams) {
	params = new Array();
	params['slave'] = slave;
	params['spool'] = spool;
	params['limit'] = limit;
	params['offset'] = offset;
	
	for (key in newparams) {
		params[key] = newparams[key];
	}
	offset = params['offset'];
	
	url = thisurl+'/monitorstatus/viewspool';
	for(key in params)
	{
		url += '/'+key+'/'+params[key];
	}
	if (request) {
		request.abort();
	}
	//alert(url);
	$("#reloading").html(loadingimg);
	request = $.ajax({
		  type: "GET",
		  url: url,
		  dataType: "html",
		  timeout: 10000,
		  async: true,
		  success: function(msg){
             $(".spoolbloc").html(msg);
             registerEvents();
          },
          error: function() {
  	         $(".spoolbloc").html('timed out');
          }
    });
}

function setPage(page) {
	a = new Array(); 
	a['offset']=(page-1)*limit; 
	loadSpool(a);
}

function registerEvents() {
	$(".tooltipped").mouseover(function(e) {
		showToolTip($(this), e);
	});
	
   $(".tooltipped").mouseout(function() {
	    hideToolTip();
	});
   
   $(".queuedelete").click(function(e) {
	   launchCommand('delete',$(this));
   });
   $(".queuelog").click(function(e) {
	   alert('log');
   });
   $(".queuetry").click(function(e) {
	   launchCommand('try',$(this));
   });
}

function launchCommand(command, el) {
   msgid = el.attr('id').substr(2);
   url = thisurl+'/monitorstatus/spool'+command+'/slave/'+slave+'/spool/'+spool+'/msg/'+msgid;
   el.parent().html(loadingimg);
   if (request) {
		request.abort();
	}
    request = $.ajax({
		  type: "GET",
		  url: url,
		  dataType: "html",
		  timeout: 10000,
		  async: true,
		  success: function(msg){
 	           loadSpool();
          },
          error: function() {
        	  el.parent().html('js error');
          }
    });
}
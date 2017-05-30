/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 */

var hostreloadtime = 60000;
var hosttimeout = 10000;
var moreStatus = new Array();
var moreTypes = new Object();
var moreModes = new Array();
var morePeriods = new Array();
var hostTimers = new Array();
var hostRequests = new Array();
var processTimers = new Array();
var processreloadtime = 2000;

$(document).ready(function(){
	
	$(".stopbutton").hover(
	   function(event) {
		   $(this).attr( 'src', images_path+"/stop_hover.gif");
	   },
	   function(event) {
		   $(this).attr( 'src', images_path+"/stop.gif");
	   }
	);
	$(".startbutton").hover(
		function(event) {
			$(this).attr( 'src', images_path+"/start_hover.gif");
		},
		function(event) {
			$(this).attr( 'src', images_path+"/start.gif");
		}
	);
	$(".restartbutton").hover(
		function(event) {
			$(this).attr( 'src', images_path+"/restart_hover.gif");
		},
		function(event) {
			$(this).attr( 'src', images_path+"/restart.gif");
		}
	);
	

	$(".hostbloc").each(function(index) { 
		 loadHost($(this).attr('id')); 
    });
	
	$(".pagehead").click(function() {
		for (t in hostTimers) {
			clearTimeout(hostTimers[t]);
		}
		for (r in hostRequests) {
			hostRequests[r].abort();
		}
		for (t in hostTimers) {
			clearTimeout(hostTimers[t]);
		}
	});

});

function loadHost(blocid) {
   var id = 1;
   var gid;

   if (blocid) {
      sepindex = blocid.indexOf('_');
      if (sepindex > 0) {
        id = blocid.substring(sepindex+1);
      } else {
        id = blocid;
        blocid = 'hostbloc_'+id;
      }
   }
   hosturl = thisurl+'/hoststatus/slave/'+id;
   
   if (moreTypes[id]) {
	   hosturl += '/t/';
	   for (m in moreTypes[id]) {
		   hosturl += m+'_'+moreTypes[id][m]+',';
	   }
   }
   if (moreModes[id]) {
	   hosturl += '/m/';
	   for (m in moreModes[id]) {
		   hosturl += m+'_'+moreModes[id][m]+',';
	   }
   }
   if (morePeriods[id]) {
	   hosturl += '/p/';
	   for (m in morePeriods[id]) {
		   hosturl += m+'_'+morePeriods[id][m]+',';
	   }
   }
   //alert(hosturl);
   $(".hoststatus_"+id+"_down").hide();
   $(".hoststatus_"+id+"_up").hide();
   $(".hoststatus_"+id+"_loading").show();
   hostRequests[id] = $.ajax({
	      type: "GET",
	      url: hosturl,
	      dataType: "html",
		  timeout: hosttimeout,
		  success: function(msg){
            $("#"+blocid).html(msg);
            $(".graphisrow").each( function(index) {
            	searchstring = new RegExp("host_\\d+_graph_\\S+");
            	matches = searchstring.exec($(this).attr('class'));
            	if (matches && matches.length > 0) {
            	  gid = matches[0];	            	
            	  if (moreStatus[gid]) {
            		 $("."+gid).show();
                         $("."+gid+'_link').hide();
            	  } else {
            		 $("."+gid).hide();
                         $("."+gid+'_link').show();
            	  }
            	}
            });
            if (moreStatus['procadvanced']) {
            	$(".procadvanced").show();
            	$(".notprocadvanced").hide();         	
            } else {
            	$(".procadvanced").hide();
            	$(".notprocadvanced").show();
            }
            alignHostStats(blocid);
            $(".hoststatus_"+id+"_loading").hide();
            $(".hoststatus_"+id+"_down").hide();
            $(".hoststatus_"+id+"_up").show();
            hostTimers[id] = setTimeout(function() { loadHost(blocid); blocid=null; }, hostreloadtime);
          },
          error: function() {
            $(".hoststatus_"+id+"_loading").hide();
            $(".hoststatus_"+id+"_up").hide();
            $(".hoststatus_"+id+"_down").show();
            hostTimers[id] = setTimeout(function() { loadHost(blocid); blocid=null; }, hostreloadtime);
          }
       });
}
function stopHostReload(id) {
	if (hostTimers[id]) {
		clearTimeout(hostTimers[id]);
	}
	if (hostRequests[id]) {
	   hostRequests[id].abort();
	}
	if (hostTimers[id]) {
		clearTimeout(hostTimers[id]);
	}
	$(".hoststatus_"+id+"_loading").hide();
    $(".hoststatus_"+id+"_up").hide();
    $(".hoststatus_"+id+"_down").hide();
	$(".hoststatus_"+id+"_stopped").show();
}
function restartHostReload(id) {
	loadHost('hostbloc_'+id);
	$(".hoststatus_"+id+"_stopped").hide();
	$(".hoststatus_"+id+"_loading").show();
}
function showGraph(id) {
	$("."+id).show();
	$("."+id+'_link').hide();
	moreStatus[id] = 1;
}
function hideGraph(id) {
	$("."+id).hide();
	$("."+id+'_link').show();
	moreStatus[id] = 0;
}

function stopstart(action, slave, process) {
	statustextid = slave+'_'+process+'_pstatus';
	$("."+statustextid).html(loadingimg);
	
	url = baseurl+'/monitorstatus/restartservice/s/'+slave+'/p/'+process+'/a/'+action;
	//alert(url);
	statusrequest = $.ajax({
		  type: "GET",
		  url: url,
		  dataType: "html",
		  timeout: hosttimeout,
		  async: false,
		  success: function(msg){
          $("."+statustextid).replaceWith(msg);
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
      	  $("."+statustextid).html('timed out - '+textStatus+' - '+errorThrown);
        }
	});
	
	showStatus(slave, process);
}

function showStatus(slave, process) {
	statustextid = slave+'_'+process+'_pstatus';
	//$("#"+statustextid).html(loadingimg);
	
	url = baseurl+'/monitorstatus/showprocess/s/'+slave+'/p/'+process;
	statusrequest = $.ajax({
		  type: "GET",
		  url: url,
		  dataType: "html",
		  timeout: hosttimeout,
		  async: true,
		  success: function(msg){
        $("."+statustextid).replaceWith(msg);
    	if ($("."+statustextid).hasClass('working')) {
    		processTimers[slave+'_'+process] = setTimeout(function() { showStatus(slave,process);slave=null;process=null;}, processreloadtime);
    	} else {
    		updateInformationalMessages();
    	}
      },
      error: function() {
    	  $("."+statustextid).html('timed out');
    	  if ($("."+statustextid).hasClass('working')) {
    	        processTimers[slave+'_'+process] = setTimeout(function() { showStatus(slave,process);slave=null;process=null;}, processreloadtime);
    	  }
      }
    });
	
}

function showAdvancedProcs() {
	$(".procadvanced").show();
	$(".notprocadvanced").hide();
	moreStatus['procadvanced'] = 1;
}
function hideAdvancedProcs() {
	$(".procadvanced").hide();
	$(".notprocadvanced").show();
	moreStatus['procadvanced'] = 0;
}

function showSpool(slave, spool) {
	var viewWidth=1000;
	var viewHeight=626;
    var WindowObjectReference = window.open(thisurl+'/viewspool/slave/'+slave+'/spool/'+spool, '', 'width=' + viewWidth
				+ ',height=' + viewHeight
				+ ',toolbar=0,resizable=1,status=0,scrollbars=yes');
				
}

function alignHostStats(blocid) {
	
	stats_block = $('#'+blocid+' .hostpiestatsbox').height();
	table_block = $('#'+blocid+' .hoststatstable').height();
	
	//alert('stats_block: '+stats_block+'  => table_bloc: '+table_block);
	top_margin = Math.floor((stats_block - table_block ) / 2);
	
	$('#'+blocid+' .hoststatstable').css('margin-top', top_margin);
}

function setMoreType(host, more, type) {
	if (!moreTypes[host]) {
        moreTypes[host] = new Object();
	}
	moreTypes[host][more] = type;
	clearTimeout(hostTimers[host]);
	loadHost(''+host);
}
function setMoreMode(host, more, mode) {
	if (!moreModes[host]) {
        moreModes[host] = new Object();
	}
	moreModes[host][more] = mode;
	clearTimeout(hostTimers[host]);
	loadHost(''+host);	   
}

function setMorePeriod(host, more, period) {
	if (!morePeriods[host]) {
        morePeriods[host] = new Object();
	}
	morePeriods[host][more] = period;
	clearTimeout(hostTimers[host]);
	loadHost(''+host);  
}

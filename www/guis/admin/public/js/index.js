/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 */

loadingcounts = false;
loadingstatus = false;

countstype = ['global', 'sessions', 'accepted', 'refused', 'delayed', 'relayed'];
currentcountstype = countstype.slice();
countsblock = ['1', '2'];
currentcountblocks =  countsblock.slice();
currentblock = 0;
previousblock = 0;
countspaused = false;

$(document).ready(function(){

	if (loadstatus()) {
		if (loadcounts()) {
			window.setInterval(loadcounts, 5000);
		} else {
			$("#countsbloc"+previousblock).html("timedout");
		}
	}
	alignStats();
	
	$('#globalstatsbloc').hover( 
			function() { countspaused = true; },
			function() { countspaused = false; });
});

function alignStats() {
	big_block = $('#globalstatsbloc').height();
	stats_block = big_block - $('#countsbloc'+currentblock+' h1').height();
	table_block = $('#countsbloc'+currentblock+' .globalstatstable').height();
	
	top_margin = Math.floor((stats_block - table_block ) / 2) - 10;
	
	$('#countsbloc'+currentblock+' .globalstatstable').css('margin-top', top_margin);
}

reloaddelay = 3000;
function loadcounts() {
	if (loadingcounts) {
		return;
	}
	if (countspaused) {
		return;
	}
	loadingcounts = true;
	
	if (currentcountstype.length < 1) {
		currentcountstype = countstype.slice();
	}
	curtype = currentcountstype.shift();
	
	url = baseurl+'/index/globalstats';
	if (curtype) {
		url += '/t/'+curtype;
	}

	statusrequest = $.ajax({
		  type: "GET",
		  url: url,
		  timeout: 10000,
		  dataType: "html",
		  success: function(msg){
			loadingcounts = false;
			if (msg == '') {
				loadcounts();
			} else {
				if (currentblock) {
			     	previousblock = currentblock;
				}
				if (currentcountblocks.length < 1) {
					currentcountblocks =  countsblock.slice();
				}
				currentblock = currentcountblocks.shift();
				
			    $("#countsbloc"+currentblock).html(msg);
			    if (previousblock) {
    			    $("#countsbloc"+previousblock).fadeOut(function() { $("#countsbloc"+previousblock).empty(); });

				    $("#countsbloc"+currentblock).fadeIn(function() {});
				    alignStats();
			    } else {
				    $("#countsbloc"+currentblock).fadeIn(function() {});
				    alignStats();
			    }
			}
          },
          error: function() {
        	  $("#countsbloc"+currentblock).html('timed out');
        	  loadingcounts = "timedout";
        	  countspaused = true;
		  return false;
          }
		});
          return true;
}

function loadstatus() {
	if (loadingstatus) {
      	  $("#globalstatusbloc").html('already loading');
		return;
	}
	loadingstatus = true;
	$("#globalstatusloaading").html(loadingimg);
	
	url = baseurl+'/index/globalstatus';
	statusrequest = $.ajax({
		  type: "GET",
		  url: url,
		  timeout: 10000,
		  dataType: "html",
		  success: function(msg){
          $("#globalstatusbloc").html(msg);
          loadingstatus = false;
        },
        error: function() {
      	  $("#globalstatusbloc").html('timed out');
      	  loadingstatus = "timedout";
          return false;
        }
		});
        return true;
}

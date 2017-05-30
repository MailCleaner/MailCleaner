/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 */

var fromline=1;
var maxlines=26;
var maxchars = 140;
var text_size = 12;
var search_position = 1;
var percent = -1;
var last_element = '';
var text_lineheight =  14;
var log_text_padding = 5;
var text_charwidth = 7.4;

var initial_window_width = 0;
var initial_window_height = 0;
var initial_logpanel_width = 0;
var initial_logpanel_height = 0;

var loadtextrequest;
var resize_timeout;
var searchtext_timeout;

$(document).ready(function(){
	
	initial_window_width = $(window).width();
	initial_window_height = $(window).height();
	initial_logpanel_width = $("#logviewpanel").width();
	initial_logpanel_height = $("#logviewpanel").height();
	text_size = $("#logviewpanel").css('font-size');
	//text_lineheight = $("#logviewpanel").css('line-height');
	//log_text_padding = $("#logviewpanel").css('padding');

	
	if ($("#logviewpanel").html()) {
		loadText();
	}
	
	$(window).resize(function() {
		if (resize_timeout) {
			clearTimeout(resize_timeout);
		}
		$("#logviewpanel").width(initial_logpanel_width - (initial_window_width - $(window).width()));
		$("#logviewpanel").height(initial_logpanel_height - (initial_window_height - $(window).height()));

		// set a timeout to avoid reloading data while being actually resizing. Just wait it's done.
		resize_timeout = setTimeout('loadText()', 200);
	});
	
	initEventHandlers();
});

function loadText() {
   if (! $("#logviewpanel").html()) {
		return;
   }

   if (loadtextrequest) {
      loadtextrequest.abort();
   }
   calculateNbLines();
   toline = fromline + maxlines;
   if ($('#logtextsearch_value').val()) {
       search_string = $('#logtextsearch_value').val();
   } else {
	   search_string = initial_search;
	   if (last_element == '') {
    	   last_element = 'search';
	   }
   }
   
   data = {
	   fl:  fromline,
	   tl:  toline,
	   le:  last_element,
	   s:   search_string,
	   sp:  search_position,
	   percent: percent,
	   maxlines: maxlines,
	   maxchars: maxchars
   };
   
   loadtextrequest = $.ajax({
	  	  type: "GET",
	  	  url: baseurl,
	  	  dataType: "html",
	  	  data: data,
		  async: false,
                  timeout: 10000,
	  	  success: function(msg){
	      $("#logviewpanel").html(msg);
	      percent = -1;
	    },
	    error: function() {
	  	  $("#logviewpanel").html('jserror');
	    }
	  });
   initEventHandlers();
}

function calculateNbLines() {
	if (text_size) {
    	text_size = text_size.replace(/\D+/g, '');
    	maxlines = Math.floor( ($("#logviewpanel").height() - log_text_padding) / (text_lineheight));
    	maxchars = Math.floor( $("#logviewpanel").width() / text_charwidth);
	}
}

function initEventHandlers() {
    $("#logtextsearch_value").keyup(function(event) {
    	if (searchtext_timeout) {
    		clearTimeout(searchtext_timeout);
		}
    	searchtext_timeout = setTimeout('loadText()', 1000);
    	last_element = 'search';
	});
	
    $("#loglinefrom_value").keyup(function(event) {
    	if (searchtext_timeout) {
    		clearTimeout(searchtext_timeout);
		}
    	fromline = $("#loglinefrom_value").val();
    	searchtext_timeout = setTimeout('loadText()', 1000);
    	last_element = 'linefrom';
	});
    
    $("#logpercent_value").keyup(function(event) {
    	if (searchtext_timeout) {
    		clearTimeout(searchtext_timeout);
		}
    	percent = $("#logpercent_value").val();
    	searchtext_timeout = setTimeout('loadText()', 1000);
    	last_element = 'linepercent';
	});
    
    $("#logviewnavig").height($("#logviewpanel").height());
    $("#loglines").height($("#logviewpanel").height()+12);
    $("#logtext").height($("#logviewpanel").height()+12);
}
function goToMatch(match) {
	search_position = match;
	last_element = 'match';
	loadText();
}
function goToLine(line) {
   last_element = 'linefrom';
   fromline=line;
   loadText();
}

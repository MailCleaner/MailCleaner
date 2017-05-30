/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 */

controller = 'managespamquarantine';
quarantineblock = 'quarantine';
$(document).ready(function(){
    if ($("#contentquarantine").html()) {
    	controller = 'managecontentquarantine';
    	quarantineblock = 'contentquarantine';
    }
    if ($("#tracinglog").html()) {
    	controller = 'managetracing';
    	quarantineblock = 'tracinglog';
    }
	
    $("#reference").keyup(function(event){
       if ($("#reference").val() == "") {
    	 showHideSearch(1);
       } else {
      	 showHideSearch(0);
       }
    });
    if ($("#reference").val() == "") {
    	showHideSearch(1);
    }
    
    $("#domain").change(function(event){
      enableSearchSubmit();
    });
    enableSearchSubmit();
    
    if (! $("#tracinglog").html()) {
        launchSearch();
    }
});

sort = 'date_desc';
page = 0;
resubmit = 0;
reloaddelay = 3000;
canceled = 0;
noshowreload = 0;
function launchSearch() {

	if (!noshowreload) {
       setLoading();
       noshowreload = 0;
	}
    url = baseurl+"/"+controller+"/search";
    url += '/search/'+$("#search").val();
    url += '/domain/'+$("#domain").val();
    url += '/sender/'+$("#sender").val();
    if ($("#subject").val()) {
        url += '/subject/'+$("#subject").val();
    }
    url += '/mpp/'+$("#mpp").val();
    if ($("#forced").is(':checked')) {
       url += '/forced/'+$("#forced").val();
    }
    url += '/td/'+$("#td").val();
    url += '/tm/'+$("#tm").val();
    url += '/fd/'+$("#fd").val();
    url += '/fm/'+$("#fm").val();
    url += '/sort/'+sort;
    if ($("#hidedup").is(':checked')) {
       url += '/hidedup/'+$("#hidedup").val();
    }
    if ($("#hiderejected").is(':checked')) {
       url += '/hiderejected/'+$("#hiderejected").val();
    }

    if ($('#showNewslettersOnly').is(':checked')) {
        url += '/showNewslettersOnly/' + $('#showNewslettersOnly').val();
    }
    
    if ($("#reference").val()) {
    	var ref = $("#reference").val();
    	ref = ref.replace(/\//, '-');
        url += '/reference/'+ref;
    }
    if (page) {
    	url += '/page/'+page;
    }
    if (resubmit) {
        url += '/submit/1';
        resubmit = 0;
    } else {
    	if (canceled) {
    		return;
    	}
    }
    //alert(url);
    statusrequest = $.ajax({
		  type: "GET",
		  url: url,
		  dataType: "html",
		  timeout: 120000,
		  success: function(msg){
            $("#"+quarantineblock).html(msg);
            setHandlers();
          },
          error: function() {
        	$("#"+quarantineblock).html('jserror');
          }
    });
    
    if ($("#dataloading").html()) {
    	setTimeout("launchSearch()", reloaddelay)
    }
}

function cancelSearch() {
	url = baseurl+"/"+controller+"/search";
	url += "/cancel/1";
	canceled = 1;
	//alert(url);
	statusrequest = $.ajax({
		  type: "GET",
		  url: url,
		  dataType: "html",
		  timeout: 120000,
		  success: function(msg){
           $("#"+quarantineblock).html(msg);
         },
         error: function() {
       	$("#"+quarantineblock).html('jserror');
         }
   });
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

function setOrder(newsort) {
	sort = newsort;
	launchSearch();
}

function setPage(newpage) {
	page = newpage;
	noshowreload = 1;
	launchSearch();
}

function enableSearchSubmit() {
  if ($("#domain").val() == "" || ($("#dosearch").val() && $("#reference").val() != "")) {
    $("#submit").attr("disabled", "disabled");
  } else {
    $("#submit").removeAttr('disabled');
  }
}


function showHideSearch(status) {
  fields = new Array('search', 'domain', 'mpp', 'sender', 'subject', 'fd', 'fm', 'td', 'tm', 'submit');
  for(i=0;i<fields.length;i++) {
     if (status) {
       $("#"+fields[i]).removeAttr('disabled');
     } else {
       $("#"+fields[i]).attr("disabled", "disabled");
     }
  };
  
  if (status) {
      $("#dosearch").attr("disabled", "disabled");
  } else {
      $("#dosearch").removeAttr('disabled');
  }
  enableSearchSubmit();
}

// old
function highlightActionIcon(img_row, forced, img_ext, img_path) {
	  var img1 = "r" + img_row;
	  var img2 = "v" + img_row;
	  var img3 = "a" + img_row;
	  
	  var img_src1 = img_path + '/released' + img_ext + ".png";
	  if (forced < 1) {
	    img_src1 = img_path + '/release' + img_ext + ".png";
	  }
	  var img_src2 = img_path + '/info' + img_ext + ".png";  
	  var img_src3 = img_path + '/analyse' + img_ext + ".png";

	  var elem = document.getElementById(img1);
	  elem.src = img_src1;
	  elem = document.getElementById(img2);
	  elem.src = img_src2;
	  elem = document.getElementById(img3);
	  if (elem) {
          elem.src = img_src3;
	  }
}

function showHideToolTip(e, show, st, comm) {
	   var elem = document.getElementById('tooltip');
	   var offsety = 0;

	   if (! elem) { return; }
	   if (! show) {
	      elem.style.display = 'none';
	      elem.style.visibility = 'hidden';
	      return;
	   }

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
	   if (st == 'fm') { text = fmtext; }
	   if (st == 'vi') { text = vitext; }
	   if (st == 'sa') { text = satext; }
	   if (st == 'cl') { text = cltext; }
	   
	   elem.innerHTML = text;
	   rExp = /&[a-z]+;/gi;
	   textcpt = text.replace(rExp, '_');
	   sl = textcpt.length * 6;
	   if (comm == "L") {
	      offsety = 5;
	   } else if (comm == "R") {
	      offsety = -sl;
	   } else {
	      offsety = comm;
	   }
	   
	   elem.style.top = posy + 10 +"px";
	   elem.style.left = posx + offsety +"px";
	   elem.style.display = 'block';
	   elem.style.visibility = 'visible';
	   elem.innerHTML = text;
}

var popup_width = 500;
var popup_height = 251;
var cpopup_height = 200;
var info_width = 500;
var info_height = 500;

function force(address, msgid, storeid) {
	window.open('/fm.php?a=' + address + '&id=' + msgid + '&s=' + storeid
			+ '&lang=en&pop=up', '', 'width=' + popup_width
			+ ',height=' + popup_height
			+ ',toolbar=0,resizable=1,scrollbars=0,status=0');
}

function cforce(msgid, storeid) {
	window.open(baseurl+'/managecontentquarantine/force/id/'+msgid+"/s/"+storeid
			+ '/lang/en/pop/up', '', 'width=' + popup_width
			+ ',height=' + cpopup_height
			+ ',toolbar=0,resizable=1,scrollbars=0,status=0');
}

function analyse(address, msgid, storeid) {
	window.open('/send_to_analyse.php?a=' + address + '&id=' + msgid
			+ '&s=' + storeid + '&lang=en&pop=up', '', 'width='
			+ popup_width + ',height=' + popup_height
			+ ',toolbar=0,resizable=1,scrollbars=0,status=0');
}

function infos(address, msgid, storeid) {
	window.open('/vi.php?a=' + address + '&id=' + msgid + '&s=' + storeid
			+ '&lang=en&pop=up', '', 'width=' + info_width
			+ ',height=' + info_height
			+ ',toolbar=0,resizable=1,status=0,scrollbars=1');
}

function cinfos(msgid, storeid) {
	window.open(baseurl+'/managecontentquarantine/view/id/'+msgid+"/s/"+storeid
			+ '/lang/en/pop/up', '', 'width=' + info_width
			+ ',height=' + info_height
			+ ',toolbar=0,resizable=1,status=0,scrollbars=1');
}

function showLogExtract(slaveid, msgid) {
   trid = 'tracelog_'+slaveid+'_'+msgid;
   tdid = 'tracelogmsg_'+slaveid+'_'+msgid;
   imgid = 'img_logextract_'+slaveid+'_'+msgid;
   imgsrc = $("."+imgid).attr('src');
   var patt=new RegExp("_up.");
   if (patt.test(imgsrc)) {
      newsrc=imgsrc.replace('_up.', '_down.');
      $('.'+trid).show();
      $("."+imgid).attr('src', newsrc);
   } else {
      newsrc=imgsrc.replace('_down.', '_up.');
      $('.'+trid).hide();
      $("."+imgid).attr('src', newsrc);
      return;
   }
   if ($("."+tdid).hasClass('searched')) {
      return;
   } 
   $("."+tdid).html(loadingimg);
   url = baseurl+'/managetracing/logextract/s/'+slaveid+'/m/'+msgid;
   //alert(tdid+' -- '+$("."+tdid).html());
   extractrequest = $.ajax({
                  type: "GET",
                  url: url,
                  dataType: "html",
                  timeout: 120000,
                  async: true,
                  success: function(msg){
                      $("."+trid).html(msg);
                  },
                  error: function(XMLHttpRequest, textStatus, errorThrown) {
                      $("."+trid).html(textStatus+' - '+errorThrown);
                  }
                });

}

function setHandlers() {
   $('.checklog_action').click( function() {
         if ($('.checklog_action').is(':checked')) {
            $('.checklog_box').attr('checked', true);             
         } else {
            $('.checklog_box').attr('checked', false);
         }
      });
   $('.checklog_box').click( function() {
       if ($('.checklog_box:checked').length > 0) {
         $('.download_row').show();
       } else {
         $('.download_row').hide();
       }
   });
}

function downloadTrace(filename) {
  url = baseurl+'/managetracing/downloadtrace';
  msgs = '';
  $('.checklog_box:checked').each( function() {
     name=$(this).attr('name');
     msgs += name.replace('checklog_', '')+',';
  });
  url += '/m/'+msgs+'/f/'+filename;
  window.location = url;
}

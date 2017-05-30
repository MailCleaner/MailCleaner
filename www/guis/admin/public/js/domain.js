/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 */

var statusrequest;
var page;

$(document).ready(function(){
	$("#sname").keyup(function(event) {
		loadsearch($("#sname").val(), 1);
	});
	$("#search").keyup(function(event) {
		loadsearch($("#search").val(), 1);
	});
	
	if ($("#sname").val() == '') {
        $("#sname").attr('class', 'searchempty');
	    $("#sname").val(defaultsearchstring);
	}
	if ($("#search").val() == '') {
        $("#search").attr('class', 'searchempty');
	    $("#search").val(defaultsearchstring);
	}
	
	
	$("#sname").click(function(event) {
		$("#sname").attr('class', '');
		$("#sname").val('');
	});
	$("#search").click(function(event) {
		$("#search").attr('class', '');
		$("#search").val('');
	});
	
	$("#domainpanel").change(function(event){
		loadDomainPanel($("#domainpanel").val());
    });

    $("#enable_whitelists").click(function(event){
                 if ($("#enable_whitelists").is(':checked')) {
             $("#whitelist_list").show();
                 } else {
                 $("#whitelist_list").hide();
                 }
    });

    $("#enable_warnlists").click(function(event){
                 if ($("#enable_warnlists").is(':checked')) {
             $("#warnlist_list").show();
                 } else {
             $("#warnlist_list").hide();
                 }
    });

    $("#enable_blacklists").click(function(event){
                 if ($("#enable_blacklists").is(':checked')) {
             $("#blacklist_list").show();
                 } else {
                 $("#blacklist_list").hide();
                 }
    });

	$("#sname").attr('autocomplete', 'off');
	$("#search").attr('autocomplete', 'off');
	
	setLocalHandlers();
	
	if (message != '' && domainaddurl !='') {
		loadsearch($("#sname").val(), page);
		//loadsearchurl(domainsearchurl+domainaddurl);
	}
});

function setLocalHandlers() {
	$("#batv_check").click(function(event) {
		setBATVKey();
	});
	setBATVKey();
	
	$("#dkim_signature").change(function(event) {
		setDKIMFields();
	});
	setDKIMFields();

    setSMTPFields();
    $("#smtpauth").click(function(event){
        setSMTPFields();
    });

    $('#clearsmtpauthcachebutton').click(function(evet) {
         clearauth_text = $('#clearsmtpauthcachebutton').attr('value');
         $('#clearsmtpauthcachebutton').attr("disabled", "disabled");
         $('#clearsmtpauthcachebutton').attr('value', 'Clearing...');
         url = baseurl+'/domain/clearsmtpauthcache/name/'+$("#domainname").html()
         clearing = $.ajax({
                type: "GET",
                url: url,
                dataType: "html",
                success: function(msg){
                    $('#clearsmtpauthcachebutton').attr('value', clearauth_text);
                    $('#clearsmtpauthcachebutton').removeAttr("disabled");
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    $("#clearsmtpauthcachebutton").attr('value', errorThrown);
                }
            });
     });

}
function setBATVKey() {
	if ($("#batv_check").is(':checked')) {
		$("#batv_secret").removeAttr('readonly');
	} else {
		$("#batv_secret").attr('readonly', 'readonly');
	}
}

function setDKIMFields() {
	if ($("#dkim_signature").val() == '_none') {
		$(".dkim_domain_fieldset").hide();
		$(".dkim_selandkey_fieldset").hide();
		$(".dkim_help_field").hide();
	} else if ($("#dkim_signature").val() == '_default') {
		$(".dkim_domain_fieldset").hide();
		$(".dkim_selandkey_fieldset").hide();
		$(".dkim_help_field").show();
	} else if ($("#dkim_signature").val() == '_custom') {
		$(".dkim_domain_fieldset").show();
		$(".dkim_selandkey_fieldset").show();
		$(".dkim_help_field").show();
	} else {
		$(".dkim_domain_fieldset").hide();
		$(".dkim_selandkey_fieldset").show();
		$(".dkim_help_field").show();
	}
}

function setSMTPFields() {
    if ($("#smtpauth").is(':checked')) {
        $(".smtp_auth_cachetime").show();
    } else {
        $(".smtp_auth_cachetime").hide();
    }
}

function loadsearch(searchstring, page) {
  if ($("#domainname").html() != '') {
	domainname = $("#domainname").html();  
  }
  if ((!domainname || domainname == '') && $("#username").val() != '') {
    domainname = $("#username").val();
  }
  if (searchstring == '' && $("#sname").val() != defaultsearchstring) {
    searchstring = $("#sname").val();
  }
  url =  domainsearchurl+'/sname/'+searchstring+"/name/"+domainname+"/page/"+page;
  //alert(url);
  loadsearchurl(url);
}

function loadsearchurl(url) {
	$("#resultpanel").html(loadingdomain);
	statusrequest = $.ajax({
		  type: "GET",
		  url: url,
		  timeout: 10000,
		  dataType: "html",
		  success: function(msg){
            $("#resultpanel").html(msg);
          },
          error: function() {
        	  $("#resultpanel").html('timed out');
          }
		});
}

function loadurl(url) {
	window.document.location = url;
}

function loadDomainPanel(panel) {
	$("#domainformcontent").slideUp(500, function() {
    	statusrequest = $.ajax({
		  type: "GET",
		  url: thisurl+'/panel/'+panel,
		  dataType: "html",
		  success: function(msg){
            $("#domainformcontent").html(msg);
            $("#domainformcontent").slideDown(500);
    	    setLocalHandlers();
          },
          error: function() {
        	  $("#domainformcontent").html('jserror');
          }
		});
	});
	
	panellinkrequest = $.ajax({
		  type: "GET",
		  url: domainpanellinkurl+'/panel/'+panel+'/name/'+$("#name").val(),
		  dataType: "html",
		  success: function(msg){
            $("#nextpreviouslinks").html(msg);
            $("#domainpanel").val(panel);
          },
          error: function() {
        	  $("#nextpreviouslinks").html('jserror');
          }
		});
}

stopreloadtest = 0;
function testDestinationSMTP(domain, reset) {
	$("#settingstest").show();
	testsetting = $.ajax({
		type: "GET",
		  url: testdestinatiosmtpURL+'/name/'+domain+'/reset/'+reset,
		  dataType: "html",
		  success: function(msg){
          $("#settingstest").html(msg);
          mstr = new RegExp('loadingwhitebg.gif',"g");
          if (! msg.match(mstr)) {
        	  stopreloadtest = 1;
          }
        },
        error: function() {
      	  $("#settingstest").html('jserror');
        }
		
	});
	if (stopreloadtest < 1) {
        setTimeout("testDestinationSMTP('"+domain+"', 0)", 2000);
	}
}

stopreloadtest = 0;
function testCallout(domain, reset) {
	$("#settingstest").show();
	testsetting = $.ajax({
		type: "GET",
		  url: testcalloutURL+'/name/'+domain+'/reset/'+reset,
		  dataType: "html",
		  success: function(msg){
          $("#settingstest").html(msg);
          mstr = new RegExp('loadingwhitebg.gif',"g");
          if (! msg.match(mstr)) {
        	  stopreloadtest = 1;
          }
        },
        error: function() {
      	  $("#settingstest").html('jserror');
        }
		
	});
	if (stopreloadtest < 1) {
        setTimeout("testCallout('"+domain+"', 0)", 2000);
	}
}

function testUserauth(domain) {
	gusername = $("#testusername").val();
	gpassword = $("#testpassword").val();
	
	$("#settingstest").show();
	$("#settingstestwaiting").show();
	$("#settingstestwaiting").html(loadingimg);
	testauth = $.ajax({
		type: "GET",
		  url: testuserauthurl+'/name/'+$("#name").val()+'/username/'+encodeURIComponent(gusername)+'/password/'+encodeURIComponent(gpassword),
		  dataType: "html",
		  success: function(msg){
          $("#settingstestwaiting").hide();
          $("#settingstest").html(msg);
	      },
	      error: function() {
	      	  $("#settingstest").html('jserror');
	      }
	});
}

function changeConnector() {
	getconnector = $.ajax({
		type: "GET",
		  url: calloutconnectorurl+'/name/'+$("#name").val()+'/connector/'+$("#connector").val(),
		  dataType: "html",
		  success: function(msg){
            $("#connectorform").html(msg);
	      },
	      error: function() {
	      	  $("#connectorform").html('jserror');
	      }
	});
}

function changeAuthConnector() {
	getconnector = $.ajax({
		type: "GET",
		  url: authconnectorurl+'/name/'+$("#name").val()+'/connector/'+$("#connector").val(),
		  dataType: "html",
		  success: function(msg){
            $("#connectorform").html(msg);
	      },
	      error: function() {
	      	  $("#connectorform").html('jserror');
	      }
	});
}

function goMultipleDomains() {
	$("#mdomainname").val('');
	$("#domainname").val('');
    $("#mdomainname").toggle();
	$("#domainname").toggle();
	$(".adddomaincontentpaneltitle").toggleClass('adddomaincontentpaneltitlearea');
}

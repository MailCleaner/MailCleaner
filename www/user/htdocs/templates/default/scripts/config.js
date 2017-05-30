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
 
function goConf(topic) {
  document.location = "configuration.php?t="+topic;
}

function enableSpamTag(value) {
  if (value == 1) {
   window.document.getElementById('param_spam_tag').disabled=false;
  } else {
   window.document.getElementById('param_spam_tag').disabled=true;
  }
}

function groupAddressesConfig() {
    checkbox = document.getElementById('quar_gui_group_quarantines_cb');
    if (checkbox.checked) {
        document.getElementById('quar_gui_default_address').disabled=true;
    } else {
        document.getElementById('quar_gui_default_address').disabled=false;
    }   
    document.getElementById('filter').submit();
}

function changeOtherSummaryToField() {
    select = document.getElementById('param_summary_to_select');
    if (select.value == 'other') {
        document.getElementById('summary_to_other').style.display = 'block';
    } else {
        document.getElementById('summary_to_other').style.display = 'none';
    }
}



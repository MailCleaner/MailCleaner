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

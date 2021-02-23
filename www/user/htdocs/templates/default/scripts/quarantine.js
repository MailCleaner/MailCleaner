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


function highlightActionIcon(img_row, forced, img_ext) {
  var img1 = "r" + img_row;
  var img2 = "v" + img_row;
  var img3 = "a" + img_row;
  
  var img_src1 = forced_icon + img_ext + ".png";
  if (forced < 1) {
    img_src1 = force_icon + img_ext + ".png";
  }
  var img_src2 = info_icon + img_ext + ".png";  
  var img_src3 = analyse_icon + img_ext + ".png";

  var elem = document.getElementById(img1);
  elem.src = img_src1;
  elem = document.getElementById(img2);
  elem.src = img_src2;
  elem = document.getElementById(img3);
  elem.src = img_src3;
}

function force(msgid, storeid, to) {
  window.open('fm.php?a='+ encodeURIComponent(to) +'&id='+msgid+'&s='+storeid+'&lang='+lang+'&pop=up', '', 'width='+popup_width+',height='+popup_height+',toolbar=0,resizable=1,scrollbars=0,status=0');
}

function summary() {
 window.open('summary.php?a='+ encodeURIComponent(email_address)+'&days='+nb_days+'&mask_forced='+mask_forced, '', 'width='+popup_width+',height='+popup_height+',toolbar=0,resizable=1,scrollbars=0,status=0');
}

function purge() {
  window.open('purge.php?a='+ encodeURIComponent(email_address)+'&days='+nb_days+'&mask_forced='+mask_forced, '', 'width='+popup_width+',height='+popup_height+',toolbar=0,resizable=1,scrollbars=0,status=0');
}

function analyse(msgid, storeid, to) {
  window.open('send_to_analyse.php?a='+ encodeURIComponent(to) +'&id='+msgid+'&s='+storeid+'&lang='+lang+'&pop=up', '', 'width='+popup_width+',height='+popup_height+',toolbar=0,resizable=1,scrollbars=0,status=0');
}

function infos(msgid, storeid, to) {
  window.open('vi.php?a='+ encodeURIComponent(to) +'&id='+msgid+'&s='+storeid+'&lang='+lang+'&pop=up', '', 'width='+info_width+',height='+info_height+',toolbar=0,resizable=1,status=0,scrollbars=1');
}

function groupAddresses() {
    checkbox = document.getElementById('filter_group_quarantines_cb');
    if (checkbox.checked) {
        document.getElementById('filter_a').disabled=true;
    } else {
        document.getElementById('filter_a').disabled=false;
    }
    document.getElementById('filter').submit();
}

function showSpamOnly() {
    spam = document.getElementById('filter_spam_only_cb');
    if (spam.checked) {
        document.getElementById('filter_newsl_only_cb').checked=false;
        document.getElementById('filter_newsl_only_checkbox').value=0;
    }
    document.getElementById('filter').submit();
}

function showNewslOnly() {
    newsl = document.getElementById('filter_newsl_only_cb');
    if (newsl.checked) {
        document.getElementById('filter_spam_only_cb').checked=false;
        document.getElementById('filter_spam_only_checkbox').value=0;
    }
    document.getElementById('filter').submit();
}

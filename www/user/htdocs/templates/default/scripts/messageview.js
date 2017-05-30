function initpanels() {
  for (f in openedparts) {
    var elem = document.getElementById(openedparts[f]);
    var image = document.getElementById(openedparts[f]+"_expandimg");
    elem.style.display = 'block';
    elem.style.visibility = 'visible';
    image.src = expanded_image;
  }
  return;
}

function openclosepanel(panel) {
  var elem = document.getElementById(panel);
  var image = document.getElementById(panel+"_expandimg");
  
  if (elem.style.visibility != 'visible' ) {
    elem.style.display = 'block';
    elem.style.visibility = 'visible';
    image.src = expanded_image;
    return;
  }
  elem.style.display = 'none';
  elem.style.visibility = 'hidden';
  image.src = contracted_image;
  return;
}

function redimAndForce() {
  redim(popup_width, popup_height);
  document.location = 'fm.php?a='+email_address+'&id='+msgid+'&s='+storeid+'&lang='+lang+'&pop=up';
}

function redimAndAnalyse() {
  redim(popup_width, popup_height);
  document.location = 'send_to_analyse.php?a='+email_address+'&id='+msgid+'&s='+storeid+'&lang='+lang+'&pop=up';
}

function redim(width, height) {
  window.resizeTo(width,height);

  // workaround for ie7 resizeTo
  if (navigator.appVersion.indexOf("MSIE 7.") != -1) {
    var cp = document.createElement("div");
    cp.style.position = "absolute";
    cp.style.width = "0px";
    cp.style.height = "0px";
    cp.style.right = "0px";
    cp.style.bottom = "0px";
    document.body.appendChild(cp);
    var current_width = cp.offsetLeft;
    var current_height = cp.offsetTop;
    var dw = popup_width - current_width;
    var dh = popup_height - current_height;
    document.body.removeChild(cp);  
    window.resizeBy(dw, dh);
  }
  // end workaround

  if (window.scrollbars) {
    window.scrollbars.visible = false;
  }
}

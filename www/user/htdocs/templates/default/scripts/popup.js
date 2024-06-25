function disableCloseWithoutReferrer() {
  if (window.opener == null) {
    if (document.getElementById('closemebutton')) {
        document.getElementById('closemebutton').style.display = 'none';
    }
    if (document.getElementById('close')) {
        document.getElementById('close').style.display = 'none';
    }
  }
}

function reloadParent() {
  opener.location.reload(true);
}

function closeAndReload() {
  opener.location.reload(true);
  window.open('', '_self', ''); window.close();
}

function closeMe() {
    window.open('', '_self', ''); window.close();
}

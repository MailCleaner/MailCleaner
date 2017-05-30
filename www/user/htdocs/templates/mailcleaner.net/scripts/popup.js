function reloadParent() {
  opener.location.reload(true);
}

function closeAndReload() {
  opener.location.reload(true);
  self.close();
}

function closeMe() {
  self.close();
}
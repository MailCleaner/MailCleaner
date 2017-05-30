function showBar() {
  var elems = getElementsByStyleClass('bargraph');
  
  for (var ei in elems) {
    var elem = elems[ei];
    if (elem.style) {
      elem.style.display = 'block';
      elem.style.visibility = 'visible';
    }
  }
  
  var elems = getElementsByStyleClass('piegraph');
  
  for (var ei in elems) {
    var elem = elems[ei];
    if (elem.style) {
      elem.style.display = 'none';
      elem.style.visibility = 'hidden';
    }
  }
}

function showPie() {
  var elems = getElementsByStyleClass('piegraph');
  
  for (var ei in elems) {
    var elem = elems[ei];
    if (elem.style) {
      elem.style.display = 'block';
      elem.style.visibility = 'visible';
    }
  }
  
  var elems = getElementsByStyleClass('bargraph');
  
  for (var ei in elems) {
    var elem = elems[ei];
    if (elem.style) {
      elem.style.display = 'none';
      elem.style.visibility = 'hidden';
    }
  }
}

function getElementsByStyleClass (className) {
  var all = document.all ? document.all :
    document.getElementsByTagName('*');
  var elements = new Array();
  for (var e = 0; e < all.length; e++)
    if (all[e].className == className)
      elements[elements.length] = all[e];
  return elements;
}

function useDateSearchType (type) {
 if (type == 'date') {
   window.document.getElementById('filter_datetype_period').checked=false;
   window.document.getElementById('filter_datetype_date').checked=true;
   
   window.document.getElementById('filter_period').disabled=true;
   window.document.getElementById('filter_startday').disabled=false;
   window.document.getElementById('filter_startmonth').disabled=false;
   window.document.getElementById('filter_startyear').disabled=false;
   window.document.getElementById('filter_stopday').disabled=false;
   window.document.getElementById('filter_stopmonth').disabled=false;
   window.document.getElementById('filter_stopyear').disabled=false;
  } else {
   window.document.getElementById('filter_datetype_period').checked=true;
   window.document.getElementById('filter_datetype_date').checked=false;

   window.document.getElementById('filter_period').disabled=false;
   window.document.getElementById('filter_startday').disabled=true;
   window.document.getElementById('filter_startmonth').disabled=true;
   window.document.getElementById('filter_startyear').disabled=true;
   window.document.getElementById('filter_stopday').disabled=true;
   window.document.getElementById('filter_stopmonth').disabled=true;
   window.document.getElementById('filter_stopyear').disabled=true;

  }
 
}

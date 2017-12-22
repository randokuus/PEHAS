function show_hide_panel (skin) {    
    obj = document.getElementById('hideColumnLink');

    if (document.getElementById('rightColumn').style.display == "none") {
        obj.style.backgroundImage = 'url("./img/templates/' + skin + '/img/b_close_col.gif")';
    } else {
        obj.style.backgroundImage = 'url("./img/templates/' + skin + '/img/b_open_col.gif")';
    }

    show_hide_div('rightColumn');

    if (window.resizeDivs) {
        resizeDivs();
    }

//    show_hide_div('calendar');
//    show_hide_div('messenger');
//    show_hide_div('shortcuts');
//    show_hide_div('search');
}

function show_hide_div(id) {
    if (document.getElementById) { // DOM3 = IE5, NS6
        if (document.getElementById(id).style.display == "none"){
            document.getElementById(id).style.display = 'block';
//          filter(("img"+id),'imgin');
        } else {
//          filter(("img"+id),'imgout');
            document.getElementById(id).style.display = 'none';
        }
    } else {
        if (document.layers) {
            if (document.id.display == "none"){
                document.id.display = 'block';
//              filter(("img"+id),'imgin');
            } else {
//              filter(("img"+id),'imgout');
                document.id.display = 'none';
            }
        } else {
            if (document.all.id.style.visibility == "none"){
                document.all.id.style.display = 'block';
            } else {
//              filter(("img"+id),'imgout');
                document.all.id.style.display = 'none';
            }
        }
    }
}
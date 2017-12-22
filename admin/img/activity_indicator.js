
/*
* Get object position on the screen;
* @return array - coordinates of object
*/
getObjectPosition = function(obj) {
    var curleft = curtop = 0;
    if (obj.offsetParent) {
        curleft = obj.offsetLeft
        curtop = obj.offsetTop
        while (obj = obj.offsetParent) {
            curleft += obj.offsetLeft
            curtop += obj.offsetTop
        }
    }
    return [curleft,curtop];
}
/*
* Get
*/
getVisibleAreaPosition = function()
{
    if (window.innerHeight)
    {
        posY = window.pageYOffset;
        posX = window.pageXOffset;
    }
    else if (document.documentElement && document.documentElement.scrollTop)
    {
        posX = document.documentElement.scrollLeft
        posY = document.documentElement.scrollTop
    }
    else if (document.body)
    {
        posX = document.body.scrollLeft;
        posY = document.body.scrollTop;
    }
    return [posX,posY];
}
/*
* Get visible area width/height
*/
getWindowSize = function()
{
    var myWidth = 0, myHeight = 0;
    if( typeof( window.innerWidth ) == 'number' )
    {
        //Non-IE
        myWidth = window.innerWidth;
        myHeight = window.innerHeight;
    }
    else if( document.documentElement &&
        ( document.documentElement.clientWidth || document.documentElement.clientHeight )
    ){
        //IE 6+ in 'standards compliant mode'
        myWidth = document.documentElement.clientWidth;
        myHeight = document.documentElement.clientHeight;
    } else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
        //IE 4 compatible
        myWidth = document.body.clientWidth;
        myHeight = document.body.clientHeight;
    }
    return [myWidth,myHeight];
}

getObjectSize = function( obj )
{
    var myWidth = 0, myHeight = 0;
    myWidth = obj.scrollWidth;
    myHeight = obj.scrollHeight;
    return [myWidth,myHeight];
}

/*
*
*/
centralizeObject = function( obj )
{
    obj.style.position = 'absolute';
    // window sizes
    var window = getWindowSize();
    // visible area position according to BODY canvas
    var canvas = getVisibleAreaPosition();

    // object size
    var object = getObjectSize(obj);

    // where must be places object.
    var newObjectPos =
    [
         window[0]/2 - object[0]/2 + canvas[0]
        ,window[1]/2 - object[1]/2 + canvas[1]
    ];
    obj.style.top = newObjectPos[1];
    obj.style.left = newObjectPos[0];
    return ;
}

var ActIndicator;

function ActivityIndicator(){

    var indicator;
    var indicator_box;
    var indicator_label;

    this.indicator_img      ='img/indicator.gif';
    this.indicator_id       ='activity_indicator';
    this.indicator_box_id   ='activity_indicator_box';
    this.indicator_label_id ='activity_indicator_label';
    this.indicator_label    ='Please wait, processing ...';

}

ActivityIndicator.prototype = {

    //===========================================
    // Set img source for indicator.
    setIndicatorImg : function( img )
    {
        this.indictor_img = img;
    }
    ,
    //===========================================
    // Set div element ID's for activity indicator.
    setIndicatorId : function( id ){ this.indicator_box_id = id; }
    ,
    setIndicatorBoxId : function( id ){ this.indicator_id = id; }
    ,
    setIndicatorLabelId : function( id ){ this.indicator_label_id = id; }
    ,
    //===========================================
    // Set indicator label ...
    setLabel : function( text )
    {
        var label = document.getElementById( this.indicator_label_id );
        label.lastChild.nodeValue = text;
    }
    ,
    //===========================================
    // this must overwrite default onscroll event.
    moveDiv : function( e )
    {
        var targ;
        if (!e) var e = window.event;
        //alert( ActIndicator.indicator_id ); return;
        var act_indic = document.getElementById( ActIndicator.indicator_id );
        var ai_position = getObjectPosition(act_indic);
        var ai_size = getObjectSize(act_indic);

        centralizeObject( act_indic );
        return;
        //alert( act_indic.style );
        act_indic.style.left = 300 + document.body.scrollLeft;
        act_indic.style.top = 300 + document.body.scrollTop;
        var i_text = document.getElementById( ActIndicator.indicator_label_id );
        i_text.innerHTML = 'window position: '+getTop()+'<br/>div position: '+findPos(act_indic);
        i_text.innerHTML = i_text.innerHTML+ '<br/>window size: ' + windowSize();
        i_text.innerHTML = i_text.innerHTML+ '<br/>element Size: '+getSize(act_indic);
        //alert( getTop() );
    }
    ,
    //===========================================
    // Create indicator and add into body
    create : function()
    {
        // check, if such element already exists or not
        var indic_id = document.getElementById( this.indicator_id );
        if( indic_id || indic_id!= null ) return;

        // create activity indicator main window.
        var main_div = document.createElement('div');
        main_div.id = this.indicator_id;

        // create activity indicator box.
        var ai_box = document.createElement('div');
        ai_box.id = this.indicator_box_id;

        // create activity label.
        var ai_label = document.createElement('div');
        ai_label.id = this.indicator_label_id;
        ai_label.style.display = 'inline';
        ai_label.style.margin = '0px';

        // create indicator img..
        var ai_img = document.createElement( 'img' );
        ai_img.src = this.indicator_img;
        ai_img.alt = this.indicator_label;
        ai_img.title = this.indicator_label;

        // insert label into ai_label_div ..
        var label_text = document.createTextNode( this.indicator_label );
        ai_label.appendChild( label_text );
        // put all together
        ai_box.appendChild( ai_img );
        ai_box.appendChild( ai_label );
        main_div.appendChild(ai_box);

        var body = document.getElementsByTagName( 'body' );
        body[0].appendChild( main_div );

    }
    ,
    //===========================================
    // Initialization. Creates indicator
    Init : function()
    {
        ActIndicator = this;
        ActIndicator.create();
        ActIndicator.Stop();
    }
    ,
    //===========================================
    // Assend default onscroll event with this.moveDiv one.
    // put into the center of window screen and show indicator
    Start : function()
    {
        var ai_main = document.getElementById( this.indicator_id );
        ai_main.style.display = 'inline';
        window.onscroll = this.moveDiv;
        centralizeObject( ai_main );
    }
    ,
    //===========================================
    // set onscroll event back to default
    // hide indicator
    Stop : function()
    {
        var ai_main = document.getElementById( this.indicator_id );
        ai_main.style.display = 'none';
        window.onscroll = null;
    }
}

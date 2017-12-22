/* IE6 flicker fix
-------------------------------------------------- */
try { document.execCommand("BackgroundImageCache", false, true); } catch(err){}


function addClassName(elem, classNameToAdd){
	if (elem){
		elem.className = elem.className + ' ' + classNameToAdd;
	}
}
function removeClassName(elem, classNameToRemove){
	if (elem){
		elem.className = (' ' + elem.className + ' ').replace(' ' + classNameToRemove + ' ', ' ');
	}
}

function findPos(obj) {
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

function showTT2(ref,cont){
	var tt = document.getElementById("tooltip2");
	tt.style.display = 'block';
	tt.style.left = findPos(ref)[0] + ref.offsetWidth + 'px';
	tt.style.top = findPos(ref)[1] + 'px';
	document.getElementById("tooltip2inner").innerHTML = cont;
	
}
function hideTT2(){
	document.getElementById("tooltip2").style.display = 'none';
}
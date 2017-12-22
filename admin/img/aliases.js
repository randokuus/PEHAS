/**
 * Convert utf8 chars to latin (transliteration)
 *
 * @param string string
 * @return string
 */
function translit(string) {
    var charsFrom = new Array(
       // european (estonian)
      'ü', 'õ', 'ö', 'ä', 'ž', 'š', 'Ü', 'Õ', 'Ö', 'Ä', 'Ž', 'Š',
       // cyrillic
      'А', 'а', 'Б', 'б', 'В', 'в', 'Г', 'г', 'Д', 'д', 'Е', 'е', 'Ё', 'ё', 'Ж', 'ж', 'З',
      'з', 'И', 'и', 'Й', 'й', 'К', 'к', 'Л', 'л', 'М', 'м', 'Н', 'н', 'О', 'о', 'П', 'п',
      'Р', 'р', 'С', 'с', 'Т', 'т', 'У', 'у', 'Ф', 'ф', 'Х', 'х', 'Ц', 'ц', 'Ч', 'ч', 'Ш',
      'ш', 'Щ', 'щ', 'Ы', 'ы', 'Э', 'э', 'Ю', 'ю', 'Я', 'я');

    var charsTo = new Array(
      // european (estonian)
      'u', 'o', 'o', 'a', 'z', 's', 'U', 'O', 'O', 'A', 'Z', 'S',
      // cyrillic
      'A', 'a', 'B', 'b', 'V', 'v', 'G', 'g', 'D', 'd', 'E', 'e', 'Jo', 'jo', 'Zh', 'zh', 'Z',
      'z', 'I', 'i', 'J', 'j', 'K', 'k', 'L', 'l', 'M', 'm', 'N', 'n', 'O', 'o', 'P', 'p',
      'R', 'r', 'S', 's', 'T', 't', 'U', 'u', 'F', 'f', 'H', 'h', 'C', 'c', 'Ch', 'ch', 'Sh',
      'sh', 'Shh', 'shh', 'Y', 'y', 'Je', 'je', 'Ju', 'ju', 'Ja', 'ja');

    var i, j, c, translated;
    var l = string.length;
    var to = new String();

    for (i = 0; i < l; i++) {
        c = string.charAt(i, 1);
        translated = false;
        for (j = 0; j < charsFrom.length; j++) {
            if (c == charsFrom[j]) {
                translated = true;
                break;
            }
        }
        to += (translated) ? charsTo[j] : c;
    }

    return to;
}


/**
 * Generate proper alias from string.
 *
 * @param string string
 */
function stringToTitle(string){
    // translit title
    string = translit(string).toLowerCase();

    // replace spaces with '-'
    string = string.replace(/ +/g, '-');
    // cut out not allowed chars
    string = string.replace(/[^\/0-9a-zA-Z\-_:]/g, '');
    // remove leading and trailing '-'
    string = string.replace(/^-+|-+$/g, '');
    // remove repeated '-'
    string = string.replace(/-{2,}/g, '-');
    // remove '-' after or before '/'
    string = string.replace(/(\/-+)|(-\/)/g, '/');

    return string;
}

/**
 * Generate page alias from title
 *
 * @param string from
 * @param string to
 * @param string mpath
 * @param string|NULL language
 */
function generateAlias(from, to, mpath, language) {
    var title, r, lang;
    var mpath_title = '';

    // generate mpath title.
    // if ':' not exists in mpath name, then this is id to mpath title.
    if (mpath.indexOf(':') == -1) {
        mpath = document.forms[0].elements[mpath];
        if (mpath == 'undefined' || !mpath) {
            mpath_title = '';
        } else {
            mpath_title = stringToTitle(mpath.value);
        }
    }
    // if mpath contains ':', then it is mpath (1.2.3.4).
    else if (mpath.indexOf(':') > 0)
    {
        var title_pref = from.substring(0, from.indexOf(':')+1);
        var mpath = document.forms[0].elements[mpath];

        // check, if mpath element exists in form.
        if (mpath != 'undefined' && mpath) { mpath = mpath.value.split('.');
        } else { mpath = new Array(); }

        var el_ttl = '';
        for (i=0; i < mpath.length-1; i++) {
            el_ttl = stringToTitle(document.forms[0].elements[title_pref + mpath[i]].value);
            if (el_ttl.length > 0) {
                if (mpath_title.length > 0) mpath_title += '/';
                mpath_title = mpath_title + el_ttl;
            }
        }
    }

    from = document.forms[0].elements[from];
    to = document.forms[0].elements[to];
    if (!(from && to)) return;

    title = from.value;
    if (0 == title.length) return;
    title = stringToTitle(title);

    if (mpath_title.length > 0) title = mpath_title + '/' + title;
    if (title.length > 0) title = '/' + title;

    // find language from form hidden elements. if exists, set language to this value.
    lang = document.forms[0].elements['language-' + to];
    if (lang != 'undefined' && lang && (language=='undefined' || !language)) {
        language = lang.value;
    }

    if (language) title = '/' + language + title;
    to.value = title;
}

function generateAll() {
    var i, from, to, mpath;

    var form = document.forms[0];
    for (i = 0; i < form.elements.length; i++) {
        if ('text' == form.elements[i].type) {
            to = form.elements[i].name;
            from = 'title-' + to;
            mpath = 'mpath-' + to;
            generateAlias(from, to, mpath);
        }
    }
    return ;
}

function clearAll() {
    var i;

    var form = document.forms[0];
    for (i = 0; i < form.elements.length; i++) {
        if ('text' == form.elements[i].type) {
            form.elements[i].value = '';
        }
    }
}
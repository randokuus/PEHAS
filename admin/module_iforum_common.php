<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

function calculateForumStats($fid = 0){

	if ($fid == 0) return false;
	static $been_here;
	if ($been_here) return;
	$been_here=true;

	if (!is_resource($GLOBALS["db"]->con)) {
		global $adm;
		if (!is_resource($adm->dbc)) {
			die('something is messed up with db connection');
		}else
		{
			$dbc =& $adm->dbc;
		}
	}
	else { $dbc =& $GLOBALS["db"]->con; }

	$sq = new sql;
	$sq->query($dbc,"select
			count(p.id) as posts, count(distinct t.id) as topics

		from
				module_iforum_forums f
				left outer join module_iforum_threads t on (f.id=t.forum_id)
					inner join module_iforum_posts p on (t.id=p.thread_id)
		where
			f.id=".intval($fid)."
		group by f.id
");
		$posts = $sq->column(0, "posts");
		if (!$posts) $posts = 0;
		$topics = $sq->column(0, "topics");
		if (!$topics) $topics = 0;
		$sq->free();


	$sq->query($dbc,"select
			p.stamp as lastdate, p.user as lastuser
		from
				module_iforum_forums f
				left outer join module_iforum_threads t on (f.id=t.forum_id)
					inner join module_iforum_posts p on (t.id=p.thread_id)
			where
			f.id=".intval($fid)."
		order by lastdate desc
		limit 1
	");
		$lastuser = $sq->column(0, "lastuser");
		if (!$lastuser) $lastuser = 0;
		$lastdate = $sq->column(0, "lastdate");
		if (!$lastdate){
			$lastdatesql = ',last_post=null';
		}
		else{
			$lastdatesql = ",last_post='".addslashes($lastdate)."'";
		}
		$sq->free();
  	$sq->query($dbc,"update module_iforum_forums
			set
				posts=".$posts."
				,topics=".$topics."
				$lastdatesql
				, last_post_user=".$lastuser."
			where
				id=".intval($fid)."
		");

		clearCacheFiles('tpl_iforum_index','');
	return true;
}
function calculateThreadStats($tid = 0){
	static $been_here;
	if ($been_here) return;
	$been_here=true;
	if ($tid == 0) return false;
	if (!is_resource($GLOBALS["db"]->con)) {
		global $adm;
		if (!is_resource($adm->dbc)) {
			die('something is messed up with db connection');
		}else
		{
			$dbc =& $adm->dbc;
		}
	}
	else { $dbc =& $GLOBALS["db"]->con; }

	$sq = new sql;
	$sq->query($dbc,"select
			count(p.id) as posts, t.forum_id

		from
			module_iforum_threads t
					left outer join module_iforum_posts p on (t.id=p.thread_id)
		where
			t.id=".intval($tid)."
		group by t.id
");
		$posts = $sq->column(0, "posts");
		$forum_id = $sq->column(0, "forum_id");
		if (!$posts) $posts = 0;
		$sq->free();
		if ($posts > 0) {

			$sq->query($dbc,"select
					p.stamp as lastdate, p.user as lastuser
				from
					module_iforum_threads t
							inner join module_iforum_posts p on (t.id=p.thread_id)
					where
					t.id=".intval($tid)."
				order by lastdate desc
				limit 1
			");
				$lastuser = $sq->column(0, "lastuser");
				if (!$lastuser) $lastuser = 0;
				$lastdate = $sq->column(0, "lastdate");
				if (!$lastdate){
					$lastdatesql = ',last_post=null';
				}
				else{
					$lastdatesql = ",last_post='".addslashes($lastdate)."'";
				}
				$sq->free();
		  	$sq->query($dbc,"update module_iforum_threads
					set
						posts=".$posts."
						$lastdatesql
						, last_post_user=".$lastuser."
					where
						id=".intval($tid)."
				");
			}else {
				$sq->query($dbc,"delete	from module_iforum_threads where module_iforum_threads.id=".intval($tid));

			}
	calculateForumStats($forum_id);
	clearCacheFiles('tpl_iforum_forum','');
	return $posts;
}

function iforumLoadSmilies() {
    global $iforum_smileys;
    static $been_here;

    if (!$been_here) {
        $iforum_smileys = array();
				if (!is_resource($GLOBALS["db"]->con)) {
					global $adm;
					if (!is_resource($adm->dbc)) {
						die('something is messed up with db connection');
					}else
					{
						$dbc =& $adm->dbc;
					}
				}
				else { $dbc =& $GLOBALS["db"]->con; }

				$sq = new sql;
				$sq->query($dbc,"select code, url FROM module_iforum_smilies order by id");
        while($data = $sq->nextrow()) {
            $iforum_smileys[$data['code']] = $data['url'];
        }
        $sq->free();
        $been_here=true;
    }
}


function iforumSmile($txt) {
    global $iforum_smileys;
    iforumLoadSmilies();
    if ( @count($iforum_smileys) > 0) {
        reset($iforum_smileys);
        foreach ($iforum_smileys as $code=>$url) {
			$orig[] = "/(?<=.\W|\W.|^\W)" . preg_quote($code, "/") . "(?=.\W|\W.|\W$)/";
			$repl[] = '<img src="./img/iforum/smilies/'.$url.'" style="border:none" alt="'.$code.'" />';

//            $txt = str_replace($code, '<img src="./img/iforum/smilies/'.$url.'" style="border:none" alt="'.$code.'" />', $txt);
        }
	$txt = preg_replace($orig, $repl, ' ' . $txt . ' ');
    }
    return $txt;
}



function formatPost($message,$wrap_at=50) {
	$uid='w';
	$message=bbencode_first_pass($message,$uid);
//	echo $message;
	$message=bbencode_second_pass($message,$uid);
	$message=iforumSmile($message);
	$message=htmlwrap($message, $wrap_at, $break = "\n", $nobreak = "");
	$message=nl2br($message);
	return $message;
}

/**
 * Does second-pass bbencoding. This should be used before displaying the message in
 * a thread. Assumes the message is already first-pass encoded, and we are given the
 * correct UID as used in first-pass encoding.
 */
function bbencode_second_pass($text, $uid)
{
	$text = preg_replace('#(script|about|applet|activex|chrome):#is', "\\1&#058;", $text);
	// pad it with a space so we can distinguish between FALSE and matching the 1st char (index 0).
	// This is important; bbencode_quote(), bbencode_list(), and bbencode_code() all depend on it.
	$text = " " . $text;

	// First: If there isn't a "[" and a "]" in the message, don't bother.
	if (! (strpos($text, "[") && strpos($text, "]")) )
	{
		// Remove padding, return.
		$text = substr($text, 1);
		return $text;
	}
	// [CODE] and [/CODE] for posting code (HTML, PHP, C etc etc) in your posts.
	$text = bbencode_second_pass_code($text, $uid, $bbcode_tpl);

	// [QUOTE] and [/QUOTE] for posting replies with quote, or just for quoting stuff.
	$text = str_replace("[quote:$uid]", "</span><table align=\"center\" class=\"quote\" cellspacing=\"0\" cellpadding=\"0\"><tr><td class=\"quote\">$lang[textquote]</td></tr><tr><td class=\"quotemessage\"><span class=\"postbody\">", $text);
	$text = str_replace("[/quote:$uid]", "</span></td></tr></table><span class=\"postbody\">", $text);


	// [b] and [/b] for bolding text.
	$text = str_replace("[b:$uid]", '<b>', $text);
	$text = str_replace("[/b:$uid]", '</b>', $text);

	// [u] and [/u] for underlining text.
	$text = str_replace("[u:$uid]", '<u>', $text);
	$text = str_replace("[/u:$uid]", '</u>', $text);

	// [i] and [/i] for italicizing text.
	$text = str_replace("[i:$uid]", '<i>', $text);
	$text = str_replace("[/i:$uid]", '</i>', $text);

        $patterns = array();
        $replacements = array();

        $patterns[] = "#\[color=([^\"'<>]*?):$uid\](.*?)\[/color:$uid\]#Ssi";
        $replacements[] = '<span style="color:\1">\2</span>';

        if (!stristr($text, 'javascript:') && (stristr($text, 'jpg[/img:'.$uid.']') || stristr($text, 'jpeg[/img:'.$uid.']') || stristr($text, 'gif[/img:'.$uid.']') || stristr($text, 'png[/img:'.$uid.']') || stristr($text, 'bmp[/img:'.$uid.']') || stristr($text, 'php[/img:'.$uid.']'))) {
            $patterns[] = '#\[img:'.$uid.'\](http[s]?|ftp[s]?){1}://([:a-z\\./_\-0-9%~]+){1}(\?[a-z=_\-0-9&;~]*)?\[/img:'.$uid.'\]#Smi';
            $replacements[] = '<img src="\1://\2\3" border="0" alt="\1://\2\3"/>';

            $patterns[] = "#\[img=([0-9]*?){1}x([0-9]*?):$uid\](http[s]?|ftp[s]?){1}://([:~a-z\\./0-9_\-%]+){1}(\?[a-z=0-9&_\-;~]*)?\[/img:$uid\]#Smi";
            $replacements[] = '<img width="\1" height="\2" src="\3://\4\5" alt="\3://\4\5" border="0" />';        }
        $patterns[] = '#([^\'"=\]]|^)(http[s]?|ftp[s]?|gopher|irc){1}://([:a-z_\-\\./0-9%~]+){1}(\?[a-z=0-9\-_&;]*)?(\#[a-z0-9]+)?#Smi';
        $replacements[] = '\1<a href="\2://\3\4\5" target="_blank">\2://\3\4\5</a>';
        $patterns[] = "#\[url:$uid\]([a-z]+?://){1}([^\"'<>]*?)\[/url:$uid\]#Smi";
        $replacements[] = '<a href="\1\2" target="_blank">\1\2</a>';
        $patterns[] = "#\[url:$uid\]([^\"'<>]*?)\[/url:$uid\]#Smi";
        $replacements[] = '<a href="http://\1" target="_blank">\1</a>';
        $patterns[] = "#\[url=([a-z]+?://){1}([^\"'<>]*?):$uid\](.*?)\[/url:$uid\]#Smi";
        $replacements[] = '<a href="\1\2" target="_blank">\3</a>';
        $patterns[] = "#\[url=([^\"'<>]*?):$uid\](.*?)\[/url:$uid\]#Smi";
        $replacements[] = '<a href="http://\1" target="_blank">\2</a>';
        $patterns[] = "#\[email:$uid\]([^\"'<>]*?)\[/email:$uid\]#Smi";
        $replacements[] = '<a href="mailto:\1">\1</a>';
        $patterns[] = "#\[email=([^\"'<>]*?){1}([^\"]*?):$uid\](.*?)\[/email:$uid\]#Smi";
        $replacements[] = '<a href="mailto:\1\2">\3</a>';
        $text = preg_replace($patterns, $replacements, $text);

	// Remove our padding from the string..
	$text = substr($text, 1);

	return $text;

}

/**
 * Does second-pass bbencoding of the [code] tags. This includes
 * running htmlspecialchars() over the text contained between
 * any pair of [code] tags that are at the first level of
 * nesting. Tags at the first level of nesting are indicated
 * by this format: [code:1:$uid] ... [/code:1:$uid]
 * Other tags are in this format: [code:$uid] ... [/code:$uid]
 */
function bbencode_second_pass_code($text, $uid, $bbcode_tpl)
{

	$code_start_html = '</span><pre style="margin:0px; padding:6px; border:1px inset; width:380px; overflow:auto; white-space: nowrap;">';
	$code_end_html =  "</pre><br /><span class=\"postbody\">";



	// First, do all the 1st-level matches. These need an htmlspecialchars() run,
	// so they have to be handled differently.
	$match_count = preg_match_all("#\[code:1:$uid\](.*?)\[/code:1:$uid\]#si", $text, $matches);

	for ($i = 0; $i < $match_count; $i++)
	{
		$before_replace = $matches[1][$i];
		$after_replace = $matches[1][$i];

		// Replace 2 spaces with "&nbsp; " so non-tabbed code indents without making huge long lines.
		$after_replace = str_replace("  ", "&nbsp; ", $after_replace);
		// now Replace 2 spaces with " &nbsp;" to catch odd #s of spaces.
		$after_replace = str_replace("  ", " &nbsp;", $after_replace);

		// Replace tabs with "&nbsp; &nbsp;" so tabbed code indents sorta right without making huge long lines.
		$after_replace = str_replace("\t", "&nbsp; &nbsp;", $after_replace);

		// now Replace space occurring at the beginning of a line
		$after_replace = preg_replace("/^ {1}/m", '&nbsp;', $after_replace);

		$str_to_match = "[code:1:$uid]" . $before_replace . "[/code:1:$uid]";

		$replacement = $code_start_html;
		$replacement .= $after_replace;
		$replacement .= $code_end_html;

		$text = str_replace($str_to_match, $replacement, $text);
	}

	// Now, do all the non-first-level matches. These are simple.
	$text = str_replace("[code:$uid]", $code_start_html, $text);
	$text = str_replace("[/code:$uid]", $code_end_html, $text);

	return $text;

}




function bbencode_first_pass($text, $uid)
{
	// pad it with a space so we can distinguish between FALSE and matching the 1st char (index 0).
	// This is important; bbencode_quote(), bbencode_list(), and bbencode_code() all depend on it.
	$text = " " . $text;

	// [CODE] and [/CODE] for posting code (HTML, PHP, C etc etc) in your posts.
	$text = bbencode_first_pass_pda($text, $uid, '[code]', '[/code]', '', true, '');

	// [QUOTE] and [/QUOTE] for posting replies with quote, or just for quoting stuff.
	$text = bbencode_first_pass_pda($text, $uid, '[quote]', '[/quote]', '', false, '');



	// [color] and [/color] for setting text color
	$text = preg_replace("#\[color=(\#[0-9A-F]{6}|[a-z\-]+)\](.*?)\[/color\]#si", "[color=\\1:$uid]\\2[/color:$uid]", $text);

	// [b] and [/b] for bolding text.
	$text = preg_replace("#\[b\](.*?)\[/b\]#si", "[b:$uid]\\1[/b:$uid]", $text);

	// [url] and [/url] for bolding text.
	$text = preg_replace("#\[url\](.*?)\[/url\]#si", "[url:$uid]\\1[/url:$uid]", $text);

	// [u] and [/u] for underlining text.
	$text = preg_replace("#\[u\](.*?)\[/u\]#si", "[u:$uid]\\1[/u:$uid]", $text);

	// [i] and [/i] for italicizing text.
	$text = preg_replace("#\[i\](.*?)\[/i\]#si", "[i:$uid]\\1[/i:$uid]", $text);

	// [img]image_url_here[/img] code..
	$text = preg_replace("#\[img\]((http|ftp|https|ftps)://)([^ \?&=\#\"\n\r\t<]*?(\.(jpg|jpeg|gif|png)))\[/img\]#sie", "'[img:$uid]\\1' . str_replace(' ', '%20', '\\3') . '[/img:$uid]'", $text);

	// Remove our padding from the string..
	return substr($text, 1);

}



/**
 * $text - The text to operate on.
 * $uid - The UID to add to matching tags.
 * $open_tag - The opening tag to match. Can be an array of opening tags.
 * $close_tag - The closing tag to match.
 * $close_tag_new - The closing tag to replace with.
 * $mark_lowest_level - boolean - should we specially mark the tags that occur
 * 					at the lowest level of nesting? (useful for [code], because
 *						we need to match these tags first and transform HTML tags
 *						in their contents..
 * $func - This variable should contain a string that is the name of a function.
 *				That function will be called when a match is found, and passed 2
 *				parameters: ($text, $uid). The function should return a string.
 *				This is used when some transformation needs to be applied to the
 *				text INSIDE a pair of matching tags. If this variable is FALSE or the
 *				empty string, it will not be executed.
 * If open_tag is an array, then the pda will try to match pairs consisting of
 * any element of open_tag followed by close_tag. This allows us to match things
 * like [list=A]...[/list] and [list=1]...[/list] in one pass of the PDA.
 *
 * NOTES:	- this function assumes the first character of $text is a space.
 *				- every opening tag and closing tag must be of the [...] format.
 */
function bbencode_first_pass_pda($text, $uid, $open_tag, $close_tag, $close_tag_new, $mark_lowest_level, $func, $open_regexp_replace = false)
{
	$open_tag_count = 0;

	if (!$close_tag_new || ($close_tag_new == ''))
	{
		$close_tag_new = $close_tag;
	}

	$close_tag_length = strlen($close_tag);
	$close_tag_new_length = strlen($close_tag_new);
	$uid_length = strlen($uid);

	$use_function_pointer = ($func && ($func != ''));

	$stack = array();

	if (is_array($open_tag))
	{
		if (0 == count($open_tag))
		{
			// No opening tags to match, so return.
			return $text;
		}
		$open_tag_count = count($open_tag);
	}
	else
	{
		// only one opening tag. make it into a 1-element array.
		$open_tag_temp = $open_tag;
		$open_tag = array();
		$open_tag[0] = $open_tag_temp;
		$open_tag_count = 1;
	}

	$open_is_regexp = false;

	if ($open_regexp_replace)
	{
		$open_is_regexp = true;
		if (!is_array($open_regexp_replace))
		{
			$open_regexp_temp = $open_regexp_replace;
			$open_regexp_replace = array();
			$open_regexp_replace[0] = $open_regexp_temp;
		}
	}

	if ($mark_lowest_level && $open_is_regexp)
	{
		die('err');
	}

	// Start at the 2nd char of the string, looking for opening tags.
	$curr_pos = 1;
	while ($curr_pos && ($curr_pos < strlen($text)))
	{
		$curr_pos = strpos($text, "[", $curr_pos);

		// If not found, $curr_pos will be 0, and the loop will end.
		if ($curr_pos)
		{
			// We found a [. It starts at $curr_pos.
			// check if it's a starting or ending tag.
			$found_start = false;
			$which_start_tag = "";
			$start_tag_index = -1;

			for ($i = 0; $i < $open_tag_count; $i++)
			{
				// Grab everything until the first "]"...
				$possible_start = substr($text, $curr_pos, strpos($text, ']', $curr_pos + 1) - $curr_pos + 1);

				//
				// We're going to try and catch usernames with "[' characters.
				//
				if( preg_match('#\[quote=\\\"#si', $possible_start, $match) && !preg_match('#\[quote=\\\"(.*?)\\\"\]#si', $possible_start) )
				{
					// OK we are in a quote tag that probably contains a ] bracket.
					// Grab a bit more of the string to hopefully get all of it..
					if ($close_pos = strpos($text, '"]', $curr_pos + 9))
					{
						if (strpos(substr($text, $curr_pos + 9, $close_pos - ($curr_pos + 9)), '[quote') === false)
						{
							$possible_start = substr($text, $curr_pos, $close_pos - $curr_pos + 2);
						}
					}
				}

				// Now compare, either using regexp or not.
				if ($open_is_regexp)
				{
					$match_result = array();
					if (preg_match($open_tag[$i], $possible_start, $match_result))
					{
						$found_start = true;
						$which_start_tag = $match_result[0];
						$start_tag_index = $i;
						break;
					}
				}
				else
				{
					// straightforward string comparison.
					if (0 == strcasecmp($open_tag[$i], $possible_start))
					{
						$found_start = true;
						$which_start_tag = $open_tag[$i];
						$start_tag_index = $i;
						break;
					}
				}
			}

			if ($found_start)
			{
				// We have an opening tag.
				// Push its position, the text we matched, and its index in the open_tag array on to the stack, and then keep going to the right.
				$match = array("pos" => $curr_pos, "tag" => $which_start_tag, "index" => $start_tag_index);
				array_push($stack, $match);
				//
				// Rather than just increment $curr_pos
				// Set it to the ending of the tag we just found
				// Keeps error in nested tag from breaking out
				// of table structure..
				//
				$curr_pos += strlen($possible_start);
			}
			else
			{
				// check for a closing tag..
				$possible_end = substr($text, $curr_pos, $close_tag_length);
				if (0 == strcasecmp($close_tag, $possible_end))
				{
					// We have an ending tag.
					// Check if we've already found a matching starting tag.
					if (sizeof($stack) > 0)
					{
						// There exists a starting tag.
						$curr_nesting_depth = sizeof($stack);
						// We need to do 2 replacements now.
						$match = array_pop($stack);
						$start_index = $match['pos'];
						$start_tag = $match['tag'];
						$start_length = strlen($start_tag);
						$start_tag_index = $match['index'];

						if ($open_is_regexp)
						{
							$start_tag = preg_replace($open_tag[$start_tag_index], $open_regexp_replace[$start_tag_index], $start_tag);
						}

						// everything before the opening tag.
						$before_start_tag = substr($text, 0, $start_index);

						// everything after the opening tag, but before the closing tag.
						$between_tags = substr($text, $start_index + $start_length, $curr_pos - $start_index - $start_length);

						// Run the given function on the text between the tags..
						if ($use_function_pointer)
						{
							$between_tags = $func($between_tags, $uid);
						}

						// everything after the closing tag.
						$after_end_tag = substr($text, $curr_pos + $close_tag_length);

						// Mark the lowest nesting level if needed.
						if ($mark_lowest_level && ($curr_nesting_depth == 1))
						{
							if ($open_tag[0] == '[code]')
							{
								$code_entities_match = array('#<#', '#>#', '#"#', '#:#', '#\[#', '#\]#', '#\(#', '#\)#', '#\{#', '#\}#');
								$code_entities_replace = array('&lt;', '&gt;', '&quot;', '&#58;', '&#91;', '&#93;', '&#40;', '&#41;', '&#123;', '&#125;');
								$between_tags = preg_replace($code_entities_match, $code_entities_replace, $between_tags);
							}
							$text = $before_start_tag . substr($start_tag, 0, $start_length - 1) . ":$curr_nesting_depth:$uid]";
							$text .= $between_tags . substr($close_tag_new, 0, $close_tag_new_length - 1) . ":$curr_nesting_depth:$uid]";
						}
						else
						{
							if ($open_tag[0] == '[code]')
							{
								$text = $before_start_tag . '&#91;code&#93;';
								$text .= $between_tags . '&#91;/code&#93;';
							}
							else
							{
								if ($open_is_regexp)
								{
									$text = $before_start_tag . $start_tag;
								}
								else
								{
									$text = $before_start_tag . substr($start_tag, 0, $start_length - 1) . ":$uid]";
								}
								$text .= $between_tags . substr($close_tag_new, 0, $close_tag_new_length - 1) . ":$uid]";
							}
						}

						$text .= $after_end_tag;

						// Now.. we've screwed up the indices by changing the length of the string.
						// So, if there's anything in the stack, we want to resume searching just after it.
						// otherwise, we go back to the start.
						if (sizeof($stack) > 0)
						{
							$match = array_pop($stack);
							$curr_pos = $match['pos'];
//							bbcode_array_push($stack, $match);
//							++$curr_pos;
						}
						else
						{
							$curr_pos = 1;
						}
					}
					else
					{
						// No matching start tag found. Increment pos, keep going.
						++$curr_pos;
					}
				}
				else
				{
					// No starting tag or ending tag.. Increment pos, keep looping.,
					++$curr_pos;
				}
			}
		}
	} // while

	return $text;

}

/**
 * Wraps HTML by breaking long words and preventing them from damaging 
 * your layout.
 * 
 * @param string str html-content
 * @param int width string width in chars
 * @param string separator string separator
 * @param string nobreak not divide words
 * @return string html-content 
 */
function htmlwrap($str, $width = 60, $separator = "\n", $nobreak = "") {
  $utf_list = array(
    "[\x09\x0A\x0D\x20-\x7E]",
    "[\xC2-\xDF]",
    "\xE0[\xA0-\xBF][\x80-\xBF]",
    "[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}",
    "\xED[\x80-\x9F][\x80-\xBF]",
    "\xF0[\x90-\xBF][\x80-\xBF]{2}",
    "[\xF1-\xF3][\x80-\xBF]{3}",
    "\xF4[\x80-\x8F][\x80-\xBF]{2}"
  );
  $stream = "";  
  $content = preg_split("/([<>])/", $str, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
  
  $nobreak = explode(" ", strtolower($nobreak));
  $save_char_list = "/?!%)-}]\\\"':;&";
  
  
  $reg_exp = "";
  foreach ($utf_list as $u){
    if ($reg_exp != ""){
     $reg_exp .= "|";
    }
    $reg_exp .= $u;
  }
  if (preg_match("/^($u)*$/", $str)){
    $utf8 = "u";
  }else{
    $utf8 = "";
  } 
  
  $inside_tag = false;
  $innbk = array();  
  while (list(, $value) = each($content)) {
    switch ($value) {
      case "<": 
        $inside_tag = true; 
        break;
      case ">": 
        $inside_tag = false; 
        break;
      default:
        if ($inside_tag === true) {
          $value = strtolower($value);          
          if ($value[0] != "/") {   
            preg_match("/^(\w*?)(\s|$)/", $value, $tmp_d);
            if (in_array($tmp_d[1], $nobreak)){ 
                array_unshift($innbk, $tmp_d[1]);
            }
          } else {
            if (in_array(substr($value, 1), $nobreak)) {
              reset($innbk);
              while (list($key, $tag) = each($innbk)) {
                if (substr($value, 1) == $tag) {
                  unset($innbk[$key]);
                  break;
                }
              }
              $innbk = array_values($innbk);
            }
          }
        } else if ($value) {
          if (!count($innbk)) {
            $value = str_replace("\x06", "", $value);
            preg_match_all("/&([a-z\d]{2,7}|#\d{2,5});/i", $value, $entrys);
            $value = preg_replace("/&([a-z\d]{2,7}|#\d{2,5});/i", "\x06", $value);
            $tmp_break = preg_quote($separator, "/");
            $reg_exp = "/^(.*?\s)?(\S{" . $width . "})(?!(" . $tmp_break . "|\s))(.*)$/s" . $utf8; 
            do {
              $store = $value;              
              if (preg_match($reg_exp , $value, $tmp_match)) {
                for ($x = 0, $scope = 0; $x < strlen($save_char_list); $x++){ 
                    $scope = max($scope, strrpos($tmp_match[2], $save_char_list{$x}));
                }
                if (!$scope){ 
                    $scope = strlen($tmp_match[2]) - 1;
                }
                $value = $tmp_match[1].substr($tmp_match[2], 0, $scope + 1).$separator.substr($tmp_match[2], $scope + 1).$tmp_match[4];
              }
            } while ($store != $value);
            
            foreach ($entrys[0] as $entry){ 
                $value = preg_replace("/\x06/", $entry, $value, 1);
            }
          }
        }
    }
    $stream .= $value;
  }
  return $stream;
}

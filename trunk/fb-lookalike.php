<?php

/**
 * helper class for making a facebook wall look a like.
 * @author sim wicki <sim.wicki@gmail.com>
 */
class FacebookWallLookalike {
	
	private $_root = '';
	private $_entries = array();
	private $_options = array();
	private $_lang = array();
	private $_type = 0;
	private $_cut_length = 420;
	private $_show_more_length = 420;
	private $_link_limit = 5;
	private $_link_format = '<a href="%s" rel="ext">%s</a>';
	private $_fb = null;
	
	public $STATUS = 'status';
	public $PHOTO = 'photo';
	public $LINK = 'link';
	public $VIDEO = 'video';
	
	/**
	 * initializes the object either from facebook graph object or manual data.
	 * @param rootLib string directory where images and styles directories are located for facebookwalllookalike
	 * @param options array with options (language, show-more, link_target, ...)
	 */
	public function __construct($rootLib, $options) {
		
		$this->_root = ($rootLib) ? $rootLib . '/' : '';
		
		// set options
		$optionTitles = array('language', 'show-more', 'link_target');
		$this->_options['language'] = 'EN';
		$this->_options['show-more'] = true;
		$this->_options['link_target'] = '_parent';
		
		if (is_array($options)) {
			foreach ($optionTitles as $entry) {
				if (array_key_exists($entry, $options)) {
					$this->_options[$entry] = $options[$entry];
				}
			}
		}
		
		$this->loadLanguage($this->_options['language']);
	}
	
	/**
	 * sets the facebook object to access the opengraph api.
	 * @param object $fb
	 */
	public function setFacebookObject($fb) {
		$this->_fb = $fb;
	}
	
	/**
	 * adds a wall post entry.
	 * @param data array facebook graph data
	 */
	public function addEntryGraph($data) {
		
		$link = explode('_', $data['id']);
		
		if ($data['type'] == $this->PHOTO && $this->_fb != null) {
			$photo = $this->_fb->api('/' . $data['object_id']);
			$data['picture'] = $photo['picture'];
		}
		
		$picture = '';
		
		$this->addEntry(
			$data['from']['name'],
			$data['type'],
			null,
			(isset($data['message'])) ? $data['message'] : '',
			$this->formTime(strtotime($data['created_time'])),
			$this->formFullTime(strtotime($data['created_time'])),
			array('likes' => (isset($data['likes'])) ? $data['likes']['count'] : 0, 'comments' => $data['comments']['count']),
			(isset($data['comments']['data'])) ? $data['comments']['data'] : null,
			"http://www.facebook.com/permalink.php?story_fbid={$link[1]}&id={$link[0]}",
			"http://graph.facebook.com/{$data['from']['id']}/picture",
			"http://facebook.com/{$data['from']['id']}",
			(isset($data['link'])) ? $data['link'] : null,
			($data['type'] == 'link') ? $data['name'] : null,
			(isset($data['description'])) ? $data['description'] : null,
			(isset($data['picture'])) ? $data['picture'] : null,
			(isset($data['object_id'])) ? $data['object_id'] : null
		);
	}
	
	/**
	 * adds a wall post entry.
	 * it's asorted rather by function calls than the time parameter.
	 * @param data array with head:string, content:string, time:int, link:string keys
	 * @param type const type of entry (status, photo, link, video)
	 * @param likes int amount of likes
	 * @param comments array of comments which contain posterName, posterLink, posterPic, content, time
	 * @param title string optional, adds an bold little title under the head
	 */
	public function addEntryManual($data, $type, $likes = 0, $comments = null) {
		
		if (is_array($data)) {
			$link = null;
			$caption = isset($data['caption']) ? $data['caption'] : null;
			$description = isset($data['description']) ? $data['description'] : null;
			
			if (isset($data['id'])) {
				$link_chunk = explode('_', $data['id']);
				if (count($link_chunk) > 1) {
					$link = "http://www.facebook.com/permalink.php?id={$link_chunk[0]}&story_fbid={$link_chunk[1]}";
				} else {
					$link = '';
				}
			}
			
			$this->addEntry(
				$data['from'],
				$type,
				(isset($data['title'])) ? $data['title'] : '',
				$data['message'],
				$this->formTime($data['time']),
				$this->formFullTime($data['time']),
				array('likes' => $likes, 'comments' => count($comments)),
				$comments,
				isset($data['entry_link']) ? $data['entry_link'] : '',
				$data['profile_image'],
				$data['profile_link'],
				$link,
				$caption,
				$description,
				isset($data['picture']) ? $data['picture'] : ''
			);
			
		}
	}
	
	private function addEntry($head, $type, $title, $content, $time, $fulltime, $likes_and_comments, $comments, $entry_link, $profile_image, $profile_link, $link = null, $caption = null, $description = null, $picture = null, $object_id = null) {
		
		$likesfull = '';
		$likesfull .= ($likes_and_comments['likes'] > 0) ? '<img class="fb-entry-icon" src="' . $this->_root . 'like.png" alt="likes" /> ' . $likes_and_comments['likes'] . '&nbsp;&nbsp;' : '';
		$likesfull .= ($likes_and_comments['comments']) ? '<img class="fb-entry-icon" src="' . $this->_root . 'comment.png" alt="comments" /> ' . $likes_and_comments['comments'] . ' ' : '';
		
		// cutting content to 420
		
		$content = nl2br(preg_replace("/(^\r\n)/", '', strip_tags($content)));
		
		$entry = array(
			'head' => $head,
			'type' => $type,
			'title' => $title,
			'content' => $content,
			'time' => $time,
			'fulltime' => $fulltime,
			'likes-and-comments' => $likesfull,
			'comments' => $comments,
			'entry_link' => $entry_link,
			'profile_image' => $profile_image,
			'profile_link' => $profile_link,
			'link' => $link,
			'caption' => $caption,
			'description' => $description,
			'picture' => $picture,
			'object_id' => $object_id
		);
		array_push($this->_entries, $entry);
	}
	
	/**
	 * prints out all added entries.
	 */
	public function printOut() {
		$i = 0;
		foreach ($this->_entries as $entry) {
			
			echo "<div class='fb-entry'>
				<div class='fb-entry-profile'>
					<img src='{$entry['profile_image']}' />
				</div>
				<div class='fb-entry-text'>
					<div class='fb-entry-head'>
						<a href='{$entry['profile_link']}' target='{$this->_options['link_target']}'>{$entry['head']}</a>
						<br/>
					</div>
					<div class='fb-entry-text'>";
			
			if ($entry['title'] != '') {
				echo "<div class='fb-entry-title'>{$entry['title']}</div>";
			}
			
			
			// cutting for show-more
			if ($this->_options['show-more'] && strlen($entry['content']) > $this->_show_more_length) {
				$cut = substr($entry['content'], $this->_show_more_length);
				$entry['content'] = substr($entry['content'], 0, $this->_show_more_length);
				
				$entry['content'] = "<span id='fb_premore_{$i}'>" . $entry['content'] . '</span>';
				$continued = "<span style='display:none;' id='fb_postmore_{$i}'>{$cut}</span>";
				
				$entry['content'] .= $continued;
				$entry['content'] .= "<span id='fb_linkmore_{$i}'>... <a href='#' onClick=\"$('fb_premore_{$i}').innerHTML+=$('fb_postmore_{$i}').innerHTML; $('fb_linkmore_{$i}').style.display='none'; return false;\">
				{$this->_lang['show-more']}
				</a></span>";
			}
			
			$this->autolink($entry['content']);
			$this->complete_url($entry['entry_link']);
			
			echo "<div class='fb-entry-content'>{$entry['content']}</div>";
			
			// LINK, PHOTO, VIDEO
			if ($entry['type'] == $this->LINK || $entry['type'] == $this->PHOTO || $entry['type'] == $this->VIDEO) {
				
				
				$style = ($entry['type'] == $this->PHOTO) ? 'fb-entry-link-photo' : '';
				$styleclass = ($entry['type'] == $this->VIDEO) ? 'background: url(' . $this->_root . 'play.png) no-repeat 0 0; bottom: 0; height: 32px; left: 0; position: absolute; width: 39px;' : ''; // using inline styling because of dynamic root directory
				
				$this->autolink($entry['caption']);
				$this->autolink($entry['description']);
				
				echo "<div class='fb-entry-content fb-entry-link'>
					<div class='fb-entry-image {$style}' style='position:relative;'><a href='{$entry['link']}' target='{$this->_options['link_target']}'><img src='{$entry['picture']}' /><i style='{$styleclass}' ></i></a></div>
					<div class='fb-entry-link-head'><a href='{$entry['link']}' target='{$this->_options['link_target']}'>{$entry['caption']}</a></div>
					<div>{$entry['description']}</div>
				</div>";
			}
			
			echo '</div>';
			echo '	<div class="fb-entry-appendix">';
			echo "		<span class='fb-entry-time' title='{$entry['fulltime']}'>{$entry['time']}</span>&nbsp;&middot;&nbsp;<a href='{$entry['entry_link']}' target='{$this->_options['link_target']}'>{$this->_lang['comment']}</a>";
			echo '</div>';
			
			$this->buildComment($entry);
			
			echo '</div>';
			echo '<div style="clear:both;"></div>';
			echo '</div>';
			
			$i++;
		}
	}
	
	/**
	 * builds a comment by using an entry array.
	 * @param entry array with comment and like data
	 */
	private function buildComment($entry) {
		if ($entry['likes-and-comments']) {
			echo "<div class='fb-entry-text fb-entry-comment-likes fb-entry-comment-box fb-entry-icon'>           
				<div class='fb-entry-comment-top'></div>
    		    <div class='fb-entry-comment-entry'>
		          {$entry['likes-and-comments']}
		        </div>
		        </div>";

			if ($entry['comments']) {
				foreach ($entry['comments'] as $comment) {
					$time = (isset($comment['created_time'])) ? strtotime($comment['created_time']) : strtotime($comment['time']);
					$timestamp = $this->formTime($time);
					$fulltime = $this->formFullTime($time);
					
					$from_id = (isset($comment['from']) && isset($comment['from']['id'])) ? $comment['from']['id'] : 0;
					$from_name = (isset($comment['from']) && is_array($comment['from']) && isset($comment['from']['name']))? $comment['from']['name'] : $comment['from'];
					$profile_image = (isset($comment['profile_image'])) ? $comment['profile_image'] : "http://graph.facebook.com/{$comment['from']['id']}/picture";
					
					echo "<div class='fb-entry-comment-box fb-entry-text fb-entry-comment'>
					<div class='fb-entry-comment-entry'>
						<img class='fb-entry-comment-profile' src='{$profile_image}' />
					</div>
					<div class='fb-entry-comment-head'>
						<div class='fb-entry-comment-content'><a href='{$entry['link']}' target='{$this->_options['link_target']}'>{$from_name}</a>
						&nbsp;{$comment['message']}</div>
						<div class='fb-entry-comment-appendix'>
							<span class='fb-entry-time' title='{$fulltime}'>{$timestamp}</span>
						</div>
					</div>
					</div>";
				}
			}
		}
	}
	
	/**
	 * loads all language related phrases according to the parameter.
	 * if passed language identifier cannot be found, the default language will be taken.
	 * @param lang language identifier (EN, DE, ...)
	 */
	private function loadLanguage($lang) {
		switch ($lang) {
			case 'DE':
				$this->_lang['seconds'] = 'vor {1} Sekunden';
				$this->_lang['minutes'] = 'vor {1} Minuten';
				$this->_lang['hours'] = 'vor {1} Stunden';
				
				$this->_lang['just-now'] = 'vor ein paar Sekunden';
				$this->_lang['about-minute'] = 'vor etwa einer Minute';
				$this->_lang['about-hour'] = 'vor etwa einer Stunde';
				$this->_lang['about-day'] = 'about a day ago';
				$this->_lang['yesterday'] = 'Gestern um {1}';
				$this->_lang['older-than-day'] = '{1} um {2}';
				$this->_lang['older-than-week'] = '{1} {2} um {3}';
				$this->_lang['full'] = '{1}, {2} {3} {4} um {5}';
				
				$this->_lang['comment'] = 'Kommentieren';
				
				$this->_lang['week-1'] = 'Montag';
				$this->_lang['week-2'] = 'Dienstag';
				$this->_lang['week-3'] = 'Mittwoch';
				$this->_lang['week-4'] = 'Donnerstag';
				$this->_lang['week-5'] = 'Freitag';
				$this->_lang['week-6'] = 'Samstag';
				$this->_lang['week-7'] = 'Sonntag';
				
				$this->_lang['month-1'] = 'Januar';
				$this->_lang['month-2'] = 'Februar';
				$this->_lang['month-3'] = 'März';
				$this->_lang['month-4'] = 'April';
				$this->_lang['month-5'] = 'Mai';
				$this->_lang['month-6'] = 'Juni';
				$this->_lang['month-7'] = 'Juli';
				$this->_lang['month-8'] = 'August';
				$this->_lang['month-9'] = 'September';
				$this->_lang['month-10'] = 'Oktober';
				$this->_lang['month-11'] = 'November';
				$this->_lang['month-12'] = 'Dezember';
				
				$this->_lang['show-more'] = 'mehr';
				$this->_lang['show-less'] = 'weniger';
				
				break;
			case 'EN':
			default:
				$this->_lang['seconds'] = '{1} seconds ago';
				$this->_lang['minutes'] = '{1} minutes ago';
				$this->_lang['hours'] = '{1} hours ago';
				
				$this->_lang['just-now'] = 'a few seconds ago';
				$this->_lang['about-minute'] = 'about a minute ago';
				$this->_lang['about-hour'] = 'about an hour ago';
				$this->_lang['about-day'] = 'about a day ago';
				$this->_lang['yesterday'] = 'Yesterday at {1}';
				$this->_lang['older-than-day'] = '{1} at {2}';
				$this->_lang['older-than-week'] = '{1} {2} at {3}';
				$this->_lang['full'] = '{1}, {2} {3} {4} at {5}';
				
				$this->_lang['comment'] = 'Comment';
				
				$this->_lang['week-1'] = 'Monday';
				$this->_lang['week-2'] = 'Tuesday';
				$this->_lang['week-3'] = 'Wednesday';
				$this->_lang['week-4'] = 'Thursday';
				$this->_lang['week-5'] = 'Friday';
				$this->_lang['week-6'] = 'Saturday';
				$this->_lang['week-7'] = 'Sunday';
				
				$this->_lang['month-1'] = 'January';
				$this->_lang['month-2'] = 'February';
				$this->_lang['month-3'] = 'March';
				$this->_lang['month-4'] = 'April';
				$this->_lang['month-5'] = 'May';
				$this->_lang['month-6'] = 'June';
				$this->_lang['month-7'] = 'July';
				$this->_lang['month-8'] = 'August';
				$this->_lang['month-9'] = 'September';
				$this->_lang['month-10'] = 'October';
				$this->_lang['month-11'] = 'November';
				$this->_lang['month-12'] = 'December';
				
				$this->_lang['show-more'] = 'more';
				$this->_lang['show-less'] = 'less';
				
				break;
		}
	}
	
	/**
	 * forms a string date according to the post time.
	 * @param time int timestamp
	 * @return formatted date
	 */
	private function formTime($time) {
		$output = '';
		
		$yesterday = strtotime('yesterday');
		$today = strtotime('today');
		$now = time();
		
		if ($now - $time > 60*60*24*7) {
			// 04 July at 11:35
			$value1 = date('d', $time);
			$value2 = $this->_lang['month-' . intval(date('n', $time))];
			$value3 = date('H:i', $time);
			$template = $this->_lang['older-than-week'];
			$template = str_replace('{1}', $value1, $template);
			$template = str_replace('{2}', $value2, $template);
			$template = str_replace('{3}', $value3, $template);
			$output = $template;
			
		} else if ($time < $yesterday) {
			// Friday at 13:46
			$value1 = $this->_lang['week-' . intval(date('N', $time))];
			$value2 = date('H:i', $time);
			$template = $this->_lang['older-than-day'];
			$template = str_replace('{1}', $value1, $template);
			$template = str_replace('{2}', $value2, $template);
			$output = $template;
			
		} else if ($time < $today) {
			// Yesterday at 20:19
			$value = date('H:i', $time);
			$output = str_replace('{1}', $value, $this->_lang['yesterday']);
			
		} else if ($time < ($now - 60*60)) {
			// 5 hours ago
			$value = intval(($now - $time) / (60*60));
			$output = str_replace('{1}', $value, $this->_lang['hours']);
			
		} else if ($time < ($now - 60)) {
			// 29 minutes ago || about an hour ago
			if ($time - ($now - 60*60) < 60*60/10) {
				$output = $this->_lang['about-hour'];
			} else {
				$value = intval(($now - $time) / (60));
				$output = str_replace('{1}', $value, $this->_lang['minutes']);
			}
			
		} else {
			// 15 seconds ago || about a minute ago
			if ($time - ($now - 60) < 60/10) {
				$output = $this->_lang['about-minute'];
			} else if ($time - $now < 60/10 || ($now - $time) < 0) {
				$output = $this->_lang['just-now'];
			} else {
				$value = intval($now - $time);
				$output = str_replace('{1}', $value, $this->_lang['seconds']);
			}
		}
		
		return $output;
	}
	
	private function formFullTime($time) {
		$output = '';
		
		$value1 = $this->_lang['week-' . intval(date('N', $time))];
		$value2 = date('d', $time);
		$value3 = $this->_lang['month-' . intval(date('n', $time))];
		$value4 = date('Y', $time);
		$value5 = date('H:i', $time);
		$template = $this->_lang['full'];
		$template = str_replace('{1}', $value1, $template);
		$template = str_replace('{2}', $value2, $template);
		$template = str_replace('{3}', $value3, $template);
		$template = str_replace('{4}', $value4, $template);
		$template = str_replace('{5}', $value5, $template);
		$output = $template;
		
		return $output;
	}
	
	private function autolink(&$text, $target='_blank', $nofollow=true) {
		$urls = $this->autolink_find_URLS($text);
		
		if (!empty($urls)) {
			foreach ($urls as $key => &$value) {
				$this->complete_url($key);
				$this->autolink_create_html_tags($value, $key, array('target' => $target, 'nofollow' => $nofollow));
			}
			$text = strtr($text, $urls);
		}
	}

	private function autolink_find_URLS($text) {
		// build the patterns
		$scheme         =       '(http:\/\/|https:\/\/)';
		$www            =       'www\.';
		$ip             =       '\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}';
		$subdomain      =       '[-a-z0-9_]+\.';
		$name           =       '[a-z][-a-z0-9]+\.';
		$tld            =       '[a-z]+(\.[a-z]{2,2})?';
		$the_rest       =       '\/?[a-z0-9._\/~#&=;%+?-]+[a-z0-9\/#=?]{1,1}';            
		$pattern        =       "$scheme?(?(1)($ip|($subdomain)?$name$tld)|($www$name$tld))$the_rest";

		$pattern        =       '/'.$pattern.'/is';
		$c              =       preg_match_all($pattern, $text, $m);
		unset($text, $scheme, $www, $ip, $subdomain, $name, $tld, $the_rest, $pattern);
		if ($c) {
			return(array_flip($m[0]));
		}
		return array();
	}

	private function autolink_create_html_tags(&$value, $key, $other=null) {
		$target = $nofollow = null;
		if (is_array($other)) {
			$target = ($other['target'] ? " target=\"$other[target]\"" : null);
			$nofollow = ($other['nofollow'] ? ' rel="nofollow"' : null);
		}
		$value = "<a href=\"$key\"$target$nofollow target='{$this->_options['link_target']}'>$key</a>";
	}
	
	private function complete_url(&$url) {
		if (strpos($url, 'http://') === false) {
			$url = 'http://' . $url;
		}
		return $url;
	}
}

?>

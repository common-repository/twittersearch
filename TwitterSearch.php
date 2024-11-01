<?php

/*
Plugin Name: TwitterSearch
Plugin URI: http://TBA
Description: Keep track of tweets based on search terms
Version: 0.2
Author: Dan Beaulieu
Author URI: danjacob.beaulieu@gmail.com
Last Modified: 6/10/11
*/
require('elitwee.php');

define("DEFAULT_CACHELOC", dirname(__FILE__)."/cache/");


class TwitterSearch {
   
	function TwitterSearch() {
		add_action('wp_dashboard_setup', array(&$this, 'register_widget'));
		add_filter('wp_dashboard_widgets', array(&$this, 'add_widget'));
	}
	
	function register_widget() {
		wp_add_dashboard_widget('TwitterSearch', 'Twitter Search', array(&$this, 'widget'), array(&$this, 'widget_control'));
	}
	
	function add_widget($widgets) {
	
		global $wp_registered_widgets;
		
		if (!isset($wp_registered_widgets['TwitterSearch']) ) return $widgets;
		array_splice($widgets, 0, 0, 'TwitterSearch');
		
		return $widgets;
		
	}
	
	function widget($args = null) {
    
    	if (!empty($args) && is_array($args))
        	extract($args, EXTR_SKIP);
        	
        $widget_id = 'TwitterSearch';
        //
		$count = (get_option("twittersearch_count") == null)? 1 : get_option("twittersearch_count");
		$tuser = (get_option("twittersearch_user") == null)? "TwitterSearch" : stripslashes(get_option("twittersearch_user"));
		$pass  =  get_option("twittersearch_pass");
		$title = stripslashes(get_option("twittersearch_title"));
		
		$cache_location = htmlspecialchars(stripslashes(get_option("twittersearch_cache_location")));
		if ($cache_location == null) {$cache_location = DEFAULT_CACHELOC;}
		$cache_life = (get_option("twittersearch_cache_life") == null) ? 900 : get_option("twittersearch_cache_life");
		
		$orderfirst = (get_option("twittersearch_order") == null) ? "putfirst_twitter" : get_option("twittersearch_order");
		
		//$separator  = (get_option("twittersearch_separator") == null)? "" : stripslashes(get_option("twittersearch_separator"));
		//$beforeall  = (get_option("twittersearch_beforeall") == null)? '<ul class="twittersearch">' : stripslashes(get_option("twittersearch_beforeall"));
		//$afterall   = (get_option("twittersearch_afterall") == null)? "</ul>" : stripslashes(get_option("twittersearch_afterall"));
		//$beforeitem = (get_option("twittersearch_beforeitem") == null)? '<li class="twittersearch">' : stripslashes(get_option("twittersearch_beforeitem"));
		//$afteritem  = (get_option("twittersearch_afteritem") == null)? "</li>" : stripslashes(get_option("twittersearch_afteritem"));
		
		//
        echo $before_widget;

        echo $before_title;
        echo $widget_name;
        echo $after_title;

        if (!$widget_options = get_option('dashboard_widget_options' ))
                $widget_options = array();

        if (!isset($widget_options[$widget_id]))
                $widget_options[$widget_id] = array();

        if (!isset($widget_options[$widget_id]['term'])) {
                echo "<b>TwitterSearch</b> has not yet been configured. Click the configure link above.";
        } else {
				
				$term = $widget_options[$widget_id]['term'];
				$highlight = false;
				if($widget_options[$widget_id]['highlight'] == 'Yes') 
					$highlight = true;
    			
				try{
				// user, pass and count are default to null, null, and 1, we don't use them. (for now)
					$et = new Elitwee($tuser, $pass, $count);
					$et->set_cache_location($cache_location);
					$et->set_cache_time($cache_life);
					$et->set_user_timeline_format('json');
					$et->set_user_search_term($term);
					
					$tweets = $et->get_search_results();
					$i = 0;
					
					foreach ($tweets as $tweet) {
						echo '<p> <a href="http://www.twitter.com/' . $tweet->from_user . '"> ' . $tweet->from_user . ' </a> - ' . $this->format($tweet->text, $term, $highlight); 
						echo '. ' . relative_time(strtotime($tweet->created_at), "") . '</p>';
					}
				}
				catch (Exception $e) {
					echo 'Error: ' .$e->getMessage();
				}
   		
            // snip
        }

        echo $after_widget;
        
    }
	
	// for now this just highlights the search text
	// TODO: make links in the tweet actual links
	// deprecated, see format()
	public function processText($text, $term, $highlight) {
		if(!$highlight) return $text;
		$replace = '<span style="background-color: #99CCFF">' . $term . '</span>';
		return str_ireplace($term,$replace,$text);
	}
	
	public function format($text, $term, $highlight) {
		$text = ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]","<i><a href=\"\\0\">\\0</a></i>",$text); // turn any URL's into links
		$text = ereg_replace("@([a-zA-Z0-9_]+)([^a-zA-Z0-9_])",'<a href="http://www.twitter.com/\\1">@\\1</a>\\2',$text); // add "@username" links
		$text = ereg_replace("#([a-zA-Z0-9]+)",'<a href="http://search.twitter.com/search?q=%23\\1">#\\1</a>',$text);// convert any # symbols to links to the channel (i.e. on twitter it'll point to twitter search for #whatever)
		
		if(!$highlight) return $text;
		$replace = '<span style="background-color: #99CCFF">' . $term . '</span>';
		return str_ireplace($term,$replace,$text);
		
	}

	
	function widget_control($args = null) {
	
		if (!empty($args) && is_array($args))
			extract($args, EXTR_SKIP);
					
		$widget_id = 'TwitterSearch';
		
		if (!$widget_options = get_option('dashboard_widget_options'))
			$widget_options = array();

		if (!isset($widget_options[$widget_id]))
			$widget_options[$widget_id] = array();

		if ('POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['widget-TwitterSearch'])) {
			echo $_POST['widget-TwitterSearch'];
			$widget_options[$widget_id] = stripslashes_deep($_POST['widget-TwitterSearch']);												
			update_option('dashboard_widget_options', $widget_options);
		}
		
		echo "<p><label for='TwitterSearch-term'>";
		_e('What term would you like to track on Twitter?', 'TwitterSearch');
		echo "</label><br /><input type='text' name='widget-TwitterSearch[term]' size='40' value='" . $widget_options[$widget_id]['term'] . "'></p>";
		//echo "<input type='checkbox' name='widget-TwitterSearch[highlight]' value='Yes'> highlight terms?";
		
		
	}
	
}

add_action('plugins_loaded', create_function('', 'global $TwitterSearch; if (empty($TwitterSearch)) $TwitterSearch = new TwitterSearch();'));
add_action('widgets_init', create_function('', 'global $TwitterSearch; if (empty($TwitterSearch)) $TwitterSearch = new TwitterSearch();'));


?>
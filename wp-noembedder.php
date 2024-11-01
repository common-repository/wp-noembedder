<?php
/*
Plugin Name: Noembedder
Version: 1.1
Plugin URI: http://blog.slaven.net.au/wordpress-plugins/noembedder-wordpress-plugin/
Description: Adds noembed tags to any embeded object that doesn't have them
Author: Glenn Slaven
Author URI: http://blog.slaven.net.au/
*/

include_once ('plugin-base.php');

if (!class_exists('wp_noembedder') && class_exists('plugin_base')) {

	class wp_noembedder extends plugin_base {

		var $name = 'Noembedder';
		var $filename = __FILE__;
		var $prefix = "wp_noembedder_";

		var $youTube_devID = 'txIjXqjLTVI';
		var $message = '<em>There is embedded content here that you cannot see. Please <a href="##POST_URL##">open the post in a web browser</a> to see this.</em>';

		var $debug = false;
		
		function wp_noembedder() {
			parent::plugin_base();

			add_filter('the_content', array(&$this, 'add_tags'));
			$msg = get_option($this->prefix . "message");
			if ($msg) {
				$this->message = $msg;
			}
		}
		
		function get_youtube_thumbnail($video_id) { 
			$url = "http://www.youtube.com/api2_rest?dev_id=$this->youTube_devID&method=youtube.videos.get_details&video_id=$video_id";
			$response = $this->parse_remote_xml($url);
			
			if (!empty($response['ut_response']['error'])) {
				$this->throw_error("The Youtube API returned error code #" . $this->parsed_response['ut_response']['error']['code'] . ": " . $this->parsed_response['ut_response']['error']['description']);
			} else {
				return $response['ut_response']['video_details']['thumbnail_url'];
			}
		}

		function add_tags($content) {
			global $post;

			$message = str_replace('##POST_URL##', $post->guid, $this->message);
					
			$pattern = '/(<embed.+src="([^"]+)"[^>]+(\/?>))(?:\s|\n)*(<[^>]+>)/im';			
			if (preg_match_all($pattern, $content, $matches)) {			
			
				for ($i = 0; $i < count($matches[0]); $i++) {
					if (preg_match("/youtube.com\/v\/([^\"]+)/im", $matches[2][$i], $int_matches)) {						
						$thumbnail = $this->get_youtube_thumbnail($int_matches[1]);
					} elseif (preg_match("/gamevideos.com.+id\%3D([0-9]+)\%/i", $matches[2][$i], $int_matches)) {
						$thumbnail = "http://download.gamevideos.com/{$int_matches[1]}/thumbnail.jpg";						
					}
					
					if (strpos($matches[4][$i], 'embed') === false) {
						$suffix = "</embed>" . $matches[4][$i];
					} else {
						$suffix = $matches[4][$i];
					}
					
					$replacement = $message;
					if ($thumbnail) {
						$replacement = '<a href="' . $post->guid . '"><img src="' . $thumbnail . '" alt="" /></a><br />' . $replacement;
					}
					$replacement = $matches[1][$i] . "<noembed><p>$replacement</p></noembed>" . $suffix;
					
					$content = str_replace($matches[0][$i], $replacement, $content); 
				}			
			}
			
			return $content;
		}

		function options_page() {
			if ($_POST[$this->prefix . "message"]) {
				$this->message = stripslashes($_POST[$this->prefix . "message"]);
				update_option($this->prefix . "message", $this->message);
			}
?>
<div class=wrap>
		 <form method="post">
		 <input type="hidden" name="wp_votd_update" value="true" />
		  <h2>Noembedder Options</h2>
		  <p>This will add &lt;noembed&gt; tags to any embedded content that doesn't already have them.  Good for when you use YouTube videos.</p>
		  <fieldset class="options">
		  <legend>Set the &lt;noembed&gt; text</legend>
		  <table width="100%" cellspacing="2" cellpadding="5" class="editform">
		  <tr>
		   <th scope="row" width="33%" valign="top">Noembed Text:<div style="font-weight:normal;">Use ##POST_URL## to put the link to the post in the text</div></th>
		   <td><textarea rows="3" cols="55" name="<?=$this->prefix?>message" id="<?=$this->prefix?>message"><?=$this->message?></textarea></td>
		  </tr>
		  </table>
		  </fieldset>
		  <div class="submit"><input type="submit" name="info_update" value="<?php _e('Update') ?> &raquo;" /></div>
		 </form>
			<div style="background-color:rgb(238, 238, 238); border: 1px solid rgb(85, 85, 85); padding: 5px; margin-top:10px;">
			<p>Did you find this plugin useful?  Please consider donating to help me continue developing it and other plugins.</p>
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_xclick"><input type="hidden" name="business" value="paypal@slaven.net.au"><input type="hidden" name="item_name" value="Noembedder Wordpress Plugin"><input type="hidden" name="no_note" value="1"><input type="hidden" name="currency_code" value="AUD"><input type="hidden" name="tax" value="0"><input type="hidden" name="bn" value="PP-DonationsBF"><input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but04.gif" border="0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!"></form>
		</div>
		</div>
<?php		
		}
	}
}

$wp_noembedder = new wp_noembedder();
?>
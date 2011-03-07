<?php

if( !function_exists('add_action') )
	die("access denied.");

define('POWERPRESS_FEED_HIGHLIGHTED', 'http://www.powerpresspodcast.com/category/highlighted/feed/?order=ASC');
define('POWERPRESS_FEED_NEWS', 'http://www.powerpresspodcast.com/feed/');

function powerpress_get_news($feed_url, $limit=10)
{
	include_once(ABSPATH . WPINC . '/feed.php');
	$rss = fetch_feed( $feed_url );
	
	// Bail if feed doesn't work
	if ( is_wp_error($rss) )
		return false;
	
	$rss_items = $rss->get_items( 0, $rss->get_item_quantity( $limit ) );
	
	// If the feed was erroneously 
	if ( !$rss_items ) {
		$md5 = md5( $this->feed );
		delete_transient( 'feed_' . $md5 );
		delete_transient( 'feed_mod_' . $md5 );
		$rss = fetch_feed( $this->feed );
		$rss_items = $rss->get_items( 0, $rss->get_item_quantity( $num ) );
	}
	
	return $rss_items;

}

	
function powerpress_dashboard_head()
{
	echo "<script type=\"text/javascript\" src=\"". powerpress_get_root_url() ."player.js\"></script>\n";
?>
<style type="text/css">
#blubrry_stats_summary {
	
}
#blubrry_stats_summary label {
	width: 40%;
	max-width: 150px;
	float: left;
}
#blubrry_stats_summary h2 {
	font-size: 14px;
	margin: 0;
	padding: 0;
}
.blubrry_stats_ul {
	padding-left: 20px;
	margin-top: 5px;
	margin-bottom: 10px;
}
.blubrry_stats_ul li {
	list-style-type: none;
	margin: 0px;
	padding: 0px;
}
#blubrry_stats_media {
	display: none;
}
#blubrry_stats_media_show {
	text-align: right;
	font-size: 85%;
}
#blubrry_stats_media h4 {
	margin-bottom: 10px;
}
.blubrry_stats_title {
	margin-left: 10px;
}
.blubrry_stats_updated {
	font-size: 80%;
}
.powerpress-news-dashboard {
/*	background-image:url(http://images.blubrry.com/powerpress/blubrry_logo.png);
	background-repeat: no-repeat;
	background-position: top right; */
}
.powerpress-news-dashboard .powerpressNewsPlayer {
	margin-top: 5px;
}
</style>
<?php
}

function powerpress_dashboard_stats_content()
{
	$Settings = get_option('powerpress_general');
	
	if( isset($Settings['disable_dashboard_widget']) && $Settings['disable_dashboard_widget'] == 1 )
		return; // Lets not do anythign to the dashboard for PowerPress Statistics
	
	// If using user capabilities...
	if( @$Settings['use_caps'] && !current_user_can('view_podcast_stats') )
		return;
		
	$content = false;
	$UserPass = $Settings['blubrry_auth'];
	$Keyword = $Settings['blubrry_program_keyword'];
	$StatsCached = get_option('powerpress_stats');
	if( $StatsCached && $StatsCached['updated'] > (time()-(60*60*3)) )
		$content = $StatsCached['content'];
	
	if( !$content )
	{
		if( !$UserPass )
		{
			$content = sprintf('<p>'. __('Wait a sec! This feature is only available to Blubrry Podcast Community members. Join our community to get free podcast statistics and access to other valuable %s.', 'powerpress') .'</p>',
					'<a href="http://www.blubrry.com/powerpress_services/" target="_blank">'. __('Services', 'powerpress') . '</a>' );
			$content .= ' ';
			$content .= sprintf('<p>'. __('Our %s integrated PowerPress makes podcast publishing simple. Check out the %s on our exciting three-step publishing system!', 'powerpress') .'</p>',
					'<a href="http://www.blubrry.com/powerpress_services/" target="_blank">'. __('Podcast Hosting', 'powerpress') .'</a>',
					'<a href="http://www.blubrry.com/powerpress_services/" target="_blank">'. __('Video', 'powerpress') .'</a>' );
		}
		else
		{
			$api_url = sprintf('%s/stats/%s/summary.html?nobody=1', rtrim(POWERPRESS_BLUBRRY_API_URL, '/'), $Keyword);
			$api_url .= (defined('POWERPRESS_BLUBRRY_API_QSA')?'&'. POWERPRESS_BLUBRRY_API_QSA:'');

			$content = powerpress_remote_fopen($api_url, $UserPass);
			if( $content )
				update_option('powerpress_stats', array('updated'=>time(), 'content'=>$content) );
			else
				$content = __('Error: An error occurred authenticating user.', 'powerpress');
		}
	}
?>
<div>
<?php
	echo $content;
	
	if( $UserPass )
	{
?>
	<div id="blubrry_stats_media_show">
		<a href="<?php echo admin_url(); ?>?action=powerpress-jquery-stats&amp;KeepThis=true&amp;TB_iframe=true&amp;modal=true" title="<?php echo __('Blubrry Media statistics', 'powerpress'); ?>" class="thickbox"><?php echo __('more', 'powerpress'); ?></a>
	</div>
<?php } ?>
</div>
<?php
}


function powerpress_dashboard_news_content()
{
	$Settings = get_option('powerpress_general');
	
	if( isset($Settings['disable_dashboard_news']) && $Settings['disable_dashboard_news'] == 1 )
		return; // Lets not do anything to the dashboard for PowerPress News
		
	powerpressadmin_community_news();
}


function powerpress_feed_text_limit( $text, $limit, $finish = '&hellip;') {
	if( strlen( $text ) > $limit ) {
			$text = substr( $text, 0, $limit );
		$text = substr( $text, 0, - ( strlen( strrchr( $text,' ') ) ) );
		$text .= $finish;
	}
	return $text;
}

function powerpress_dashboard_setup()
{
	if( !function_exists('wp_add_dashboard_widget') )
		return;
	
	$Settings = get_option('powerpress_general');
	$StatsDashboard = true;
	$NewsDashboard = true;
	
	if( isset($Settings['disable_dashboard_widget']) && $Settings['disable_dashboard_widget'] == 1 )
		$StatsDashboard = false; // Lets not do anythign to the dashboard for PowerPress Statistics
	
	if( isset($Settings['disable_dashboard_news']) && $Settings['disable_dashboard_news'] == 1 )
		$NewsDashboard = false; // Lets not do anythign to the dashboard for PowerPress Statistics
		
	if( @$Settings['use_caps'] && !current_user_can('view_podcast_stats') )
		$StatsDashboard = false;
		
	if( $Settings )
	{
		if( $NewsDashboard )
			wp_add_dashboard_widget( 'powerpress_dashboard_news', __( 'Blubrry PowerPress & Community Podcast', 'powerpress'), 'powerpress_dashboard_news_content' );
			
		if( $StatsDashboard )
			wp_add_dashboard_widget( 'powerpress_dashboard_stats', __( 'Blubrry Podcast Statistics', 'powerpress'), 'powerpress_dashboard_stats_content' );
	}
	
	if( !isset($Settings['dashboard_installed']) )
	{
		// First time we've seen this setting, so must be first time we've added the widgets, lets stack them at the top for convenience.
		powerpressadmin_add_dashboard_widgets();
		$NewSettings = array();
		$NewSettings['dashboard_installed'] = 1;
		powerpress_save_settings($NewSettings);
	}
}

function powerpressadmin_add_dashboard_widgets()
{
	global $wp_meta_boxes;
	$dashboard_current = $wp_meta_boxes['dashboard']['normal']['core'];
	
	$dashboard_powerpress = array();
	if( isset( $dashboard_current['powerpress_dashboard_news'] ) )
	{
		$dashboard_powerpress['powerpress_dashboard_news'] = $dashboard_current['powerpress_dashboard_news'];
		unset($dashboard_current['powerpress_dashboard_news']);
	}
	
	if( isset( $dashboard_current['powerpress_dashboard_stats'] ) )
	{
		$dashboard_powerpress['powerpress_dashboard_stats'] = $dashboard_current['powerpress_dashboard_stats'];
		unset($dashboard_current['powerpress_dashboard_stats']);
	}
	
	if( count($dashboard_powerpress) > 0 )
	{
		$wp_meta_boxes['dashboard']['normal']['core'] = array_merge($dashboard_powerpress, $dashboard_current);
	}
}
	 
add_action('admin_head-index.php', 'powerpress_dashboard_head');
add_action('wp_dashboard_setup', 'powerpress_dashboard_setup');

?>
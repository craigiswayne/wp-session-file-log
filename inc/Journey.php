<?php
namespace Splinter\WP;

/**
 * Class Journey
 *
 * @usage   do_action( 'Splinter\WP\Journey\log', 'Your Log Entry Title', 'Your Log Entry Description' );
 *
 * @package Splinter\WP\Journey
 */
class Journey{
	
	const PREFIX     = "splinter-file-logger";
	const LOG_FORMAT = 'html';
	
	const LOG_LEVEL_INFO = 'info';
	const LOG_LEVEL_PRIMARY = 'primary';
	const LOG_LEVEL_ERROR = 'danger';
	const LOG_LEVEL_WARNING = 'warning';
	const LOG_LEVEL_SUCCESS = 'success';
	const LOG_LEVEL_STANDARD = 'light';
	const LOG_LEVEL_DEBUG = 'debug';
	
	private static $current_log_file = false;
	
	static $vary_by_session = true;
	
	/**
	 * Used to vary log files by sessions
	 *
	 * @return bool|string
	 */
	private static function get_session_id(){
		
		if( PHP_SESSION_DISABLED === session_status() ){
			return false;
		}
		
		if ( empty($_SESSION)  && !isset($_SESSION) )  {
			session_start();
		}
		
		return session_id();
	}
	
	/**
	 * Gets access to a writable file
	 *
	 * @return false|string
	 */
	private static function get_writable_file(){
		$dir = wp_upload_dir()['path'];
		
		if( self::$vary_by_session && $session_id = self::get_session_id() ){
			self::$current_log_file = self::$current_log_file ?: $dir . '/'.self::PREFIX.'-'.$session_id.'.'.self::LOG_FORMAT;
		}else{
			self::$current_log_file = self::$current_log_file ?: $dir .'/'. wp_unique_filename( $dir, self::PREFIX . '.' . self::LOG_FORMAT );
		}
		
		return self::$current_log_file;
	}
	
	/**
	 * Writes the log details to the log file
	 *
	 * @param string $title
	 * @param string $info
	 * @param string $log_level
	 */
	private static function add( $title = '', $info = '', $log_level =  self::LOG_LEVEL_STANDARD ){
		
		if( !$title ){
			return;
		}
		
		self::prep();
		
		if( is_array( $info ) || is_object($info) ){
			$formatted_info = sprintf('<div><details closed><summary>Content</summary><pre>%s</pre></details></div>',var_export( $info, true ));
		}else{
			$formatted_info = strval($info);
		}
		
		$stacktrace = sprintf('<div><details closed><summary>%s</summary><pre>%s</pre></details></div>','Stacktrace', var_export(wp_debug_backtrace_summary(null,null,false), true));;
		
		$html = '
		      <tr class="table-%s">
		    	<td class="timestamp"><div style="width:170px">%s</div></td>
		    	<td class="message">%s</td>
				<td class="info" style="max-width:50vw">%s</td>
				<td class="stacktrace">%s</td>
			</tr>';
		
		$html = sprintf($html, $log_level, date_i18n( 'Y-m-d H:i' ), $title, $formatted_info, $stacktrace );
		
		
		self::write($html);
		
	}
	
	private static function write( $html ){
		$target = self::get_writable_file();
		$resource = fopen($target, 'a' );
		
		if( null === $resource ){
			return;
		}
		
		fputs( $resource, $html );
		fclose( $resource );
	}
	
	/**
	 * Creates a standard log entry
	 *
	 * @param string $title
	 * @param string $info
	 */
	public static function log( $title = '', $info = '' ){
		self::add( $title, $info, self::LOG_LEVEL_STANDARD );
	}
	
	/**
	 * Creates a standard log entry
	 *
	 * @param string $title
	 * @param string $info
	 */
	public static function info( $title = '', $info = '' ){
		self::add( $title, $info, self::LOG_LEVEL_INFO );
	}
	
	/**
	 * Creates a standard log entry
	 *
	 * @param string $title
	 * @param string $info
	 */
	public static function error( $title = '', $info = '' ){
		self::add( $title, $info, self::LOG_LEVEL_ERROR );
	}
	
	/**
	 * Creates a debug log entry
	 *
	 * @param string $title
	 * @param string $info
	 */
	public static function debug( $title = '', $info = '' ){
		self::add( $title, $info, self::LOG_LEVEL_DEBUG );
	}
	
	/**
	 * Creates a warning log entry
	 *
	 * @param string $title
	 * @param string $info
	 */
	public static function warn( $title = '', $info = '' ){
		self::add( $title, $info, self::LOG_LEVEL_WARNING );
	}
	
	/**
	 * Creates a primary log entry
	 *
	 * @param string $title
	 * @param string $info
	 */
	public static function primary( $title = '', $info = '' ){
		self::add( $title, $info, self::LOG_LEVEL_PRIMARY );
	}
	
	/**
	 * Creates a log entry with a success level
	 *
	 * @param string $title
	 * @param string $info
	 */
	public static function success( $title = '', $info = '' ){
		self::add( $title, $info, self::LOG_LEVEL_SUCCESS );
	}
	
	/**
	 * Preps the log file with the necessary headers
	 */
	private static function prep() {
		if ( file_exists( self::get_writable_file() ) ) {
			return;
		}
		
		$header = '<link rel="stylesheet" id="bootstrap-style-css" href="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" type="text/css" media="all">';
		if( $_SERVER ){
			$html = '<table class="table m-0">
		    <thead class="thead-dark">
		      <tr>
		    	<th><span style="display:block;width:170px">Timestamp</span></th>
		    	<th>Title</th>
				<th>Description</th>
				<th><details closed><summary>$_SERVER</summary><pre style="color:white" class="font-weight-light">%s</pre></details></th>
			</tr>
		    </thead>
		    <tbody>';
			$header .= sprintf($html, var_export($_SERVER, true) );
		}
		self::write($header);
	}
	
	/**
	 * Returns the current log file being used
	 *
	 * @return false|string
	 */
	public static function get_current_log_path(){
		return self::$current_log_file;
	}
	
	/**
	 * Logs all super globals to the current log file
	 */
	public static function log_all_globals(){
		self::debug( '$_POST', $_POST );
		self::debug( '$_GET', $_GET );
		self::debug( '$_FILES', $_POST );
		self::debug( '$_SESSION', $_POST );
		self::debug( '$_SERVER', $_POST );
		self::debug( '$_REQUEST', $_POST );
	}
}

/**
 * usage: do_action( 'Splinter\WP\Journey\log', $args );
 */
add_action( __NAMESPACE__.'\Journey\title', ['\Splinter\WP\Journey','primary'], 15, 2 );
add_action( __NAMESPACE__.'\Journey\primary', ['\Splinter\WP\Journey','primary'], 15, 2 );
add_action( __NAMESPACE__.'\Journey\log', ['\Splinter\WP\Journey','log'], 15, 2 );
add_action( __NAMESPACE__.'\Journey\info', ['\Splinter\WP\Journey','info'], 15, 2 );
add_action( __NAMESPACE__.'\Journey\error', ['\Splinter\WP\Journey','error'], 15, 2 );
add_action( __NAMESPACE__.'\Journey\warn', ['\Splinter\WP\Journey','warn'], 15, 2 );
add_action( __NAMESPACE__.'\Journey\debug', ['\Splinter\WP\Journey','debug'], 15, 2 );
add_action( __NAMESPACE__.'\Journey\success', ['\Splinter\WP\Journey','success'], 15, 2 );
add_action( __NAMESPACE__.'\Journey\log_all_globals', ['\Splinter\WP\Journey','log_all_globals'], 15, 2 );
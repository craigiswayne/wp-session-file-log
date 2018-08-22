<?php
namespace Splinter;

/**
 * Class File_Logger
 *
 * @usage   do_action( 'Splinter\File_Logger\log', 'Your Log Entry Title', 'Your Log Entry Description' );
 *
 * @package Splinter
 */
class File_Logger{
	
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
	 * @param string $content
	 * @param string $log_level
	 */
	private static function add( $title = '', $content = '', $log_level =  self::LOG_LEVEL_STANDARD ){
		
		if( !$title ){
			return;
		}
		
		self::prep();
		
		if( is_array( $content ) || is_object($content) ){
			$formatted_content = sprintf('<div><details closed><summary>Content</summary><pre>%s</pre></details></div>',var_export( $content, true ));
		}else{
			$formatted_content = strval($content);
		}
		
		$stacktrace = sprintf('<div><details closed><summary>%s</summary><pre>%s</pre></details></div>','Stacktrace', var_export(wp_debug_backtrace_summary(null,null,false), true));;
		
		$html = '<table class="table m-0">
		    <tbody>
		      <tr class="table-%s">
		    	<td><span style="display:block;width:170px">%s</span></td>
		    	<td>%s</td>
				<td>%s</td>
				<td>%s</td>
			</tr>
		    </tbody>
		  </table>';
		
		$html = sprintf($html, $log_level, date_i18n( 'Y-m-d H:i' ), $title, $formatted_content, $stacktrace );
		
		
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
	 * @param string $content
	 */
	public static function log( $title = '', $content = '' ){
		self::add( $title, $content, self::LOG_LEVEL_STANDARD );
	}
	
	/**
	 * Creates a standard log entry
	 *
	 * @param string $title
	 * @param string $content
	 */
	public static function info( $title = '', $content = '' ){
		self::add( $title, $content, self::LOG_LEVEL_INFO );
	}
	
	/**
	 * Creates a standard log entry
	 *
	 * @param string $title
	 * @param string $content
	 */
	public static function error( $title = '', $content = '' ){
		self::add( $title, $content, self::LOG_LEVEL_ERROR );
	}
	
	
	public static function debug( $title = '', $content = '' ){
		self::add( $title, $content, self::LOG_LEVEL_DEBUG );
	}
	
	
	public static function warn( $title = '', $content = '' ){
		self::add( $title, $content, self::LOG_LEVEL_WARNING );
	}
	
	/**
	 * Creates a primary log entry
	 *
	 * @param string $title
	 */
	public static function primary( $title = '' ){
		self::add( $title, null, self::LOG_LEVEL_PRIMARY );
	}
	
	/**
	 * Creates a log entry with a success level
	 *
	 * @param string $title
	 */
	public static function success( $title = '' ){
		self::add( $title, null, self::LOG_LEVEL_SUCCESS );
	}
	
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
		  </table>';
			$header .= sprintf($html, var_export($_SERVER, true) );
		}
		self::write($header);
	}
	
	public function get_current_log_path(){
		return self::$current_log_file;
	}
}

/**
 * usage: do_action( 'Splinter\File_Logger\log', $args );
 */
add_action( __NAMESPACE__.'\File_Logger\title', ['\Splinter\File_Logger','primary'], 15, 2 );
add_action( __NAMESPACE__.'\File_Logger\primary', ['\Splinter\File_Logger','primary'], 15, 2 );
add_action( __NAMESPACE__.'\File_Logger\log', ['\Splinter\File_Logger','log'], 15, 2 );
add_action( __NAMESPACE__.'\File_Logger\info', ['\Splinter\File_Logger','info'], 15, 2 );
add_action( __NAMESPACE__.'\File_Logger\error', ['\Splinter\File_Logger','error'], 15, 2 );
add_action( __NAMESPACE__.'\File_Logger\warn', ['\Splinter\File_Logger','error'], 15, 2 );
add_action( __NAMESPACE__.'\File_Logger\debug', ['\Splinter\File_Logger','warn'], 15, 2 );
add_action( __NAMESPACE__.'\File_Logger\success', ['\Splinter\File_Logger','success'], 15, 2 );
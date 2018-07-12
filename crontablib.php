<?php
/**
 * 
 * This classs provides handling of crontab jobs by performing unix command through exec.
 * Command to be executed `crontab $CRONFILE_PATH`.
 * This action WILL REPLACE ALL CRONTAB sheduled tasks for crontab for user under which your server runs,
 * so BE CAREFUL.
 * 
 * Please make sure:
 *  - you'r php exec is enabled
 *  - your server user has access to crontab
 *  - you know what you are doing
 * 
 * 
 * @author biozshock, Markus {code}
 * @package Library
 */
class Crontab
{
	/**
	 * Options of crontab. Loaded from config file if any
	 * 
	 * @var array
	 */
	protected $_options;
	protected $crontime = 's i G j n';
	protected $time_offset = -6;

	
	/**
	 * Constructor
	 * 
	 * @param array $config
	 * @return void
	 */
	public function __construct($config = array())
	{
		$this->PHP_PATH = PHP_PATH;
		foreach ($config as $key => $value) {
			$this->$key = $value;
		}
	}
	
	/**
	 * Adds job for action.
	 * 
	 * Action is a cli script you want to be executed
	 * 
	 * @param string/int $time time in crontab format
	 * 								if int then translate_timestamp function will be used with default format
	 * @param string $action action you want to be executed
	 * @return bool
	 */
	public function add_job($time, $action)
	{
		if (is_int($time)) {
			$time = $this->translate_timestamp($time);
		}
		if (!preg_match('~^([\d/\\*]+)\s([\d/\\*]+)\s([\d/\\*]+)\s([\d/\\*]+)\s([\d/\\*]+)$~', $time)) {
			log_message('DEBUG', 'Wrong preg for cron time ' . $time);
			return FALSE;
		}
		
		if (strpos($action, LIB_PATH) === FALSE) {
			$action = LIB_PATH . $action;
		}
		
		$jobs = explode("\n", read_file(CRONFILE_PATH));
		
		if (!is_array($jobs)) {
			$jobs = array();
		}
		
		$job = $time . ' ' .$this->PHP_PATH . ' ' . $action;
		
		if (!in_array($job, $jobs)) {
			$this->_write($job . "\n", FOPEN_READ_WRITE_CREATE);
		}
		
		return TRUE;
	}
	
	/**
	 * Removes cronjob for action at specified time
	 * 
	 * @param string/int $time time in crontab format
	 * 								if int then translate_timestamp function will be used with default format
	 * @param string $action action you want to be executed
	 * @return bool
	 */
	public function remove_job($time, $action)
	{
		if (is_int($time)) {
			$time = $this->translate_timestamp($time);
		}
		if (!preg_match('~^([\d/\\*]+)\s([\d/\\*]+)\s([\d/\\*]+)\s([\d/\\*]+)\s([\d/\\*]+)$~', $time)) {
			log_message('DEBUG', 'Wrong preg for cron time ' . $time);
			return FALSE;
		}
		$output     = shell_exec('crontab -l');
		$newcron    = $output;
		$outputfile = file_get_contents(CRONFILE_PATH,FILE_USE_INCLUDE_PATH);
		if (strpos($action, LIB_PATH) === FALSE) {
			$action = LIB_PATH . $action;
		}
		$job = $time . ' ' .$this->PHP_PATH . ' ' . $action;
		if(!empty($job)){
			if (strstr($output, $job)) {
				$newcron    = str_replace($job,"",$newcron);
				$outputfile = str_replace($job,"",$outputfile);
			}
		}
		put_to_file(CRONFILE_PATH, '');
		put_to_file(CRONTMPLFILE_PATH, $newcron.PHP_EOL);
		echo exec('crontab '.CRONTMPLFILE_PATH);
		return TRUE;
	}
	
	/**
	 * Replaces time for action
	 * 
	 * @param string $from_time time in crontab format was sheduled 
	 * 							if int then translate_timestamp function will be used with default format
	 * @param string $to_time time in crontab format should be scheduled
	 * 							if int then translate_timestamp function will be used with default format
	 * @param string $action action you want to be executed
	 * @return bool
	 */
	public function replace_time($from_time, $to_time, $action)
	{
		$this->remove_job($from_time, $action);
		$this->add_job($to_time, $action);
	}
	
	/**
	 * Remove all jobs for action
	 * 
	 * @param string $action action you want to be executed
	 * @return void
	 */
	public function remove_all_jobs()
	{
		$jobs = explode("\n", read_file(CRONFILE_PATH));
		
		if (!is_array($jobs)) {
			$jobs = array();
		}
		$output = shell_exec('crontab -l');
		$newcron = $output;
		foreach ($jobs as &$job) {
			if(!empty($job)){
				if (strstr($output, $job)) {
					$newcron = str_replace($job,"",$newcron);
				}
			}
		}
		put_to_file(CRONFILE_PATH, '');
		put_to_file(CRONTMPLFILE_PATH, $newcron.PHP_EOL);
		echo exec('crontab '.CRONTMPLFILE_PATH);
	}
	
	/**
	 * Write to CRONFILE_PATH function
	 * 
	 * @param string $text text to write
	 * @param string $mode
	 * @return void
	 */
	protected function _write($text, $mode = FOPEN_WRITE_CREATE_DESTRUCTIVE)
	{
		log_message('DEBUG', 'Writing to CRONFILE_PATH: ' . CRONFILE_PATH,$text);
		write_file(CRONFILE_PATH, $text, $mode);
		$this->_exec('crontab ' . CRONFILE_PATH);
	}
	
	/**
	 * Translate timestamp to cron time string
	 * 
	 * @param int $timestamp
	 * @param string $format format of cron time for date() function
	 * @return string
	 */
	public function translate_timestamp($timestamp, $format = '') // s i H d m ? Y  but w/o leading zeroz
	{
		if ($format == '') {
			$format = $this->crontime;
		}
		return date($format, $timestamp + ($this->time_offset * 60 * 60));
	}
	
	/**
	 * Exec of external program
	 * 
	 * @param string $command command to execute
	 * @return void
	 */
	protected function _exec($command)
	{
		$output = array();
		if (!defined('PHPUnit_MAIN_METHOD')) {
			exec($command, $output);
		} else {
			$output = array('called from phpunit');
		}
		log_message('DEBUG', $command);
	}
	
	public function __get($variable)
	{
		if (isset($this->$variable)) {
			return $this->_options[$variable];
		}
		
		return NULL;
	}
	
	public function __set($variable, $value)
	{
		$this->_options[$variable] = $value;
	}
	
	public function __isset($variable)
	{
		return isset($this->_options[$variable]);
	}
	
	public function __unset($variable)
	{
		if (isset($this->_options[$variable])) {
			unset($this->_options[$variable]);
		}
	}
}
if ( ! function_exists('octal_permissions'))
{
	function octal_permissions($perms)
	{
		return substr(sprintf('%o', $perms), -3);
	}
}
if ( ! function_exists('symbolic_permissions'))
{
	function symbolic_permissions($perms)
	{	
		if (($perms & 0xC000) == 0xC000)
		{
			$symbolic = 's'; // Socket
		}
		elseif (($perms & 0xA000) == 0xA000)
		{
			$symbolic = 'l'; // Symbolic Link
		}
		elseif (($perms & 0x8000) == 0x8000)
		{
			$symbolic = '-'; // Regular
		}
		elseif (($perms & 0x6000) == 0x6000)
		{
			$symbolic = 'b'; // Block special
		}
		elseif (($perms & 0x4000) == 0x4000)
		{
			$symbolic = 'd'; // Directory
		}
		elseif (($perms & 0x2000) == 0x2000)
		{
			$symbolic = 'c'; // Character special
		}
		elseif (($perms & 0x1000) == 0x1000)
		{
			$symbolic = 'p'; // FIFO pipe
		}
		else
		{
			$symbolic = 'u'; // Unknown
		}

		// Owner
		$symbolic .= (($perms & 0x0100) ? 'r' : '-');
		$symbolic .= (($perms & 0x0080) ? 'w' : '-');
		$symbolic .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x' ) : (($perms & 0x0800) ? 'S' : '-'));

		// Group
		$symbolic .= (($perms & 0x0020) ? 'r' : '-');
		$symbolic .= (($perms & 0x0010) ? 'w' : '-');
		$symbolic .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x' ) : (($perms & 0x0400) ? 'S' : '-'));

		// World
		$symbolic .= (($perms & 0x0004) ? 'r' : '-');
		$symbolic .= (($perms & 0x0002) ? 'w' : '-');
		$symbolic .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x' ) : (($perms & 0x0200) ? 'T' : '-'));

		return $symbolic;		
	}
}
if ( ! function_exists('get_mime_by_extension'))
{
	function get_mime_by_extension($file)
	{
		$extension = substr(strrchr($file, '.'), 1);

		global $mimes;

		if ( ! is_array($mimes))
		{
			if ( ! require_once(APPPATH.'config/mimes.php'))
			{
				return FALSE;
			}
		}

		if (array_key_exists($extension, $mimes))
		{
			if (is_array($mimes[$extension]))
			{
				// Multiple mime types, just give the first one
				return current($mimes[$extension]);
			}
			else
			{
				return $mimes[$extension];
			}
		}
		else
		{
			return FALSE;
		}
	}
}
if ( ! function_exists('get_file_info'))
{
	function get_file_info($file, $returned_values = array('name', 'server_path', 'size', 'date'))
	{

		if ( ! file_exists($file))
		{
			return FALSE;
		}

		if (is_string($returned_values))
		{
			$returned_values = explode(',', $returned_values);
		}

		foreach ($returned_values as $key)
		{
			switch ($key)
			{
				case 'name':
					$fileinfo['name'] = substr(strrchr($file, DIRECTORY_SEPARATOR), 1);
					break;
				case 'server_path':
					$fileinfo['server_path'] = $file;
					break;
				case 'size':
					$fileinfo['size'] = filesize($file);
					break;
				case 'date':
					$fileinfo['date'] = filectime($file);
					break;
				case 'readable':
					$fileinfo['readable'] = is_readable($file);
					break;
				case 'writable':
					// There are known problems using is_weritable on IIS.  It may not be reliable - consider fileperms()
					$fileinfo['writable'] = is_writable($file);
					break;
				case 'executable':
					$fileinfo['executable'] = is_executable($file);
					break;
				case 'fileperms':
					$fileinfo['fileperms'] = fileperms($file);
					break;
			}
		}

		return $fileinfo;
	}
}
if ( ! function_exists('get_dir_file_info'))
{
	function get_dir_file_info($source_dir, $include_path = FALSE, $_recursion = FALSE)
	{
		static $_filedata = array();
		$relative_path = $source_dir;

		if ($fp = @opendir($source_dir))
		{
			// reset the array and make sure $source_dir has a trailing slash on the initial call
			if ($_recursion === FALSE)
			{
				$_filedata = array();
				$source_dir = rtrim(realpath($source_dir), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
			}

			while (FALSE !== ($file = readdir($fp)))
			{
				if (@is_dir($source_dir.$file) && strncmp($file, '.', 1) !== 0)
				{
					 get_dir_file_info($source_dir.$file.DIRECTORY_SEPARATOR, $include_path, TRUE);
				}
				elseif (strncmp($file, '.', 1) !== 0)
				{
					$_filedata[$file] = get_file_info($source_dir.$file);
					$_filedata[$file]['relative_path'] = $relative_path;
				}
			}
			return $_filedata;
		}
		else
		{
			return FALSE;
		}
	}
}
if ( ! function_exists('get_filenames'))
{
	function get_filenames($source_dir, $include_path = FALSE, $_recursion = FALSE)
	{
		static $_filedata = array();
				
		if ($fp = @opendir($source_dir))
		{
			// reset the array and make sure $source_dir has a trailing slash on the initial call
			if ($_recursion === FALSE)
			{
				$_filedata = array();
				$source_dir = rtrim(realpath($source_dir), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
			}
			
			while (FALSE !== ($file = readdir($fp)))
			{
				if (@is_dir($source_dir.$file) && strncmp($file, '.', 1) !== 0)
				{
					 get_filenames($source_dir.$file.DIRECTORY_SEPARATOR, $include_path, TRUE);
				}
				elseif (strncmp($file, '.', 1) !== 0)
				{
					$_filedata[] = ($include_path == TRUE) ? $source_dir.$file : $file;
				}
			}
			return $_filedata;
		}
		else
		{
			return FALSE;
		}
	}
}
if ( ! function_exists('delete_files'))
{
	function delete_files($path, $del_dir = FALSE, $level = 0)
	{	
		// Trim the trailing slash
		$path = rtrim($path, DIRECTORY_SEPARATOR);
			
		if ( ! $current_dir = @opendir($path))
			return;
	
		while(FALSE !== ($filename = @readdir($current_dir)))
		{
			if ($filename != "." and $filename != "..")
			{
				if (is_dir($path.DIRECTORY_SEPARATOR.$filename))
				{
					// Ignore empty folders
					if (substr($filename, 0, 1) != '.')
					{
						delete_files($path.DIRECTORY_SEPARATOR.$filename, $del_dir, $level + 1);
					}				
				}
				else
				{
					unlink($path.DIRECTORY_SEPARATOR.$filename);
				}
			}
		}
		@closedir($current_dir);
	
		if ($del_dir == TRUE AND $level > 0)
		{
			@rmdir($path);
		}
	}
}
if ( ! function_exists('write_file'))
{
	function write_file($path, $data, $mode = FOPEN_WRITE_CREATE_DESTRUCTIVE)
	{
		if ( ! $fp = @fopen($path, $mode))
		{
			return FALSE;
		}
		
		flock($fp, LOCK_EX);
		fwrite($fp, $data);
		flock($fp, LOCK_UN);
		fclose($fp);	

		return TRUE;
	}
}
if ( ! function_exists('read_file'))
{
	function read_file($file)
	{
		if ( ! file_exists($file))
		{
			return FALSE;
		}
	
		if (function_exists('file_get_contents'))
		{
			return file_get_contents($file);		
		}

		if ( ! $fp = @fopen($file, FOPEN_READ))
		{
			return FALSE;
		}
		
		flock($fp, LOCK_SH);
	
		$data = '';
		if (filesize($file) > 0)
		{
			$data =& fread($fp, filesize($file));
		}

		flock($fp, LOCK_UN);
		fclose($fp);

		return $data;
	}
}
if ( ! function_exists('put_to_file'))
{
	function put_to_file($file,$text)
	{
		if ( ! $fp = fopen($file, 'w+'))
		{
			return FALSE;
		}
		fwrite($fp, $text);
		fclose($fp);
		return true;
	}
}
if(!function_exists('log_message')){

	function log_message($title = "",$sub = "",$data = ""){
		$out = $title.', '.$sub.', '.$data."\n";
		$fp  = fopen(dirname(__FILE__).DIRECTORY_SEPARATOR.'log_message.txt', 'a+');
		fwrite($fp, $out);
		fclose($fp);
	}
	
}
if(!function_exists('findPHP')){

	function findPHP() {
	    if (defined('PHP_BINARY') && is_executable(PHP_BINARY)) {
	        $res = PHP_BINARY;
	    } else {
	        $which       = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'where' : 'which';
	        $outputArr   = [];
	        $whichReturn = false;
	        $res         = exec($which . " php 2>&1", $outputArr, $whichReturn);
	        if ($whichReturn !== 0) {
	            $res        = false;
	            $lookIn     = array(
	                defined('PHP_BINDIR') ? PHP_BINDIR : '',
	                'c:\xampp',
	                'd:\xampp',
	                getenv('ProgramFiles'),
	                getenv('ProgramFiles(x86)'),
	                getenv('ProgramW6432')
	            );
	            $suffixes   = array(
	                'php',
	                '/php/php',
	                '/php/bin/php',
	            );
	            $extensions = array(
	                "",
	                ".exe"
	            );
	            foreach ($lookIn as $folder) {
	                foreach ($suffixes as $suffix) {
	                    foreach ($extensions as $extension) {
	                        $php = $folder . $suffix . $extension;
	                        if (is_executable($php)) {
	                            $res = realpath($php);
	                            break 3;
	                        }
	                    }
	                }
	            }
	        }
	    }
	    return $res;
	}
	
}
define('LIB_PATH'          , dirname(__FILE__).'/');
define('CRONFILE_PATH'     , LIB_PATH.'crontab');
define('CRONTMPLFILE_PATH' , LIB_PATH.'crontab.tmp');
define('FOPEN_READ_WRITE_CREATE' , 'a+');
$PHP_path = findPHP();
define('PHP_PATH' , $PHP_path);
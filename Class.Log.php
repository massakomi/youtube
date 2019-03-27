<?php
class Log {

	public function __construct()
    {

        $file = 'log-current.txt';
        if (file_exists($file)) {
        	unlink($file);
        }
        $this->logfile = fopen($file, 'w+');

        $file = 'log-all.txt';
        $this->logallfile = fopen($file, 'a+');

        register_shutdown_function(function() {
            Log::get()->log('register_shutdown_function finish!');
        });
	}

	public static function get()
    {
        static $log;
        if (!isset($log)) {
        	$log = new Log;
        }
        return $log;
	}

    public function log($txt, $nl=1)
    {
        if ($nl) {
        	$txt = "\n".date('Y-m-d H:i:s').' '.$txt;
        }
        fwrite($this->logfile, $txt);
    }


    public function all($txt, $nl=1)
    {
        if ($nl) {
        	$txt = "\n".date('Y-m-d H:i:s').' '.$txt;
        }
        fwrite($this->logallfile, $txt);
    }
}
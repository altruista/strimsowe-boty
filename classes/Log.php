<?php

class Log
{    
    static $lines = Array();
    
    public static function Add($text)
    {
        $line = date("H:i:s") . " " . $text;
        echo $line . PHP_EOL;
        self::$lines[] = $line;
    }
    
    public static function SaveToFile($filepath)
    {
        file_put_contents($filepath, join(PHP_EOL, self::$lines) . PHP_EOL, FILE_APPEND);
    }
    
}
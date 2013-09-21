<?php

namespace Strimy;

class StrimFactory
{
    static public function CreateList($strimy)
    {
        if ( empty($strimy) )
        {
            die("Brak strimow do obrobienia = nie ma nic do roboty.");
        }

        // robimy ladna asocjacyjna tablice strimow
        foreach($strimy as &$strim_name)
        {
            $strim_name = basename($strim_name, ".strim.php");
        }
        $strimy = array_flip($strimy);

        // tworzenie klas strimów
        foreach($strimy as $strim_name => &$strim)
        {    
            $strim = StrimFactory::GetInstance($strim_name);
        }

        return $strimy;
    }
    
    static protected $instances = array();
    
    /**
     * @return \Strimy\AbstractStrim
     */    
    static public function GetInstance($strim_name)
    {
        if ( ! isset(self::$instances[$strim_name]) )
        {
            self::$instances[$strim_name] = StrimFactory::Create($strim_name);
        }
        return self::$instances[$strim_name];
    }
    
    /**
     * @return \Strimy\AbstractStrim
     */
    static public function Create($strim_name)
    {
        $strim_class_name = "\\Strimy\\{$strim_name}";
        return new $strim_class_name();
    }
}
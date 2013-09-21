<?php

namespace Boty;

class BotFactory
{
    
    static protected $instances = array();
    
    /**
     * @param string $bot_name Nazwa bota
     * @see configs/bots/
     * @return Bot
     */
    static public function getInstance($bot_name)
    {        
        if ( ! isset(self::$instances[$bot_name]) )
        {
            self::$instances[$bot_name] = BotFactory::Create($bot_name);
        }
        return self::$instances[$bot_name];
    }
    
    /**
     * @param string $bot_name Nazwa bota
     * @see configs/bots/
     * @return Bot
     */
    static public function Create($bot_name)
    {
        require_once DIR_MODULES . "boty/{$bot_name}.bot.php";        
        $bot_class_name = "\\Boty\\{$bot_name}";
        return new $bot_class_name();
    }
}


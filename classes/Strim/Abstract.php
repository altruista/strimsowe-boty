<?php

namespace Strimy;

abstract class AbstractStrim
{
    private $_strim;   
    
    /**
     * @var \Boty\Bot
     */
    private $_strim_name;    
    
    protected $bot_name;
    
    protected $interval = 0;
        
    public function __construct()
    {
        $this->setBot( $this->bot_name );        
        $class_name_slashes = explode("\\", get_class($this));
        $this->_strim_name = end($class_name_slashes);
    }
    
    /**
     * @return \Boty\AbstractBot
     */
    final public function Bot()
    {
        return $this->_bot;        
    }
    
    final public function setBot($bot_mixed)
    {
        if ( is_string($bot_mixed) )
        {
            $this->_bot = \Boty\BotFactory::getInstance($bot_mixed);
        } else if ( $bot_mixed instanceof \Boty\AbstractBot )
        {
            $this->_bot = $bot_mixed;
        } else
        {            
            die("To nie bot: " . print_r($bot_mixed, true));
        }
    }
    
    final public function getStrimName()
    {
        return $this->_strim_name;
    }
    
    final public function getInterval()
    {
        return $this->interval;
    }
    
    final public function getListingsAndUpdate()
    {
        $result = $this->getListings();
        
        \Database::getInstance()->update('strimy', array(
            'last_update' => date("Y-m-d H:i:s")
        ), array('strim' => $this->getStrimName()));

        \Log::Add('Zakutalizowalem strim ' . $this->getStrimName() . ' ostatnia aktualizacja: ' . date("Y-m-d H:i:s"));        
        return $result;
    }
    
    public function getLastUpdate()
    {
        $row = \Database::getInstance()->get_row('strimy', array('strim' => $this->getStrimName()));
        if ( ! $row ) 
        {
            return FALSE;
        }
        return $row->last_update;
    }
        
    abstract public function getListings();    
}

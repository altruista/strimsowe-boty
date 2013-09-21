<?php

namespace Boty;

require_once DIR_CONFIGS . "passwords.php";

class agregat extends AbstractBot
{    
    protected $password = PASSWORD_AGREGAT;
    
    public function __construct()
    {        
        parent::__construct();
        $this->say('Cześć!');         
    }
}
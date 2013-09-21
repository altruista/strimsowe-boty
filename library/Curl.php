<?php

namespace Library;

class Curl extends \API_Curl
{
    public function __construct($config = array())
    {
        $this->config['cookie_file'] = tempnam(sys_get_temp_dir(), 'tmp_cookie');
        parent::__construct($config);
    }
    
    public function getJSON($url)
    {
        return json_decode($this->get($url));
    }
}
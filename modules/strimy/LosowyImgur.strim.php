<?php

namespace Strimy;

class LosowyImgur extends AbstractStrim
{
    protected $bot_name = "agregat";
    
    protected $interval = 3600; // co godzina

    protected $max_tries = 50;
    
    public function getListings()
    {
        function rand_string($length) 
        {
            $alf = "0123456789qwertyuiopasdfghjklzxcvbnm";
            $result = "";
            for($i=0;$i<$length;$i++) $result .= $alf[ rand(0,strlen($alf)-1) ];
            return $result;
        }

        function imgur_get_random_photo($tries = 100)
        {
            if($tries <= 0) return false;

            $url = 'http://i.imgur.com/'.rand_string(7).'.jpg';
            $raw = @file_get_contents($url);
            $test = strlen( $raw );
            
            // 503 bajty zajmuje obrazek o tresci "to nie istnieje"
            if($test == 503) return imgur_get_random_photo($tries - 1);
            
            // minumium 50 KB
            if($test < 50*1024) return imgur_get_random_photo($tries - 1);
            
            if(strpos($raw, 'File not found!')) return imgur_get_random_photo($tries - 1);

            return $url;
        }

        $url = imgur_get_random_photo($this->max_tries);
        //$url = "http://i.imgur.com/dZia6s.png"; // testy

        if (!$url) {
            return array();
        }

        // to jest numer ostatniego losowegoimgura
        // ktory byl jeszcze przed magicznymi limitami strimsa        
        $start = 2055;
        $counter = 1 + $start + \Database::getInstance()->get_var("listings", "COUNT(*)", array('strim' => 'LosowyImgur'));
        
        $title = $counter;
        while(strlen($title) < 7) $title = "0" . $title;
        $title = "#".$title;

        $listing = ListingFactory::CreateInStrim($this);
        
        $listing->setTitle($title);
        $listing->setUrl($url);
        
        return array($listing);
    }
}
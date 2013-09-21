<?php

namespace Boty;

class AbstractBot
{   
    private $_api;

    private $_name;
    
    protected $password;
       
    public function __construct()
    {
        $class_name_slashes = explode("\\", get_class($this));
        $this->_name = end($class_name_slashes);
    }
    
    public function getName()
    {
        return $this->_name;
    }
    
    private $_is_logged_in = false;
    
    public function isLoggedIn()
    {
        return $this->_is_logged_in;
    }
    
    public function logIn()
    {
        $this->_logged_in = $this->API()->login($this->getName(), $this->password);
        if ( ! $this->_logged_in ) 
        {
            $this->say('Nie moge się zalogować :(');
            return false;
        }
        return true;
    }
    
    /**
     * @return \Strims
     */
    public function API($is_request = true)
    {   
        if ( is_null($this->_api) )
        {
            $this->_api = new \Strims(array(
                'cookie_file' => DIR_COOKIES . $this->getName() . ".bot.cookie.txt"
            ));
        } else
        {
            if ( $is_request )
            {
                // na potrzebych ograniczen .... dzieki @strims
                $this->say('Czekam 10 sekund ;x');
                sleep( 10 ); 
            }
        }        
        return $this->_api;
    }
    
    protected function say($message)
    {
        \Log::Add($this->getName() . ": {$message}");
    }
    
    protected function postListingSuccess(\Strimy\Listing $listing, $link_id)
    {
        $this->say("Dodano link: {$link_id}");           
    }
    
    public function postRelatedLink(\Strimy\Listing $listing, $link_id, $related)
    {
        $this->say("Dodaję powiązane {$related['title']}");
        $this->API()->add_related_link($link_id, $related['title'], $related['url']);
    }
    
    protected function postListingAlreadyPosted(\Strimy\Listing $listing)
    {
        $this->say("Link już istnieje w tym strimie!");
    }
    
    protected function postListingFailure(\Strimy\Listing $listing)
    {
        $this->say("Nie mogę dodać linku :-( HTML z zapytania : " . $this->API(false)->html);
    }
    
    public function postListing(\Strimy\Listing $listing)
    {
        $this->say("Dodaję nowy link " . $listing->getUrl() . " (" . $listing->getTitle() . ") do s/" . $listing->getStrimName() . "...");
        
        if ( !$this->isLoggedIn() && !$this->logIn() ) 
        {            
            $this->say('Nie dodaje linku bo nie moge sie zalogować...');
            return false;
        }
        
        $link_id = $this->API()->post_link($listing->getStrimName(), $listing->getTitle(), $listing->getUrl());
                
        if ( ! $link_id )
        {
            if (strpos($this->API(false)->html, 'tnieje w tym strimie') !== false)
            {
                $this->postListingAlreadyPosted($listing);                
                return true;
            }

            $this->postListingFailure($listing);            
            return false;
        }
                
        $this->postListingSuccess($listing, $link_id);
        
        $related = $listing->getRelatedLink();
        
        if ( $related )
        {
            $this->postRelatedLink($listing, $link_id, $related);            
        }
                 
        return $link_id;
    }
}


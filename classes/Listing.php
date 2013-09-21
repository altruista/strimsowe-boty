<?php

namespace Strimy;

require_once DIR_ROOT . "classes/Listing/Factory.php";

class Listing
{
    protected $properties = array(
        'strim'     => null,
        'url'       => null,
        'title'     => null,
        'extras'    => array()
    );
    
    protected $strim;
    
    protected $already_listed = false;
    
    protected $listing_id = false;
    
    public function __construct(AbstractStrim $strim, $already_listed = false, $listing_id = false)
    {        
        $this->strim = $strim;
        $this->properties['strim'] = $strim->getStrimName();
        
        if ( $already_listed )
        {
            $this->already_listed = $already_listed;
        }
        if ( $listing_id )
        {
            $this->listing_id = $listing_id;
        }
    }
    
    public function add()
    {
        \Log::Add("Dodaje listing " . $this->getTitle() . " do " . $this->getStrimName());
        if ( $this->already_listed )
        {
            \Log::Add("Listing już dodany");
            return ;
        }
        $result = $this->strim->Bot()->postListing($this);        
        
        // todo wiecej informacji
        \Log::Add('Wynik = ' . ($result ? "sukces" : "nie udało się...") . ' link_id = ' . (($result === true) ? "[już istnieje w strimie]" : $result ));
        
        if ( $result ) 
        {
            $this->already_listed = true;   
            
            if ( $this->listing_id )
            {
                \Database::getInstance()->update('listings', array('already_listed' => 1), array(
                    'listing_id' => $this->listing_id                    
                ));
            }
        }
        return $result;
    }
    
    public function getStrimName()
    {
        return $this->properties['strim'];
    }
    
    public function getUrl()
    {
        return $this->properties['url'];
    }
    
    public function getTitle()
    {
        return $this->properties['title'];
    }
    
    public function getExtras()
    {
        return $this->properties['extras'];
    }
    
    public function getRelatedLink()
    {
        return isset($this->properties['extras']['related_link']) ? $this->properties['extras']['related_link'] : null;
    }
    
    public function setUrl($url)
    {
        $this->properties['url'] = $url;
    }
    
    public function setTitle($title)
    {
        $this->properties['title'] = $title;
    }
    
    public function setRelatedLink($title, $url)
    {
        $this->properties['extras']['related_link'] = array(
            'title' => $title,
            'url'   => $url
        );
    }
    
}
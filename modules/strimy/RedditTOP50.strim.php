<?php

namespace Strimy;

class RedditTop50 extends AbstractStrim
{
    protected $bot_name = "agregat";
    
    public function getListings()
    {        
        $top50 = json_decode(file_get_contents('http://www.reddit.com/top.json?limit=50&t=week'));
        
        $listings = array();
        foreach($top50->data->children as $link)
        {
            $title      = html_entity_decode($link->data->title);
            $url        = html_entity_decode($link->data->url);
            $permalink  = "http://reddit.com".html_entity_decode($link->data->permalink);
            
            $listing = ListingFactory::CreateInStrim($this);
            $listing->setURL($url);
            $listing->setTitle($title);
            $listing->setRelatedLink("Post na reddit.com", $permalink);
            $listings[] = $listing;
        }        
        return $listings;
    }
}
<?php

namespace Strimy;

class RedditScience extends AbstractStrim
{

    protected $bot_name = "agregat";
    
    protected $subreddits = array(
        'science',
        'physics',
        'space',
        'compsci',
        'statistics',
        'PureMathematics',
        'astrobiology',
        'Biophysics',
        'particlephysics',
        'cosmology',
        'chemistry',
        'math',
        'Astronomy',
        //'curiosityrover', 
        'chemicalreactiongifs',
        'spaceflight',
        //'nasa',
        //'spacegifs'
    );

    public function getListings()
    {
        $links = array();

        foreach ($this->subreddits as $subreddit)
        {
            $subreddit_links = file_get_contents('http://www.reddit.com/r/' . $subreddit . '/top.json?limit=5&t=year');
            $subreddit_links = json_decode($subreddit_links);
            if ( is_array($subreddit_links->data->children) )
            {
                $links = array_merge($links, $subreddit_links->data->children);
            }
        }
        
        $listings = array();

        foreach ($links as $link)
        {
            if ( ! $link->data->url )
                continue;

            $title = html_entity_decode($link->data->title);
            $url = html_entity_decode($link->data->url);
            $permalink = "http://reddit.com" . html_entity_decode($link->data->permalink);

            $listing = ListingFactory::CreateInStrim($this);
            $listing->setURL($url);
            $listing->setTitle($title);
            $listing->setRelatedLink("Post na reddit.com", $permalink);
            $listings[] = $listing;
        }

        return $listings;
    }

}
<?php

namespace Strimy;

class GlownaWykopu extends AbstractStrim
{
    protected $bot_name = "agregat";
    
    public function getListings()
    {        
        $html = find_one_between(file_get_contents('http://www.wykop.pl/'), 'id="links-list', false);
        $articles = find_between($html, '<article', '</article');

        foreach($articles as $index => &$article)
        {
            $article = (Object) array(
		'title'     => html_entity_decode(find_one_between($article, 'lass="link"><span class="fbold">','</span>')),
		'url'       => find_one_between($article, "\tz <a href=\"", '"'),
                'wykop_url' => find_one_between($article, '<p class="lheight18"><a href="', '"')
            );
            if( ! $article->url )
                unset($articles[$index]);
        }
        
        $listings = array();
        foreach($articles as $link)
        {    
            // sponsorowanym podziękujemy ;x
            if( strpos($link->url, 'wykop.pl/link/partnerredirect/') !== false )
		continue ;
            
            // to z czasów afery zbożowej :D
            // if(stripos($link->title, 'zboż') !== false) {
            //     continue ;
            // }
            
            $listing = ListingFactory::CreateInStrim($this);
            $listing->setTitle($link->title);
            $listing->setURL($link->url);            
            $listing->setRelatedLink('Znalezisko na wykop.pl', $link->wykop_url);            
            $listings[] = $listing;
        }
        
        return $listings;
    }
}
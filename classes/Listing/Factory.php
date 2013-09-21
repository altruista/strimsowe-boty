<?php

namespace Strimy;

class ListingFactory
{
    static public function CreateInStrim(AbstractStrim $strim)
    {
        return new Listing($strim);
    }
    
    /**
     * @param object $row
     * @return \Listing
     */
    static public function CreateFromDatabaseRow($row)
    {
        $strim = StrimFactory::GetInstance($row->strim);        
        $listing = new Listing($strim, $row->already_listed, $row->listing_id);
        
        $listing->setUrl($row->url);
        $listing->setTitle($row->title);
        
        $extras = unserialize(base64_decode($row->extras));
        
        if ( $extras )
        {
            if ( $extras['related_link'] )
            {
                $listing->setRelatedLink($extras['related_link']['title'], $extras['related_link']['url']);
            }
        }
        
        return $listing;
    }
}
<?php

class Queue
{
    public function populateListingsQueue($array_strims)
    {
        Log::Add("Rozpoczynam tworzenie linkow dla wszystkich strimow");
        
        $all_listings = array();
                
        foreach($array_strims as $strim)
        {
            Log::Add("Rozpoczynam tworzenie linkow dla strimu s/" . $strim->getStrimName());            
            
            if ( $interval = $strim->getInterval() )
            {
                $last_update_ts = strtotime( $strim->getLastUpdate() );
                $current_ts = time();
                $time_left = $last_update_ts + $interval - $current_ts;
                        
                if ( $time_left > 0 )
                {
                    Log::Add("Wstrzymano ze względu na interwał. Następta aktualizacja dopiero za {$time_left} sekund");
                    continue ;
                }
            }
            
            $listings = $strim->getListingsAndUpdate();
            $listings_count = empty($listings) ? 0 : count($listings);
            
            Log::Add("Zakończyłem tworzenie linkow dla strimu s/" . $strim->getStrimName() . ". Ilość linków: " . $listings_count);
            
            if ( $listings_count > 0 )
            {                
                $all_listings = array_merge($all_listings, $listings);
            }            
        }
        Log::Add("Zakończyłem tworzenie linkow dla wszystkich strimow");
        
        Log::Add("Rozpoczynam zapisywanie linkow");
        $this->addListingsToQueue($all_listings);                
        Log::Add("Zakończyłem zapisywanie linkow");
    }
        
    public function addListingsToQueue($listings)
    {
        foreach($listings as $listing)
        {
            $listing_ok = $listing->getURL() && $listing->getTitle();
            if ( ! $listing_ok )
            {
                Log::Add("Pomijam link - " .
                        "URL: " . $listing->getURL() . ", " .
                        "STRIM: " . $listing->getStrimName() . ", " .
                        "TITLE: " . $listing->getTitle() . " - " .
                        " brak jednego ze elementow url/tytul.");
                continue ;
            }            
            Log::Add("Dodaję link " . $listing->getURL() . " (s/" . $listing->getStrimName() . ") do kolejki...");            
            $this->addListingToQueue($listing);
        }
    }
    
    public function addListingToQueue(Strimy\Listing $listing)
    {
        $db = Database::getInstance();
        $db->insert_ignore("listings", array(
            'strim'             => $listing->getStrimName(),
            'url'               => $listing->getURL(),
            'url_md5'           => md5($listing->getURL()),
            'title'             => $listing->getTitle(),
            'extras'            => base64_encode(serialize($listing->getExtras()))
        ));
    }
    
    public function getListingsFromQueue( $limit = false )
    {
        $rows = Database::getInstance()->get_rows('listings', array('already_listed' => 0), 'added ASC', $limit);
        if ( ! $rows )
        {
            return array();
        }
        
        foreach($rows as &$row)
        {
            $row = Strimy\ListingFactory::CreateFromDatabaseRow($row);
        }
        return $rows;
    }
    
    public function postListings($options)
    {        
        $time_start = time();
        $counters = array(
            'listings' => 0
        );
        
        $limit_reached = function() use ($time_start, $options, $counters)
        {
            $time_left = $time_start + $options['time_limit'] - time();
            $result = ($time_left > 0) && ($counters['listings'] > $options['limit']);
            Log::Add("Sprawdzam limit ({$time_left} > 0) && ({$counters['listings']} > {$options['limit']})");
            return $result;
        };
        
        Log::Add("Mielę treści z bazy i wrzucam na strims (limit = {$options['limit']})...");
        
        $listings = $this->getListingsFromQueue( $options['limit'] );
        
        if ( empty($listings) )
        {
            Log::Add('Nie mam więcej czego dodawać.');
            return ;
        }
            
        foreach($listings as $listing)
        {
            Log::Add("Wrzucam " . count($listings) . " treści na strims...");
            
            $added = $listing->add();
            if ( $added )
            {                
                $counters['listings']++;
            }
            if ( $limit_reached() )
            {
                Log::Add('Limit osiągnięty');
                break;
            }            
        }
        Log::Add('Zakonczono petle mielenia tresci i wrzucania na strims :D');
    }
}
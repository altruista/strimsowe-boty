<?php

 /**
 * @author Altruista <http://strims.pl/u/altruista>
 */    
function find_between($str, $from, $to = false)
{
    $tmp = explode($from, $str);
    unset($tmp[0]);
    $result = Array();
    foreach ($tmp as $chunk) {
        if ($to === false && $chunk) {
            $result[] = $chunk;
            continue;
        }
        list($add) = explode($to, $chunk);
        if ($add) {
            $result[] = $add;
        }
    }
    return $result;
}

 /**
 * @author Altruista <http://strims.pl/u/altruista>
 */  
function find_one_between($str, $from, $to = false)
{
    $result = find_between($str, $from, $to);
    return isset($result[0]) ? $result[0] : false;
}

 /**
 * cURL
 * @author Altruista <http://strims.pl/u/altruista>
 */  
class API_Curl
{    
    public      $html;
    public      $last_url = "";
    private     $_ch      = false;
    protected   $_config  = Array(
        'cookie_file' => 'cookie.txt',
        'user_agent' => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1'
    );
    
    
    /**
     * Konstruktor api
     * @param array $config ustawienia. można określić położenie pliku cookie
     * @throws Exception
     */
    public function __construct($config = Array())
    {
        $this->_config = array_merge($this->_config, $config);
        if (!is_writeable($this->_config['cookie_file'])) {
            // plik nie jest do zapisu. próbujemy go utworzyć
            @file_put_contents($this->_config['cookie_file'], '');
            
            // czy plik dalej jest nie do zapisu?
            if (!is_writeable($this->_config['cookie_file'])) {
                throw new Exception("Plik {$this->_config['cookie_file']} nie ma uprawnien do zapisu!");
            }
        }
    }
    
    private function _curl_init()
    {
        if ($this->_ch) {
            curl_close($this->_ch);
        }
        $this->_ch = curl_init();
        curl_setopt($this->_ch, CURLOPT_COOKIEFILE, $this->_config['cookie_file']);
        curl_setopt($this->_ch, CURLOPT_COOKIEJAR, $this->_config['cookie_file']);
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, 1); nie działa w safe_mode...
        curl_setopt($this->_ch, CURLOPT_USERAGENT, $this->_config['user_agent']);
    }
    
    private function _curl_close()
    {
        curl_close($this->_ch);
        $this->_ch = false;
    }
    
    public function get($url)
    {
        $this->_curl_init();
        $this->last_url = $url;
        curl_setopt($this->_ch, CURLOPT_URL, $url);
        $this->html = $this->_curl_exec($this->_ch);
        $this->_curl_close();
        return $this->html;
    }
    
    public function post($url, $data, $no_redirect = false)
    {
        $this->_curl_init();
        $this->last_url = $url;
        curl_setopt($this->_ch, CURLOPT_URL, $url);
        curl_setopt($this->_ch, CURLOPT_POST, 1);
        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, http_build_query($data));
        if ($no_redirect) {
            $this->html = curl_exec($this->_ch);
        } else {
            $this->html = $this->_curl_exec($this->_ch);
        }
        $this->_curl_close();
        return $this->html;
    }
    
    /**
     * Chamska funkcja bo CURLOPT_FOLLOWLOCATION nie działa w safe_mode :/           *
     */
    private function _curl_exec($ch, &$maxredirect = null)
    {
        $mr = $maxredirect === null ? 10 : intval($maxredirect);
        if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $mr > 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $mr);
        } else {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            if ($mr > 0) {
                $newurl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
                
                $rch = curl_copy_handle($ch);
                curl_setopt($rch, CURLOPT_HEADER, true);
                curl_setopt($rch, CURLOPT_NOBODY, false);
                curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
                curl_setopt($rch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($rch, CURLOPT_COOKIEFILE, $this->_config['cookie_file']);
                curl_setopt($rch, CURLOPT_COOKIEJAR, $this->_config['cookie_file']);
                do {
                    curl_setopt($rch, CURLOPT_URL, $newurl);
                    $header = curl_exec($rch);
                    
                    if (curl_errno($rch)) {
                        $code = 0;
                    } else {
                        $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
                        if ($code == 301 || $code == 302) {
                            preg_match('/Location:(.*?)\n/', $header, $matches);
                            $newurl = trim(array_pop($matches));
                        } else {
                            $code = 0;
                        }
                    }
                    $this->last_url = $newurl;
                } while ($code && --$mr);
                curl_close($rch);
                if (!$mr) {
                    if ($maxredirect === null) {
                        throw new Exception('Too many redirects. When following redirects, libcurl hit the maximum amount.');
                    } else {
                        $maxredirect = 0;
                    }
                    return false;
                }
                curl_setopt($ch, CURLOPT_URL, $newurl);
                $this->last_url = $newurl;
            }
        }
        return curl_exec($ch);
    }
}

class Strims extends API_Curl
{
    /**
     * @var string domena strims
     * @author Altruista <http://strims.pl/u/altruista>
     */
    private $_strims_domain = 'http://strims.pl/';
    
    /**
     * @var string token sesji. patrz get_token()
     * @author Altruista <http://strims.pl/u/altruista>
     */    
    private $_token;
    
    /**
     * @var bool prawda jeśli jesteśmy zalogowani
     * @author Altruista <http://strims.pl/u/altruista>
     */
    private $_logged_in;
    
    /**
     * Bazowa metoda wysyłająca zapytanie GET do serwera
     * @author Altruista <http://strims.pl/u/altruista>
     * @param string $url lokacja np. u/altruista
     * @return string html
     */
    public function get($url)
    {
        return parent::get($this->_strims_domain . $url);
    }
    
    /**
     * Bazowa metoda wysyłająca zapytanie POST do serwera
     * @author Altruista <http://strims.pl/u/altruista>
     * @param string $url lokacja np. u/altruista
     * @return string html
     */    
    public function post($url, $data, $no_redirect = false)
    {
        return parent::post($this->_strims_domain . $url, $data, $no_redirect);
    }
    
    /**
     * Zamienia krótką lokację (url) na pełny url (seo-friendly)
     * @author Altruista <http://strims.pl/u/altruista>
     * @param string $short_location krótka lokacja na strimsie np. t/abc123, 
     * @return string pełna lokacja na strimsie np. s/nazwa_strimu/t/abc123/tytul-tresci
     */
    public function get_full_location($short_location)
    {
        $this->get($short_location);
        // wycinamy początkowe "http://strims.pl/"
        list($nil, $result) = explode('://strims.pl/', $this->last_url, 2);
        return $result;
    }
    
    /**
     * Pobiera tytul tresci z linku  krotkiego
     * @author Simivar <http://strims.pl/u/simivar>
     * @param string $short_link krotka lokacja w strimsie, np. s/MariuszMaxKolonko/t/bslykt/
     * @return string zwraca tytul np. max-kolonko-polityczne-grzechy-obamy
    */
    public function get_title( $short_link ){
        $full_link = $this->get_full_location($short_link);
    	return str_replace($short_link, '', $full_link);
    }
    
    /**
     * Pobiera token sesji. Token jest używany do tego,
     * aby nikt nie mógł spreprarować linka do UV/DV itd.
     * Wykop swego czasu miał ten problem - "samowykopujace sie znelzisko"
     * @author Altruista <http://strims.pl/u/altruista>
     * @return string
     */
    public function get_token()
    {
        $this->get('zaloguj');
        return find_one_between($this->html, "page_template.token = '", "'");
    }
    
    /**
     * Logowanie do strims.pl
     * @author Altruista <http://strims.pl/u/altruista>
     * @param string $username Nazwa użytkownika
     * @param string $password Hasło uzytkownika
     * @return bool true jeśli udało się zalogować
     */
    public function login($username, $password)
    {
        $token          = $this->get_token();
        $login_postdata = Array(
            'token'                 => $token,
            '_external[remember]'   => 1,
            'name'                  => $username,
            'password'              => $password
        );
        $this->post('zaloguj', $login_postdata);
        $result = stripos($this->html, 'wyloguj') !== false;
        if ($result) {
            $this->_logged_in = true;
            // $this->_token = $token;
            // zdaje się że po logowaniu czasem (?) token się zmienia.
            // dlatego po zalogowaniu wejdziemy na strims.pl i ściągniemy najnowszy token
            $this->_token = $this->get_token();
        }
        return $result;
    }
    
    /**
     * Tworzy nowy strim
     * @author Simivar <http://strims.pl/u/simivar>
     * @param string $name Nazwa nowego strimu
     * @param string $title Tytul nowego strimu
     * @param int $privacy Prywatnosc strimu: 0 (publiczny) / 1 (polprywatny) / 2 (prywatny)
     * @param string $desc OPTIONAL Opis strimu 
     * @param string $sidebar OPTIONAL Sidebar strimu 
     * @param int $nsfw OPTIONAL 1: tylko dla doroslych
     * @param int $exposed OPTIONAL 1: pokazuj w strimie glownym
     * @param int $media OPTIONAL 1: pokazuj miniaturki
     * @param int $style OPTIONAL 1: uzywaj stylow 
     * @return bool true jesli udalo sie utworzyc strim
     */
    public function create_strim($name, $title, $privacy, $desc = '', $sidebar = '', $nsfw = 0, $exposed = 1, $media = 1, $style = 1)
    {
        if (!$this->_logged_in) {
            throw new Exception("Musisz byc zalogowany!");
        }
         
        $strim_postdata = Array(
         'token'     => $this->_token,
         'name'      => $name,
         'title'     => $title,
         'description' => $desc,
         'sidebar' => $sidebar,
         'privacy' => $privacy,
         'nsfw' => $nsfw,
         'exposed' => $exposed,
         'media' => $media,
         'style' => $style
        );
    
         $this->post('strimy/dodaj', $strim_postdata);
         $tmp = find_one_between($this->html, "page_template.section_name = '", "'");

         if ($tmp != $name) {
            return false;
        }
    	
        return true;
    }
    
    
    /**
     * Edycja opcji strimu
     * @author Simivar <http://strims.pl/u/simivar>
     * @required Prawa moderatora w danym strimie.
     * @param string $strim Nazwa strimu w ktorym zmienic opcje.
     * @param null|bool $nsfw Tylko dla doroslych, null jezeli nie 
     * chcesz zmieniac tej opcji, 0 wylaczenie, 1 wlaczenie.
     * @param null|bool $exposed Pokazuj w strimie glownym, null jezeli nie 
     * chcesz zmieniac tej opcji, 0 wylaczenie, 1 wlaczenie.
     * @param null|bool $media Pokazuj miniatury, null jezeli nie 
     * chcesz zmieniac tej opcji, 0 wylaczenie, 1 wlaczenie.
     * @param null|bool $style Uzywanie styli, null jezeli nie 
     * chcesz zmieniac tej opcji, 0 wylaczenie, 1 wlaczenie.
     * @return null
     */
    public function edit_strim_options( $strim, $nsfw  = NULL, $exposed  = NULL, $media  = NULL, $style  = NULL )
    {
        if (!$this->_logged_in) {
            throw new Exception("Musisz byc zalogowany!");
        }
        $this->get( 's/' . $strim . '/ustawienia' );
        
        /* Sprawdzamy czy uzytkownik chce zmienic dana opcje, jezeli nie - pobieramy stara wartosc */
        if (!is_null($nsfw))
            $postdata['nsfw'] = $nsfw;
        else {
            $nsfw_state = find_one_between($this->html, '<input type="checkbox" id="input_nsfw" name="nsfw" value="1" class="input_checkbox"', ' />');
            $postdata['nsfw'] = ($nsfw_state === FALSE) ? 1 : 0;
        }
        
        if( !is_null( $exposed ) )
            $postdata['exposed'] = $exposed;
        else {
            $exposed_state = find_one_between( $this->html, '<input type="checkbox" id="input_main" name="exposed" value="1" class="input_checkbox"', ' />' );
            $postdata['exposed'] = ($exposed_state === FALSE) ? 1 : 0;            
        }
    
        if( !is_null( $media ) )
            $postdata['media'] = $media;
        else {
            $media_state = find_one_between( $this->html, '<input type="checkbox" id="input_media" name="media" value="1" class="input_checkbox"', ' />' );
            $postdata['media'] = ($media_state === FALSE) ? 1 : 0;                        
        }
    
        if( !is_null( $style ) )
            $postdata['style'] = $style;
        else {
            $style_state = find_one_between( $this->html, '<input type="checkbox" id="input_style" name="style" value="1" class="input_checkbox"', ' />' );
            $postdata['style'] = ($style_state === FALSE) ? 1 : 0;                        
        }
    
        $postdata['token'] = $this->_token;
        $this->post('s/'.$strim.'/ustawienia', $postdata, true);
    }
    
    
    /**
     * Edycja tekstowych ustawien strimu
     * @author Simivar <http://strims.pl/u/simivar>
     * @required Prawa moderatora w danym strimie.
     * @param string $title Tytul strimu, null dla braku zmian
     * @param string $desc Opis strimu, null dla braku zmian
     * @param string $sidebar Sidebar strimu, null dla braku zmian
     * @return null
     */
    public function edit_strim_text( $strim, $title = NULL, $desc = NULL, $sidebar = NULL )
     {
     	if (!$this->_logged_in) {
            throw new Exception("Musisz byc zalogowany!");
        }
        $this->get( 's/' . $strim . '/ustawienia' );
    
        if( !is_null( $title ) )
            $postdata['title'] = $title;
            
        if( !is_null( $desc ) )
            $postdata['description'] = $desc;
    
        if( !is_null( $sidebar ) )
            $postdata['sidebar'] = $sidebar;
	 
        $postdata['token'] = $this->_token;
        $this->post('s/'.$strim.'/ustawienia', $postdata, true);
    }
    
    /**
     * Pobieranie wpisów
     * @author Altruista <http://strims.pl/u/altruista>
     * @param bool|string $strim skad wpisy np. "s/Ciekawostki", "u/Uzytkownik" lub false jeśli główne wpisy
     * @param int $page numer strony zaczynajać od 1
     * @return array Tablica z wynikami
     */
    public function get_entries($strim = false, $page = 1)
    {
        $this->get($strim . '/wpisy' . ($page == 1 ? "" : "?strona={$page}"));
        $tmp = find_between($this->html, 'entry   level_0', '</ul');
        
        $entries = Array();
        foreach ($tmp as $div) {
            $div_user = find_one_between($div, '<div class="entry_user">', '</a>');
            $div_info = find_one_between($div, 'entry_info', false);

            $entry = (Object) Array(
                'user'  => find_one_between($div_user, '<span class="bold">', '</span>'),
                'id'    => find_one_between($div, '<a id="', '" class="anchor"></a>'),
                'html'  => find_one_between($div, 'div class="markdown">', '</div>'),
                'strim' => find_one_between($div_info, 'href="/s/', '/')
            );
            $entry->text = strip_tags($entry->html);
            
            $entries[] = $entry;
        }
        return $entries;
    }
    
    /**
     * Pobieranie treści ze strimu
     * @author Altruista <http://strims.pl/u/altruista> 
     * @param string $strim nazwa strimu np. "s/Ciekawostki", "s/Ciekawostki/najnowsze"
     * @param int $page numer strony
     * @return array tablica z wynikami
     */
    public function get_listings($strim, $page = 1)
    {
        $this->get($strim . ($page == 1 ? "" : "?strona={$page}"));
        
        $tmp = find_between($this->html, '<div class="content level_0', '<ul class="content_info_actions');
        
        $listings = Array();
        foreach ($tmp as $content) {
            $title = find_one_between($content, '<a class="content_title', '>');
            
            $listing = (Object) Array(
                'title'         => find_one_between($content, 'title="', '"'),
                'thumb'         => find_one_between($content, '<img src="', '"'),
                'id'            => find_one_between($content, 'ref="/ajax/t/', '/lubi'),                
                'link'          => find_one_between($title, 'href="', '"')                
            );         
            
            /* TODO: FIXME: w zależności od tego czy jesteśmy zalogowani i mamy w ustawieniach
             * włączone ramki te wyniki będa się różnić. tr */                
            $listing->external_url = $listing->link;
            $listing->frame_url = $listing->link;
            
            $listing->strims_short_url = "http://strims.pl/t/{$listing->link}";
            //$listing->strims_full_url = null; /* TODO, na pewnoe nie ma sensu używać get_full_location() */            
            
            $listings[] = $listing;
        }
        return $listings;
    }
    
    /**
     * Pobieranie właściwości strimu
     * @todo na razie to tylko sprawdza czy strim istnieje
     * @author Altruista <http://strims.pl/u/altruista>
     * @param string $strim nazwa strimu np. "Ciekawostki"
     * @return boolean|object FALSE jeśli strim nie istnieje, obiekt z właściwościami jeśli istnieje
     */
    public function get_strim_details($strim)
    {
        $url = "s/{$strim}";
        $full_url = $this->_strims_domain . $url;
        $html = $this->get($url);
        
        // czy nastąpiło przekierowanie do http://strims.pl/strimy?szukaj=abcde
        if($this->last_url !== $full_url) {
            return FALSE;            
        }
        
        return (Object) Array(
            'exists'    => 1,
            'link'      => $full_url
        );
    }
    
    /**
     * Dodawanie wpisu
     * @author Altruista <http://strims.pl/u/altruista>
     * @param bool|string $strim dokad wpis np. "Ciekawostki" lub false jeśli do głównego 
     * @param string $content Treść wpisu
     * @return object odpowiedź ajax ze strimsa
     */
    public function post_entry($strim, $content)
    {
        if (!$this->_logged_in) {
            throw new Exception("Musisz byc zalogowany!");
        }
        $entry_postdata = Array(
            'token'             => $this->_token,
            '_external[parent]' => '',
            'text'              => $content,
            '_external[strim]'  => $strim
        );
        $result = $this->post('ajax/wpisy/dodaj', $entry_postdata, true);
        return json_decode($result);
    }
    
    /**
     * Dodawanie odpowiedzi do wpisu
     * @author Altruista <http://strims.pl/u/altruista>
     * @param string $entry_id id wpisu np. 'fgsd234'
     * @param string $content Treść wpisu (odpowiedzi)
     * @return object odpowiedź ajax ze strimsa
     */
    public function post_entry_answer($entry_id, $content)
    {
        if (!$this->_logged_in) {
            throw new Exception("Musisz byc zalogowany!");
        }
        $entry_postdata = Array(
            'token'             => $this->_token,
            '_external[parent]' => $entry_id,
            'text'              => $content
        );
        $result = $this->post('ajax/wpisy/dodaj', $entry_postdata, true);
        return json_decode($result);
    }
    
    /**
     * Dodawanie treści (link)
     * @author Altruista <http://strims.pl/u/altruista>
     * @param string $strim dokad wpis np. "Ciekawostki"
     * @param string $title tytuł
     * @param string $url url odnośnika
     * @param bool $thumb miniaturka true/false     
     * @return bool|string id tresci lub falsz w przypadku niepowodzenia
     */
    public function post_link($strim, $title, $url, $thumb = true)
    {
        if (!$this->_logged_in) {
            throw new Exception("Musisz byc zalogowany!");
        }
        $link_postdata = Array(
            'token'             => $this->_token,
            'kind'              => 'link',
            'title'             => $title,
            'url'               => $url,
            'text'              => '',
            '_external[strim]'  => $strim,
            'media'             => $thumb ? 1 : 0
        );
        $this->post('s/' . $strim . '/dodaj', $link_postdata);
        $tmp = find_one_between($this->html, 'content level_0', '</a>');
        
        if (!$tmp) {
            return false;
        }
        $id = find_one_between($tmp, 'id="', '"');
        if (!$id) {
            return false;
        }
        return $id;
    }
    
     /**
      * Dodawanie treści (text)
      * @author Simivar <http://strims.pl/u/simivar>
      * @param string $strim dokad wpis np. "Ciekawostki"
      * @param string $title tytuł
      * @param string $content zawartosc wpisu
      * @param bool $thumb miniaturka true/false     
      * @return bool|string id tresci lub falsz w przypadku niepowodzenia
      */
    public function post_text($strim, $title, $content, $thumb = true)
    {
        if (!$this->_logged_in) {
            throw new Exception("Musisz byc zalogowany!");
        }
        $link_postdata = Array(
            'token'             => $this->_token,
            'kind'              => 'text',
            'title'             => $title,
            'link'	        => '',
            'text'              => $content,
            '_external[strim]'  => $strim,
            'media'             => $thumb ? 1 : 0
        );
        $this->post('s/' . $strim . '/dodaj', $link_postdata);
        $tmp = find_one_between($this->html, 'content level_0', '</a>');
        
        if (!$tmp) {
            return false;
        }
        $id = find_one_between($tmp, 'id="', '"');
        if (!$id) {
            return false;
        }
        return $id;
    }
    
     /**
     * Dodaje etykiete do tresci
     * @author Simivar <http://strims.pl/u/simivar>
     * @required Prawa moderatora / bycie autorem tresci
     * @param string $strim dokad etykieta np. "Ciekawostki"
     * @param string $link_id id linku np. 'bslykt'
     * @param int $label_id id etykiety np. 544
     * @return string|bool klasa etykiety lub falsz w przypadku niepowodzenia
     */
    public function add_label($strim, $link_id, $label_id)
    {
        if (!$this->_logged_in) {
            throw new Exception("Musisz byc zalogowany!");
        }
         
    	$label_postdata = array(
            'token'             => $this->_token,
            'strim_flair_id'    => $label_id
    	);
    	
    	$title = $this->get_title('s/' . $strim . '/t/' . $link_id);
    
    	$this->post('s/' . $strim . '/t/' . $link_id . '/etykieta' . $title, $label_postdata);
        $tmp = find_one_between($this->html, 'content level_0', '">');
    	
    	if (!$tmp) {
            return false;
        }
    	
    	$label = str_replace('flair_', '', find_one_between( $tmp, 'flair ', ' clear' ));
    	
    	if (!$label) {
            return false;
        }
    	
    	return $label;
    }
    
     /**
     * Tworzy etykiete w strimie
     * @author Simivar <http://strims.pl/u/simivar>
     * @required Prawa moderatora w strimie
     * @param string $strim dokad etykieta np. "Ciekawostki"
     * @param string $name nazwa etykiety
     * @param string $css_class klasa css etykiety
     * @return string|bool ID etykiety lub falsz w przypadku niepowodzenia
     */
    public function create_label($strim, $name, $css_class = '')
    {
        if (!$this->_logged_in) {
            throw new Exception("Musisz byc zalogowany!");
        }
         
        $label_postdata = Array(
            'token'     => $this->_token,
            'name'      => $name,
            'class'     => $css_class,
            'action'    => 'add'
        );
    
    	$this->post('s/' . $strim . '/etykiety', $label_postdata);
        $tmp = find_one_between($this->html, $css_class, '</tr');

        if (!$tmp) {
            return false;
        }
    	
        $label_id = find_one_between($tmp, 'a href="/s/' . $strim . '/etykieta?id=', '"');

        if (!$label_id) {
            return false;
        }

        return $label_id;
    }
    
    /**
     * Dodaje link do powiązanych
     * @author Altruista <http://strims.pl/u/altruista>
     * @param string $link_id id linku np. 'ffa523'
     * @param string $title tytuł
     * @param string $url url linku
     * @param bool $thumb miniaturka 
     */
    public function add_related_link($link_id, $title, $url, $thumb = true)
    {
        if (!$this->_logged_in) {
            throw new Exception("Musisz byc zalogowany!");
        }        
        $related_postdata = Array(
            'token' => $this->_token,
            'title' => $title,
            'url'   => $url,
            'media' => $thumb ? 1 : 0
        );
        
        // aby dodać powiązany link trzeba znać pełen URL treści
        // np. s/nazwa_strimu/t/523fsd/tytul-tej-tresci
        $full_location = $this->get_full_location("t/{$link_id}");
        
        // tworzymy lokacje do wysłania POST aby dodać powiązany link
        $add_related_link_location = str_replace("t/{$link_id}/", "t/{$link_id}/powiazana/", $full_location);
        
        $this->post($add_related_link_location, $related_postdata);        
    }
    
    /**
     * Zatwierdzanie tresci/komentarza/wpisu w strimie
     * @author Simivar <http://strims.pl/u/simivar>
     * @required Prawa moderatora w strimie
     * @param string $content id tresci/komentarza/wpisu "t/lnnjb9" lub "k/j1ye65"
     * @return object odpowiedź ze strimsa
     */
    public function approve($content)
    {
        if (!$this->_logged_in) {
            throw new Exception("Musisz byc zalogowany!");
        }
        $result = $this->get('ajax/'.$content.'/zatwierdz?token='.$this->_token);
        return json_decode($result);
    }
    
    /**
     * Usuwanie tresci/komentarza/wpisu w strimie
     * @author Simivar <http://strims.pl/u/simivar>
     * @required Prawa moderatora w strimie
     * @param string $content id tresci/komentarza/wpisu "t/lnnjb9" lub "k/j1ye65"
     * @return object odpowiedź ze strimsa
     */
    public function remove($content)
    {
        if (!$this->_logged_in) {
            throw new Exception("Musisz byc zalogowany!");
        }
        $result = $this->get('ajax/'.$content.'/moderuj?token='.$this->_token);
        return json_decode($result);
    }
    
    /**
     * Subskrybuje strim
     * @author Simivar <http://strims.pl/u/simivar>
     * @param string $strim Nazwa strimu
     * @return object odpowiedź ze strimsa
     */
    public function subscribe($strim)
    {
        if (!$this->_logged_in) {
            throw new Exception("Musisz byc zalogowany!");
        }
        $result = $this->get('ajax/s/'.$strim.'/subskrybuj?token='.$this->_token);
        return json_decode($result);
    }
    
    /**
     * Odsubskrybuje strim
     * @author Simivar <http://strims.pl/u/simivar>
     * @param string $strim Nazwa strimu
     * @return object odpowiedź ze strimsa
     */
    public function unsubscribe($strim)
    {
        if (!$this->_logged_in) {
            throw new Exception("Musisz byc zalogowany!");
        }
        $result = $this->get('ajax/s/'.$strim.'/niesubskrybuj?token='.$this->_token);
        return json_decode($result);
    }
    
    /**
     * Blokuje strim
     * @author Simivar <http://strims.pl/u/simivar>
     * @param string $strim Nazwa strimu
     * @return object odpowiedź ze strimsa
     */
    public function block($strim)
    {
        if (!$this->_logged_in) {
            throw new Exception("Musisz byc zalogowany!");
        }
        $result = $this->get('ajax/s/'.$strim.'/zablokuj?token='.$this->_token);
        return json_decode($result);
    }
    
    /**
     * Odblokowuje strim
     * @author Simivar <http://strims.pl/u/simivar>
     * @param string $strim Nazwa strimu
     * @return object odpowiedź ze strimsa
     */
    public function unblock($strim)
    {
        if (!$this->_logged_in) {
            throw new Exception("Musisz byc zalogowany!");
        }
        $result = $this->get('ajax/s/'.$strim.'/odblokuj?token='.$this->_token);
        return json_decode($result);
    }
    
    /**
     * UV wpisu/treści np. 'w/9mv3db' lub 't/523fag'
     * @author Altruista <http://strims.pl/u/altruista>
     * @param string $content id wpisu
     * @return object odpowiedź ze strimsa
     */
    public function like($content)
    {
        if (!$this->_logged_in) {
            throw new Exception("Musisz byc zalogowany!");
        }
        $result = $this->get('ajax/'.$content.'/lubie?token='.$this->_token);
        return json_decode($result);
    }

    /**
     * DV wpisu/treści np. 'w/9mv3db' lub 't/523fag'
     * @author Altruista <http://strims.pl/u/altruista>
     * @param string $content id wpisu
     * @return object odpowiedź ze strimsa
     */
    public function dislike($content)
    {
        if (!$this->_logged_in) {
            throw new Exception("Musisz byc zalogowany!");
        }
        $result = $this->get('ajax/'.$content.'/nielubie?token='.$this->_token);
        return json_decode($result);
    }
    
    /**
     * UV wpisu np. '9mv3db'
     * @author Altruista <http://strims.pl/u/altruista>
     * @param string $entry_id id wpisu
     * @return object odpowiedź ze strimsa
     */
    public function like_entry($entry_id)
    {
        return $this->like("w/{$entry_id}");
    }

    /**
     * DV wpisu np. '9mv3db'
     * @author Altruista <http://strims.pl/u/altruista>
     * @param string $entry_id id wpisu
     * @return object odpowiedź ze strimsa
     */
    public function dislike_entry($entry_id)
    {
        return $this->dislike("t/{$entry_id}");
    }

    /**
     * UV treści np. '9mv3db'
     * @author Altruista <http://strims.pl/u/altruista>
     * @param string $link_id id wpisu
     * @return object odpowiedź ze strimsa
     */
    public function like_link($link_id)
    {
        return $this->like("t/{$link_id}");
    }

    /**
     * DV treści np. '9mv3db'
     * @author Altruista <http://strims.pl/u/altruista>
     * @param string $link_id id wpisu
     * @return object odpowiedź ze strimsa
     */
    public function dislike_link($link_id)
    {
        return $this->dislike("t/{$link_id}");
    }    
}

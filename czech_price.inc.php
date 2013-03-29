<?

/*
*	Converting prices between BTC <-> CZK
*
*	@author slush <info@bitcoin.cz>
*/
class CzechPrice
{
    protected $cache_file = '';
    protected $validity = null;
    protected $price = null;
    
    public function __construct($cache_file='czech_price.dat', $validity=300)
    {
        $this->cache_file = $cache_file;
        $this->validity = $validity;
    }

    /*
    *	Convert CZK price to BTC price
    */
    public function czk_to_btc($v, $decimal_places=3)
    {
        return round($v / $this->get_price(), $decimal_places);
    }
    
    /*
    *	Convert BTC price to CZK price
    */
    public function btc_to_czk($v, $decimal_places=2)
    {
        return round($v * $this->get_price(), $decimal_places);
    }   
    
    /**
    *	Load price from local cache or remote servers
    *
    *	This will throw an exception if the price cannot be retrieved
    *   (usually when mtgox is down and the cache is expired).
    */
    public function get_price()
    {
    	$price = $this->_cache_load();
    	
    	if($price === null)
    	{
    		// Refreshing price from servers
    	    try
    	    {
    		    $price = $this->get_fresh_price();
    		    $this->_cache_save($price);
    		} catch (Exception $e) {
    		    
    		    // Check failed, try to load outdated price from cache
    		    $price = $this->_cache_load(false);    		
    		}
    	}
    	
    	if ($price === null) throw new Exception("Cannot check current price");
    	return $price;
    }
    
    /**
    *	Load current price from Mtgox + CNB
    */
    protected function get_fresh_price()
    {
    	return $this->_get_mtgox() * $this->_get_cnb();
    }
    
    protected function _cache_load($check_timestamp=true)
    {
        if ($this->price !== null) return $this->price;
        
    	// Fail if we have no cache file
        if(!file_exists($this->cache_file)) return null;
		
        // Load structure from file
        $data = file_get_contents($this->cache_file);
        $data = json_decode($data, true);
				
        // Check if cached price is still valid
        if($check_timestamp && (time() - $data['timestamp']) > $this->validity) return null;
        
        $this->price = $data['price'];
        return $this->price;    
    }
    
    protected function _cache_save($price)
    {
    	$data = array('price' => $price, 'timestamp' => time());
		file_put_contents($this->cache_file, json_encode($data));
    }
    
    /*
    *	Return price of BTC in USD
    */
    protected function _get_mtgox()
    {
    	$data = $this->_get_url('https://data.mtgox.com/code/ticker.php');
        $data = json_decode((string)$data, true);

        if(!$data) throw new Exception("Error during retrieving data from mtgox.com, please try again.");

        return (float)$data['ticker']['last'];
    }

    /*
    *  Return price of USD in CZK
    */
    protected function _get_cnb()
    {
    	$data = $this->_get_url('http://www.cnb.cz/cs/financni_trhy/devizovy_trh/kurzy_devizoveho_trhu/denni_kurz.txt');
        $dollar = null;
        foreach(explode("\n", $data) as $line)
        {
            $cols = explode("|", $line);
	    
            if (count($cols)<3 || $cols[3] != 'USD') continue;
	
            $dollar = (float)str_replace(',', '.', $cols[4]);
        }
	    
        if(!$dollar) throw new Exception("Error during retrieving data from CNB, please try again");	    
        return $dollar;
    }
	    
    protected function _get_url($url)
    {
        $c = curl_init($url);
        curl_setopt($c, CURLOPT_TIMEOUT, 10);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
        $ret = curl_exec($c);
        curl_close($c);
        return $ret;    
    }
}

<?php
namespace SimplyTestable\WorkerBundle\Services;

use Guzzle\Http\Client as HttpClient;
use Guzzle\Plugin\Backoff\BackoffPlugin;
use Doctrine\Common\Cache\MemcacheCache;
use Guzzle\Cache\DoctrineCacheAdapter;
use Guzzle\Plugin\Cache\CachePlugin;

class HttpClientService { 
    
    
    /**
     *
     * @var \Guzzle\Http\Client
     */
    protected $httpClient = null;   
    
    
    /**
     *
     * @var (\SimplyTestable\WorkerBundle\Services\MemcacheService 
     */
    private $memcacheService = null;
    
    
    /**
     *
     * @var \Doctrine\Common\Cache\MemcacheCache 
     */
    private $memcacheCache = null;
    
    
    /**
     *
     * @var array
     */
    private $curlOptions = array();    
    
    
    /**
     * 
     * @param \SimplyTestable\WorkerBundle\Services\MemcacheService $memcacheService
     * @param array $curlOptions
     */
    public function __construct(
            \SimplyTestable\WorkerBundle\Services\MemcacheService $memcacheService,
            $curlOptions) {
        $this->memcacheService = $memcacheService;   
        
        foreach ($curlOptions as $curlOption) {
            if (defined($curlOption['name'])) {
                $this->curlOptions[constant($curlOption['name'])] = $curlOption['value'];
            }
        }        
    }      
    
    
    public function get($baseUrl = '', $config = null) {
        if (is_null($this->httpClient)) {
            $this->httpClient = new HttpClient($baseUrl, $config);            
            $this->enablePlugins();         
        }
        
        return $this->httpClient;
    }
    
    public function enablePlugins() {        
        foreach ($this->getPlugins() as $plugin) {
            if (!$this->hasPlugin(get_class($plugin))) {
                $this->httpClient->addSubscriber($plugin);
            }            
        }        
    }    
    
    
    public function disablePlugin($pluginClassName) {
        if (!$this->hasPlugin($pluginClassName)) {
            return true;
        }
        
        $this->get()->getEventDispatcher()->removeSubscriber($this->getPluginListener($pluginClassName));
    }  
    
    
    private function getPluginListener($pluginClassName) {
        foreach ($this->httpClient->getEventDispatcher()->getListeners() as $eventName => $eventListeners) {
            foreach ($eventListeners as $eventListener) {
                if (get_class($eventListener[0]) == $pluginClassName) {
                    return $eventListener[0];
                }
            }
        }       
    }
    
    
    /**
     * 
     * @param string $pluginClassName
     * @return boolean
     */
    public function hasPlugin($pluginClassName) {        
        foreach ($this->httpClient->getEventDispatcher()->getListeners() as $eventName => $eventListeners) {
            foreach ($eventListeners as $eventListener) {
                if (get_class($eventListener[0]) == $pluginClassName) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    
    protected function getPlugins() {        
        return array(
            BackoffPlugin::getExponentialBackoff(
                3,
                array(500, 503, 504)
            ),
            $this->getCachePlugin(),
            new \Guzzle\Plugin\History\HistoryPlugin()
        );
    }
    
    protected function getCachePlugin() {
        $memcacheCache = $this->getMemcacheCache();
        if (is_null($memcacheCache)) {
            return null;
        }

        $adapter = new DoctrineCacheAdapter($memcacheCache);            
        return new CachePlugin($adapter, true);
    }
    
    
    /**
     * 
     * @param string $uri
     * @param array $headers
     * @param string $body
     * @return \Guzzle\Http\Message\Request
     */
    public function getRequest($uri = null, $headers = null, $body = null) {        
        $request = $this->get()->get($uri, $headers, $body);        
        $request->setHeader('Accept-Encoding', 'gzip,deflate');
        
        foreach ($this->curlOptions as $key => $value) {
            $request->getCurlOptions()->set($key, $value);
        }        
        
        return $request;
    }
    
    
    /**
     * 
     * @param string $uri
     * @param array $headers
     * @param array $postBody
     * @return \Guzzle\Http\Message\Request
     */
    public function postRequest($uri = null, $headers = null, $postBody = null) {
        $request = $this->get()->post($uri, $headers, $postBody);        
        
        foreach ($this->curlOptions as $key => $value) {
            $request->getCurlOptions()->set($key, $value);
        }
        
        return $request;        
    }
    
    
    /**
     * 
     * @return \Doctrine\Common\Cache\MemcacheCache 
     */
    public function getMemcacheCache() {
        if (is_null($this->memcacheCache)) {
            $memcache = $this->memcacheService->get();
            if (!is_null($memcache)) {                
                $this->memcacheCache = new MemcacheCache();                
                $this->memcacheCache->setMemcache($memcache);                                    
            }            
        }
        
        return $this->memcacheCache;
    }
    
    
    /**
     * 
     * @return boolean
     */
    public function hasMemcacheCache() {
        return !is_null($this->getMemcacheCache());
    }
    
    
    
    /**
     * 
     * @return \Guzzle\Plugin\History\HistoryPlugin|null
     */
    public function getHistory() {
        $listenerCollections = $this->get()->getEventDispatcher()->getListeners('request.sent');
        
        foreach ($listenerCollections as $listener) {
            if ($listener[0] instanceof \Guzzle\Plugin\History\HistoryPlugin) {
                return $listener[0];
            }
        }
        
        return null;     
    }
    
}
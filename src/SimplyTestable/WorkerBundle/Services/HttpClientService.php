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
     * @param \SimplyTestable\WorkerBundle\Services\MemcacheService $memcacheService 
     */
    public function __construct(\SimplyTestable\WorkerBundle\Services\MemcacheService $memcacheService) {
        $this->memcacheService = $memcacheService;   
    }      
    
    
    public function get($baseUrl = '', $config = null) {
        if (is_null($this->httpClient)) {
            $this->httpClient = new HttpClient($baseUrl, $config);
            
            foreach ($this->getPlugins() as $plugin) {
                $this->httpClient->addSubscriber($plugin);
            }            
        }
        
        return $this->httpClient;
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
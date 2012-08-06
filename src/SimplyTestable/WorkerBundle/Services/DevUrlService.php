<?php
namespace SimplyTestable\WorkerBundle\Services;


/**
 * URL handling tasks 
 */
class DevUrlService extends UrlService {    
    
    /**
     *
     * @param string $url
     * @return string
     */
    public function prepare($url) {
        return $url;
    }
    
}
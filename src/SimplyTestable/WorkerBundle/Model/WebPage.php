<?php
namespace SimplyTestable\WorkerBundle\Model;

/**
 * 
 */
class WebPage extends WebResource
{
    /**
     *
     * @var string
     */
    private $characterEncoding;    
    
    
    /**
     *
     * @param string $url
     * @param string $content 
     */
    public function __construct($url, $content) {
        parent::__construct($url, 'text/html', $content);
    }
    
    
    /**
     *
     * @param string $characterEncoding 
     */
    public function setCharacterEncoding($characterEncoding) {
        $this->characterEncoding = $characterEncoding;
    }
    
    
    /**
     *
     * @return string
     */
    public function getCharacterEncoding() {
        return $this->getCharacterEncoding();
    }
    
}
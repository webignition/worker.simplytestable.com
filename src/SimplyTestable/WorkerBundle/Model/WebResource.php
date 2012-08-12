<?php
namespace SimplyTestable\WorkerBundle\Model;

/**
 * 
 */
class WebResource
{    
    
    /**
     * Full absolute URL to the remote endpoint
     * 
     * @var string
     * 
     */
    private $url;
    
    
    /**
     *
     * @var string
     */
    private $contentType;
    
    
    /**
     *
     * @var string
     */
    private $content;
    
    
    /**
     *
     * @param string $url
     * @param string $contentType
     * @param string $content 
     */
    public function __construct($url, $contentType, $content) {
        $this->setUrl($url);
        $this->setContentType($contentType);
        $this->setContent($content);
    }
    

    /**
     * Set url
     *
     * @param string $url
     * @return WebResource
     */
    public function setUrl($url)
    {
        $this->url = $url;    
        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }
    
    
    /**
     * Set content type
     *
     * @param string $contentType
     * @return WebResource
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;    
        return $this;
    }

    /**
     * Get content type
     *
     * @return string 
     */
    public function getContentType()
    {
        return $this->contentType;
    }    
    
    
    /**
     * Set content
     *
     * @param string $content
     * @return WebResource
     */
    public function setContent($content)
    {
        $this->content = $content;    
        return $this;
    }

    /**
     * Get content
     *
     * @return string 
     */
    public function getContent()
    {
        return $this->content;
    }    
}
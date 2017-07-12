<?php
namespace SimplyTestable\WorkerBundle\Model;

class RemoteEndpoint
{
    /**
     * Unique identifier for this remote endpoint, could be a string, integer, whatever you prefer
     *
     * @var mixed
     */
    private $identifier;

    /**
     * Full absolute URL to the remote endpoint
     *
     * @var string
     *
     */
    private $url;

    /**
     * @var int
     */
    private $method = 'GET';

    /**
     * @param string $url
     *
     * @return RemoteEndpoint
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $identifier
     *
     * @return RemoteEndpoint
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param int $method
     *
     * @return RemoteEndpoint
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     *
     * @return int
     */
    public function getMethod()
    {
        return $this->method;
    }
}

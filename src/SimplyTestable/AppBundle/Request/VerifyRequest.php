<?php

namespace SimplyTestable\AppBundle\Request;

use SimplyTestable\AppBundle\Entity\Task\Task;

class VerifyRequest
{
    /**
     * @var string
     */
    private $hostname;

    /**
     * @var string
     */
    private $token;

    /**
     * @param string $hostname
     * @param string $token
     */
    public function __construct($hostname, $token)
    {
        $this->hostname = trim($hostname);
        $this->token = trim($token);
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return !empty($this->hostname) && !empty($this->token);
    }

    /**
     * @return string
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }
}

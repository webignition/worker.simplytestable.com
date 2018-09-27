<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class ThisWorker
{
    const STATE_ACTIVE = 'active';
    const STATE_AWAITING_ACTIVATION_VERIFICATION = 'awaiting-activation-verification';
    const STATE_NEW = 'new';
    const STATE_MAINTENANCE_READ_ONLY = 'maintenance-read-only';

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true, nullable=false)
     */
    protected $hostname;


    /**
     * @var State
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\State")
     * @ORM\JoinColumn(name="state_id", referencedColumnName="id", nullable=false)
     */
    protected $state;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $activationToken;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $hostname
     *
     * @return $this
     */
    public function setHostname($hostname)
    {
        $this->hostname = $hostname;

        return $this;
    }

    /**
     * @return string
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * @param State $state
     *
     * @return $this
     */
    public function setState(State $state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return State
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $activationToken
     *
     * @return $this
     */
    public function setActivationToken($activationToken)
    {
        $this->activationToken = $activationToken;

        return $this;
    }

    /**
     * @return string
     */
    public function getActivationToken()
    {
        return $this->activationToken;
    }
}

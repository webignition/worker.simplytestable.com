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
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true, nullable=false)
     */
    private $hostname;

    /**
     * @var string
     *
     * @ORM\Column(nullable=true)
     */
    private $state;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $activationToken;

    public function getId(): int
    {
        return $this->id;
    }

    public function setHostname(string $hostname)
    {
        $this->hostname = $hostname;
    }

    public function getHostname(): string
    {
        return $this->hostname;
    }

    public function setState(string $state)
    {
        $this->state = $state;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setActivationToken(string $activationToken)
    {
        $this->activationToken = $activationToken;
    }

    public function getActivationToken(): string
    {
        return $this->activationToken;
    }

    public function isNew(): bool
    {
        return  self::STATE_NEW == $this->state;
    }

    public function isAwaitingActivationVerification(): bool
    {
        return self::STATE_AWAITING_ACTIVATION_VERIFICATION == $this->state;
    }

    public function isActive(): bool
    {
        return self::STATE_ACTIVE == $this->state;
    }
}

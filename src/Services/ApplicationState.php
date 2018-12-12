<?php

namespace App\Services;

class ApplicationState
{
    const STATE_ACTIVE = 'active';
    const STATE_AWAITING_ACTIVATION_VERIFICATION = 'awaiting-activation-verification';
    const STATE_NEW = 'new';
    const DEFAULT_STATE = self::STATE_ACTIVE;

    /**
     * @var string
     */
    private $stateResourcePath;

    /**
     * @var string
     */
    private $state;

    public function __construct(string $stateResourcePath)
    {
        $this->stateResourcePath = $stateResourcePath;
    }

    public function set(?string $state): bool
    {
        $state = trim($state);

        if (!$this->isAllowedState($state)) {
            return false;
        }

        if (file_put_contents($this->stateResourcePath, $state) !== false) {
            $this->state = $state;

            return true;
        }

        return false;
    }

    public function get(): string
    {
        if (is_null($this->state)) {
            $this->state = $this->readState();
        }

        return $this->state;
    }

    public function isActive()
    {
        return $this->isState(self::STATE_ACTIVE);
    }

    public function isAwaitingActivationVerification()
    {
        return $this->isState(self::STATE_AWAITING_ACTIVATION_VERIFICATION);
    }

    public function isNew()
    {
        return $this->isState(self::STATE_NEW);
    }

    private function isState(string $state)
    {
        return $state === $this->get();
    }

    private function readState(): string
    {
        $state = self::DEFAULT_STATE;

        if (file_exists($this->stateResourcePath)) {
            $state = trim(file_get_contents($this->stateResourcePath));
        }

        if (!$this->isAllowedState($state)) {
            $state = self::DEFAULT_STATE;
        }

        return $state;
    }

    private function isAllowedState(string $state): bool
    {
        return in_array($state, [
            self::STATE_ACTIVE,
            self::STATE_AWAITING_ACTIVATION_VERIFICATION,
            self::STATE_NEW,
        ]);
    }
}

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

    public function set(string $state): bool
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
        if (empty($this->state)) {
            $this->state = $this->readState();
        }

        return $this->state;
    }

    private function readState(): string
    {
        $state = self::STATE_NEW;

        if (file_exists($this->stateResourcePath)) {
            $state = file_get_contents($this->stateResourcePath);
            $state = $state === false ? '' : trim($state);
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

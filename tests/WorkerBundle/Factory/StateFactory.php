<?php

namespace Tests\WorkerBundle\Factory;

use SimplyTestable\WorkerBundle\Entity\State;

class StateFactory
{
    /**
     * @param string $name
     *
     * @return State
     */
    public static function create($name)
    {
        $state = new State();
        $state->setName($name);

        return $state;
    }
}

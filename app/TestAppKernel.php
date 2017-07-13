<?php

use PSS\SymfonyMockerContainer\DependencyInjection\MockerContainer;

class TestAppKernel extends AppKernel
{
    /**
     * @return string
     */
    protected function getContainerBaseClass()
    {
        if ('test' == $this->environment) {
            return MockerContainer::class;
        }

        return parent::getContainerBaseClass();
    }
}

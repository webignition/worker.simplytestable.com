<?php

namespace SimplyTestable\WorkerBundle\Tests\Services;

use SimplyTestable\WorkerBundle\Tests\BaseSimplyTestableTestCase;

abstract class ServiceTest extends BaseSimplyTestableTestCase {

    /**
     * @return mixed
     */
    protected function getService() {
        return $this->container->get($this->getServiceNameFromClassName());
    }


    /**
     * @return string
     */
    private function getServiceNameFromClassName() {
        $hasFoundServicePart = false;

        $classNameParts = explode('\\', get_class($this));

        foreach ($classNameParts as $index => $classNamePart) {
            if ($hasFoundServicePart) {
                unset($classNameParts[$index]);
            }

            if (in_array($classNamePart, ['WorkerBundle', 'Tests'])) {
                unset($classNameParts[$index]);
            }

            if (preg_match('/Test$/', $classNamePart)) {
                unset($classNameParts[$index]);
            }

            if (preg_match('/Service$/', $classNamePart)) {
                $hasFoundServicePart = true;
            }
        }

        return implode('.', $classNameParts);
    }




}

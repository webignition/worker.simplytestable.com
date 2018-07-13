<?php

namespace SimplyTestable\AppBundle\Services\TaskDriver;

use SimplyTestable\AppBundle\Entity\Task\Task;
use webignition\NodeJslint\Wrapper\Configuration\Configuration as WrapperConfiguration;
use webignition\NodeJslint\Wrapper\Configuration\Flag\JsLint as JsLintFlag;
use webignition\NodeJslint\Wrapper\Configuration\Option\JsLint as JsLintOption;

class NodeJsLintWrapperConfigurationFactory
{
    const JSLINT_PARAMETER_NAME_PREFIX = 'jslint-option-';

    /**
     * @var string
     */
    private $nodePath;

    /**
     * @var string
     */
    private $nodeJsLintPath;

    /**
     * @param string $nodePath
     * @param string $nodeJsLintPath
     */
    public function __construct($nodePath, $nodeJsLintPath)
    {
        $this->nodePath = $nodePath;
        $this->nodeJsLintPath = $nodeJsLintPath;
    }

    /**
     * @param Task $task
     *
     * @return WrapperConfiguration
     */
    public function create(Task $task)
    {
        return new WrapperConfiguration(array_merge(
            [
                WrapperConfiguration::CONFIG_KEY_NODE_PATH => $this->nodePath,
                WrapperConfiguration::CONFIG_KEY_NODE_JSLINT_PATH => $this->nodeJsLintPath,
            ],
            $this->getNodeJsLintConfigurationFlagsAndOptionsFromParameters($task->getParametersArray())
        ));
    }

    /**
     * @param array $parameters
     *
     * @return array
     */
    private function getNodeJsLintConfigurationFlagsAndOptionsFromParameters($parameters = [])
    {
        if (empty($parameters)) {
            $parameters = [];
        }

        $flags = [];
        $options = [];

        foreach ($parameters as $key => $value) {
            if (!$this->isJslintParameter($key)) {
                continue;
            }

            $jsLintKey = str_replace(self::JSLINT_PARAMETER_NAME_PREFIX, '', $key);

            if ($this->isJslintBooleanParameter($jsLintKey)) {
                $flags[$jsLintKey] = (bool)$value;
            } elseif ($this->isJsLintSingleOccurrenceOptionParameter($jsLintKey)) {
                $options[$jsLintKey] = $value;
            } elseif ($this->isJslintCollectionOptionParameter($jsLintKey)) {
                if (is_array($value)) {
                    $value = $value[0];
                }

                $options[$jsLintKey] = explode(' ', $value);
            }
        }

        return [
            WrapperConfiguration::CONFIG_KEY_FLAGS => $flags,
            WrapperConfiguration::CONFIG_KEY_OPTIONS => $options,
        ];
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    private function isJslintParameter($key)
    {
        return substr($key, 0, strlen(self::JSLINT_PARAMETER_NAME_PREFIX)) == self::JSLINT_PARAMETER_NAME_PREFIX;
    }

    /**
     * @param string $jsLintKey
     *
     * @return bool
     */
    private function isJslintBooleanParameter($jsLintKey)
    {
        return in_array($jsLintKey, JsLintFlag::getList());
    }

    /**
     * @param string $jsLintKey
     *
     * @return bool
     */
    private function isJslintOptionParameter($jsLintKey)
    {
        return in_array($jsLintKey, JsLintOption::getList());
    }

    /**
     * @param string $jsLintKey
     *
     * @return bool
     */
    private function isJsLintSingleOccurrenceOptionParameter($jsLintKey)
    {
        $singleOccurrenceOptions = array(
            JsLintOption::INDENT,
            JsLintOption::MAXERR,
            JsLintOption::MAXLEN,
        );

        return $this->isJslintOptionParameter($jsLintKey) && in_array($jsLintKey, $singleOccurrenceOptions);
    }

    /**
     * @param string $jsLintKey
     *
     * @return bool
     */
    private function isJslintCollectionOptionParameter($jsLintKey)
    {
        $singleOccurrenceOptions = array(
            JsLintOption::PREDEF
        );

        return $this->isJslintOptionParameter($jsLintKey) && in_array($jsLintKey, $singleOccurrenceOptions);
    }
}

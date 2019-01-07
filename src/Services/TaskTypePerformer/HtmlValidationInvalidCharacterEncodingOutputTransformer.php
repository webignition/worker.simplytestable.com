<?php

namespace App\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\Task\TypeInterface;
use App\Services\TaskOutputMessageFactory;
use webignition\InternetMediaType\InternetMediaType;

class HtmlValidationInvalidCharacterEncodingOutputTransformer implements TaskPerformerInterface
{
    /**
     * @var TaskOutputMessageFactory
     */
    private $taskOutputMessageFactory;

    /**
     * @var int
     */
    private $priority;

    public function __construct(TaskOutputMessageFactory $taskOutputMessageFactory, int $priority)
    {
        $this->taskOutputMessageFactory = $taskOutputMessageFactory;
        $this->priority = $priority;
    }

    public function perform(Task $task)
    {
        $output = $task->getOutput();
        if (empty($output)) {
            return null;
        }

        $decodedOutput = json_decode($output->getOutput(), true);
        $messages = $decodedOutput['messages'] ?? null;
        if (empty($messages)) {
            return null;
        }

        if (count($messages) > 1) {
            return null;
        }

        $message = $messages[0];
        $messageId = $message['messageId'] ?? null;

        if ('character-encoding' !== $messageId) {
            return null;
        }

        $messageContent = $message['message'];
        $characterEncoding = $this->getCharacterEncodingFromInvalidCharacterEncodingOutput($messageContent);

        $updatedOutput = Output::create(
            json_encode($this->taskOutputMessageFactory->createInvalidCharacterEncodingOutput($characterEncoding)),
            new InternetMediaType('application', 'json'),
            1
        );

        $task->setOutput($updatedOutput);

        return null;
    }

    public function handles(string $taskType): bool
    {
        return TypeInterface::TYPE_HTML_VALIDATION === $taskType;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    private function getCharacterEncodingFromInvalidCharacterEncodingOutput(string $messageContent): string
    {
        $codeFragmentMatches = [];
        preg_match('/<code>[^<]+<\/code>/', $messageContent, $codeFragmentMatches);

        return strip_tags($codeFragmentMatches[0]);
    }
}

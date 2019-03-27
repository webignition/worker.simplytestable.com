<?php

namespace App\Services\TaskTypePerformer\HtmlValidation;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\Model\Task\Type;
use App\Services\TaskOutputMessageFactory;
use webignition\InternetMediaType\InternetMediaType;

class InvalidCharacterEncodingOutputTransformer
{
    /**
     * @var TaskOutputMessageFactory
     */
    private $taskOutputMessageFactory;

    public function __construct(TaskOutputMessageFactory $taskOutputMessageFactory)
    {
        $this->taskOutputMessageFactory = $taskOutputMessageFactory;
    }

    public function __invoke(TaskEvent $taskEvent)
    {
        if (Type::TYPE_HTML_VALIDATION === (string) $taskEvent->getTask()->getType()) {
            $this->perform($taskEvent->getTask());
        }
    }

    public function perform(Task $task)
    {
        $output = $task->getOutput();
        if (empty($output)) {
            return null;
        }

        $decodedOutput = json_decode($output->getContent(), true);
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
            (string) json_encode(
                $this->taskOutputMessageFactory->createInvalidCharacterEncodingOutput($characterEncoding)
            ),
            new InternetMediaType('application', 'json'),
            1
        );

        $task->setOutput($updatedOutput);

        return null;
    }

    private function getCharacterEncodingFromInvalidCharacterEncodingOutput(string $messageContent): string
    {
        $codeFragmentMatches = [];
        preg_match('/<code>[^<]+<\/code>/', $messageContent, $codeFragmentMatches);

        return strip_tags($codeFragmentMatches[0]);
    }
}

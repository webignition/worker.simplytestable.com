<?php

namespace App\Services\TaskTypePerformer\HtmlValidation;

use App\Entity\Task\Output as TaskOutput;
use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\Exception\UnableToPerformTaskException;
use App\Model\HtmlValidationMessageList;
use App\Model\Task\Type;
use App\Services\TaskCachedSourceWebPageRetriever;
use webignition\HtmlValidator\Wrapper\Wrapper as HtmlValidatorWrapper;
use webignition\HtmlValidatorOutput\Parser\Flags;
use webignition\InternetMediaType\InternetMediaType;
use webignition\WebResource\WebPage\WebPage;
use webignition\HtmlDocumentType\Extractor as DoctypeExtractor;
use webignition\HtmlDocumentType\Validator as DoctypeValidator;
use webignition\HtmlDocumentType\Factory as DoctypeFactory;

class TaskTypePerformer
{
    const DEFAULT_CHARACTER_ENCODING = 'UTF-8';
    const USER_AGENT = 'ST Web Resource Task Driver (http://bit.ly/RlhKCL)';

    private $taskCachedSourceWebPageRetriever;
    private $htmlValidatorWrapper;

    public function __construct(
        TaskCachedSourceWebPageRetriever $taskCachedSourceWebPageRetriever,
        HtmlValidatorWrapper $htmlValidatorWrapper
    ) {
        $this->taskCachedSourceWebPageRetriever = $taskCachedSourceWebPageRetriever;
        $this->htmlValidatorWrapper = $htmlValidatorWrapper;
    }

    /**
     * @param TaskEvent $taskEvent
     *
     * @throws UnableToPerformTaskException
     */
    public function __invoke(TaskEvent $taskEvent)
    {
        if (Type::TYPE_HTML_VALIDATION === (string) $taskEvent->getTask()->getType()) {
            $this->perform($taskEvent->getTask());
        }
    }

    /**
     * @param Task $task
     *
     * @return null
     *
     * @throws UnableToPerformTaskException
     */
    public function perform(Task $task)
    {
        if (!empty($task->getOutput())) {
            return null;
        }

        $webPage = $this->taskCachedSourceWebPageRetriever->retrieve($task);
        if (empty($webPage)) {
            throw new UnableToPerformTaskException();
        }

        return $this->performValidation($task, $webPage);
    }

    /**
     * {@inheritdoc}
     */
    private function performValidation(Task $task, WebPage $webPage)
    {
        $webPageContent = $webPage->getContent();
        $docTypeString = DoctypeExtractor::extract($webPageContent);

        if (empty($docTypeString)) {
            $isMarkup = strip_tags($webPageContent) !== $webPageContent;
            $output = $isMarkup
                ? (string) json_encode($this->getMissingDocumentTypeOutput())
                : (string) json_encode($this->getIsNotMarkupOutput($webPageContent));

            return $this->setTaskOutputAndState($task, $output, Task::STATE_FAILED_NO_RETRY_AVAILABLE, 1);
        }

        $doctypeValidator = new DoctypeValidator();
        $doctypeValidator->setMode(DoctypeValidator::MODE_IGNORE_FPI_URI_VALIDITY);

        try {
            if (!$doctypeValidator->isValid(DoctypeFactory::createFromDocTypeString($docTypeString))) {
                return $this->setTaskOutputAndState(
                    $task,
                    (string) json_encode($this->createInvalidDocumentTypeOutput($docTypeString)),
                    Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                    1
                );
            }
        } catch (\InvalidArgumentException $invalidArgumentException) {
            return $this->setTaskOutputAndState(
                $task,
                (string) json_encode($this->createInvalidDocumentTypeOutput($docTypeString)),
                Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                1
            );
        }

        $documentUri = 'file:' . $this->storeTmpFile($webPageContent);
        $characterEncoding = $webPage->getCharacterEncoding();
        if (null === $characterEncoding || 'ascii' === $characterEncoding) {
            $characterEncoding = self::DEFAULT_CHARACTER_ENCODING;
        }

        $output = $this->htmlValidatorWrapper->validate(
            $documentUri,
            $characterEncoding,
            Flags::IGNORE_AMPERSAND_ENCODING_ISSUES | Flags::IGNORE_CSS_VALIDATION_ISSUES
        );

        $state = $output->wasAborted() ? Task::STATE_FAILED_NO_RETRY_AVAILABLE : Task::STATE_COMPLETED;

        $messages = new HtmlValidationMessageList($output->getMessages());

        return $this->setTaskOutputAndState(
            $task,
            (string) json_encode([
                'messages' => array_values($messages->getMessages()),
            ]),
            $state,
            $messages->getErrorCount()
        );
    }

    private function setTaskOutputAndState(Task $task, string $output, string $state, int $errorCount)
    {
        $task->setOutput(TaskOutput::create(
            $output,
            new InternetMediaType('application', 'json'),
            $errorCount
        ));

        $task->setState($state);

        return null;
    }

    private function storeTmpFile(string $content): string
    {
        $filename = sys_get_temp_dir() . '/' . md5($content) . '.html';

        if (!file_exists($filename)) {
            file_put_contents($filename, $content);
        }

        return $filename;
    }

    private function getMissingDocumentTypeOutput(): array
    {
        return $this->createErrorOutput('No doctype', 'document-type-missing');
    }

    private function getIsNotMarkupOutput(string $fragment): array
    {
        return $this->createErrorOutput('Not markup', 'document-is-not-markup', [
            'fragment' => $fragment,
        ]);
    }

    private function createInvalidDocumentTypeOutput(string $documentType): array
    {
        return $this->createErrorOutput($documentType, 'document-type-invalid');
    }

    private function createErrorOutput(string $message, string $messageId, ?array $additionalData = []): array
    {
        return [
            'messages' => [
                array_merge([
                    'message' => $message,
                    'messageId' => $messageId,
                    'type' => 'error',
                ], $additionalData)
            ]
        ];
    }
}

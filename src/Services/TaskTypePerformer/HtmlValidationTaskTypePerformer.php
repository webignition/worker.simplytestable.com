<?php

namespace App\Services\TaskTypePerformer;

use App\Entity\Task\Output as TaskOutput;
use App\Entity\Task\Task;
use App\Model\Task\TypeInterface;
use App\Services\TaskCachedSourceWebPageRetriever;
use webignition\HtmlValidator\Output\Parser\Configuration as HtmlValidatorOutputParserConfiguration;
use webignition\HtmlValidator\Wrapper\Wrapper as HtmlValidatorWrapper;
use webignition\InternetMediaType\InternetMediaType;
use webignition\WebResource\WebPage\WebPage;
use webignition\HtmlDocumentType\Extractor as DoctypeExtractor;
use webignition\HtmlDocumentType\Validator as DoctypeValidator;
use webignition\HtmlDocumentType\Factory as DoctypeFactory;

class HtmlValidationTaskTypePerformer implements TaskPerformerInterface
{
    const DEFAULT_CHARACTER_ENCODING = 'UTF-8';
    const USER_AGENT = 'ST Web Resource Task Driver (http://bit.ly/RlhKCL)';

    /**
     * @var TaskCachedSourceWebPageRetriever
     */
    private $taskCachedSourceWebPageRetriever;

    /**
     * @var HtmlValidatorWrapper
     */
    private $htmlValidatorWrapper;

    /**
     * @var string
     */
    private $validatorPath;

    /**
     * @var int
     */
    private $priority;

    public function __construct(
        TaskCachedSourceWebPageRetriever $taskCachedSourceWebPageRetriever,
        HtmlValidatorWrapper $htmlValidatorWrapper,
        string $validatorPath,
        int $priority
    ) {
        $this->taskCachedSourceWebPageRetriever = $taskCachedSourceWebPageRetriever;

        $this->htmlValidatorWrapper = $htmlValidatorWrapper;
        $this->validatorPath = $validatorPath;
        $this->priority = $priority;
    }

    public function perform(Task $task)
    {
        if (!empty($task->getOutput())) {
            return null;
        }

        return $this->performValidation($task, $this->taskCachedSourceWebPageRetriever->retrieve($task));
    }

    public function handles(string $taskType): bool
    {
        return TypeInterface::TYPE_HTML_VALIDATION === $taskType;
    }

    public function getPriority(): int
    {
        return $this->priority;
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
                ? json_encode($this->getMissingDocumentTypeOutput())
                : json_encode($this->getIsNotMarkupOutput($webPageContent));

            return $this->setTaskOutputAndState($task, $output, Task::STATE_FAILED_NO_RETRY_AVAILABLE, 1);
        }

        $doctypeValidator = new DoctypeValidator();
        $doctypeValidator->setMode(DoctypeValidator::MODE_IGNORE_FPI_URI_VALIDITY);

        try {
            if (!$doctypeValidator->isValid(DoctypeFactory::createFromDocTypeString($docTypeString))) {
                return $this->setTaskOutputAndState(
                    $task,
                    json_encode($this->createInvalidDocumentTypeOutput($docTypeString)),
                    Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                    1
                );
            }
        } catch (\InvalidArgumentException $invalidArgumentException) {
            return $this->setTaskOutputAndState(
                $task,
                json_encode($this->createInvalidDocumentTypeOutput($docTypeString)),
                Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                1
            );
        }

        $webPageCharacterSet = $webPage->getCharacterSet();

        if (empty($webPageCharacterSet)) {
            $webPageCharacterSet = self::DEFAULT_CHARACTER_ENCODING;
        }

        $this->htmlValidatorWrapper->configure([
            HtmlValidatorWrapper::CONFIG_KEY_DOCUMENT_URI => 'file:' . $this->storeTmpFile($webPageContent),
            HtmlValidatorWrapper::CONFIG_KEY_VALIDATOR_PATH => $this->validatorPath,
            HtmlValidatorWrapper::CONFIG_KEY_DOCUMENT_CHARACTER_SET => $webPageCharacterSet,
            HtmlValidatorWrapper::CONFIG_KEY_PARSER_CONFIGURATION_VALUES => [
                HtmlValidatorOutputParserConfiguration::KEY_CSS_VALIDATION_ISSUES => true,
            ],
        ]);

        $output = $this->htmlValidatorWrapper->validate();
        $state = $output->wasAborted() ? Task::STATE_FAILED_NO_RETRY_AVAILABLE : Task::STATE_COMPLETED;

        return $this->setTaskOutputAndState(
            $task,
            json_encode([
                'messages' => $output->getMessages(),
            ]),
            $state,
            (int) $output->getErrorCount()
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

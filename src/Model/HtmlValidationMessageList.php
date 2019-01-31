<?php

namespace App\Model;

use webignition\HtmlValidatorOutput\Models\ValidatorErrorMessage;
use webignition\ValidatorMessage\MessageInterface;
use webignition\ValidatorMessage\MessageList;
use webignition\ValidatorMessage\MessageListInterface;

class HtmlValidationMessageList implements MessageListInterface
{
    private $messageList;

    public function __construct(MessageListInterface $messageList)
    {
        $this->messageList = $messageList;
    }

    public function addMessage(MessageInterface $message)
    {
        return $this->messageList->addMessage($message);
    }

    /**
     * @return MessageInterface[]
     */
    public function getMessages(): array
    {
        return $this->messageList->getMessages();
    }

    /**
     * @return MessageInterface[]
     */
    public function getErrors(): array
    {
        return $this->messageList->getErrors();
    }

    /**
     * @return MessageInterface[]
     */
    public function getWarnings(): array
    {
        return $this->messageList->getWarnings();
    }

    public function getErrorCount(): int
    {
        if (1 === count($this->messageList->getMessages())) {
            $message = current($this->messageList->getMessages());

            if ($message instanceof ValidatorErrorMessage &&
                'validator-internal-server-error' === $message->getMessageId()) {
                return 0;
            }
        }

        return $this->messageList->getErrorCount();
    }

    public function getWarningCount(): int
    {
        return $this->messageList->getWarningCount();
    }

    public function getInfoCount(): int
    {
        return $this->messageList->getInfoCount();
    }

    public function getMessageCount(): int
    {
        return $this->messageList->getMessageCount();
    }

    public function mutate(callable $mutator): MessageList
    {
        return $this->messageList->mutate($mutator);
    }

    public function filter(callable $matcher): MessageList
    {
        return $this->messageList->filter($matcher);
    }

    public function contains(MessageInterface $message): bool
    {
        return $this->messageList->contains($message);
    }

    public function merge(MessageList $messageList): MessageList
    {
        return $this->messageList->merge($messageList);
    }
}

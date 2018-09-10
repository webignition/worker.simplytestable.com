<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use App\Entity\Task\Type\Type as TaskType;

class TaskTypeService
{
    const HTML_VALIDATION_NAME = 'HTML Validation';
    const CSS_VALIDATION_NAME = 'CSS Validation';
    const URL_DISCOVERY_NAME = 'URL discovery';
    const LINK_INTEGRITY_NAME = 'Link integrity';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EntityRepository
     */
    private $entityRepository;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->entityRepository = $entityManager->getRepository(TaskType::class);
    }

    /**
     * @param string $name
     *
     * @return TaskType
     */
    public function fetch($name)
    {
        return $this->entityRepository->findOneBy([
            'name' => $name,
        ]);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return !is_null($this->fetch($name));
    }

    /**
     * @return TaskType
     */
    public function getLinkIntegrityTaskType()
    {
        return $this->fetch(self::LINK_INTEGRITY_NAME);
    }

    /**
     * @return TaskType
     */
    public function getHtmlValidationTaskType()
    {
        return $this->fetch(self::HTML_VALIDATION_NAME);
    }

    /**
     * @return TaskType
     */
    public function getCssValidationTaskType()
    {
        return $this->fetch(self::CSS_VALIDATION_NAME);
    }

    /**
     * @return TaskType
     */
    public function getUrlDiscoveryTaskType()
    {
        return $this->fetch(self::URL_DISCOVERY_NAME);
    }
}

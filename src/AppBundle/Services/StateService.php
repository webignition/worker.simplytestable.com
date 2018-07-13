<?php

namespace AppBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use AppBundle\Entity\State;

class StateService
{
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
        $this->entityRepository = $entityManager->getRepository(State::class);
    }

    /**
     * @param string $name
     *
     * @return State
     */
    public function fetch($name)
    {
        if (!$this->has($name)) {
            $this->create($name);
        }

        return $this->find($name);
    }

    /**
     * @param string $name
     *
     * @return State
     */
    public function find($name)
    {
        return $this->entityRepository->findOneBy([
            'name' => $name,
        ]);
    }

    /**
     * @param string $name
     * @return boolean
     */
    public function has($name)
    {
        return !is_null($this->find($name));
    }

    /**
     * @param string $name
     *
     * @return State
     */
    public function create($name)
    {
        $state = new State();
        $state->setName($name);

        $this->entityManager->persist($state);
        $this->entityManager->flush();

        return $state;
    }
}

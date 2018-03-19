<?php

namespace SimplyTestable\WorkerBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use SimplyTestable\WorkerBundle\Entity\TimeCachedTaskOutput;

class TimeCachedTaskOutputService
{
    const DEFAULT_MAX_AGE = 180;

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
        $this->entityRepository = $entityManager->getRepository(TimeCachedTaskOutput::class);
    }

    /**
     * @param string $hash
     *
     * @return TimeCachedTaskOutput
     */
    public function find($hash)
    {
        return $this->entityRepository->findOneBy([
            'hash' => $hash
        ]);
    }

    /**
     * @param string $hash
     *
     * @return bool
     */
    public function isStale($hash)
    {
        if (!$this->has($hash)) {
            return true;
        }

        $timeCachedTaskOutput = $this->find($hash);
        $now = new \DateTime();
        $currentAge = $now->format('U') - $timeCachedTaskOutput->getLastModified()->format('U');

        return $currentAge > $timeCachedTaskOutput->getMaxAge();
    }

    /**
     * @param string $hash
     *
     * @return bool
     */
    public function has($hash)
    {
        return !is_null($this->find($hash));
    }

    /**
     * @param string $hash
     * @param string $output
     * @param int $errorCount
     * @param int $warningCount
     * @param int $maxAge
     *
     * @return TimeCachedTaskOutput
     */
    public function set($hash, $output, $errorCount, $warningCount, $maxAge = null)
    {
        if (!$this->has($hash)) {
            return $this->create($hash, $output, $errorCount, $warningCount, $maxAge);
        }

        return $this->update($hash, $output, $errorCount, $warningCount, $maxAge = null);
    }


    /**
     * @param string $hash
     * @param string $output
     * @param int $errorCount
     * @param int $warningCount
     * @param int $maxAge
     *
     * @return TimeCachedTaskOutput
     */
    private function update($hash, $output, $errorCount, $warningCount, $maxAge = null)
    {
        $timeCachedTaskOutput = $this->find($hash);
        $timeCachedTaskOutput->setOutput($output);
        $timeCachedTaskOutput->setErrorCount($errorCount);
        $timeCachedTaskOutput->setWarningCount($warningCount);
        $timeCachedTaskOutput->setLastModified(new \DateTime());
        $timeCachedTaskOutput->setMaxAge(filter_var($maxAge, FILTER_VALIDATE_INT, array('options'=> array(
            'min_range' => 0,
            'default' => self::DEFAULT_MAX_AGE
        ))));

        $this->entityManager->persist($timeCachedTaskOutput);
        $this->entityManager->flush();

        return $timeCachedTaskOutput;
    }

    /**
     * @param string $hash
     * @param string $output
     * @param int $errorCount
     * @param int $warningCount
     * @param int $maxAge
     * @return TimeCachedTaskOutput
     */
    private function create($hash, $output, $errorCount, $warningCount, $maxAge = null)
    {
        $timeCachedTaskOutput = new TimeCachedTaskOutput();
        $timeCachedTaskOutput->setHash($hash);
        $timeCachedTaskOutput->setOutput($output);
        $timeCachedTaskOutput->setErrorCount($errorCount);
        $timeCachedTaskOutput->setWarningCount($warningCount);
        $timeCachedTaskOutput->setLastModified(new \DateTime());

        $timeCachedTaskOutput->setMaxAge(filter_var($maxAge, FILTER_VALIDATE_INT, array('options'=> array(
            'min_range' => 0,
            'default' => self::DEFAULT_MAX_AGE
        ))));

        $this->entityManager->persist($timeCachedTaskOutput);
        $this->entityManager->flush();

        return $timeCachedTaskOutput;
    }
}

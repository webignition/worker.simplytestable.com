<?php

namespace App\Repository;

use App\Entity\Task\Task;
use Doctrine\ORM\EntityRepository;

class TaskRepository extends EntityRepository
{
    /**
     * @param string $state
     *
     * @return int[]
     */
    public function getIdsByState(string $state): array
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('Task.id');
        $queryBuilder->where('Task.state = :State');
        $queryBuilder->setParameter('State', $state);

        $repostitoryResult = $queryBuilder->getQuery()->getResult();

        $taskIds = [];
        foreach ($repostitoryResult as $taskId) {
            $taskIds[] = $taskId['id'];
        }

        return $taskIds;
    }

    /**
     * @return int[]
     */
    public function getIdsWithOutput()
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->join('Task.output', 'TaskOutput');
        $queryBuilder->select('Task.id as TaskId');
        $queryBuilder->where('Task.output IS NOT NULL');

        $result = $queryBuilder->getQuery()->getResult();

        if (count($result) === 0) {
            return array();
        }

        $ids = array();

        foreach ($result as $taskOutputIdResult) {
            $ids[] = $taskOutputIdResult['TaskId'];
        }

        return $ids;
    }

    /**
     * @param \DateTime $startDateTime
     *
     * @return int[]
     */
    public function getUnfinishedIdsByMaxStartDate(\DateTime $startDateTime)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('Task.id as TaskId');
        $queryBuilder->where('Task.startDateTime <= :StartDateTime AND Task.endDateTime IS NULL');

        $queryBuilder->setParameter('StartDateTime', $startDateTime);

        $result = $queryBuilder->getQuery()->getResult();

        if (count($result) === 0) {
            return array();
        }

        $ids = array();

        foreach ($result as $taskOutputIdResult) {
            $ids[] = $taskOutputIdResult['TaskId'];
        }

        return $ids;
    }

    /**
     * @param array $states
     *
     * @return int
     */
    public function getCountByStates($states = [])
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('COUNT(Task.id)');

        if (count($states)) {
            $queryBuilder->andWhere('Task.state IN (:TaskStates)')
                ->setParameter('TaskStates', $states);
        }

        return (int)$queryBuilder->getQuery()->getResult()[0][1];
    }

    public function getTypeById(int $taskId): ?string
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('Task.type');
        $queryBuilder->where('Task.id = :Id');
        $queryBuilder->setParameter('Id', $taskId);

        $result = $queryBuilder->getQuery()->getResult();

        if (count($result) === 0) {
            return null;
        }

        return $result[0]['type'];
    }

    public function isSourceValueInUse(int $taskId, string $value): bool
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('Task.id, Task.sources');
        $queryBuilder->where('Task.sources IS NOT NULL AND Task.state IN (:States)');

        $queryBuilder->setParameter('States', [
            Task::STATE_PREPARED,
            Task::STATE_IN_PROGRESS,
        ]);

        $results = $queryBuilder->getQuery()->getResult();

        foreach ($results as $result) {
            $resultId = $result['id'];

            if ($taskId === $resultId) {
                continue;
            }

            $sources = $result['sources'];

            foreach ($sources as $source) {
                if ($value === $source['value']) {
                    return true;
                }
            }
        }

        return false;
    }
}

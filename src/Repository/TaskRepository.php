<?php
namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\State;

class TaskRepository extends EntityRepository
{
    /**
     * Get collection of task ids for tasks in given state
     *
     * @param State $state
     *
     * @return int[]
     */
    public function getIdsByState(State $state)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('Task.id');
        $queryBuilder->where('Task.state = :State');
        $queryBuilder->setParameter('State', $state);

        $repostitoryResult = $queryBuilder->getQuery()->getResult();

        $taskIds = array();
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
        $queryBuilder->join('Task.timePeriod', 'TimePeriod');
        $queryBuilder->select('Task.id as TaskId');
        $queryBuilder->where('TimePeriod.startDateTime <= :StartDateTime AND TimePeriod.endDateTime IS NULL');

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
}

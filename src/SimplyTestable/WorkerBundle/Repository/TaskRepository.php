<?php
namespace SimplyTestable\WorkerBundle\Repository;

use Doctrine\ORM\EntityRepository;
use SimplyTestable\WorkerBundle\Entity\State;

class TaskRepository extends EntityRepository
{
    
    /**
     * Get collection of task ids for tasks in given state
     * 
     * @param \SimplyTestable\WorkerBundle\Entity\State $state
     * @return array
     */
    public function getIdsByState(State $state) {
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
    
    
    public function getIdsWithOutput() {
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
    
    
    public function getUnfinishedIdsByMaxStartDate(\DateTime $startDateTime) {
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
     * @return int
     */
    public function getCount() {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('COUNT(Task.id)');

        return (int)$queryBuilder->getQuery()->getResult()[0][1];
    }

    
  
}

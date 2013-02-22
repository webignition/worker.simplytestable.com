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
    
  
}

<?php

namespace SimplyTestable\WorkerBundle\Tests\Controller;


class TaskControllerCreateTest extends TaskControllerTest {

    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }

    /**
     * @group standard
     */
    public function testCreateCollectionActionInNormalState() {
        $this->setActiveState();

        $taskData = array(
                array(
                    'url' => 'http://example.com/one/',
                    'type' => 'HTML validation'
                ),
                array(
                    'url' => 'http://example.com/two/',
                    'type' => 'CSS validation'
                ),
                array(
                    'url' => 'http://example.com/three/',
                    'type' => 'JS static analysis'
                ),
        );

        $postData = array(
            'tasks' => $taskData
        );

        $controller = $this->getTaskController('createCollectionAction', $postData);

        $response = $controller->createCollectionAction();
        $responseObject = json_decode($response->getContent());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(3, count($responseObject));

        foreach ($responseObject as $responseTaskIndex => $responseTask) {
            $this->assertInternalType('integer', $responseTask->id);
            $this->assertGreaterThan(0, $responseTask->id);
            $this->assertEquals($taskData[$responseTaskIndex]['url'], $responseTask->url);
            $this->assertEquals('queued', $responseTask->state);
            $this->assertEquals($taskData[$responseTaskIndex]['type'], $responseTask->type);
        }
    }

    /**
     * @group standard
     */
    public function testCreateCollectionActionInMaintenanceReadOnlyStateReturnsHttp503() {
        $this->setMaintenanceReadOnlyState();

        $response = $this->getTaskController('createCollectionAction', array(
            'tasks' => array(
                    array(
                        'url' => 'http://example.com/one/',
                        'type' => 'HTML validation'
                    ),
                    array(
                        'url' => 'http://example.com/two/',
                        'type' => 'CSS validation'
                    ),
                    array(
                        'url' => 'http://example.com/three/',
                        'type' => 'JS static analysis'
                    ),
            )
        ))->createCollectionAction();

        $this->assertEquals(503, $response->getStatusCode());
    }

    public function testCreateLinkIntegrityTaskCollectionAcceptsExludedUrlsParameter() {
        $parameters = array(
            'excluded-urls' => array(
                'http://example.com/'
            )
        );

        $response = $this->getTaskController('createCollectionAction', array(
            'tasks' => array(
                array(
                    'url' => 'http://example.com/foo bar/',
                    'type' => 'Link integrity',
                    'parameters' => json_encode($parameters)
                )
            )
        ))->createCollectionAction();

        $responseObject = json_decode($response->getContent());
        $task = $this->getTaskService()->getById($responseObject[0]->id);

        $this->assertEquals($parameters, json_decode($task->getParameters(), true));
    }

}



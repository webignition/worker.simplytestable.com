<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\UrlDiscovery;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\UrlDiscovery\TaskDriverTest;

class DefaultTest extends TaskDriverTest {

    /**
     * @group standard
     */
    public function testPerformOnValidUrl() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));

        $task = $this->getTask('http://example.com/', array(
            'scope' => 'http://example.com/'
        ));

        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals(array(
            "http://example.com/",
            "http://example.com/articles/",
            "http://example.com/articles/symfony-container-aware-migrations/",
            "http://example.com/articles/i-make-the-internet/",
            "http://example.com/articles/getting-to-building-simpytestable-dot-com/"
        ), json_decode($task->getOutput()->getOutput()));
    }


    /**
     * @group standard
     */
    public function testTreatWwwAndNonWwwAsEquivalent() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));

        $task = $this->getTask('http://example.com/', array(
            'scope' => array(
                'http://example.com/',
                'http://www.example.com/'
            )
        ));

        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals(23, count(json_decode($task->getOutput()->getOutput())));
    }

    /**
     * @group standard
     */
    public function testDiscoveredRelativeUrlsAreReportedInAbsoluteFormInOutput() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));

        $task = $this->getTask('http://example.com/', array(
            'scope' => 'http://example.com/'
        ));

        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals(array(
            'http://example.com/',
            'http://example.com/foo/contact.php',
            'http://example.com/register/',
        ), json_decode($task->getOutput()->getOutput()));
    }


    /**
     * @group standard
     */
    public function testDiscoveredUrlsAreOfCorrectAbsoluteForm() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));

        $task = $this->getTask('http://example.com/', array(
            'scope' => 'http://example.com/'
        ));

        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals(array(
            'http://example.com/foo/foo.html',
            'http://example.com/bar/',
            'http://example.com/foo/foo/bar/',
        ), json_decode($task->getOutput()->getOutput()));
    }


    /**
     * @group standard
     */
    public function testDiscoveredUrlsWithRelativeBaseHrefAreOfCorrectAbsoluteForm() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));

        $task = $this->getTask('http://example.com/', array(
            'scope' => array(
                'http://example.com/',
                'http://www.example.com/'
            )
        ));

        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals(array(
            'http://example.com/',
            'http://example.com/one.html',
            'http://example.com/two',
            'http://example.com/foo/bar.html',
        ), json_decode($task->getOutput()->getOutput()));
    }


    /**
     * @group standard
     */
    public function testDiscoverUrlsWithEquivalentSchemes() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));

        $task = $this->getTask('http://example.com/', array(
            'scope' => array(
                'http://example.com/',
                'http://www.example.com/'
            )
        ));

        $this->assertEquals(0, $this->getTaskService()->perform($task));

        $this->assertEquals(array(
            'https://example.com/',
            'https://example.com/contact/'
        ), json_decode($task->getOutput()->getOutput()));
    }

}

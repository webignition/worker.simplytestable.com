<?php

namespace SimplyTestable\WorkerBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest  extends BaseControllerJsonTestCase
{
    public function testIndex()
    {
        $this->setupDatabase();
        
        $client = static::createClient();

        $crawler = $client->request('GET', '/hello/Fabien');

        $this->assertTrue($crawler->filter('html:contains("Hello Fabien")')->count() > 0);
    }
}

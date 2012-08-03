<?php

namespace SimplyTestable\WorkerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class VerifyController extends Controller
{
    public function indexAction()
    {
        var_dump("cp01",  $this->container->getParameter('url'));
        exit();
    }
}

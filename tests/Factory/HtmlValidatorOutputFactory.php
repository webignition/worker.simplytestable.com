<?php

namespace App\Tests\Factory;

use webignition\HtmlValidator\Output\Body\Body as HtmlValidatorOutputBody;
use webignition\HtmlValidator\Output\Header\Header as HtmlValidatorOutputHeader;
use webignition\HtmlValidator\Output\Output as HtmlValidatorOutput;

class HtmlValidatorOutputFactory
{
    public static function create($status)
    {
        $header = new HtmlValidatorOutputHeader();
        $header->set('status', $status);

        $body = new HtmlValidatorOutputBody();
        $output = new HtmlValidatorOutput($header, $body);

        return $output;
    }
}

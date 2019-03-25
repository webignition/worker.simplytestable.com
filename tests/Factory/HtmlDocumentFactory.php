<?php

namespace App\Tests\Factory;

class HtmlDocumentFactory
{
    public static function load(string $name): string
    {
        return (string) file_get_contents(__DIR__ . '/../Fixtures/Data/HtmlDocuments/' . $name . '.html');
    }
}

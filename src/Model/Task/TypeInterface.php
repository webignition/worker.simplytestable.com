<?php

namespace App\Model\Task;

interface TypeInterface
{
    const TYPE_HTML_VALIDATION = 'html validation';
    const TYPE_CSS_VALIDATION = 'css validation';
    const TYPE_URL_DISCOVERY = 'url discovery';
    const TYPE_LINK_INTEGRITY = 'link integrity';

    public function getName(): string;
}

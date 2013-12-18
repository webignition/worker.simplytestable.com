#!/bin/sh
cd /home/travis/webignition/worker.simplytestable.com
sed -i '80s/.*/        $exception = $event['\''exception'\''] instanceof \Guzzle\Http\Exception\CurlException ? $event['\''exception'\''] : null;/' vendor/guzzle/guzzle/src/Guzzle/Plugin/Backoff/BackoffPlugin.php
cat vendor/guzzle/guzzle/src/Guzzle/Plugin/Backoff/BackoffPlugin.php
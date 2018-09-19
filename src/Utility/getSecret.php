<?php

namespace RtcTalker\Utility;

function getSecret($filename) {
    if(!file_exists($filename)) {
        throw new \RuntimeException('File with secret key: '+$filename+' has not been found');

    }
    return file_get_contents($filename);
}
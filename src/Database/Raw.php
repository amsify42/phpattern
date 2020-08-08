<?php

namespace PHPattern\Database;

class Raw
{
    private $content = '';

    function __construct($content)
    {
        $this->content = $content;
    }

    public function __toString()
    {
        return $this->content;
    }
}
<?php

namespace phpSmug\Exception;

class UnauthorizedException extends RuntimeException
{
    private $type;

    public function __construct($type, $code = 0, $previous = null)
    {
        $this->type = $type;
        parent::__construct('Unauthorized', $code, $previous);
    }

    public function getType()
    {
        return $this->type;
    }
}

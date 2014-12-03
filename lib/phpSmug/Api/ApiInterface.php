<?php

namespace phpSmug\Api;

/**
 * Api interface
 *
 * @author Joseph Bielawski <stloyd@gmail.com>
 */
interface ApiInterface
{

    public function getPerPage();

    public function setPerPage($perPage);
}

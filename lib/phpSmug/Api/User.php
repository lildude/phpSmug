<?php

namespace phpSmug\Api;

class User extends AbstractApi
{
    /**
     * Get extended information about a user by its username
     * @link http://developer.github.com/v3/users/
     *
     * @param  string $username the username to show
     * @return array  informations about the user
     */
    public function show($username)
    {
        return $this->get('user/'.rawurlencode($username));
    }
}
?>

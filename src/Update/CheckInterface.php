<?php

namespace Bolt\Update;

interface CheckInterface
{
    /**
     * The name of the check
     *
     * @return string
     */
    public function getName();

    /**
     * The type of the check
     *
     * @return string
     */
    public function getType();
}
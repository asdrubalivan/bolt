<?php

namespace Bolt\Update;

class CheckGit extends CheckAbstract implements CheckInterface
{
    /**
     * {@inheritdoc }
     */
    public function getName()
    {
        return 'standard';
    }

    /**
     * {@inheritdoc }
     */
    public function getType()
    {
        return 'standard';
    }
}
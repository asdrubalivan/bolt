<?php

namespace Bolt\Update;

class CheckComposer extends CheckAbstract implements CheckInterface
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
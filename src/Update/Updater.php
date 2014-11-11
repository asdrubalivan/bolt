<?php

namespace Bolt\Update;

use Bolt\Application;

/**
 * Bolt application update/clean-up
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Updater
{
    public function __construct(Application $app)
    {
        $this->app = $app;
    }
}
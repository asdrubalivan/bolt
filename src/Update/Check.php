<?php

namespace Bolt\Update;

use Bolt\Application;
use Guzzle\Http\Exception\RequestException;
use Guzzle\Service\Client;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class for Bolt core update checks
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Check
{
    /**
     * @var string
     */
    private $stability = 'stable';

    /**
     * @var array
     */
    private $version_local = null;

    /**
     * @var array
     */
    private $version_remote = null;

    /**
     * @var boolean
     */
    private $update_required = null;

    /**
     * @var \Guzzle\Service\Client
     */
    protected $client;

    /**
     * @param \Guzzle\Service\Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     *
     * @return string
     */
    public function getVersionLocal()
    {
        return $this->version_local;
    }

    /**
     * @return array|boolean
     */
    public function getVersionRemote()
    {
        return $this->version_remote;
    }

    /**
     * @return boolean
     */
    public function isUpdateRequired()
    {
        $this->setStability();

        if ($this->update_required === null) {
            $this->checkUpdateRequired();
        }

        return $this->update_required;
    }

    /**
     * Get our stability level
     */
    public function getStability()
    {
        return $this->stability;
    }

    /**
     * Set our stability level from config
     */
    private function setStability()
    {
        $stability = $this->app['config']->get('general/stability');

        if ($stability == 'dev') {
            $this->stability = 'dev';
        } else {
            $this->stability = 'stable';
        }
    }

    /**
     * Check Bolt HQ to see if we have an available update!
     *
     * @param  boolean      $skipcache
     * @param  string       $versionJson
     * @return boolean|NULL
     */
    private function checkUpdateRequired($skipcache = false, $versionJson = 'https://bolt.cm/distribution/version.json')
    {
        if ($this->getAvailableVersions()) {
            // Find our local version (pre-updates)
            $vercheck = $this->getLocalVersion();

            // If we get a null there is a problem reading the version file, just say no!
            if ($vercheck === null) {
                return $this->update_required = false;
            }

            // Local version wasn't found, we assume that the update is required
            if ($vercheck === false) {
                return $this->update_required = true;
            }

            // Check if were on the same stable patch version
            if ($this->stability == 'stable' && version_compare($this->version_local['version'], $this->version_remote[$this->stability]['version'], '<')) {
                return $this->update_required = true;
            }

            // Check if were on the same dev patch version
            if ($this->stability == 'dev' && version_compare($this->version_local['version'], $this->version_remote[$this->stability]['version'], '<=')) {
                // Check the 'name' version too
                if ($this->stability == 'dev' && $this->version_remote[$this->stability]['version'] > $this->version_local['name']) {
                    return $this->update_required = true;
                }
            }

            return $this->update_required = false;
        } else {
            $this->app['logger.system']->error('Error checking Bolt update site', array('event' => 'updater'));

            return $this->update_required = null;
        }
    }

    /**
     * Check the main Bolt site for our desired version
     *
     * @param  boolean $skipcache
     * @param  string  $versionJson
     * @return boolean
     */
    private function getAvailableVersions($skipcache, $versionJson)
    {

        $this->version_remote = $this->app['cache']->fetch('boltversion');

        if ($this->version_remote === false) {
            //
            $query = array(
                'bolt_ver'  => $this->app['bolt_version'],
                'bolt_name' => $this->app['bolt_name'],
                'php'       => phpversion(),
                'www'       => $_SERVER['SERVER_SOFTWARE']
            );

            try {
                $response = $this->client->get($versionJson, null, array('query' => $query, 'timeout' => 10))->send();

                if ($response->getStatusCode() == Response::HTTP_OK) {
                    $this->version_remote = $response->json();

                    // Cache it for 24 hours
                    $this->app['cache']->save('boltversion', $this->version_remote, 86400);

                    // Log it!
                    $this->app['logger.system']->info('Downloaded new version update data from Bolt HQ', array('event' => 'updater'));

                    return true;
                } else {
                    $this->app['logger.system']->error('Error checking Bolt update site. Return code: ' . $response->getStatusCode(), array('event' => 'updater'));

                    return false;
                }
            } catch (RequestException $e) {
                $this->app['logger.system']->error('Error checking Bolt update site. Error message: ' . $e->getMessage(0), array('event' => 'updater'));

                return false;
            }
        }
    }

    /**
     * Get the local version that was last checked/updated against
     *
     * @return string|NULL|boolean
     */
    private function getLocalVersion()
    {
        $fs = new Filesystem();
        $file = $this->app['resources']->getPath('root') . '/.boltversion';

        if ($fs->exists($file) && is_readable($file)) {
            try {
                $this->version_local = json_decode(file_get_contents($file), true);

                return true;
            } catch (IOExceptionInterface $e) {
                $this->app['logger.system']->error("Error checking Bolt version file: $file", array('event' => 'updater'));

                return null;
            }
        }

        return false;
    }
}

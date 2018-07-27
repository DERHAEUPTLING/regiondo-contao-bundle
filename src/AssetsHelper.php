<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle;

class AssetsHelper
{
    /**
     * @var string
     */
    private $bundleDir;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var bool
     */
    private $useSources;

    /**
     * AssetsHelper constructor.
     *
     * @param string $bundleDir
     * @param string $rootDir
     * @param bool   $useSources
     */
    public function __construct(string $bundleDir, string $rootDir, bool $useSources)
    {
        $this->bundleDir = $bundleDir;
        $this->rootDir = $rootDir;
        $this->useSources = $useSources;
    }

    /**
     * Get the include path with fingerprint.
     *
     * @param string $file
     *
     * @return null|string
     */
    public function getIncludePath(string $file): ?string
    {
        // Use the minified file if available
        if (!$this->useSources) {
            $pathinfo = \pathinfo($file);
            $minifiedFile = \sprintf('%s.min.%s', $pathinfo['filename'], $pathinfo['extension']);

            if (\is_file($this->rootDir.'/web/'.$this->bundleDir.'/'.$minifiedFile)) {
                $file = $minifiedFile;
            }
        }

        $relativePath = $this->bundleDir.'/'.$file;
        $absolutePath = $this->rootDir.'/web/'.$relativePath;

        if (!\is_file($absolutePath)) {
            return null;
        }

        return \sprintf('%s?v=%s', $relativePath, \filemtime($absolutePath));
    }
}

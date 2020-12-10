<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento;

use Composer\IO\IOInterface;
use Composer\Composer;
use Composer\Installer\LibraryInstaller;
use Composer\Installer\InstallerInterface;
use Composer\Package\PackageInterface;

/**
 * Composer Magento Installer
 */
class Installer extends LibraryInstaller implements InstallerInterface
{
    /**
     * The base directory of the magento installation
     *
     * @var \SplFileInfo
     */
    protected $magentoRootDir = null;

    /**
     * The default base directory of the magento installation
     *
     * @var \SplFileInfo
     */
    protected $defaultMagentoRootDir = './';

    /**
     * The base directory of the modman packages
     *
     * @var \SplFileInfo
     */
    protected $modmanRootDir = null;

    /**
     * If set overrides existing files
     *
     * @var bool
     */
    protected $isForced = false;


    protected $originalMagentoRootDir = null;
    protected $backupMagentoRootDir = null;


    /**
     * @var ProjectConfig
     */
    protected $config;



    /**
     * Initializes Magento Module installer
     *
     * @param IOInterface $io
     * @param Composer $composer
     * @param string $type
     * @throws \ErrorException
     */
    public function __construct(IOInterface $io, Composer $composer, $type = 'magento-module')
    {
        parent::__construct($io, $composer, $type);
        $this->initializeVendorDir();

        $extra = $composer->getPackage()->getExtra();

        if (isset($extra['magento-root-dir']) || $rootDirInput = $this->defaultMagentoRootDir) {

            if (isset($rootDirInput)) {
                $extra['magento-root-dir'] = $rootDirInput;
            }

            $dir = rtrim(trim($extra['magento-root-dir']), '/\\');
            $this->magentoRootDir = new \SplFileInfo($dir);
            if (!is_dir($dir) && $io->askConfirmation('magento root dir "' . $dir . '" missing! create now? [Y,n] ')) {
                $this->initializeMagentoRootDir($dir);
            }
            if (!is_dir($dir)) {
                $dir = $this->vendorDir . "/$dir";
                $this->magentoRootDir = new \SplFileInfo($dir);
            }
        }

    }

    /**
     * Create base requrements for project installation
     */
    protected function initializeMagentoRootDir() {
        if (!$this->magentoRootDir->isDir()) {
            $magentoRootPath = $this->magentoRootDir->getPathname();
            $pathParts = explode(DIRECTORY_SEPARATOR, $magentoRootPath);
            $baseDir = explode(DIRECTORY_SEPARATOR, $this->vendorDir);
            array_pop($baseDir);
            $pathParts = array_merge($baseDir, $pathParts);
            $directoryPath = '';
            foreach ($pathParts as $pathPart) {
                $directoryPath .=  $pathPart . DIRECTORY_SEPARATOR;
                $this->filesystem->ensureDirectoryExists($directoryPath);
            }
        }
    }


    /**
     * Return Source dir of package
     *
     * @param \Composer\Package\PackageInterface $package
     * @return string
     */
    protected function getSourceDir(PackageInterface $package)
    {
        $this->filesystem->ensureDirectoryExists($this->vendorDir);
        return $this->getInstallPath($package);
    }

    /**
     * Return the absolute target directory path for package installation
     *
     * @return string
     */
    public function getTargetDir()
    {
        $targetDir = realpath($this->magentoRootDir->getPathname());
        return $targetDir;
    }


    /**
     * set permissions recursively
     *
     * @param string $path Path to set permissions for
     * @param int $dirmode Permissions to be set for directories
     * @param int $filemode Permissions to be set for files
     */
    protected function setPermissions($path, $dirmode, $filemode) {
        if (is_dir($path)) {
            if (!@chmod($path, $dirmode)) {
                $this->io->write(
                        'Failed to set permissions "%s" for directory "%s"', decoct($dirmode), $path
                );
            }
            $dh = opendir($path);
            while (($file = readdir($dh)) !== false) {
                if ($file != '.' && $file != '..') { // skip self and parent pointing directories
                    $fullpath = $path . '/' . $file;
                    $this->setPermissions($fullpath, $dirmode, $filemode);
                }
            }
            closedir($dh);
        } elseif (is_file($path)) {
            if (false == !@chmod($path, $filemode)) {
                $this->io->write(
                        'Failed to set permissions "%s" for file "%s"', decoct($filemode), $path
                );
            }
        }
    }


    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {

        if (!is_null($this->modmanRootDir) && true === $this->modmanRootDir->isDir()) {
            $targetDir = $package->getTargetDir();
            if (!$targetDir) {
                list($vendor, $targetDir) = explode('/', $package->getPrettyName());
            }
            $installPath = $this->modmanRootDir . '/' . $targetDir;
        } else {
            $installPath = parent::getInstallPath($package);
        }

        // Make install path absolute. This is needed in the symlink deploy strategies.
        if (DIRECTORY_SEPARATOR !== $installPath[0] && $installPath[1] !== ':') {
            $installPath = getcwd() . "/$installPath";
        }

        return $installPath;
    }
    

}

<?php
/**
 *
 *
 *
 *
 */

namespace MagentoHackathon\Composer\Magento;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;

class Plugin implements PluginInterface, EventSubscriberInterface
{

    /**
     * @var IOInterface
     */
    protected $io;


    /**
     * @var Composer
     */
    protected $composer;


    /**
     * @var Filesystem
     */
    protected $filesystem;


    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io = $io;
        $this->composer = $composer;
        $this->filesystem = new Filesystem();
    }

    public static function getSubscribedEvents()
    {
        return array(
            ScriptEvents::POST_INSTALL_CMD => array(
                array('onNewCodeEvent', 0),
            ),
            ScriptEvents::POST_UPDATE_CMD => array(
                array('onNewCodeEvent', 0),
            ),
        );
    }

    /**
     * event listener is named this way, as it listens for events leading to changed code files
     *
     * @param Event $event
     */
    public function onNewCodeEvent(Event $event)
    {
        $this->saveVendorDirPath($event->getComposer());
    }

    /**
     * Generate file with path to Composer 'vendor' dir to be used by the application
     *
     * @param \Composer\Composer $composer
     * @throws \UnexpectedValueException
     */
    private function saveVendorDirPath(Composer $composer)
    {
        $magentoDir = './';
        $vendorDirPath = $this->filesystem->findShortestPath(
            $magentoDir,
            realpath($composer->getConfig()->get('vendor-dir')),
            true
        );
        $vendorPathFile = $magentoDir . '/app/etc/vendor_path.php';
        $content = <<<AUTOLOAD
<?php
/**
 * Path to Composer vendor directory
 */
            PluginEvents::COMMAND => array(
                array('onCommandEvent', 0),
            ),
return '$vendorDirPath';

AUTOLOAD;
        file_put_contents($vendorPathFile, $content);
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
    }
}

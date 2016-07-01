<?php
/**
 * This file is part of Zource. (https://github.com/zource/)
 *
 * @link https://github.com/zource/zource for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zource. (https://github.com/zource/)
 * @license https://raw.githubusercontent.com/zource/zource/master/LICENSE MIT
 */

namespace ZourceApplication\TaskService;

use Doctrine\ORM\EntityManager;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Zend\Http\Request;
use Zend\Math\Rand;
use ZipArchive;
use ZourceApplication\Entity\Plugin;

class PluginManager
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getPluginByName($name)
    {
        $repository = $this->entityManager->getRepository(Plugin::class);

        return $repository->findOneBy([
            'name' => $name,
        ]);
    }

    public function activatePlugin(Plugin $plugin)
    {
        $plugin->setActive(true);

        $this->entityManager->flush($plugin);

        $this->updateAutoloader();
    }

    public function deactivatePlugin(Plugin $plugin)
    {
        $plugin->setActive(false);

        $this->entityManager->flush($plugin);

        $this->updateAutoloader();
    }

    public function getPlugin($id)
    {
        $repository = $this->entityManager->getRepository(Plugin::class);

        return $repository->find($id);
    }

    public function getPlugins()
    {
        $repository = $this->entityManager->getRepository(Plugin::class);

        return $repository->findAll();
    }

    public function installExternal($path)
    {
        if (is_dir($path)) {
            $this->installDirectory($path);
        } else {
            // Download the file
            $this->downloadFile($path);
        }
    }

    public function installFile($file)
    {
        $zip = new ZipArchive();

        if ($zip->open($file) !== true) {
            unlink($file);
            throw new RuntimeException('Failed to extract the plugin ' . $file);
        }

        $pluginInfoContent = $zip->getFromName('zource-plugin.json');
        if ($pluginInfoContent === false) {
            unlink($file);
            throw new RuntimeException('Invalid plugin provided, missing the file zource-plugin.json');
        }

        $pluginInfo = $this->createPluginInfo($pluginInfoContent);
        $pluginDirectory = 'data/plugins/' . $pluginInfo['name'];

        $zip->extractTo($pluginDirectory);
        $zip->close();

        return $this->installDirectory($pluginDirectory);
    }

    private function installDirectory($path)
    {
        $pluginJsonFile = $path . '/zource-plugin.json';
        if (!is_file($pluginJsonFile)) {
            throw new RuntimeException('The plugin is invalid, missing plugin.json');
        }

        $pluginJsonFileContent = file_get_contents($pluginJsonFile);
        $pluginJsonFileData = $this->createPluginInfo($pluginJsonFileContent);

        $plugin = $this->getPluginByName($pluginJsonFileData['name']);
        if ($plugin) {
            return;
        }

        $plugin = new Plugin($pluginJsonFileData['name'], (array)$pluginJsonFileData['namespaces']);
        $plugin->setActive(true);

        if (array_key_exists('description', $pluginJsonFileData)) {
            $plugin->setDescription($pluginJsonFileData['description']);
        }

        $this->entityManager->persist($plugin);
        $this->entityManager->flush($plugin);

        $this->updateAutoloader();
    }

    public function uninstall(Plugin $plugin)
    {
        $this->entityManager->remove($plugin);
        $this->entityManager->flush($plugin);

        $this->cleanUpDirectory('data/plugins/' . $plugin->getName());

        $this->updateAutoloader();
    }

    private function updateAutoloader()
    {
        $pluginRepository = $this->entityManager->getRepository(Plugin::class);

        $content = "<?php\n\n";
        $content .= "// This file is automatically generated by Zource\n";
        $content .= sprintf("// Generated on %s\n\n", date('r'));
        $content .= "return [\n";

        foreach ($pluginRepository->findBy(['active' => true]) as $plugin) {
            $pluginPath = 'data/plugins/' . $plugin->getName();

            foreach ($plugin->getNamespaces() as $namespaceName => $namespacePath) {
                $content .= sprintf(
                    "\t'%s' => '%s',\n",
                    addslashes($namespaceName),
                    $pluginPath . '/' . ltrim($namespacePath, '/')
                );
            }
        }

        $content .= "];\n";

        file_put_contents('data/plugins/autoloader.php', $content);
    }

    private function downloadFile($url)
    {
        $request = new Request();
        $request->setUri($url);

        $pluginPath = sprintf('data/tmp/plugin-%s.zip', Rand::getString(8, implode('', range('a', 'z'))));

        $client = new \Zend\Http\Client($url, [
            'keepalive' => true,
            'outputstream' => $pluginPath,
        ]);

        $response = $client->send($request);

        if (!$response->isOk()) {
            throw new RuntimeException('Failed to download the plugin from ' . $url);
        }

        return $this->installFile($pluginPath);
    }

    private function createPluginInfo($content)
    {
        $data = json_decode($content, true);

        if (!$data) {
            throw new RuntimeException('The plugin is invalid, invalid JSON found in plugin.json.');
        }

        if (!array_key_exists('name', $data)) {
            throw new RuntimeException('Missing the name of the plugin.');
        }

        if (preg_match('/[a-z0-9-]+\/[a-z0-9-]+/', $data['name']) === 0) {
            throw new RuntimeException('The plugin name is invalid.');
        }

        if (!array_key_exists('namespaces', $data)) {
            throw new RuntimeException('The namespaces of the plugin are not configured.');
        }

        return $data;
    }

    private function cleanUpDirectory($dirPath)
    {
        if (!is_dir($dirPath)) {
            throw new InvalidArgumentException($dirPath . " must be a directory");
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirPath),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        /** @var \SplFileInfo $entry */
        foreach ($iterator as $entry) {
            if ($entry->getFilename() === '.' || $entry->getFilename() === '..') {
                continue;
            } elseif ($entry->isFile()) {
                unlink($entry->getPathname());
            } elseif ($entry->isDir()) {
                rmdir($entry->getPathname());
            }
        }

        rmdir($dirPath);
    }
}

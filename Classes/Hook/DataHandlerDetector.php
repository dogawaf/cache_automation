<?php
namespace Pluswerk\CacheAutomation\Hook;

/***
 * This file is part of an +Pluswerk AG Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2017 Markus Hölzle <markus.hoelzle@pluswerk.ag>, +Pluswerk AG
 ***/

use Pluswerk\CacheAutomation\Agents\AgentInterface;
use Pluswerk\CacheAutomation\Service\Configuration;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Service\CacheService;

/**
 * Class DataHandlerDetector
 *
 * @author Markus Hölzle <markus.hoelzle@pluswerk.ag>
 * @package Pluswerk\CacheAutomation\Hook
 */
class DataHandlerDetector implements SingletonInterface
{

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var CacheService
     */
    protected $cacheService;

    /**
     * DataHandlerHook constructor.
     */
    public function __construct()
    {
        $this->configuration = Configuration::getInstance();
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->cacheService = $objectManager->get(CacheService::class);
    }

    /**
     * @param string $status
     * @param string $table
     * @param int $id
     * @param array $changedFields
     * @param DataHandler $dataHandler
     * @throws \Exception
     */
    public function processDatamap_afterDatabaseOperations($status, string $table, int $id, array $changedFields, DataHandler $dataHandler)
    {
        $expiredPages = [];
        if ($this->configuration->isConfigured($table)) {
            $agentConfigurations = $this->configuration->getAgentsForTable($table);
            foreach ($agentConfigurations as $agentConfiguration) {
                /** @var AgentInterface $agent */
                $agent = GeneralUtility::makeInstance($agentConfiguration['agent']);
                if ($agent instanceof AgentInterface) {
                    $expiredPages = $agent->getExpiredPages($table, $id, $agentConfiguration['agentConfiguration'], $changedFields);
                } else {
                    throw new \Exception('Agent "' . $agentConfiguration['agent'] . '" must implement \Pluswerk\CacheAutomation\Agent\AgentInterface', 1500979398);
                }
            }
        }

        if (count($expiredPages) !== 0) {
            $this->cacheService->clearPageCache(array_unique($expiredPages));
        }
    }
}
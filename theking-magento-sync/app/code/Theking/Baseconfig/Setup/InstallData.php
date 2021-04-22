<?php
/**
* Copyright © 2016 TuanHatay. All rights reserved.
*/

namespace Theking\Baseconfig\Setup;


use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Store\Model\ResourceModel\Group as GroupResourceModel;
use Magento\Store\Model\ResourceModel\Store as StoreResourceModel;
use Magento\Store\Model\ResourceModel\Website as WebsiteResourceModel;
use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\WebsiteFactory;
use Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\Registry;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
 
    /**
     * UpgradeData constructor.
     * @param Installer $installer
     * @param ManagerInterface $eventManager
     * @param WebsiteResourceModel $websiteModel
     * @param GroupResourceModel $groupModel
     * @param StoreResourceModel $storeModel
     * @param WebsiteFactory $websiteFactory
     * @param GroupFactory $groupFactory
     * @param StoreFactory $storeFactory
     */
    public function __construct(
        ManagerInterface $eventManager,
        WebsiteResourceModel $websiteModel,
        GroupResourceModel $groupModel,
        StoreResourceModel $storeModel,
        WebsiteFactory $websiteFactory,
        GroupFactory $groupFactory,
        StoreFactory $storeFactory,
        StoreManagerInterface $storeManager,
        Registry $registry
        
    ) {
        $this->eventManager = $eventManager;
        $this->websiteResourceModel = $websiteModel;
        $this->groupResourceModel = $groupModel;
        $this->storeResourceModel = $storeModel;
        $this->websiteFactory = $websiteFactory;
        $this->groupFactory = $groupFactory;
        $this->storeFactory = $storeFactory;
        $this->storeManager = $storeManager;
        $this->registry = $registry;
    }
    
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $this->registry->register('isSecureArea', true);

        $this->installStores();
        $this->deleteDefaultStores();
        
        $installer->endSetup();
    }
    
    protected function installStores()
    {
        
        /** @var Magento\Store\Model\Website $website */
        $website = $this->websiteFactory->create();
        $website->setCode('theking');
        $website->setName('The King Website');
        $website->setIsDefault(true);
        // Set an existing store group id
        $this->websiteResourceModel->save($website);
        
        /** @var \Magento\Store\Model\Group $group */
        $group = $this->groupFactory->create();
        $group->setWebsiteId($website->getWebsiteId());
        $group->setCode('theking');
        $group->setName('The King store');
        $group->setRootCategoryId(2);
        $this->groupResourceModel->save($group);
        
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeFactory->create();
        $store->setCode('es');
        $store->setName('ES');
        $store->setWebsite($website);
        $store->setGroupId($group->getId());
        $store->setData('is_active', '1');
        $this->storeResourceModel->save($store);
        $this->eventManager->dispatch('store_add', ['store' => $store]);
        
        ///** @var \Magento\Store\Model\Store $store */
        $store2 = $this->storeFactory->create();
        $store2->setCode('en');
        $store2->setName('EN');
        $store2->setWebsite($website);
        $store2->setGroupId($group->getId());
        $store2->setData('is_active', '1');
        $this->storeResourceModel->save($store2);
        $this->eventManager->dispatch('store_add', ['store' => $store2]);
        
        // change the default group id for the website
        $website->setDefaultGroupId($group->getId());
        $this->websiteResourceModel->save($website);
        
        // change the default store id for the store group
        $group->setDefaultStoreId($store->getId());
        $this->groupResourceModel->save($group);
    }
    
       
    protected function deleteDefaultStores()
    {
        $storeList = $this->storeManager->getStores();
        foreach ($storeList as $store) {
            if ($store->getCode() == 'default') {
                $store->delete();
            }
        }
        $storeGroupList = $this->storeManager->getGroups();
        foreach ($storeGroupList as $storeGroup) {
            if ($storeGroup->getCode() == 'main_website_store') {
                $storeGroup->delete();
            }
        }
        $websiteList = $this->storeManager->getWebsites();
        foreach ($websiteList as $website) {
            if ($website->getCode() == 'base') {
                $website->delete();
            }
        }
    }
}
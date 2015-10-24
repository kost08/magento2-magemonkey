<?php
/**
 * Author: info@ebizmarts.com
 * Date: 3/4/15
 * Time: 12:59 AM
 * File: Setup.php
 * Module: magento2
 */

namespace Ebizmarts\MageMonkey\Model\Resource;

class Setup  extends \Magento\Framework\Module\Setup
{

    /**
     * @param \Magento\Framework\App\Resource $resource
     * @param string $resourceName
     * @param string $moduleName
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\App\Resource $resource,
        $resourceName,
        $moduleName = 'Ebizmarts_MageMonkey',
        $connectionName = \Magento\Framework\Setup\ModuleDataSetupInterface::DEFAULT_SETUP_CONNECTION
    ) {
        parent::__construct($resource, $connectionName);
    }
}
<?php
/**
 * Author: info@ebizmarts.com
 * Date: 3/2/15
 * Time: 5:59 PM
 * File: Subscriber.php
 * Module: magento2
 */

namespace Ebizmarts\MageMonkey\Model\Plugin;


class Subscriber
{
    /**
     * @var \Ebizmarts\MageMonkey\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $_customer;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    /**
     * @var \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory
     */
    private $subscriberCollectionFactory;

    /**
     * @param \Ebizmarts\MageMonkey\Helper\Data $helper
     * @param \Magento\Customer\Model\Customer $customer
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory|\Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollectionFactory
     */
    public function __construct(
        \Ebizmarts\MageMonkey\Helper\Data $helper,
        \Magento\Customer\Model\Customer $customer,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollectionFactory
    )
    {
        $this->_helper          = $helper;
        $this->_customer        = $customer;
        $this->_customerSession = $customerSession;
        $this->subscriberCollectionFactory = $subscriberCollectionFactory;
    }

    public function afterUnsubscribeCustomerById(
        $subscriber
    )
    {
        if($subscriber->getMagemonkeyId())
        {
            $api = New \Ebizmarts\MageMonkey\Model\Api(array(),$this->_helper);
            $return = $api->listDeleteMember($this->_helper->getDefaultList(),$subscriber->getMagemonkeyId());
            $subscriber->setMagemonkeyId('')->save();
        }
    }

    public function afterSubscribeCustomerById(
        $subscriber
    )
    {
        $storeId = $subscriber->getStoreId();
        if($this->_helper->isMonkeyEnabled($storeId)) {
            $customer = $this->_customer;
            $mergeVars = $this->_helper->getMergeVars($customer);
            $api = New \Ebizmarts\MageMonkey\Model\Api(array(), $this->_helper);
            $isSubscribeOwnEmail = $this->_customerSession->isLoggedIn()
                && $this->_customerSession->getCustomerDataObject()->getEmail() == $subscriber->getSubscriberEmail();
            if($this->_helper->isDoubleOptInEnabled($storeId) && !$isSubscribeOwnEmail) {
                $status = 'pending';
            }else{
                $status = 'subscribed';
            }
            $data = array('list_id' => $this->_helper->getDefaultList(), 'email_address' => $subscriber->getEmail(), 'email_type' => 'html', 'status' => $status, /*'merge_fields' => $mergeVars*/);

            $subscriberCollection = $this->subscriberCollectionFactory->create();
            $collection = $subscriberCollection->load()
                ->useOnlySubscribed()
                ->addFieldToFilter("subscriber_email", array('eq' => $subscriber->getEmail()));

            if(!$collection->count()){
                $return = $api->listCreateMember($this->_helper->getDefaultList(), json_encode($data));
                if (isset($return->id)) {
                    $subscriber->setMagemonkeyId($return->id)->save();
                }
            }
        }
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param \Closure $proceed
     * @return mixed
     */
    public function aroundUnsubscribe(
        \Magento\Newsletter\Model\Subscriber $subscriber,
        \Closure $proceed
    )
    {
        $monkeyId = $subscriber->getMagemonkeyId();
        $result = $proceed();
        if($result::STATUS_UNSUBSCRIBED == $result->getStatus() && $monkeyId);
        {
            $api = New \Ebizmarts\MageMonkey\Model\Api(array(),$this->_helper);
            $return = $api->listDeleteMember($this->_helper->getDefaultList(), $monkeyId);
            $result->setMagemonkeyId('')->save();
        }
        return $result;
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param \Closure $proceed
     * @param $email
     * @return mixed
     */
    public function aroundSubscribe
    (
        \Magento\Newsletter\Model\Subscriber $subscriber,
        \Closure $proceed,
        $email
    )
    {
        $result = $proceed($email);
        $storeId = $subscriber->getStoreId();
        if($this->_helper->isMonkeyEnabled($storeId)) {
            $api = New \Ebizmarts\MageMonkey\Model\Api(array(), $this->_helper);
            $data = array('list_id' => $this->_helper->getDefaultList(), 'email_address' => $email, 'email_type' => 'html', 'status' => 'subscribed');
            $return = $api->listCreateMember($this->_helper->getDefaultList(), json_encode($data));
            if (isset($return->id)) {
                $subscriber->setMagemonkeyId($return->id)->save();
            }
        }

        return $result;
    }

    public function aroundConfirm
    (
        \Magento\Newsletter\Model\Subscriber $subscriber,
        \Closure $proceed,
        $code
    )
    {
        $result = $proceed($code);
        if($result)
        {
            $storeId = $subscriber->getStoreId();
            if($this->_helper->isMonkeyEnabled($storeId)) {
                $api = New \Ebizmarts\MageMonkey\Model\Api(array(), $this->_helper);
                $data = array('list_id' => $this->_helper->getDefaultList(), 'email_address' => $subscriber->getEmail(), 'email_type' => 'html', 'status' => 'subscribed');
                $return = $api->listCreateMember($this->_helper->getDefaultList(), json_encode($data));
                if (isset($return->id)) {
                    $subscriber->setMagemonkeyId($return->id)->save();
                }
            }
        }
        return $result;
    }

    public function aroundDelete(
        \Magento\Newsletter\Model\Subscriber $subscriber,
        \Closure $proceed
    )
    {
        $monkeyId = $subscriber->getMagemonkeyId();
        $result = $proceed();
        if($monkeyId)
        {
            $api = New \Ebizmarts\MageMonkey\Model\Api(array(),$this->_helper);
            $return = $api->listDeleteMember($this->_helper->getDefaultList(), $monkeyId);
        }
        return $result;
    }
}
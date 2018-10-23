<?php

class MageProfis_EmailQueue_Model_Observer
{
    const REGISTER_KEY = 'email_queue_id';

    /**
     * @param Varien_Event_Observer $event
     */
    public function beforeLoadEmailQueueCollection(Varien_Event_Observer $event)
    {
        $queue_id = Mage::registry(self::REGISTER_KEY);
        if (!is_null($queue_id) && (int) $queue_id > 0)
        {
            $collection = $event->getCollection();
            /* @var $collection Mage_Core_Model_Resource_Email_Queue_Collection */
            if ($collection instanceof Mage_Core_Model_Resource_Email_Queue_Collection)
            {
                $collection->addFieldToFilter('message_id', (int) $queue_id);
            }
        }
    }

    /**
     * send emails if the original method of magento
     * does not send the e-mail
     * you can found the issue in the class
     * Mage_Core_Model_Resource_Email_Queue_Collection::send
     * and look in to the Exception Method, you will find an "return false"
     * 
     * @return void
     */
    public function run()
    {
        $limit = Mage_Core_Model_Email_Queue::MESSAGES_LIMIT_PER_CRON_RUN * 2;
        $collection = Mage::getModel('core/email_queue')->getCollection()
            ->addOnlyForSendingFilter()
            ->setPageSize($limit)
            ->setCurPage(1)
            ->addOrder('message_id', 'ASC')
        ;
        /* @var $collection Mage_Core_Model_Resource_Email_Queue_Collection */
        foreach ($collection->getAllIds() as $id)
        {
            $this->_register($id);
            try {
                Mage::getModel('core/email_queue')->send();
            } catch (Exception $e) {
                Mage::logException($e);
            }
            $this->_unregister();
        }
    }

    /**
     * @param int $id
     * @return void
     */
    protected function _register($id)
    {
        $this->_unregister();
        Mage::register(self::REGISTER_KEY, $id);
    }

    /**
     * @return void
     */
    protected function _unregister()
    {
        try {
                Mage::unregister(self::REGISTER_KEY);
        } catch (Exception $e)
        { /* we dont need */ }
    }

    /**
     * Remove all Old unsended E-Mails,
     * so when there is one E-Mail with an issue
     * and magento will send all e-mail normaly
     */
    public function removeOldEMails()
    {
        $oldDate = date('Y-m-d H:i:s', strtotime('-2 days'));
        $collection = Mage::getModel('core/email_queue')->getCollection()
            ->addOnlyForSendingFilter()
            ->addFieldToFilter('created_at', array('lteq' => $oldDate))
            ->addOrder('message_id', 'ASC')
        ;
        /* @var $collection Mage_Core_Model_Resource_Email_Queue_Collection */
        foreach ($collection as $_queue)
        {
            /* @var $_queue Mage_Core_Model_Email_Queue */
            $_queue->delete();
        }
    }
}

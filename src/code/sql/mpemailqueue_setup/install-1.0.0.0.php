<?php
$installer = $this;

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

/**
 * this installer delete only old entries,
 * so we encountered some issues with not sended e-mails
 * and we hopfully fixed this with this modul
 */

$olddate = date('Y-m-d H:i:s', strtotime('-2 days'));
$where = $installer->getConnection()->quoteInto('created_at <= ?', $olddate);
$installer->getConnection()->delete($installer->getTable('core/email_queue'), $where);

$installer->endSetup();

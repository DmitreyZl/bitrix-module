<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Request\Loyalty\Account
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api\Request\Loyalty\Account;

use Intaro\RetailCrm\Component\Json\Mapping;
use Intaro\RetailCrm\Model\Api\AbstractApiModel;
use Intaro\RetailCrm\Model\Api\LoyaltyAccount;

/**
 * Class LoyaltyAccountEditRequest
 *
 * @package Intaro\RetailCrm\Model\Api\Request\Loyalty\Account
 */
class LoyaltyAccountEditRequest extends AbstractApiModel
{
    /**
     * @var \Intaro\RetailCrm\Model\Api\LoyaltyAccount
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\LoyaltyAccount")
     * @Mapping\SerializedName("loyalty_account")
     */
    private $loyaltyAccount;
    
    /**
     * @return \Intaro\RetailCrm\Model\Api\LoyaltyAccount
     */
    public function getLoyaltyAccount(): LoyaltyAccount
    {
        return $this->loyaltyAccount;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\LoyaltyAccount $loyaltyAccount
     */
    public function setLoyaltyAccount(LoyaltyAccount $loyaltyAccount): void
    {
        $this->loyaltyAccount = $loyaltyAccount;
    }
}

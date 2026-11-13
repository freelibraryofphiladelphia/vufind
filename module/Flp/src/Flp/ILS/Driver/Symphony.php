<?php

namespace Flp\ILS\Driver;

class Symphony extends \VuFind\ILS\Driver\Symphony
{
    public function getMyTransactions($patron)
    {
        try {
            $transList = array();
            $options = array('includePatronCheckoutInfo' => 'ALLEXCEPTCLAIMEDRETURNED');

            $result = $this->makeRequest(
                'patron',
                'lookupMyAccountInfo',
                $options,
                array(
                    'login' => $patron['cat_username'],
                    'password' => $patron['cat_password']
                )
            );

            if (isset($result->patronCheckoutInfo)) {
                $transactions = $result->patronCheckoutInfo;
                $transactions = !is_array($transactions) ? array($transactions) : $transactions;

                foreach ($transactions as $transaction) {
                    if (isset($transaction->renewalsRemaining) and $transaction->renewalsRemaining > 0) {
                        $renewable = true;
                    } else {
                        $renewable = false;
                    }

                    $transList[] = [
                        'duedate' => isset($transaction->dueDate) ? date('F j, Y', strtotime($transaction->dueDate)) : null,
                        'id' => $transaction->titleKey,
                        'barcode' => isset($transaction->itemID) ? $transaction->itemID : null,
                        'renew' => $transaction->renewals,
                        'lastRenewDate' => isset($transaction->lastRenewedDate) ? date('F j, Y', strtotime($transaction->lastRenewedDate)) : null,
                        'renewalsRemaining' => isset($transaction->renewalsRemaining) ? $transaction->renewalsRemaining : null,
                        'request' => $transaction->recallNoticesSent,
                        'renewable' => $renewable,
                        'title' => $transaction->title,
                        'author' => isset($transaction->author) ? $transaction->author : null,
                        'item_id' => isset($transaction->itemID) ? $transaction->itemID : null,
                        'fines' => isset($transaction->fine) ? $transaction->fine->_ : false,
                        'overdue' => isset($transaction->overdue) ? $transaction->overdue : null,
                    ];
                }
            }

            return $transList;
        } catch (\Exception $e) {
            throw new ILSException($e->getMessage());
        }
    }
}

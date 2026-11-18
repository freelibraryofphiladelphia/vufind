<?php

namespace FreeLibrary\ILS\Driver;

class Symphony extends \VuFind\ILS\Driver\Symphony
{

    /**
     * Look up a patron by barcode.
     *
     * @param string $barcode Patron barcode
     *
     * @return ?array Patron data containing the barcode, or null if unavailable
     */
    public function lookupPatronByBarcode(string $barcode): ?array
    {
        $barcode = trim($barcode);

        if ($barcode === '') {
            return null;
        }

        try {
            $result = $this->makeRequest(
                'patron',
                'lookupPatronInfo',
                [
                    'userID' => $barcode,
                    'includePatronInfo' => true,
                    'includePatronStatusInfo' => true,
                ],
                [
                    'login' => $this->config['WebServices']['user'],
                    'password' => $this->config['WebServices']['password'] ?? null,
                ]
            );

            if (empty($result->patronInfo->userID)) {
                return null;
            }

            $expiration = $result->patronStatusInfo->datePrivilegeExpires ?? null;

            // Preserve the original expiration behavior.
            if ($expiration !== null && $expiration !== '') {

                $expires = new \DateTimeImmutable($expiration);

                if (time() >= $expires->getTimestamp()) {
                    return null;
                }
            }

            return [
                'barcode' => (string) $result->patronInfo->userID,
            ];

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get Patron Transactions
     *
     * @param array $patron The patron array from patronLogin
     *
     * @return array Array of the patron's transactions on success.
     */
    public function getMyTransactions($patron)
    {
        try {
            $transList = [];
            $options   = ['includePatronCheckoutInfo' => 'ALLEXCEPTCLAIMEDRETURNED'];

            $result = $this->makeRequest(
                'patron',
                'lookupMyAccountInfo',
                $options,
                [
                    'login' => $patron['cat_username'],
                    'password' => $patron['cat_password']
                ]
            );

            if (isset($result->patronCheckoutInfo)) {
                $transactions = $result->patronCheckoutInfo;
                $transactions = !is_array($transactions) ? [$transactions] : $transactions;

                foreach ($transactions as $transaction) {
                    $urr = !empty($transaction->unseenRenewalsRemaining)
                        || !empty($transaction->unseenRenewalsRemainingUnlimited);
                    $rr = !empty($transaction->renewalsRemaining)
                        || !empty($transaction->renewalsRemainingUnlimited);
                    $renewable = ($urr && $rr);

                    $transList[] = [
                        'duedate'           => !empty($transaction->dueDate) ? date('F j, Y', strtotime($transaction->dueDate)) : null,
                        'id'                => $transaction->titleKey ?? null,
                        'barcode'           => $transaction->itemID ?? null,
                        'renew'             => $transaction->renewals ?? 0,
                        'lastRenewDate'     => !empty($transaction->lastRenewedDate) ? date('F j, Y', strtotime($transaction->lastRenewedDate)) : null,
                        'renewalsRemaining' => $transaction->renewalsRemaining ?? null,
                        'request'           => $transaction->recallNoticesSent ?? null,
                        'renewable'         => $renewable,
                        'title'             => $transaction->title ?? '',
                        'author'            => $transaction->author ?? null,
                        'item_id'           => $transaction->itemID ?? null,
                        'fines'             => $transaction->fine?->_ ?? false,
                        'overdue'           => $transaction->overdue ?? false,
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->throwAsIlsException($e);
        }
        return $transList;
    }
}

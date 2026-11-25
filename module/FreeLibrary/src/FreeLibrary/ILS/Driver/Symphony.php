<?php

namespace FreeLibrary\ILS\Driver;

use SoapFault;
use VuFind\Exception\ILS as ILSException;

class Symphony extends \VuFind\ILS\Driver\Symphony
{
    /******************************
     * OVERRIDDEN FUNCTIONS
     ******************************/

    /**
     * Return a SymWS session token for given credentials.
     *
     * To avoid needing to repeatedly log in the same user,
     * cache acquired session tokens by the credentials provided.
     * If the cached session token is expired or otherwise defective,
     * the caller can use the $reset parameter.
     *
     * @param string  $login    The login account name
     * @param ?string $password The login password, or null for no password
     * @param bool    $reset    If true, replace any currently cached token
     *
     * @return string The session token
     */
    protected function getSessionToken(
        string $login,
        ?string $password = null,
        bool $reset = false
    ) {
        static $sessionTokens = [];

        // If we keyed only by $login, we might mistakenly retrieve a valid
        // session token when provided with an invalid password.
        // We hash the credentials to reduce the potential for
        // incompatibilities with key limitations of whatever cache backend
        // an administrator might elect to use for session tokens,
        // and though more expensive, we use a secure hash because
        // what we're hashing contains a password.
        $key = hash('sha256', "$login:$password");

        if (!isset($sessionTokens[$key]) || $reset) {
            // Need to set the session key if it is not set here
            if (!$reset && $token = ($_SESSION['symws']['session'][$key] ?? null)) {
                $sessionTokens[$key] = $token;
            } else {
                $params = ['login' => $login];
                if (isset($password)) {
                    $params['password'] = $password;
                }

                $response = $this->makeRequest('security', 'loginUser', $params);
                $sessionTokens[$key] = $response->sessionToken;
                $_SESSION['symws']['session'] = $sessionTokens;
            }
        }

        return $sessionTokens[$key];
    }

    /**
     * Patron Login.
     *
     * This is responsible for authenticating a patron against the catalog.
     *
     * @param string $username The patron username
     * @param string $password The patron password
     *
     * @throws ILSException
     * @return ?array          Associative array of patron info on successful login,
     * null on unsuccessful login.
     */
    public function patronLogin($username, $password)
    {
        $usernameField = $this->config['Behaviors']['usernameField'];

        try {
            $resp = $this->makeRequest(
                'patron',
                'lookupMyAccountInfo',
                [
                    'includePatronInfo' => 'true',
                    'includePatronAddressInfo' => 'true',
                ],
                [
                    'login' => $username,
                    'password' => $password,
                ]
            );
        } catch (SoapFault $e) {
            // Need to check for both of these exception types with current version of Symphony
            $unableToLogin = [
                'ns0:com.sirsidynix.symws.service.exceptions.SecurityServiceException.unableToLogin',
                'ns0:com.sirsidynix.ilsws.util.exceptions.SecurityServiceException.unableToLogin',
            ];

            if (in_array($e->faultcode, $unableToLogin, true)) {
                return null;
            }

            throw $e;
        }

        $regEx = '/([^,]*),\s([^\s]*)/';
        $firstName = '';
        $lastName = '';
        if (preg_match($regEx, $resp->patronInfo->displayName, $matches)) {
            $firstName = $matches[2];
            $lastName  = $matches[1];
        }

        // There may be an email address in any of three numbered addresses,
        // so we search each one until we find an email address,
        // starting with the one marked primary.
        $addrinfo_check_order = ['1','2','3'];
        if (isset($resp->patronAddressInfo->primaryAddress)) {
            $primary_addr_n = $resp->patronAddressInfo->primaryAddress;
            array_unshift($addrinfo_check_order, $primary_addr_n);
        }
        $email = '';
        foreach ($addrinfo_check_order as $n) {
            $AddressNInfo = "Address{$n}Info";
            if (isset($resp->patronAddressInfo->$AddressNInfo)) {
                $addrinfos = is_array($resp->patronAddressInfo->$AddressNInfo)
                    ? $resp->patronAddressInfo->$AddressNInfo
                    : [$resp->patronAddressInfo->$AddressNInfo];
                foreach ($addrinfos as $addrinfo) {
                    if (
                        $addrinfo->addressPolicyID == 'EMAIL'
                        && !empty($addrinfo->addressValue)
                    ) {
                        $email = $addrinfo->addressValue;
                        break;
                    }
                }
            }
        }

        // @TODO: major, college
        return $this->createPatronArray(
            id: $resp->patronInfo->$usernameField,
            firstname: $firstName,
            lastname: $lastName,
            email: $email,
            cat_username: $username,
            cat_password: $password,
            nonDefaultFields: [
                // Legacy support, see createProfileArray:home_library
                'library' => $resp->patronInfo->patronLibraryID,
            ]
        );
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

    /******************************
     * FREE LIBRARY FUNCTIONS
     ******************************/

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
}

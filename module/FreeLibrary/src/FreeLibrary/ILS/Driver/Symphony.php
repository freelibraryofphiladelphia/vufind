<?php

/**
 * Free Library Symphony ILS driver.
 *
 * PHP version 8
 *
 * Copyright (C) Free Library of Philadelphia 2026.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */

namespace FreeLibrary\ILS\Driver;

use FreeLibrary\ILS\Logic\Holdability;
use VuFind\Cache\Manager;
use VuFind\Record\Loader;

use function array_filter;
use function array_values;
use function in_array;
use function is_array;

/**
 * Free Library Symphony driver.
 *
 * Extends the core Symphony driver with Free Library holdability rules:
 * items at non-holdable locations or of non-holdable material types
 * cannot be held, and libraries excluded by configuration cannot be
 * selected as hold pickup locations.
 */
class Symphony extends \VuFind\ILS\Driver\Symphony
{
    /**
     * Constructor.
     *
     * @param Loader      $loader       Record loader
     * @param ?Manager    $cacheManager Cache manager (optional)
     * @param Holdability $holdability  Free Library holdability rules
     */
    public function __construct(
        Loader $loader,
        ?Manager $cacheManager,
        protected Holdability $holdability
    ) {
        parent::__construct($loader, $cacheManager);
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

    /**
     * Parse Call Info.
     *
     * Builds the standard item array through the core driver, then applies
     * the Free Library holdability rules: an item is holdable only if the
     * title is holdable per SymWS, the item's material type is holdable,
     * and the item's home location is holdable. Items whose status is
     * configured as unavailable are additionally forced unavailable in
     * availability displays (without affecting holdability).
     *
     * @param object|array $callInfos   The CallInfo objects of the title
     * @param string       $titleID     The ID of the title in the catalog
     * @param bool         $is_holdable Whether or not the title is holdable
     * @param ?int         $bound_in    Whether or not the title is bound with
     * another
     *
     * @return array An array of items on success, an empty array otherwise.
     */
    protected function parseCallInfo(
        $callInfos,
        $titleID,
        $is_holdable = false,
        $bound_in = null
    ) {
        $items = parent::parseCallInfo($callInfos, $titleID, $is_holdable, $bound_in);

        if (!$this->holdability->hasHoldabilityRules()) {
            return $items;
        }

        // Map raw Symphony policies to items so holdability can be checked
        // against the un-translated policy IDs:
        $policies = [];
        $callInfos = is_array($callInfos) ? $callInfos : [$callInfos];
        foreach ($callInfos as $callInfo) {
            if (!isset($callInfo->ItemInfo)) {
                continue;
            }
            $itemInfos = is_array($callInfo->ItemInfo)
                ? $callInfo->ItemInfo
                : [$callInfo->ItemInfo];
            foreach ($itemInfos as $itemInfo) {
                $policies[$itemInfo->itemID] = [
                    'homeLocationID' => $itemInfo->homeLocationID,
                    'itemTypeID' => $itemInfo->itemTypeID,
                ];
            }
        }

        foreach ($items as $i => $item) {
            // Force items with statuses configured as unavailable to be
            // reported as unavailable (availability display only; this
            // does not affect holdability):
            if ($this->holdability->isStatusUnavailable((string)($item['status'] ?? ''))) {
                $item['availability'] = false;
                $item['holdtype'] = 'recall';
            }
            $itemID = $item['item_id'] ?? null;
            if (null === $itemID || !isset($policies[$itemID])) {
                $items[$i] = $item;
                continue;
            }
            $homeLocationID = $policies[$itemID]['homeLocationID'];
            $itemTypeID = $policies[$itemID]['itemTypeID'];
            $item['homeLocationID'] = $homeLocationID;
            $item['itemTypeID'] = $itemTypeID;
            $holdable = $item['is_holdable']
                && $this->holdability->isLocationHoldable($homeLocationID)
                && $this->holdability->isItemTypeHoldable($itemTypeID);
            $item['is_holdable'] = $holdable;
            $item['addLink'] = $holdable;
            if (!$holdable) {
                $item['holdOverride'] = 'disabled';
            }
            $items[$i] = $item;
        }

        return $items;
    }

    /**
     * Check whether a request is valid.
     *
     * A title-level request is valid if any of the title's items is
     * holdable; a copy-level request is valid if the requested item is
     * holdable. Items that do not report holdability information (e.g.
     * copies on order or MARC holdings pseudo-items) are treated as
     * holdable. Failures to retrieve holdings block the request.
     *
     * @param string $id     A Bibliographic ID
     * @param array  $data   Collected Holds Data
     * @param array  $patron Patron related data
     *
     * @return bool True if the request may proceed, false otherwise.
     */
    public function checkRequestIsValid($id, $data, $patron)
    {
        try {
            $items = $this->getHolding($id);

            if (!empty($data['item_id'])) {
                foreach ($items as $item) {
                    $itemID = $item['item_id'] ?? null;
                    if (null !== $itemID && (string)$itemID === (string)$data['item_id']) {
                        return (bool)($item['is_holdable'] ?? true);
                    }
                }
                // The requested item does not exist:
                return false;
            }

            foreach ($items as $item) {
                if ($item['is_holdable'] ?? true) {
                    return true;
                }
            }
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get Pick Up Locations.
     *
     * Returns the core driver's list of libraries with any location
     * excluded by Free Library configuration removed.
     *
     * @param array $patron      Patron information returned by the patronLogin
     * method.
     * @param array $holdDetails Optional array, only passed in when getting a
     * list in the context of placing or editing a hold.
     *
     * @return array An array of associative arrays with locationID and
     * locationDisplay keys
     */
    public function getPickUpLocations($patron = false, $holdDetails = null)
    {
        $libraries = parent::getPickUpLocations($patron, $holdDetails);

        if (!$this->holdability->hasHoldabilityRules()) {
            return $libraries;
        }

        return array_values(
            array_filter(
                $libraries,
                fn ($library) => $this->holdability->isPickupLocationAllowed(
                    (string)$library['locationID']
                )
            )
        );
    }

    /**
     * Get Default Pick Up Location.
     *
     * Applies the core driver's logic, but falls back to the first allowed
     * pickup location when the patron's home library or the configured
     * default is excluded by Free Library configuration.
     *
     * @param array $patron      Patron information returned by the patronLogin
     * method.
     * @param array $holdDetails Optional array, only passed in when getting a
     * list in the context of placing a hold.
     *
     * @return string The default pickup location for the patron.
     */
    public function getDefaultPickUpLocation($patron = false, $holdDetails = null)
    {
        $default = parent::getDefaultPickUpLocation($patron, $holdDetails);
        if (!is_string($default) || $this->holdability->isPickupLocationAllowed($default)) {
            return $default;
        }
        $allowed = $this->getPickUpLocations();
        return $allowed[0]['locationID'] ?? $default;
    }
}

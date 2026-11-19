<?php

/**
 * Free Library holdability rules.
 *
 * Loads the Free Library's non-holdable location, material type and status
 * lists from configuration and applies them consistently across hold
 * workflows.
 *
 * Configuration sources:
 * - nonholdablelocations.ini: nonHoldableLoc[] (non-holdable item home
 *   locations; also excluded from pickup location lists) and an optional
 *   [PickupLocations] exclude[] list of pickup-only exclusions.
 * - config.ini: [non_holdable_material_types] ItemTypeIDs[] and
 *   [unavailable_statuses] statuses[].
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
 * @package  ILS_Logic
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */

namespace FreeLibrary\ILS\Logic;

use VuFind\Config\Config;
use VuFind\Config\ConfigManagerInterface;
use VuFind\Http\CachingDownloader;

use function array_column;
use function array_map;
use function array_values;
use function filter_var;
use function in_array;
use function strtoupper;

/**
 * Free Library holdability rules.
 *
 * @category VuFind
 * @package  ILS_Logic
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class Holdability
{
    /**
     * Name of the custom configuration file holding the location lists.
     */
    protected const NON_HOLDABLE_LOCATIONS_FILE = 'nonholdablelocations.ini';

    /**
     * Name of the Free Library configuration file (API settings).
     */
    protected const FLP_CONFIG_FILE = 'flp.ini';

    /**
     * Cached non-holdable item home locations (from nonholdablelocations.ini).
     *
     * @var ?array
     */
    protected ?array $nonHoldableLocations = null;

    /**
     * Cached non-holdable item type IDs (from config.ini).
     *
     * @var ?array
     */
    protected ?array $nonHoldableItemTypes = null;

    /**
     * Cached unavailable statuses (from config.ini).
     *
     * @var ?array
     */
    protected ?array $unavailableStatuses = null;

    /**
     * Cached pickup-only location exclusions (from nonholdablelocations.ini).
     *
     * @var ?array
     */
    protected ?array $pickupExcludedLocations = null;

    /**
     * Cached contents of nonholdablelocations.ini.
     *
     * @var ?Config
     */
    protected ?Config $iniConfig = null;

    /**
     * Cached contents of flp.ini.
     *
     * @var ?Config
     */
    protected ?Config $flpConfig = null;

    /**
     * Cached API whitelist of active hold pickup locations (null when
     * unavailable; a separate flag tracks whether a fetch was attempted).
     *
     * @var ?array
     */
    protected ?array $apiPickupWhitelist = null;

    /**
     * Has the API whitelist been fetched (successfully or not)?
     *
     * @var bool
     */
    protected bool $apiPickupWhitelistLoaded = false;

    /**
     * Constructor.
     *
     * @param Config                  $mainConfig   Main VuFind configuration
     * @param ConfigManagerInterface $configLoader Config file loader
     * @param ?CachingDownloader     $downloader   HTTP client for the
     * pickup location whitelist API (null = API support disabled)
     */
    public function __construct(
        protected Config $mainConfig,
        protected ConfigManagerInterface $configLoader,
        protected ?CachingDownloader $downloader = null
    ) {
    }

    /**
     * Is the given item home location holdable?
     *
     * Matching is case-insensitive (SymWS policy IDs are uppercase, but
     * configuration entries may not be).
     *
     * @param string $locationID Symphony home location ID
     *
     * @return bool
     */
    public function isLocationHoldable(string $locationID): bool
    {
        return !in_array(strtoupper($locationID), $this->getNonHoldableLocations(), true);
    }

    /**
     * Is the given item type holdable?
     *
     * Matching is case-insensitive (SymWS policy IDs are uppercase, but
     * configuration entries may not be).
     *
     * @param string $itemTypeID Symphony item type ID
     *
     * @return bool
     */
    public function isItemTypeHoldable(string $itemTypeID): bool
    {
        return !in_array(strtoupper($itemTypeID), $this->getNonHoldableItemTypes(), true);
    }

    /**
     * Is the given item status considered unavailable?
     *
     * Items whose status matches this list are forced unavailable in
     * availability displays. This does not affect holdability, which is
     * governed by the location and material type lists.
     *
     * @param string $status Item status
     *
     * @return bool
     */
    public function isStatusUnavailable(string $status): bool
    {
        return in_array($status, $this->getUnavailableStatuses(), true);
    }

    /**
     * Is the given location allowed as a hold pickup location?
     *
     * Combines the non-holdable location list and the pickup-only
     * exclusion list with the dynamic API whitelist of active hold
     * pickup branches (when configured); static exclusions always win.
     * When the configured API is unreachable, filtering fails open to
     * the static lists unless flp.ini sets failClosed = true.
     *
     * @param string $locationID Symphony library policy ID
     *
     * @return bool
     */
    public function isPickupLocationAllowed(string $locationID): bool
    {
        if (!$this->isLocationHoldable($locationID)) {
            return false;
        }
        $id = strtoupper($locationID);
        if (in_array($id, $this->getPickupExcludedLocations(), true)) {
            return false;
        }
        // When the API whitelist is configured and reachable, a pickup
        // location must appear in it; when unavailable, fail open to the
        // static lists:
        $whitelist = $this->getApiPickupWhitelist();
        return null === $whitelist || in_array($id, $whitelist, true);
    }

    /**
     * Are any holdability rules configured?
     *
     * @return bool
     */
    public function hasHoldabilityRules(): bool
    {
        return !empty($this->getNonHoldableLocations())
            || !empty($this->getNonHoldableItemTypes())
            || !empty($this->getPickupExcludedLocations())
            || !empty($this->getUnavailableStatuses());
    }

    /**
     * Get flp.ini contents (loaded lazily; a missing or unreadable file
     * degrades to an empty configuration).
     *
     * @return Config
     */
    protected function getFlpConfig(): Config
    {
        if (null === $this->flpConfig) {
            try {
                $this->flpConfig = $this->configLoader->getConfigObject(self::FLP_CONFIG_FILE);
            } catch (\Throwable $e) {
                $this->flpConfig = new Config([]);
            }
        }
        return $this->flpConfig;
    }

    /**
     * Get the API whitelist of active hold pickup locations.
     *
     * The whitelist is fetched from the URL configured in flp.ini
     * ([PickupLocations] apiUrl) and cached through the caching
     * downloader (see flp.ini [Cache_PickupLocations] for TTL options).
     * Returns null when the whitelist is not configured or the API
     * cannot be reached and the failClosed option is unset (fail
     * open); returns an empty array (blocking all pickup locations)
     * when the configured API is unreachable and flp.ini sets
     * [PickupLocations] failClosed = true.
     *
     * @return ?array Array of uppercase Symphony library policy IDs
     */
    protected function getApiPickupWhitelist(): ?array
    {
        if (!$this->apiPickupWhitelistLoaded) {
            $this->apiPickupWhitelistLoaded = true;
            $this->apiPickupWhitelist = null;
            try {
                $apiUrl = (string)$this->getFlpConfig()->get('PickupLocations')?->get('apiUrl');
                if ('' !== $apiUrl && null !== $this->downloader) {
                    $this->downloader->setUpCache(
                        'PickupLocations',
                        'PickupLocations',
                        self::FLP_CONFIG_FILE
                    );
                    $json = $this->downloader->downloadJson($apiUrl);
                    $this->apiPickupWhitelist = array_map(
                        'strtoupper',
                        array_column($json->data ?? [], 'sirsi_id')
                    );
                }
            } catch (\Throwable $e) {
                // On API failure, fail open to the static lists unless
                // flp.ini configures failClosed behavior:
                $this->apiPickupWhitelist = $this->apiFailureFailsClosed()
                    ? [] : null;
            }
        }
        return $this->apiPickupWhitelist;
    }

    /**
     * Should an unreachable API block all pickup locations?
     *
     * Reads the [PickupLocations] failClosed setting from flp.ini
     * (default: false = fail open).
     *
     * @return bool
     */
    protected function apiFailureFailsClosed(): bool
    {
        $setting = $this->getFlpConfig()->get('PickupLocations')?->get('failClosed');
        return filter_var($setting, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get nonholdablelocations.ini contents (loaded lazily; a missing or
     * unreadable file degrades to an empty configuration).
     *
     * @return Config
     */
    protected function getIniConfig(): Config
    {
        if (null === $this->iniConfig) {
            try {
                $this->iniConfig = $this->configLoader
                    ->getConfigObject(self::NON_HOLDABLE_LOCATIONS_FILE);
            } catch (\Throwable $e) {
                $this->iniConfig = new Config([]);
            }
        }
        return $this->iniConfig;
    }

    /**
     * Get non-holdable item home locations.
     *
     * @return array
     */
    protected function getNonHoldableLocations(): array
    {
        return $this->nonHoldableLocations ??= array_map(
            'strtoupper',
            $this->extractList($this->getIniConfig(), 'nonHoldableLoc')
        );
    }

    /**
     * Get non-holdable item type IDs.
     *
     * @return array
     */
    protected function getNonHoldableItemTypes(): array
    {
        return $this->nonHoldableItemTypes ??= array_map(
            'strtoupper',
            $this->extractList(
                $this->mainConfig->get('non_holdable_material_types'),
                'ItemTypeIDs'
            )
        );
    }

    /**
     * Get unavailable statuses.
     *
     * @return array
     */
    protected function getUnavailableStatuses(): array
    {
        return $this->unavailableStatuses ??= $this->extractList(
            $this->mainConfig->get('unavailable_statuses'),
            'statuses'
        );
    }

    /**
     * Get pickup-only location exclusions.
     *
     * @return array
     */
    protected function getPickupExcludedLocations(): array
    {
        return $this->pickupExcludedLocations ??= array_map(
            'strtoupper',
            $this->extractList(
                $this->getIniConfig()->get('PickupLocations'),
                'exclude'
            )
        );
    }

    /**
     * Extract a list of values from a (possibly absent) configuration
     * section. A scalar value is treated as a single-item list so that an
     * entry written without the array suffix is not silently ignored.
     *
     * @param ?Config $config Configuration section (null if missing)
     * @param string  $key    Key holding the list
     *
     * @return array
     */
    protected function extractList(?Config $config, string $key): array
    {
        $value = $config?->get($key);
        if ($value instanceof Config) {
            return array_values($value->toArray());
        }
        if (is_string($value) && '' !== $value) {
            return [$value];
        }
        return is_array($value) ? array_values($value) : [];
    }
}

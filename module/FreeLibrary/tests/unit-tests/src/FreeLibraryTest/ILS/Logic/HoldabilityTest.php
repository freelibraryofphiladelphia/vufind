<?php

/**
 * Holdability logic test.
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
 * @package  Tests
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace FreeLibraryTest\ILS\Logic;

use FreeLibrary\ILS\Logic\Holdability;
use VuFind\Config\Config;
use VuFind\Config\ConfigManagerInterface;

/**
 * Holdability logic test.
 *
 * @category VuFind
 * @package  Tests
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class HoldabilityTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Get a sample main configuration array.
     *
     * @return array
     */
    protected function getMainConfigData(): array
    {
        return [
            'non_holdable_material_types' => [
                'ItemTypeIDs' => ['AREFERENCE', 'SHEETMUSIC'],
            ],
            'unavailable_statuses' => [
                'statuses' => ['In transit', 'Being fixed/mended'],
            ],
        ];
    }

    /**
     * Get a sample nonholdablelocations.ini configuration object.
     *
     * @param bool $withPickupSection Include the [PickupLocations] section?
     *
     * @return Config
     */
    protected function getNonHoldableConfig(bool $withPickupSection = true): Config
    {
        $data = ['nonHoldableLoc' => ['ART-VAULT', 'ILL', 'STACKS']];
        if ($withPickupSection) {
            $data['PickupLocations'] = ['exclude' => ['MATERIALS', 'BLIND']];
        }
        return new Config($data);
    }

    /**
     * Build a Holdability service with a mocked config plugin manager.
     *
     * @param array    $mainData   Main config data (null = file with no sections)
     * @param ?Config  $iniConfig  nonholdablelocations.ini contents (null = file missing)
     *
     * @return Holdability
     */
    protected function getService(?array $mainData = null, ?Config $iniConfig = null): Holdability
    {
        $loader = $this->createMock(ConfigManagerInterface::class);
        if (null === $iniConfig) {
            $loader->method('getConfigObject')
                ->willThrowException(new \VuFind\Exception\ConfigException('not found'));
        } else {
            $loader->method('getConfigObject')
                ->with('nonholdablelocations.ini')
                ->willReturn($iniConfig);
        }
        return new Holdability(new Config($mainData ?? []), $loader);
    }

    /**
     * Test location holdability checks against nonHoldableLoc list.
     *
     * @return void
     */
    public function testIsLocationHoldable()
    {
        $service = $this->getService($this->getMainConfigData(), $this->getNonHoldableConfig());
        $this->assertFalse($service->isLocationHoldable('ART-VAULT'));
        $this->assertFalse($service->isLocationHoldable('ILL'));
        $this->assertFalse($service->isLocationHoldable('STACKS'));
        $this->assertTrue($service->isLocationHoldable('ANDORRA'));
        $this->assertTrue($service->isLocationHoldable('GEN-CIRC'));
    }

    /**
     * Test item type holdability checks against ItemTypeIDs list.
     *
     * @return void
     */
    public function testIsItemTypeHoldable()
    {
        $service = $this->getService($this->getMainConfigData(), $this->getNonHoldableConfig());
        $this->assertFalse($service->isItemTypeHoldable('AREFERENCE'));
        $this->assertFalse($service->isItemTypeHoldable('SHEETMUSIC'));
        $this->assertTrue($service->isItemTypeHoldable('ABOOK'));
    }

    /**
     * Test status unavailability checks against statuses list.
     *
     * @return void
     */
    public function testIsStatusUnavailable()
    {
        $service = $this->getService($this->getMainConfigData(), $this->getNonHoldableConfig());
        $this->assertTrue($service->isStatusUnavailable('In transit'));
        $this->assertTrue($service->isStatusUnavailable('Being fixed/mended'));
        $this->assertFalse($service->isStatusUnavailable('Checked in'));
    }

    /**
     * Test pickup location allowance against both the nonHoldableLoc list
     * and the [PickupLocations] exclude list.
     *
     * @return void
     */
    public function testIsPickupLocationAllowed()
    {
        $service = $this->getService($this->getMainConfigData(), $this->getNonHoldableConfig());
        // Blocked by top-level nonHoldableLoc list:
        $this->assertFalse($service->isPickupLocationAllowed('ART-VAULT'));
        $this->assertFalse($service->isPickupLocationAllowed('STACKS'));
        // Blocked by [PickupLocations] exclude list:
        $this->assertFalse($service->isPickupLocationAllowed('MATERIALS'));
        $this->assertFalse($service->isPickupLocationAllowed('BLIND'));
        // Allowed:
        $this->assertTrue($service->isPickupLocationAllowed('ANDORRA'));
        $this->assertTrue($service->isPickupLocationAllowed('CENTRAL'));
    }

    /**
     * Test that pickup filtering works with no [PickupLocations] section
     * (top-level list still applies).
     *
     * @return void
     */
    public function testPickupFilteringWithoutPickupSection()
    {
        $service = $this->getService(
            $this->getMainConfigData(),
            $this->getNonHoldableConfig(false)
        );
        $this->assertFalse($service->isPickupLocationAllowed('ART-VAULT'));
        $this->assertTrue($service->isPickupLocationAllowed('MATERIALS'));
        $this->assertTrue($service->isPickupLocationAllowed('ANDORRA'));
    }

    /**
     * Test that a missing nonholdablelocations.ini degrades gracefully
     * (everything holdable and allowed).
     *
     * @return void
     */
    public function testMissingIniFileDegradesGracefully()
    {
        $service = $this->getService($this->getMainConfigData(), null);
        $this->assertTrue($service->isLocationHoldable('ART-VAULT'));
        $this->assertTrue($service->isPickupLocationAllowed('MATERIALS'));
        $this->assertTrue($service->hasHoldabilityRules());
    }

    /**
     * Test that missing config.ini sections degrade gracefully.
     *
     * @return void
     */
    public function testMissingMainConfigSectionsDegradeGracefully()
    {
        $service = $this->getService([], $this->getNonHoldableConfig());
        $this->assertTrue($service->isItemTypeHoldable('AREFERENCE'));
        $this->assertFalse($service->isStatusUnavailable('In transit'));
        $this->assertTrue($service->hasHoldabilityRules());
    }

    /**
     * Test hasHoldabilityRules() with all configuration present.
     *
     * @return void
     */
    public function testHasHoldabilityRules()
    {
        $service = $this->getService($this->getMainConfigData(), $this->getNonHoldableConfig());
        $this->assertTrue($service->hasHoldabilityRules());
    }

    /**
     * Test hasHoldabilityRules() with no rules configured at all.
     *
     * @return void
     */
    public function testHasHoldabilityRulesWithNoRules()
    {
        $service = $this->getService([], new Config([]));
        $this->assertFalse($service->hasHoldabilityRules());
        $this->assertTrue($service->isLocationHoldable('ART-VAULT'));
        $this->assertTrue($service->isItemTypeHoldable('AREFERENCE'));
        $this->assertTrue($service->isPickupLocationAllowed('MATERIALS'));
        $this->assertFalse($service->isStatusUnavailable('In transit'));
    }

    /**
     * Test hasHoldabilityRules() with only statuses configured.
     *
     * @return void
     */
    public function testHasHoldabilityRulesWithOnlyStatuses()
    {
        $mainData = ['unavailable_statuses' => ['statuses' => ['In transit']]];
        $service = $this->getService($mainData, new Config([]));
        $this->assertTrue($service->hasHoldabilityRules());
    }

    /**
     * Test that a scalar list value (missing [] suffix) is treated as a
     * single-item list rather than silently ignored.
     *
     * @return void
     */
    public function testScalarListValueIsTreatedAsSingleItem()
    {
        $ini = new Config(['nonHoldableLoc' => 'ART-VAULT']);
        $service = $this->getService($this->getMainConfigData(), $ini);
        $this->assertFalse($service->isLocationHoldable('ART-VAULT'));
        $this->assertTrue($service->isLocationHoldable('STACKS'));
        $this->assertTrue($service->hasHoldabilityRules());
    }

    /**
     * Test that location and item type matching is case-insensitive
     * (SymWS policy IDs are uppercased; config may not be).
     *
     * @return void
     */
    public function testLocationAndTypeMatchingIsCaseInsensitive()
    {
        $mainData = [
            'non_holdable_material_types' => [
                'ItemTypeIDs' => ['areference'],
            ],
        ];
        $ini = new Config(['nonHoldableLoc' => ['art-vault']]);
        $service = $this->getService($mainData, $ini);
        $this->assertFalse($service->isLocationHoldable('ART-VAULT'));
        $this->assertTrue($service->isLocationHoldable('STACKS'));
        $this->assertFalse($service->isItemTypeHoldable('AREFERENCE'));
        $this->assertTrue($service->isItemTypeHoldable('ABOOK'));
        $this->assertFalse($service->isPickupLocationAllowed('Art-Vault'));
    }

    /**
     * Build a Holdability service with per-file config responses and an
     * optional API downloader.
     *
     * @param ?object $downloader CachingDownloader mock (null = no API support)
     * @param ?Config $flpConfig  flp.ini contents (null = file missing)
     *
     * @return Holdability
     */
    protected function getApiService(?object $downloader, ?Config $flpConfig = null): Holdability
    {
        $loader = $this->createMock(ConfigManagerInterface::class);
        $loader->method('getConfigObject')->willReturnCallback(
            function (string $path) use ($flpConfig) {
                return match ($path) {
                    'nonholdablelocations.ini' => $this->getNonHoldableConfig(),
                    'flp.ini' => $flpConfig
                        ?? throw new \VuFind\Exception\ConfigException('not found'),
                    default => new Config([]),
                };
            }
        );
        return new Holdability(new Config($this->getMainConfigData()), $loader, $downloader);
    }

    /**
     * Test that a configured API whitelist constrains pickup locations
     * alongside the static exclusion lists.
     *
     * @return void
     */
    public function testApiWhitelistFiltersPickupLocations()
    {
        $downloader = $this->createMock(\VuFind\Http\CachingDownloader::class);
        $downloader->method('downloadJson')->willReturn(
            (object)['data' => [
                ['sirsi_id' => 'andorra'],
                ['sirsi_id' => 'CENTRAL'],
                ['sirsi_id' => 'MATERIALS'],
            ]]
        );
        $flp = new Config(['PickupLocations' => ['apiUrl' => 'http://example/api']]);
        $service = $this->getApiService($downloader, $flp);

        // Whitelisted (case-insensitive):
        $this->assertTrue($service->isPickupLocationAllowed('ANDORRA'));
        $this->assertTrue($service->isPickupLocationAllowed('CENTRAL'));
        // Not whitelisted:
        $this->assertFalse($service->isPickupLocationAllowed('FALLS'));
        // Static exclusions win over the whitelist:
        $this->assertFalse($service->isPickupLocationAllowed('MATERIALS'));
        // Static location rules still apply:
        $this->assertFalse($service->isPickupLocationAllowed('ART-VAULT'));
    }

    /**
     * Test that an unreachable API fails open to static-only filtering.
     *
     * @return void
     */
    public function testApiFailureFailsOpen()
    {
        $downloader = $this->createMock(\VuFind\Http\CachingDownloader::class);
        $downloader->method('downloadJson')->willThrowException(
            new \VuFind\Exception\HttpDownloadException('unreachable', 'http://example/api')
        );
        $flp = new Config(['PickupLocations' => ['apiUrl' => 'http://example/api']]);
        $service = $this->getApiService($downloader, $flp);

        // Fail open: API unavailable -> static-only behavior:
        $this->assertTrue($service->isPickupLocationAllowed('FALLS'));
        $this->assertTrue($service->isPickupLocationAllowed('ANDORRA'));
        // Static floor still enforced:
        $this->assertFalse($service->isPickupLocationAllowed('MATERIALS'));
        $this->assertFalse($service->isPickupLocationAllowed('ART-VAULT'));
    }

    /**
     * Test that a missing flp.ini leaves static filtering in charge and
     * never touches the API.
     *
     * @return void
     */
    public function testMissingFlpIniUsesStaticLists()
    {
        $downloader = $this->createMock(\VuFind\Http\CachingDownloader::class);
        $downloader->expects($this->never())->method('downloadJson');
        $service = $this->getApiService($downloader, null);

        $this->assertTrue($service->isPickupLocationAllowed('FALLS'));
        $this->assertTrue($service->isPickupLocationAllowed('ANDORRA'));
        $this->assertFalse($service->isPickupLocationAllowed('MATERIALS'));
    }

    /**
     * Test that failClosed = true blocks all pickup locations when the
     * API is unreachable (legacy catalog behavior).
     *
     * @return void
     */
    public function testApiFailureCanFailClosedViaConfig()
    {
        $downloader = $this->createMock(\VuFind\Http\CachingDownloader::class);
        $downloader->method('downloadJson')->willThrowException(
            new \VuFind\Exception\HttpDownloadException('unreachable', 'http://example/api')
        );
        $flp = new Config(
            ['PickupLocations' => ['apiUrl' => 'http://example/api', 'failClosed' => 'true']]
        );
        $service = $this->getApiService($downloader, $flp);

        // Fail closed: even allowed branches are blocked:
        $this->assertFalse($service->isPickupLocationAllowed('ANDORRA'));
        $this->assertFalse($service->isPickupLocationAllowed('CENTRAL'));
        $this->assertFalse($service->isPickupLocationAllowed('FALLS'));
    }

    /**
     * Test that failClosed only matters when the API is configured.
     *
     * @return void
     */
    public function testFailClosedRequiresConfiguredApi()
    {
        $downloader = $this->createMock(\VuFind\Http\CachingDownloader::class);
        $downloader->expects($this->never())->method('downloadJson');
        $flp = new Config(['PickupLocations' => ['failClosed' => 'true']]);
        $service = $this->getApiService($downloader, $flp);

        // No apiUrl -> static-only behavior, not fail-closed:
        $this->assertTrue($service->isPickupLocationAllowed('ANDORRA'));
        $this->assertTrue($service->isPickupLocationAllowed('FALLS'));
        $this->assertFalse($service->isPickupLocationAllowed('MATERIALS'));
    }
}

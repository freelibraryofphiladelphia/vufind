<?php

/**
 * Free Library Symphony driver test.
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

namespace FreeLibraryTest\ILS\Driver;

use FreeLibrary\ILS\Driver\Symphony as FreeLibrarySymphony;
use FreeLibrary\ILS\Logic\Holdability;
use VuFind\Config\Config;
use VuFind\Config\ConfigManagerInterface;

/**
 * Free Library Symphony driver test.
 *
 * @category VuFind
 * @package  Tests
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class SymphonyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Fixture driver under test.
     *
     * @var SymphonyFixture
     */
    protected $driver;

    /**
     * Standard setup method.
     *
     * @return void
     */
    public function setUp(): void
    {
        $mainConfig = new Config([
            'non_holdable_material_types' => [
                'ItemTypeIDs' => ['AREFERENCE', 'SHEETMUSIC'],
            ],
            'unavailable_statuses' => [
                'statuses' => ['In transit', 'Being fixed/mended'],
            ],
        ]);
        $iniConfig = new Config([
            'nonHoldableLoc' => ['ART-VAULT', 'ILL', 'STACKS'],
            'PickupLocations' => ['exclude' => ['MATERIALS', 'BLIND']],
        ]);
        $loader = $this->createMock(ConfigManagerInterface::class);
        $loader->method('getConfigObject')->willReturn($iniConfig);
        $holdability = new Holdability($mainConfig, $loader);

        $recordLoader = $this->createMock(\VuFind\Record\Loader::class);
        $this->driver = new SymphonyFixture($recordLoader, null, $holdability);
        $this->driver->setConfig([]);
        $this->driver->init();
        $this->driver->policyLists = [
            'LIBR' => [
                'CENTRAL' => 'Central Library',
                'ANDORRA' => 'Andorra Branch',
                'ART-VAULT' => 'Art Vault',
                'MATERIALS' => 'Materials Management',
                'BLIND' => 'Library for the Blind',
                'STACKS' => 'Stacks',
            ],
            'LOCN' => [
                'ART-VAULT' => 'Art Vault',
                'STACKS' => 'Stacks',
                'GEN-CIRC' => 'General Circulation',
                'MENDING' => 'Being fixed/mended',
            ],
            'ITYP' => [
                'ABOOK' => 'Book',
                'AREFERENCE' => 'Reference',
            ],
        ];
    }

    /**
     * Build a callInfo object with the given items.
     *
     * @param array $items Item info objects
     *
     * @return object
     */
    protected function getCallInfo(array $items): object
    {
        return (object)[
            'libraryID' => 'CENTRAL',
            'callNumber' => 'F 123 .A1',
            'ItemInfo' => $items,
        ];
    }

    /**
     * Build an item info object.
     *
     * @param string $itemID      Item ID
     * @param string $homeLoc     Home location ID
     * @param string $itemType    Item type ID
     * @param bool   $chargeable  Is the item chargeable?
     *
     * @return object
     */
    protected function getItemInfo(
        string $itemID,
        string $homeLoc,
        string $itemType,
        bool $chargeable = false
    ): object {
        return (object)[
            'itemID' => $itemID,
            'homeLocationID' => $homeLoc,
            'currentLocationID' => $homeLoc,
            'itemTypeID' => $itemType,
            'chargeable' => $chargeable,
        ];
    }

    /**
     * Index parsed items by item_id.
     *
     * @param array $items Parsed items
     *
     * @return array
     */
    protected function indexItems(array $items): array
    {
        $indexed = [];
        foreach ($items as $item) {
            $indexed[$item['item_id']] = $item;
        }
        return $indexed;
    }

    /**
     * Test that parseCallInfo() marks items at non-holdable locations or of
     * non-holdable material types as non-holdable.
     *
     * @return void
     */
    public function testParseCallInfoMarksNonHoldableItems()
    {
        $callInfo = $this->getCallInfo([
            $this->getItemInfo('I1', 'ART-VAULT', 'ABOOK'),
            $this->getItemInfo('I2', 'STACKS', 'AREFERENCE'),
            $this->getItemInfo('I3', 'GEN-CIRC', 'ABOOK', true),
        ]);
        $items = $this->indexItems(
            $this->driver->parseCallInfoPublic([$callInfo], '12345', true)
        );

        // Item at a non-holdable location:
        $this->assertFalse($items['I1']['is_holdable']);
        $this->assertFalse($items['I1']['addLink']);
        $this->assertSame('disabled', $items['I1']['holdOverride']);
        $this->assertSame('ART-VAULT', $items['I1']['homeLocationID']);
        $this->assertSame('ABOOK', $items['I1']['itemTypeID']);
        // Availability display remains governed by core logic:
        $this->assertTrue($items['I1']['availability']);

        // Item of a non-holdable material type at a non-holdable location:
        $this->assertFalse($items['I2']['is_holdable']);
        $this->assertSame('disabled', $items['I2']['holdOverride']);

        // Holdable item remains holdable, with no override marker:
        $this->assertTrue($items['I3']['is_holdable']);
        $this->assertTrue($items['I3']['addLink']);
        $this->assertArrayNotHasKey('holdOverride', $items['I3']);
        $this->assertSame('GEN-CIRC', $items['I3']['homeLocationID']);
        $this->assertSame('ABOOK', $items['I3']['itemTypeID']);
        $this->assertTrue($items['I3']['availability']);
    }

    /**
     * Test that a title SymWS reports as non-holdable stays non-holdable
     * for all of its items.
     *
     * @return void
     */
    public function testParseCallInfoPreservesTitleLevelNonHoldability()
    {
        $callInfo = $this->getCallInfo([
            $this->getItemInfo('I3', 'GEN-CIRC', 'ABOOK', true),
        ]);
        $items = $this->indexItems(
            $this->driver->parseCallInfoPublic([$callInfo], '12345', false)
        );
        $this->assertFalse($items['I3']['is_holdable']);
        $this->assertFalse($items['I3']['addLink']);
        $this->assertSame('disabled', $items['I3']['holdOverride']);
    }

    /**
     * Test title-level checkRequestIsValid().
     *
     * @return void
     */
    public function testCheckRequestIsValidTitleLevel()
    {
        // Some holdable items: valid.
        $this->driver->holdingItems = [
            ['item_id' => 'I1', 'is_holdable' => false],
            ['item_id' => 'I3', 'is_holdable' => true],
        ];
        $this->assertTrue(
            $this->driver->checkRequestIsValid(
                '12345',
                ['id' => '12345', 'level' => 'title'],
                []
            )
        );

        // No holdable items: invalid.
        $this->driver->holdingItems = [
            ['item_id' => 'I1', 'is_holdable' => false],
            ['item_id' => 'I2', 'is_holdable' => false],
        ];
        $this->assertFalse(
            $this->driver->checkRequestIsValid(
                '12345',
                ['id' => '12345', 'level' => 'title'],
                []
            )
        );

        // Pseudo-items without holdability data are neutral: valid.
        $this->driver->holdingItems = [
            ['item_id' => 'O1', 'availability' => false],
        ];
        $this->assertTrue(
            $this->driver->checkRequestIsValid(
                '12345',
                ['id' => '12345', 'level' => 'title'],
                []
            )
        );
    }

    /**
     * Test copy-level checkRequestIsValid().
     *
     * @return void
     */
    public function testCheckRequestIsValidCopyLevel()
    {
        $this->driver->holdingItems = [
            ['item_id' => 'I1', 'is_holdable' => false],
            ['item_id' => 'I3', 'is_holdable' => true],
        ];
        $this->assertFalse(
            $this->driver->checkRequestIsValid(
                '12345',
                ['id' => '12345', 'level' => 'copy', 'item_id' => 'I1'],
                []
            )
        );
        $this->assertTrue(
            $this->driver->checkRequestIsValid(
                '12345',
                ['id' => '12345', 'level' => 'copy', 'item_id' => 'I3'],
                []
            )
        );
        // A fabricated item that does not exist is invalid:
        $this->assertFalse(
            $this->driver->checkRequestIsValid(
                '12345',
                ['id' => '12345', 'level' => 'copy', 'item_id' => 'NOPE'],
                []
            )
        );
    }

    /**
     * Test that checkRequestIsValid() fails closed when holdings cannot
     * be retrieved.
     *
     * @return void
     */
    public function testCheckRequestIsValidFailsClosedOnIlsError()
    {
        $this->driver->throwInGetHolding = true;
        $this->assertFalse(
            $this->driver->checkRequestIsValid(
                '12345',
                ['id' => '12345', 'level' => 'title'],
                []
            )
        );
    }

    /**
     * Test that getPickUpLocations() filters excluded libraries.
     *
     * @return void
     */
    public function testGetPickUpLocationsFiltersExcluded()
    {
        $locations = $this->driver->getPickUpLocations();
        $ids = array_column($locations, 'locationID');
        $this->assertEquals(['CENTRAL', 'ANDORRA'], $ids);
        $this->assertEquals('Central Library', $locations[0]['locationDisplay']);
    }

    /**
     * Test that getDefaultPickUpLocation() falls back to an allowed
     * location when the configured default is excluded.
     *
     * @return void
     */
    public function testGetDefaultPickUpLocationFallsBackWhenExcluded()
    {
        // Allowed home library is kept:
        $this->assertSame(
            'ANDORRA',
            $this->driver->getDefaultPickUpLocation(['library' => 'ANDORRA'])
        );
        // Excluded home library falls back to first allowed location:
        $this->assertSame(
            'CENTRAL',
            $this->driver->getDefaultPickUpLocation(['library' => 'MATERIALS'])
        );
        // Excluded config default falls back to first allowed location:
        $this->driver->setConfig(
            ['Holds' => ['defaultPickUpLocation' => 'BLIND']]
        );
        $this->assertSame('CENTRAL', $this->driver->getDefaultPickUpLocation());
    }

    /**
     * Test that checkRequestIsValid compares item IDs strictly (a numeric
     * string like 0123 must not match item 123).
     *
     * @return void
     */
    public function testCheckRequestIsValidMatchesItemIdsStrictly()
    {
        $this->driver->holdingItems = [
            ['item_id' => '123', 'is_holdable' => true],
        ];
        $this->assertFalse(
            $this->driver->checkRequestIsValid(
                '12345',
                ['id' => '12345', 'level' => 'copy', 'item_id' => '0123'],
                []
            )
        );
        $this->assertTrue(
            $this->driver->checkRequestIsValid(
                '12345',
                ['id' => '12345', 'level' => 'copy', 'item_id' => '123'],
                []
            )
        );
    }

    /**
     * Test that call info objects without items are handled gracefully.
     *
     * @return void
     */
    public function testParseCallInfoWithNoItemInfo()
    {
        $callInfo = $this->getCallInfo([]);
        unset($callInfo->ItemInfo);
        $items = $this->driver->parseCallInfoPublic([$callInfo], '12345', true);
        $this->assertEmpty($items);
    }

    /**
     * Test that holdability flags are left untouched when no rules are
     * configured.
     *
     * @return void
     */
    public function testParseCallInfoPassthroughWithoutRules()
    {
        $noRulesService = new Holdability(
            new Config([]),
            (function () {
                $loader = $this->createMock(\VuFind\Config\ConfigManagerInterface::class);
                $loader->method('getConfigObject')->willReturn(new Config([]));
                return $loader;
            })()
        );
        $recordLoader = $this->createMock(\VuFind\Record\Loader::class);
        $driver = new SymphonyFixture($recordLoader, null, $noRulesService);
        $driver->setConfig([]);
        $driver->init();
        $driver->policyLists = $this->driver->policyLists;

        $callInfo = $this->getCallInfo([
            $this->getItemInfo('I1', 'ART-VAULT', 'AREFERENCE'),
        ]);
        $items = $this->indexItems($driver->parseCallInfoPublic([$callInfo], '12345', true));
        $this->assertTrue($items['I1']['is_holdable']);
        $this->assertTrue($items['I1']['addLink']);
        $this->assertArrayNotHasKey('holdOverride', $items['I1']);
        $this->assertArrayNotHasKey('homeLocationID', $items['I1']);
    }

    /**
     * Test that items whose status is configured as unavailable are
     * forced unavailable without affecting their holdability.
     *
     * @return void
     */
    public function testParseCallInfoMarksUnavailableStatuses()
    {
        $callInfo = $this->getCallInfo([
            $this->getItemInfo('I1', 'MENDING', 'ABOOK', true),
            $this->getItemInfo('I3', 'GEN-CIRC', 'ABOOK', true),
        ]);
        $items = $this->indexItems(
            $this->driver->parseCallInfoPublic([$callInfo], '12345', true)
        );

        // MENDING item: status "Being fixed/mended" is configured unavailable:
        $this->assertFalse($items['I1']['availability']);
        $this->assertSame('recall', $items['I1']['holdtype']);
        $this->assertTrue($items['I1']['is_holdable']);

        // Control: unaffected item remains available:
        $this->assertTrue($items['I3']['availability']);
        $this->assertSame('hold', $items['I3']['holdtype']);
        $this->assertTrue($items['I3']['is_holdable']);
    }

    /**
     * Test that status rules alone (no location or type rules) still apply.
     *
     * @return void
     */
    public function testStatusRuleAppliesWithoutOtherRules()
    {
        $mainConfig = new Config([
            'unavailable_statuses' => ['statuses' => ['Being fixed/mended']],
        ]);
        $loader = $this->createMock(\VuFind\Config\ConfigManagerInterface::class);
        $loader->method('getConfigObject')->willReturn(new Config([]));
        $holdability = new Holdability($mainConfig, $loader);
        $this->assertTrue($holdability->hasHoldabilityRules());

        $recordLoader = $this->createMock(\VuFind\Record\Loader::class);
        $driver = new SymphonyFixture($recordLoader, null, $holdability);
        $driver->setConfig([]);
        $driver->init();
        $driver->policyLists = $this->driver->policyLists;

        $callInfo = $this->getCallInfo([
            $this->getItemInfo('I1', 'MENDING', 'ABOOK', true),
        ]);
        $items = $this->indexItems($driver->parseCallInfoPublic([$callInfo], '12345', true));
        $this->assertFalse($items['I1']['availability']);
        $this->assertTrue($items['I1']['is_holdable']);
        $this->assertArrayNotHasKey('holdOverride', $items['I1']);
    }

    /**
     * Test that a configured API whitelist constrains the pickup
     * location list end-to-end through the driver.
     *
     * @return void
     */
    public function testGetPickUpLocationsAppliesApiWhitelist()
    {
        $mainConfig = new Config([
            'non_holdable_material_types' => ['ItemTypeIDs' => []],
        ]);
        $iniConfig = new Config([
            'nonHoldableLoc' => ['ART-VAULT', 'ILL', 'STACKS'],
            'PickupLocations' => ['exclude' => ['MATERIALS', 'BLIND']],
        ]);
        $loader = $this->createMock(\VuFind\Config\ConfigManagerInterface::class);
        $loader->method('getConfigObject')->willReturnCallback(
            fn (string $path) => match ($path) {
                'nonholdablelocations.ini' => $iniConfig,
                'flp.ini' => new Config(
                    ['PickupLocations' => ['apiUrl' => 'http://example/api']]
                ),
                default => new Config([]),
            }
        );
        $downloader = $this->createMock(\VuFind\Http\CachingDownloader::class);
        $downloader->method('downloadJson')->willReturn(
            (object)['data' => [
                ['sirsi_id' => 'CENTRAL'],
                ['sirsi_id' => 'ANDORRA'],
            ]]
        );
        $holdability = new Holdability($mainConfig, $loader, $downloader);

        $recordLoader = $this->createMock(\VuFind\Record\Loader::class);
        $driver = new SymphonyFixture($recordLoader, null, $holdability);
        $driver->setConfig([]);
        $driver->init();
        $driver->policyLists = $this->driver->policyLists;
        // A branch allowed by static rules but absent from the API
        // whitelist must be filtered out:
        $driver->policyLists['LIBR'] += ['WELSHROAD' => 'Welsh Road Library'];

        $ids = array_column($driver->getPickUpLocations(), 'locationID');
        $this->assertEquals(['CENTRAL', 'ANDORRA'], $ids);
    }
}

/**
 * Fixture subclass providing canned policy lists and holdings so the
 * Free Library customizations can be exercised without SymWS access.
 *
 * @category VuFind
 * @package  Tests
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class SymphonyFixture extends FreeLibrarySymphony
{
    /**
     * Canned policy lists, keyed by policy type.
     *
     * @var array
     */
    public array $policyLists = [];

    /**
     * Canned holding items.
     *
     * @var array
     */
    public array $holdingItems = [];

    /**
     * Should getHolding throw an ILS exception?
     *
     * @var bool
     */
    public bool $throwInGetHolding = false;

    /**
     * Get a canned policy list.
     *
     * @param string $policyType Policy type
     *
     * @return array
     */
    protected function getPolicyList($policyType)
    {
        return $this->policyLists[$policyType] ?? [];
    }

    /**
     * Get canned holdings.
     *
     * @param string   $id      Record id
     * @param ?array   $patron  Patron data
     * @param array    $options Options
     *
     * @return array
     */
    public function getHolding($id, ?array $patron = null, array $options = [])
    {
        if ($this->throwInGetHolding) {
            throw new \VuFind\Exception\ILS('test failure');
        }
        return $this->holdingItems;
    }

    /**
     * Public wrapper for protected parseCallInfo().
     *
     * @param mixed  $callInfos  Call info objects
     * @param string $titleID    Title ID
     * @param bool   $is_holdable Is the title holdable per SymWS?
     * @param ?int   $bound_in   Bound-with parent ID
     *
     * @return array
     */
    public function parseCallInfoPublic(
        $callInfos,
        $titleID,
        $is_holdable = false,
        $bound_in = null
    ): array {
        return $this->parseCallInfo($callInfos, $titleID, $is_holdable, $bound_in);
    }
}

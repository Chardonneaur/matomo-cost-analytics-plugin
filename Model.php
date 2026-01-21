<?php

/**
 * CostAnalytics Model
 *
 * Database operations for cost data.
 */

namespace Piwik\Plugins\CostAnalytics;

use Piwik\Common;
use Piwik\Db;
use Piwik\DbHelper;

class Model
{
    public static $rawPrefix = 'cost_analytics';
    private $table;

    // Channel type constants matching Matomo's referrer types
    const CHANNEL_DIRECT = 'direct';
    const CHANNEL_WEBSITE = 'website';
    const CHANNEL_SEARCH = 'search';
    const CHANNEL_SOCIAL = 'social';
    const CHANNEL_CAMPAIGN = 'campaign';

    public static $channelTypes = [
        self::CHANNEL_DIRECT => 'Direct Entry',
        self::CHANNEL_WEBSITE => 'Websites',
        self::CHANNEL_SEARCH => 'Search Engines',
        self::CHANNEL_SOCIAL => 'Social Networks',
        self::CHANNEL_CAMPAIGN => 'Campaigns',
    ];

    public function __construct()
    {
        $this->table = Common::prefixTable(self::$rawPrefix);
    }

    /**
     * Get all costs for a site within a date range
     */
    public function getCosts($idSite, $dateStart, $dateEnd, $channelType = null)
    {
        $query = 'SELECT * FROM ' . $this->table . ' WHERE idsite = ? AND cost_date >= ? AND cost_date <= ? AND deleted = 0';
        $bind = [$idSite, $dateStart, $dateEnd];

        if ($channelType !== null) {
            $query .= ' AND channel_type = ?';
            $bind[] = $channelType;
        }

        $query .= ' ORDER BY cost_date ASC';

        return Db::fetchAll($query, $bind);
    }

    /**
     * Get aggregated costs by channel type
     */
    public function getCostsByChannel($idSite, $dateStart, $dateEnd)
    {
        $query = 'SELECT channel_type, SUM(cost_amount) as total_cost, currency
                  FROM ' . $this->table . '
                  WHERE idsite = ? AND cost_date >= ? AND cost_date <= ? AND deleted = 0
                  GROUP BY channel_type, currency
                  ORDER BY total_cost DESC';

        return Db::fetchAll($query, [$idSite, $dateStart, $dateEnd]);
    }

    /**
     * Get total cost for a specific channel type
     */
    public function getChannelCost($idSite, $channelType, $dateStart, $dateEnd)
    {
        $query = 'SELECT SUM(cost_amount) as total_cost
                  FROM ' . $this->table . '
                  WHERE idsite = ? AND channel_type = ? AND cost_date >= ? AND cost_date <= ? AND deleted = 0';

        $result = Db::fetchOne($query, [$idSite, $channelType, $dateStart, $dateEnd]);
        return $result ? (float)$result : 0.0;
    }

    /**
     * Get total cost for all channels
     */
    public function getTotalCost($idSite, $dateStart, $dateEnd)
    {
        $query = 'SELECT SUM(cost_amount) as total_cost
                  FROM ' . $this->table . '
                  WHERE idsite = ? AND cost_date >= ? AND cost_date <= ? AND deleted = 0';

        $result = Db::fetchOne($query, [$idSite, $dateStart, $dateEnd]);
        return $result ? (float)$result : 0.0;
    }

    /**
     * Insert a single cost entry
     */
    public function insertCost($idSite, $channelType, $costDate, $costAmount, $currency = 'USD', $campaignName = null, $description = null)
    {
        $data = [
            'idsite' => $idSite,
            'channel_type' => $channelType,
            'campaign_name' => $campaignName,
            'description' => $description,
            'cost_date' => $costDate,
            'cost_amount' => $costAmount,
            'currency' => $currency,
            'ts_created' => date('Y-m-d H:i:s'),
        ];

        Db::get()->insert($this->table, $data);
        return Db::get()->lastInsertId();
    }

    /**
     * Insert multiple cost entries (batch import)
     */
    public function insertCostBatch($costs)
    {
        $inserted = 0;
        foreach ($costs as $cost) {
            try {
                $this->insertCost(
                    $cost['idsite'],
                    $cost['channel_type'],
                    $cost['cost_date'],
                    $cost['cost_amount'],
                    $cost['currency'] ?? 'USD',
                    $cost['campaign_name'] ?? null,
                    $cost['description'] ?? null
                );
                $inserted++;
            } catch (\Exception $e) {
                // Log error but continue with other entries
                continue;
            }
        }
        return $inserted;
    }

    /**
     * Update a cost entry
     */
    public function updateCost($idCost, $data)
    {
        $data['ts_updated'] = date('Y-m-d H:i:s');
        Db::get()->update($this->table, $data, 'idcost = ' . (int)$idCost);
    }

    /**
     * Soft delete a cost entry
     */
    public function deleteCost($idCost)
    {
        Db::get()->update($this->table, ['deleted' => 1], 'idcost = ' . (int)$idCost);
    }

    /**
     * Delete all costs for a site and date range (for re-import)
     */
    public function deleteCostsForDateRange($idSite, $dateStart, $dateEnd, $channelType = null)
    {
        $query = 'UPDATE ' . $this->table . ' SET deleted = 1 WHERE idsite = ? AND cost_date >= ? AND cost_date <= ?';
        $bind = [$idSite, $dateStart, $dateEnd];

        if ($channelType !== null) {
            $query .= ' AND channel_type = ?';
            $bind[] = $channelType;
        }

        Db::query($query, $bind);
    }

    /**
     * Get a single cost entry
     */
    public function getCost($idCost)
    {
        $query = 'SELECT * FROM ' . $this->table . ' WHERE idcost = ? AND deleted = 0';
        return Db::fetchRow($query, [$idCost]);
    }

    /**
     * Get all costs for a site (for management view)
     */
    public function getAllCostsForSite($idSite, $limit = 100, $offset = 0, $orderBy = 'cost_date', $orderDir = 'DESC')
    {
        $allowedOrderBy = ['cost_date', 'channel_type', 'cost_amount', 'campaign_name', 'ts_created'];
        if (!in_array($orderBy, $allowedOrderBy)) {
            $orderBy = 'cost_date';
        }
        $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

        $query = 'SELECT * FROM ' . $this->table . ' WHERE idsite = ? AND deleted = 0 ORDER BY ' . $orderBy . ' ' . $orderDir . ' LIMIT ? OFFSET ?';
        return Db::fetchAll($query, [$idSite, (int)$limit, (int)$offset]);
    }

    /**
     * Count all costs for a site
     */
    public function countCostsForSite($idSite)
    {
        $query = 'SELECT COUNT(*) FROM ' . $this->table . ' WHERE idsite = ? AND deleted = 0';
        return (int)Db::fetchOne($query, [$idSite]);
    }

    /**
     * Check if channel type is valid
     */
    public static function isValidChannelType($channelType)
    {
        return array_key_exists($channelType, self::$channelTypes);
    }

    /**
     * Get channel type label
     */
    public static function getChannelLabel($channelType)
    {
        return self::$channelTypes[$channelType] ?? $channelType;
    }

    /**
     * Install database table
     */
    public static function install()
    {
        $tableDefinition = "`idcost` INT(11) NOT NULL AUTO_INCREMENT,
                  `idsite` INT(11) NOT NULL,
                  `channel_type` VARCHAR(50) NOT NULL,
                  `campaign_name` VARCHAR(255) DEFAULT NULL,
                  `description` TEXT DEFAULT NULL,
                  `cost_date` DATE NOT NULL,
                  `cost_amount` DECIMAL(15,4) NOT NULL DEFAULT 0,
                  `currency` VARCHAR(10) DEFAULT 'USD',
                  `ts_created` DATETIME DEFAULT NULL,
                  `ts_updated` DATETIME DEFAULT NULL,
                  `deleted` TINYINT(1) NOT NULL DEFAULT 0,
                  PRIMARY KEY (`idcost`),
                  INDEX `idx_site_date` (`idsite`, `cost_date`),
                  INDEX `idx_channel` (`channel_type`),
                  INDEX `idx_site_channel_date` (`idsite`, `channel_type`, `cost_date`)";

        DbHelper::createTable(self::$rawPrefix, $tableDefinition);

        // Add description column if upgrading from older version
        self::addDescriptionColumnIfMissing();
    }

    /**
     * Add description column for existing installations
     */
    private static function addDescriptionColumnIfMissing()
    {
        $table = Common::prefixTable(self::$rawPrefix);
        try {
            $columns = Db::fetchAll("SHOW COLUMNS FROM $table LIKE 'description'");
            if (empty($columns)) {
                Db::exec("ALTER TABLE $table ADD COLUMN `description` TEXT DEFAULT NULL AFTER `campaign_name`");
            }
        } catch (\Exception $e) {
            // Table might not exist yet, ignore
        }
    }

    /**
     * Uninstall database table
     */
    public static function uninstall()
    {
        Db::dropTables(Common::prefixTable(self::$rawPrefix));
    }
}

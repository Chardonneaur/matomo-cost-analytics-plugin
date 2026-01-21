<?php

/**
 * CostAnalytics API
 *
 * API methods for cost data operations and ROI calculations.
 */

namespace Piwik\Plugins\CostAnalytics;

use Piwik\Archive;
use Piwik\Common;
use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Date;
use Piwik\Period;
use Piwik\Period\Factory as PeriodFactory;
use Piwik\Piwik;
use Piwik\Plugin\API as PluginAPI;
use Piwik\Plugins\Goals\API as GoalsAPI;
use Piwik\Site;

class API extends PluginAPI
{
    /**
     * @var Model
     */
    private $model;

    public function __construct()
    {
        $this->model = new Model();
    }

    /**
     * Get costs by channel for a given period
     *
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool $includeROI
     * @return DataTable
     */
    public function getCostsByChannel($idSite, $period, $date, $includeROI = true)
    {
        Piwik::checkUserHasViewAccess($idSite);

        $dateRange = $this->getDateRange($idSite, $period, $date);
        $costs = $this->model->getCostsByChannel($idSite, $dateRange['start'], $dateRange['end']);

        // Get channels that have costs
        $channelsWithCosts = array_column($costs, 'channel_type');

        // Get all channel revenues at once
        $channelRevenues = [];
        if ($includeROI) {
            $channelRevenues = $this->getAllChannelRevenues($idSite, $period, $date, $channelsWithCosts);
        }

        $dataTable = new DataTable();

        foreach ($costs as $cost) {
            $row = new Row();
            $row->setColumn('label', Model::getChannelLabel($cost['channel_type']));
            $row->setColumn('channel_type', $cost['channel_type']);
            $row->setColumn('cost', (float)$cost['total_cost']);
            $row->setColumn('currency', $cost['currency']);

            if ($includeROI) {
                $revenue = $channelRevenues[$cost['channel_type']] ?? 0;
                $roi = $this->calculateROI((float)$cost['total_cost'], $revenue);
                $row->setColumn('revenue', $revenue);
                $row->setColumn('roi', $roi);
                $row->setColumn('profit', $revenue - (float)$cost['total_cost']);
            }

            $dataTable->addRow($row);
        }

        return $dataTable;
    }

    /**
     * Get cost data for a specific date range
     *
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param string|null $channelType
     * @return DataTable
     */
    public function getCosts($idSite, $period, $date, $channelType = null)
    {
        Piwik::checkUserHasViewAccess($idSite);

        $dateRange = $this->getDateRange($idSite, $period, $date);
        $costs = $this->model->getCosts($idSite, $dateRange['start'], $dateRange['end'], $channelType);

        $dataTable = new DataTable();

        foreach ($costs as $cost) {
            $row = new Row();
            $row->setColumn('label', $cost['cost_date']);
            $row->setColumn('channel_type', $cost['channel_type']);
            $row->setColumn('channel_label', Model::getChannelLabel($cost['channel_type']));
            $row->setColumn('campaign_name', $cost['campaign_name']);
            $row->setColumn('cost', (float)$cost['cost_amount']);
            $row->setColumn('currency', $cost['currency']);
            $dataTable->addRow($row);
        }

        return $dataTable;
    }

    /**
     * Get ROI summary for all channels
     *
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @return DataTable
     */
    public function getROISummary($idSite, $period, $date)
    {
        Piwik::checkUserHasViewAccess($idSite);

        $dateRange = $this->getDateRange($idSite, $period, $date);
        $totalCost = $this->model->getTotalCost($idSite, $dateRange['start'], $dateRange['end']);
        $totalRevenue = $this->getTotalGoalRevenue($idSite, $period, $date);
        $roi = $this->calculateROI($totalCost, $totalRevenue);

        $dataTable = new DataTable();
        $row = new Row();
        $row->setColumn('label', 'Summary');
        $row->setColumn('total_cost', $totalCost);
        $row->setColumn('total_revenue', $totalRevenue);
        $row->setColumn('profit', $totalRevenue - $totalCost);
        $row->setColumn('roi', $roi);
        $dataTable->addRow($row);

        return $dataTable;
    }

    /**
     * Add a cost entry
     *
     * @param int $idSite
     * @param string $channelType
     * @param string $costDate
     * @param float $costAmount
     * @param string $currency
     * @param string|null $campaignName
     * @param string|null $description
     * @return int
     */
    public function addCost($idSite, $channelType, $costDate, $costAmount, $currency = 'USD', $campaignName = null, $description = null)
    {
        Piwik::checkUserHasAdminAccess($idSite);

        if (!Model::isValidChannelType($channelType)) {
            throw new \Exception('Invalid channel type. Valid types: ' . implode(', ', array_keys(Model::$channelTypes)));
        }

        $costDate = Date::factory($costDate)->toString('Y-m-d');
        $costAmount = (float)$costAmount;

        if ($costAmount < 0) {
            throw new \Exception('Cost amount cannot be negative');
        }

        return $this->model->insertCost($idSite, $channelType, $costDate, $costAmount, $currency, $campaignName, $description);
    }

    /**
     * Update a cost entry
     *
     * @param int $idSite
     * @param int $idCost
     * @param string|null $channelType
     * @param string|null $costDate
     * @param float|null $costAmount
     * @param string|null $currency
     * @param string|null $campaignName
     * @param string|null $description
     * @return bool
     */
    public function updateCost($idSite, $idCost, $channelType = null, $costDate = null, $costAmount = null, $currency = null, $campaignName = null, $description = null)
    {
        Piwik::checkUserHasAdminAccess($idSite);

        $cost = $this->model->getCost($idCost);
        if (!$cost || $cost['idsite'] != $idSite) {
            throw new \Exception('Cost entry not found');
        }

        $data = [];

        if ($channelType !== null) {
            if (!Model::isValidChannelType($channelType)) {
                throw new \Exception('Invalid channel type. Valid types: ' . implode(', ', array_keys(Model::$channelTypes)));
            }
            $data['channel_type'] = $channelType;
        }

        if ($costDate !== null) {
            $data['cost_date'] = Date::factory($costDate)->toString('Y-m-d');
        }

        if ($costAmount !== null) {
            $costAmount = (float)$costAmount;
            if ($costAmount < 0) {
                throw new \Exception('Cost amount cannot be negative');
            }
            $data['cost_amount'] = $costAmount;
        }

        if ($currency !== null) {
            $data['currency'] = $currency;
        }

        if ($campaignName !== null) {
            $data['campaign_name'] = $campaignName ?: null;
        }

        if ($description !== null) {
            $data['description'] = $description ?: null;
        }

        if (!empty($data)) {
            $this->model->updateCost($idCost, $data);
        }

        return true;
    }

    /**
     * Get all costs for a site (for management view)
     *
     * @param int $idSite
     * @param int $limit
     * @param int $offset
     * @return DataTable
     */
    public function getAllCosts($idSite, $limit = 100, $offset = 0)
    {
        Piwik::checkUserHasAdminAccess($idSite);

        $costs = $this->model->getAllCostsForSite($idSite, $limit, $offset);
        $totalCount = $this->model->countCostsForSite($idSite);

        $dataTable = new DataTable();
        $dataTable->setMetadata('total_count', $totalCount);

        foreach ($costs as $cost) {
            $row = new Row();
            $row->setColumn('idcost', $cost['idcost']);
            $row->setColumn('label', $cost['cost_date']);
            $row->setColumn('channel_type', $cost['channel_type']);
            $row->setColumn('channel_label', Model::getChannelLabel($cost['channel_type']));
            $row->setColumn('campaign_name', $cost['campaign_name']);
            $row->setColumn('description', $cost['description']);
            $row->setColumn('cost', (float)$cost['cost_amount']);
            $row->setColumn('currency', $cost['currency']);
            $row->setColumn('ts_created', $cost['ts_created']);
            $dataTable->addRow($row);
        }

        return $dataTable;
    }

    /**
     * Import costs from CSV data
     *
     * Expected CSV format:
     * channel_type,cost_date,cost_amount,currency,campaign_name,description (optional columns)
     *
     * @param int $idSite
     * @param string $csvData
     * @param bool $deleteExisting Delete existing costs for the same dates
     * @return array
     */
    public function importCostsFromCSV($idSite, $csvData, $deleteExisting = false)
    {
        Piwik::checkUserHasAdminAccess($idSite);

        $lines = explode("\n", trim($csvData));
        $header = str_getcsv(array_shift($lines));

        // Normalize header names
        $header = array_map(function ($col) {
            return strtolower(trim($col));
        }, $header);

        $requiredColumns = ['channel_type', 'cost_date', 'cost_amount'];
        foreach ($requiredColumns as $col) {
            if (!in_array($col, $header)) {
                throw new \Exception("Missing required column: $col");
            }
        }

        $costs = [];
        $errors = [];
        $lineNum = 1;

        foreach ($lines as $line) {
            $lineNum++;
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $data = str_getcsv($line);
            if (count($data) < count($header)) {
                $data = array_pad($data, count($header), '');
            }
            $row = array_combine($header, $data);

            // Validate channel type
            if (!Model::isValidChannelType($row['channel_type'])) {
                $errors[] = "Line $lineNum: Invalid channel type '{$row['channel_type']}'";
                continue;
            }

            // Validate date
            try {
                $costDate = Date::factory($row['cost_date'])->toString('Y-m-d');
            } catch (\Exception $e) {
                $errors[] = "Line $lineNum: Invalid date format '{$row['cost_date']}'";
                continue;
            }

            // Validate amount
            $costAmount = (float)$row['cost_amount'];
            if ($costAmount < 0) {
                $errors[] = "Line $lineNum: Cost amount cannot be negative";
                continue;
            }

            $costs[] = [
                'idsite' => $idSite,
                'channel_type' => $row['channel_type'],
                'cost_date' => $costDate,
                'cost_amount' => $costAmount,
                'currency' => $row['currency'] ?? 'USD',
                'campaign_name' => $row['campaign_name'] ?? null,
                'description' => $row['description'] ?? null,
            ];
        }

        if (empty($costs)) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => $errors,
            ];
        }

        // Delete existing costs if requested
        if ($deleteExisting && !empty($costs)) {
            $dates = array_column($costs, 'cost_date');
            $minDate = min($dates);
            $maxDate = max($dates);
            $this->model->deleteCostsForDateRange($idSite, $minDate, $maxDate);
        }

        $inserted = $this->model->insertCostBatch($costs);

        return [
            'success' => true,
            'imported' => $inserted,
            'total_rows' => count($costs),
            'errors' => $errors,
        ];
    }

    /**
     * Delete a cost entry
     *
     * @param int $idSite
     * @param int $idCost
     * @return bool
     */
    public function deleteCost($idSite, $idCost)
    {
        Piwik::checkUserHasAdminAccess($idSite);

        $cost = $this->model->getCost($idCost);
        if (!$cost || $cost['idsite'] != $idSite) {
            throw new \Exception('Cost entry not found');
        }

        $this->model->deleteCost($idCost);
        return true;
    }

    /**
     * Get available channel types
     *
     * @return array
     */
    public function getChannelTypes()
    {
        return Model::$channelTypes;
    }

    /**
     * Calculate ROI
     *
     * @param float $cost
     * @param float $revenue
     * @return float
     */
    private function calculateROI($cost, $revenue)
    {
        if ($cost == 0) {
            return $revenue > 0 ? 100 : 0;
        }
        return round((($revenue - $cost) / $cost) * 100, 2);
    }

    /**
     * Get date range from period and date
     *
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @return array
     */
    private function getDateRange($idSite, $period, $date)
    {
        $site = new Site($idSite);
        $timezone = $site->getTimezone();

        $periodObj = PeriodFactory::build($period, $date, $timezone);

        return [
            'start' => $periodObj->getDateStart()->toString('Y-m-d'),
            'end' => $periodObj->getDateEnd()->toString('Y-m-d'),
        ];
    }

    /**
     * Get total goal revenue for a site
     *
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @return float
     */
    private function getTotalGoalRevenue($idSite, $period, $date)
    {
        try {
            $goalsApi = GoalsAPI::getInstance();
            $goals = $goalsApi->get($idSite, $period, $date);

            if ($goals instanceof DataTable) {
                $row = $goals->getFirstRow();
                if ($row) {
                    return (float)$row->getColumn('revenue') ?: 0;
                }
            }
        } catch (\Exception $e) {
            // Goals plugin might not be available or configured
        }

        return 0;
    }

    /**
     * Get revenue for all channel types at once
     *
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param array $channelsWithCosts Channels that have costs (to distribute revenue among)
     * @return array Associative array of channel_type => revenue
     */
    private function getAllChannelRevenues($idSite, $period, $date, $channelsWithCosts = [])
    {
        // Get total revenue first - this is what works in ROI Summary
        $totalRevenue = $this->getTotalGoalRevenue($idSite, $period, $date);

        $revenues = [
            Model::CHANNEL_DIRECT => 0,
            Model::CHANNEL_SEARCH => 0,
            Model::CHANNEL_SOCIAL => 0,
            Model::CHANNEL_WEBSITE => 0,
            Model::CHANNEL_CAMPAIGN => 0,
        ];

        if ($totalRevenue <= 0) {
            return $revenues;
        }

        $distributed = false;

        try {
            // Get visits by referrer type to distribute revenue proportionally
            $referrersApi = \Piwik\Plugins\Referrers\API::getInstance();
            $dataTable = $referrersApi->getReferrerType($idSite, $period, $date);

            if ($dataTable instanceof DataTable && $dataTable->getRowsCount() > 0) {
                $totalVisits = 0;
                $visitsByChannel = [];

                foreach ($dataTable->getRows() as $row) {
                    $channelType = $this->mapRowToChannelType($row);

                    if ($channelType !== null) {
                        $visits = (int)$row->getColumn('nb_visits');
                        $visitsByChannel[$channelType] = ($visitsByChannel[$channelType] ?? 0) + $visits;
                        $totalVisits += $visits;
                    }
                }

                // Distribute revenue proportionally by visits
                if ($totalVisits > 0) {
                    foreach ($visitsByChannel as $channelType => $visits) {
                        $revenues[$channelType] = round(($visits / $totalVisits) * $totalRevenue, 2);
                    }
                    $distributed = true;
                }
            }
        } catch (\Exception $e) {
            // Will use fallback below
        }

        // Fallback: if no distribution happened, distribute among channels with costs only
        if (!$distributed) {
            if (!empty($channelsWithCosts)) {
                // Distribute revenue equally among channels that have costs
                $channelCount = count($channelsWithCosts);
                $perChannel = round($totalRevenue / $channelCount, 2);
                foreach ($channelsWithCosts as $channelType) {
                    $revenues[$channelType] = $perChannel;
                }
            } else {
                // No channels with costs specified, distribute equally among all
                $channelCount = count($revenues);
                $perChannel = round($totalRevenue / $channelCount, 2);
                foreach ($revenues as $key => $val) {
                    $revenues[$key] = $perChannel;
                }
            }
        }

        return $revenues;
    }

    /**
     * Map a DataTable row to our channel type
     *
     * @param Row $row
     * @return string|null
     */
    private function mapRowToChannelType(Row $row)
    {
        // Try to get referrer_type from metadata first
        $refererType = $row->getMetadata('referer_type');
        if ($refererType === null || $refererType === false) {
            // Try label as numeric ID
            $label = $row->getColumn('label');
            if (is_numeric($label)) {
                $refererType = (int)$label;
            } else {
                // Map by label name
                $refererType = $this->mapLabelToRefererType($label);
            }
        }

        // Map Matomo's referrer type IDs to our channel types
        $typeIdToChannel = [
            Common::REFERRER_TYPE_DIRECT_ENTRY => Model::CHANNEL_DIRECT,
            Common::REFERRER_TYPE_SEARCH_ENGINE => Model::CHANNEL_SEARCH,
            Common::REFERRER_TYPE_SOCIAL_NETWORK => Model::CHANNEL_SOCIAL,
            Common::REFERRER_TYPE_WEBSITE => Model::CHANNEL_WEBSITE,
            Common::REFERRER_TYPE_CAMPAIGN => Model::CHANNEL_CAMPAIGN,
        ];

        return $typeIdToChannel[$refererType] ?? null;
    }

    /**
     * Map translated label to referrer type ID
     *
     * @param string $label
     * @return int|null
     */
    private function mapLabelToRefererType($label)
    {
        $label = strtolower(trim($label));

        // Common label patterns in different languages
        $patterns = [
            // Direct
            'direct' => Common::REFERRER_TYPE_DIRECT_ENTRY,
            'direct entry' => Common::REFERRER_TYPE_DIRECT_ENTRY,
            'entrée directe' => Common::REFERRER_TYPE_DIRECT_ENTRY,
            'direkt' => Common::REFERRER_TYPE_DIRECT_ENTRY,

            // Search
            'search' => Common::REFERRER_TYPE_SEARCH_ENGINE,
            'search engine' => Common::REFERRER_TYPE_SEARCH_ENGINE,
            'search engines' => Common::REFERRER_TYPE_SEARCH_ENGINE,
            'moteur de recherche' => Common::REFERRER_TYPE_SEARCH_ENGINE,
            'moteurs de recherche' => Common::REFERRER_TYPE_SEARCH_ENGINE,

            // Website
            'website' => Common::REFERRER_TYPE_WEBSITE,
            'websites' => Common::REFERRER_TYPE_WEBSITE,
            'site web' => Common::REFERRER_TYPE_WEBSITE,
            'sites web' => Common::REFERRER_TYPE_WEBSITE,

            // Social
            'social' => Common::REFERRER_TYPE_SOCIAL_NETWORK,
            'social network' => Common::REFERRER_TYPE_SOCIAL_NETWORK,
            'social networks' => Common::REFERRER_TYPE_SOCIAL_NETWORK,
            'réseau social' => Common::REFERRER_TYPE_SOCIAL_NETWORK,
            'réseaux sociaux' => Common::REFERRER_TYPE_SOCIAL_NETWORK,

            // Campaign
            'campaign' => Common::REFERRER_TYPE_CAMPAIGN,
            'campaigns' => Common::REFERRER_TYPE_CAMPAIGN,
            'campagne' => Common::REFERRER_TYPE_CAMPAIGN,
            'campagnes' => Common::REFERRER_TYPE_CAMPAIGN,
        ];

        if (isset($patterns[$label])) {
            return $patterns[$label];
        }

        // Partial match
        foreach ($patterns as $pattern => $type) {
            if (strpos($label, $pattern) !== false) {
                return $type;
            }
        }

        return null;
    }
}

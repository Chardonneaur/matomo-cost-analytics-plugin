<?php

/**
 * Profit Metric
 *
 * Calculates Revenue - Cost
 */

namespace Piwik\Plugins\CostAnalytics\Columns\Metrics;

use Piwik\Common;
use Piwik\DataTable\Row;
use Piwik\Metrics\Formatter;
use Piwik\Plugin\ProcessedMetric;
use Piwik\Columns\Dimension;

class Profit extends ProcessedMetric
{
    public function getName()
    {
        return 'profit';
    }

    public function getTranslatedName()
    {
        return 'Profit';
    }

    public function compute(Row $row)
    {
        $cost = $this->getMetric($row, 'cost');
        $revenue = $this->getMetric($row, 'revenue');

        return $revenue - $cost;
    }

    public function getDependentMetrics()
    {
        return ['cost', 'revenue'];
    }

    public function format($value, Formatter $formatter)
    {
        $idSite = Common::getRequestVar('idSite', 1, 'int');
        return $formatter->getPrettyMoney($value, $idSite);
    }

    public function getSemanticType(): ?string
    {
        return Dimension::TYPE_MONEY;
    }
}

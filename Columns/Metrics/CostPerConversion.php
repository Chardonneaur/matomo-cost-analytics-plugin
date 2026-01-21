<?php

/**
 * Cost Per Conversion Metric
 *
 * Calculates Cost / Conversions
 */

namespace Piwik\Plugins\CostAnalytics\Columns\Metrics;

use Piwik\Common;
use Piwik\DataTable\Row;
use Piwik\Metrics\Formatter;
use Piwik\Plugin\ProcessedMetric;
use Piwik\Columns\Dimension;

class CostPerConversion extends ProcessedMetric
{
    public function getName()
    {
        return 'cost_per_conversion';
    }

    public function getTranslatedName()
    {
        return 'Cost per Conversion';
    }

    public function compute(Row $row)
    {
        $cost = $this->getMetric($row, 'cost');
        $conversions = $this->getMetric($row, 'nb_conversions');

        if ($conversions == 0) {
            return 0;
        }

        return $cost / $conversions;
    }

    public function getDependentMetrics()
    {
        return ['cost', 'nb_conversions'];
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

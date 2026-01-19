<?php

/**
 * ROI Metric
 *
 * Calculates Return on Investment: ((Revenue - Cost) / Cost) * 100
 */

namespace Piwik\Plugins\CostAnalytics\Columns\Metrics;

use Piwik\DataTable\Row;
use Piwik\Metrics\Formatter;
use Piwik\Plugin\ProcessedMetric;
use Piwik\Columns\Dimension;

class ROI extends ProcessedMetric
{
    public function getName()
    {
        return 'roi';
    }

    public function getTranslatedName()
    {
        return 'ROI %';
    }

    public function compute(Row $row)
    {
        $cost = $this->getMetric($row, 'cost');
        $revenue = $this->getMetric($row, 'revenue');

        if ($cost == 0) {
            return $revenue > 0 ? 100 : 0;
        }

        return (($revenue - $cost) / $cost) * 100;
    }

    public function getDependentMetrics()
    {
        return ['cost', 'revenue'];
    }

    public function format($value, Formatter $formatter)
    {
        return $formatter->getPrettyNumber($value, 2) . '%';
    }

    public function getSemanticType(): ?string
    {
        return Dimension::TYPE_PERCENT;
    }
}

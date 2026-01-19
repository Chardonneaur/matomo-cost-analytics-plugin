<?php

/**
 * CostAnalytics Base Report
 */

namespace Piwik\Plugins\CostAnalytics\Reports;

use Piwik\Plugin\Report;

abstract class Base extends Report
{
    protected function init()
    {
        $this->categoryId = 'Referrers_Referrers';
        $this->subcategoryId = 'CostAnalytics_CostROI';
    }
}

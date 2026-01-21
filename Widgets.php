<?php

/**
 * CostAnalytics Widgets
 *
 * Widget definitions for dashboard integration.
 */

namespace Piwik\Plugins\CostAnalytics;

use Piwik\Piwik;
use Piwik\Widget\Widget;
use Piwik\Widget\WidgetConfig;

class Widgets extends Widget
{
    public static function configure(WidgetConfig $config)
    {
        $config->setCategoryId('Referrers_Referrers');
        $config->setSubcategoryId('CostAnalytics_CostROI');
        $config->setName('CostAnalytics_CostByChannel');
        $config->setModule('CostAnalytics');
        $config->setAction('getCostsByChannel');
        $config->setOrder(1);
    }
}

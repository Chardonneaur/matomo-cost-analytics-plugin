<?php

/**
 * Cost by Channel Report
 *
 * Shows cost and ROI metrics for each marketing channel.
 */

namespace Piwik\Plugins\CostAnalytics\Reports;

use Piwik\Piwik;
use Piwik\Plugin\ViewDataTable;
use Piwik\Plugins\CostAnalytics\Columns\Metrics\ROI;
use Piwik\Plugins\CostAnalytics\Columns\Metrics\Profit;

class GetCostsByChannel extends Base
{
    protected function init()
    {
        parent::init();

        $this->name = Piwik::translate('CostAnalytics_CostByChannel');
        $this->documentation = Piwik::translate('CostAnalytics_CostByChannelDocumentation');
        $this->order = 1;
        $this->metrics = ['cost', 'revenue', 'profit', 'roi'];
        $this->processedMetrics = [new ROI(), new Profit()];
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->columns_to_display = ['label', 'cost', 'revenue', 'profit', 'roi'];
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_insights = false;

        $view->config->addTranslation('label', Piwik::translate('CostAnalytics_Channel'));
        $view->config->addTranslation('cost', Piwik::translate('CostAnalytics_Cost'));
        $view->config->addTranslation('revenue', Piwik::translate('CostAnalytics_Revenue'));
        $view->config->addTranslation('profit', Piwik::translate('CostAnalytics_Profit'));
        $view->config->addTranslation('roi', Piwik::translate('CostAnalytics_ROI'));
    }
}

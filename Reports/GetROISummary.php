<?php

/**
 * ROI Summary Report
 *
 * Shows overall ROI summary across all channels.
 */

namespace Piwik\Plugins\CostAnalytics\Reports;

use Piwik\Piwik;
use Piwik\Plugin\ViewDataTable;

class GetROISummary extends Base
{
    protected function init()
    {
        parent::init();

        $this->name = Piwik::translate('CostAnalytics_ROISummary');
        $this->documentation = Piwik::translate('CostAnalytics_ROISummaryDocumentation');
        $this->order = 2;
        $this->metrics = ['total_cost', 'total_revenue', 'profit', 'roi'];
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->columns_to_display = ['label', 'total_cost', 'total_revenue', 'profit', 'roi'];
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_insights = false;
        $view->config->show_footer = false;
        $view->config->show_limit_control = false;

        $view->config->addTranslation('label', Piwik::translate('CostAnalytics_Summary'));
        $view->config->addTranslation('total_cost', Piwik::translate('CostAnalytics_TotalCost'));
        $view->config->addTranslation('total_revenue', Piwik::translate('CostAnalytics_TotalRevenue'));
        $view->config->addTranslation('profit', Piwik::translate('CostAnalytics_Profit'));
        $view->config->addTranslation('roi', Piwik::translate('CostAnalytics_ROI'));
    }
}

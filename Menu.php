<?php

/**
 * CostAnalytics Menu
 *
 * Menu entries for the plugin.
 */

namespace Piwik\Plugins\CostAnalytics;

use Piwik\Menu\MenuAdmin;
use Piwik\Menu\MenuReporting;
use Piwik\Piwik;
use Piwik\Plugin\Menu as PluginMenu;

class Menu extends PluginMenu
{
    public function configureAdminMenu(MenuAdmin $menu)
    {
        if (Piwik::hasUserSuperUserAccess()) {
            $menu->addMeasurableItem(
                'CostAnalytics_CostManagement',
                $this->urlForAction('index'),
                $order = 50
            );
        }
    }

    public function configureReportingMenu(MenuReporting $menu)
    {
        $menu->addItem(
            'Referrers_Referrers',
            'CostAnalytics_CostROI',
            $this->urlForAction('viewCosts'),
            $order = 50
        );
    }
}

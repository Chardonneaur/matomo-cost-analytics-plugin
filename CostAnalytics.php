<?php

/**
 * CostAnalytics Plugin
 *
 * Import cost data via CSV and calculate ROI metrics for marketing channels.
 */

namespace Piwik\Plugins\CostAnalytics;

use Piwik\Plugin;

class CostAnalytics extends Plugin
{
    public function registerEvents()
    {
        return [
            'AssetManager.getJavaScriptFiles' => 'getJsFiles',
            'AssetManager.getStylesheetFiles' => 'getStylesheetFiles',
            'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys',
        ];
    }

    public function install()
    {
        Model::install();
    }

    public function uninstall()
    {
        Model::uninstall();
    }

    public function activate()
    {
        Model::install();
    }

    public function getJsFiles(&$jsFiles)
    {
        $jsFiles[] = 'plugins/CostAnalytics/javascripts/costAnalytics.js';
    }

    public function getStylesheetFiles(&$stylesheets)
    {
        $stylesheets[] = 'plugins/CostAnalytics/stylesheets/costAnalytics.less';
    }

    public function getClientSideTranslationKeys(&$translationKeys)
    {
        $translationKeys[] = 'CostAnalytics_CostImport';
        $translationKeys[] = 'CostAnalytics_ROI';
        $translationKeys[] = 'CostAnalytics_Cost';
        $translationKeys[] = 'CostAnalytics_ImportSuccess';
        $translationKeys[] = 'CostAnalytics_ImportError';
    }
}

<?php

/**
 * CostAnalytics Controller
 *
 * UI controller for cost data management and CSV import.
 */

namespace Piwik\Plugins\CostAnalytics;

use Piwik\Common;
use Piwik\Notification;
use Piwik\Piwik;
use Piwik\Plugin\Controller as PluginController;

class Controller extends PluginController
{
    /**
     * Main cost import page
     */
    public function index()
    {
        Piwik::checkUserHasAdminAccess($this->idSite);

        return $this->renderTemplate('index', [
            'channelTypes' => Model::$channelTypes,
            'idSite' => $this->idSite,
        ]);
    }

    /**
     * CSV import form
     */
    public function importCost()
    {
        Piwik::checkUserHasAdminAccess($this->idSite);

        return $this->renderTemplate('importCost', [
            'channelTypes' => Model::$channelTypes,
            'idSite' => $this->idSite,
        ]);
    }

    /**
     * Handle CSV upload
     */
    public function uploadCsv()
    {
        Piwik::checkUserHasAdminAccess($this->idSite);

        $notification = null;

        try {
            if (!isset($_FILES['cost_csv']) || $_FILES['cost_csv']['error'] !== UPLOAD_ERR_OK) {
                throw new \Exception(Piwik::translate('CostAnalytics_NoFileUploaded'));
            }

            $csvFile = $_FILES['cost_csv']['tmp_name'];
            $csvData = file_get_contents($csvFile);

            if (empty($csvData)) {
                throw new \Exception(Piwik::translate('CostAnalytics_EmptyFile'));
            }

            $deleteExisting = Common::getRequestVar('delete_existing', 0, 'int') === 1;

            $api = API::getInstance();
            $result = $api->importCostsFromCSV($this->idSite, $csvData, $deleteExisting);

            if ($result['success']) {
                $message = Piwik::translate('CostAnalytics_ImportSuccess', [$result['imported']]);
                if (!empty($result['errors'])) {
                    $message .= ' ' . Piwik::translate('CostAnalytics_ImportWarnings', [count($result['errors'])]);
                }
                $notification = new Notification($message);
                $notification->context = Notification::CONTEXT_SUCCESS;
            } else {
                $message = Piwik::translate('CostAnalytics_ImportFailed');
                if (!empty($result['errors'])) {
                    $message .= ': ' . implode(', ', array_slice($result['errors'], 0, 3));
                }
                $notification = new Notification($message);
                $notification->context = Notification::CONTEXT_ERROR;
            }
        } catch (\Exception $e) {
            $notification = new Notification($e->getMessage());
            $notification->context = Notification::CONTEXT_ERROR;
        }

        if ($notification) {
            Notification\Manager::notify('CostAnalytics_ImportResult', $notification);
        }

        $this->redirectToIndex('CostAnalytics', 'importCost');
    }

    /**
     * Manual cost entry form
     */
    public function addCost()
    {
        Piwik::checkUserHasAdminAccess($this->idSite);

        return $this->renderTemplate('addCost', [
            'channelTypes' => Model::$channelTypes,
            'idSite' => $this->idSite,
        ]);
    }

    /**
     * Handle manual cost entry
     */
    public function saveCost()
    {
        Piwik::checkUserHasAdminAccess($this->idSite);

        $notification = null;

        try {
            $channelType = Common::getRequestVar('channel_type', '', 'string');
            $costDate = Common::getRequestVar('cost_date', '', 'string');
            $costAmount = Common::getRequestVar('cost_amount', 0, 'float');
            $currency = Common::getRequestVar('currency', 'USD', 'string');
            $campaignName = Common::getRequestVar('campaign_name', '', 'string');

            if (empty($channelType) || empty($costDate) || $costAmount <= 0) {
                throw new \Exception(Piwik::translate('CostAnalytics_InvalidInput'));
            }

            $api = API::getInstance();
            $api->addCost($this->idSite, $channelType, $costDate, $costAmount, $currency, $campaignName ?: null);

            $notification = new Notification(Piwik::translate('CostAnalytics_CostAdded'));
            $notification->context = Notification::CONTEXT_SUCCESS;
        } catch (\Exception $e) {
            $notification = new Notification($e->getMessage());
            $notification->context = Notification::CONTEXT_ERROR;
        }

        if ($notification) {
            Notification\Manager::notify('CostAnalytics_SaveResult', $notification);
        }

        $this->redirectToIndex('CostAnalytics', 'addCost');
    }

    /**
     * View costs report
     */
    public function viewCosts()
    {
        Piwik::checkUserHasViewAccess($this->idSite);

        return $this->renderTemplate('viewCosts', [
            'idSite' => $this->idSite,
        ]);
    }
}

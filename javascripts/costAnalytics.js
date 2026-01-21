/**
 * CostAnalytics JavaScript
 */
(function () {
    // Utility functions for CostAnalytics plugin
    window.CostAnalytics = window.CostAnalytics || {};

    /**
     * Format currency value
     */
    CostAnalytics.formatCurrency = function (value, currency) {
        currency = currency || 'USD';
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency
        }).format(value);
    };

    /**
     * Format ROI percentage
     */
    CostAnalytics.formatROI = function (value) {
        var formatted = parseFloat(value).toFixed(2) + '%';
        if (value > 0) {
            return '+' + formatted;
        }
        return formatted;
    };

    /**
     * Calculate ROI from cost and revenue
     */
    CostAnalytics.calculateROI = function (cost, revenue) {
        if (cost === 0) {
            return revenue > 0 ? 100 : 0;
        }
        return ((revenue - cost) / cost) * 100;
    };

})();

# Changelog

All notable changes to the CostAnalytics plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2025-01-21

### Added
- **Manage Costs**: New page to view, edit, and delete all imported cost entries
- **Description field**: Added optional description field to cost entries for notes and details
- New API methods:
  - `updateCost` - Update an existing cost entry
  - `getAllCosts` - Get all costs for a site (with pagination)
- Edit cost form with pre-filled values
- Delete cost action with confirmation dialog
- Filter costs by website in the management view

### Changed
- CSV import now supports optional `description` column
- Manual cost entry form now includes description textarea
- Database schema updated to include description column (auto-migrated on plugin activation)
- Updated example CSV to include description field

### Database
- Added `description` TEXT column to the cost_analytics table
- Automatic migration for existing installations

## [1.1.0] - 2025-01-21

### Added
- **Per-website import**: Added a "Target Website" dropdown selector to both CSV import and manual cost entry forms
- Users can now import costs to any website they have admin access to, without switching sites
- New translation strings for the website selector UI

### Changed
- CSV import form now includes website selector as the first field
- Manual cost entry form now includes website selector as the first field
- Controller validates admin access to the selected target site before processing

### Security
- Added permission check to verify user has admin access to the target website before importing costs

## [1.0.0] - 2025-01-19

### Added
- Initial release
- CSV import functionality for bulk cost data
- Manual cost entry form
- Channel mapping to Matomo's acquisition channels (Direct, Search, Social, Campaigns, Websites)
- ROI calculation based on goal revenue
- Cost by Channel report in Acquisition section
- ROI Summary report
- API methods for programmatic access:
  - `getCostsByChannel` - Get costs aggregated by channel
  - `getCosts` - Get raw cost data
  - `getROISummary` - Get overall ROI metrics
  - `addCost` - Add a single cost entry
  - `importCostsFromCSV` - Import costs from CSV data
  - `deleteCost` - Remove a cost entry
  - `getChannelTypes` - List available channel types

---

## Upgrade Guide

### From 1.1.0 to 1.2.0

This update adds a new database column. The migration is automatic.

**New Features:**
- Navigate to **Administration > Measurables > Manage Costs** to view all your cost entries
- Edit or delete any cost entry
- Add descriptions to your cost entries for better organization
- CSV imports can now include a `description` column

**Database Changes:**
- A new `description` column is automatically added to the `cost_analytics` table on first access
- Existing data is preserved; the new column defaults to NULL

**Rolling Back:**
If you need to revert to version 1.1.0:
1. Download version 1.1.0 from the [releases page](https://github.com/Chardonneaur/matomo-cost-analytics-plugin/releases/tag/v1.1.0)
2. Replace the `CostAnalytics` folder in your Matomo `plugins/` directory
3. Clear Matomo's cache

Note: The `description` column will remain in the database but will be ignored by older versions.

### From 1.0.0 to 1.1.0

This is a backwards-compatible update. No migration is required.

**New Features:**
- When importing costs (CSV or manual), you'll now see a "Target Website" dropdown at the top of the form
- Select the website you want to import costs to
- The current site is pre-selected by default

**Rolling Back:**
If you need to revert to version 1.0.0:
1. Download version 1.0.0 from the [releases page](https://github.com/Chardonneaur/matomo-cost-analytics-plugin/releases/tag/v1.0.0)
2. Replace the `CostAnalytics` folder in your Matomo `plugins/` directory
3. Clear Matomo's cache

No database changes are required when upgrading or downgrading between these versions.

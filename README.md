# CostAnalytics Plugin for Matomo

Import marketing cost data via CSV and calculate ROI metrics for your acquisition channels.

## Features

- **CSV Import**: Upload cost data in bulk via CSV files
- **Per-Website Import**: Import costs to any website you have admin access to, not just the current site
- **Manual Entry**: Add individual cost entries through the admin interface
- **Channel Mapping**: Costs are mapped to Matomo's acquisition channels (Direct, Search, Social, Campaigns, Websites)
- **ROI Calculation**: Automatic ROI calculation based on goal revenue: `ROI = ((Revenue - Cost) / Cost) × 100`
- **Reports**: View cost and ROI metrics by channel in the Acquisition section

## Installation

1. Copy the `CostAnalytics` folder to your Matomo `plugins/` directory
2. Go to **Administration > Plugins**
3. Find **CostAnalytics** and click **Activate**
4. The database table will be created automatically

## Usage

### Importing Cost Data via CSV

1. Go to **Administration > Measurables > Cost Management**
2. Click **Import CSV**
3. **Select the target website** from the dropdown (you can import to any site you have admin access to)
4. Upload your CSV file
5. Optionally check "Replace existing costs" to overwrite costs for the same date range
6. Click **Upload**

> **Tip**: The website selector allows you to import costs for multiple websites without switching between them. Simply select the target website, upload the CSV, and repeat for other sites.

#### CSV Format

Your CSV file must include the following columns:

| Column | Required | Description |
|--------|----------|-------------|
| `channel_type` | Yes | Channel identifier (see valid types below) |
| `cost_date` | Yes | Date in YYYY-MM-DD format |
| `cost_amount` | Yes | Cost amount (decimal number) |
| `currency` | No | Currency code (default: USD) |
| `campaign_name` | No | Optional campaign identifier |

#### Valid Channel Types

| Channel Type | Description |
|--------------|-------------|
| `direct` | Direct Entry |
| `search` | Search Engines (Google, Bing, etc.) |
| `social` | Social Networks (Facebook, Twitter, etc.) |
| `campaign` | Campaigns (UTM tagged traffic) |
| `website` | Referral Websites |

#### Example CSV

```csv
channel_type,cost_date,cost_amount,currency,campaign_name
campaign,2024-01-01,150.00,USD,Summer Sale
campaign,2024-01-02,175.00,USD,Summer Sale
search,2024-01-01,200.00,USD,Google Ads
search,2024-01-02,220.00,USD,Google Ads
social,2024-01-01,75.50,USD,Facebook Ads
social,2024-01-02,80.00,USD,Instagram Ads
website,2024-01-01,50.00,USD,Affiliate Program
direct,2024-01-01,0.00,USD,
```

### Adding Costs Manually

1. Go to **Administration > Measurables > Cost Management**
2. Click **Add Manual Cost**
3. **Select the target website** from the dropdown (you can add costs to any site you have admin access to)
4. Fill in the form:
   - Select the channel type
   - Choose the date
   - Enter the cost amount
   - Optionally specify currency and campaign name
5. Click **Add Cost**

### Viewing Reports

1. Go to **Acquisition > Cost & ROI**
2. View cost and ROI metrics by channel
3. Reports show:
   - **Cost**: Total spend per channel
   - **Revenue**: Goal revenue attributed to the channel
   - **Profit**: Revenue minus Cost
   - **ROI %**: Return on investment percentage

## API Methods

The plugin provides API methods for programmatic access:

### Get Costs by Channel
```
?module=API&method=CostAnalytics.getCostsByChannel&idSite=1&period=month&date=today&format=json
```

### Get ROI Summary
```
?module=API&method=CostAnalytics.getROISummary&idSite=1&period=month&date=today&format=json
```

### Import Costs via API
```
?module=API&method=CostAnalytics.importCostsFromCSV&idSite=1&csvData=...&format=json
```

### Add Single Cost Entry
```
?module=API&method=CostAnalytics.addCost&idSite=1&channelType=campaign&costDate=2024-01-01&costAmount=100&currency=USD&format=json
```

### Get Available Channel Types
```
?module=API&method=CostAnalytics.getChannelTypes&format=json
```

## ROI Calculation

ROI is calculated using the following formula:

```
ROI = ((Revenue - Cost) / Cost) × 100
```

Where:
- **Revenue** = Total goal revenue for the channel during the period
- **Cost** = Total imported cost for the channel during the period

### Example

If a campaign cost $1,000 and generated $3,000 in goal revenue:

```
ROI = (($3,000 - $1,000) / $1,000) × 100 = 200%
```

## Troubleshooting

### Costs not appearing in reports
- Ensure the cost dates match the report period
- Verify the channel type matches (e.g., `campaign` for campaign traffic)
- Check that costs were imported for the correct site ID

### CSV import errors
- Ensure all required columns are present
- Check date format is YYYY-MM-DD
- Verify channel types are valid (lowercase)
- Make sure cost amounts are positive numbers

### ROI showing as 0%
- Verify that goals are configured and tracking revenue
- Ensure traffic is being attributed to the correct channels
- Check that the date ranges match between costs and visits

## Requirements

- Matomo 5.0 or higher
- PHP 7.4 or higher
- Goals plugin enabled (for revenue tracking)

## License

GPL v3 or later

## Support

For issues and feature requests, please create an issue in the repository.

# Lab Reports for Craft CMS

Custom content/data reporting for Craft CMS.

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Basic Reports](#basic-reports)
- [Advanced Reports](#advanced-reports)
- [Running Reports](#running-reports)
	* [From the Control Panel](#from-the-control-panel)
	* [Console Command](#console-command)
	* [Cron Job](#cron-job)
- [Template Variables](#template-variables)
- [Element Properties & Methods](#element-properties-methods)
	* [ConfiguredReport Properties/Methods](#configuredreport)
	* [Report Properties/Methods](#report)
- [Planned Features](#planned-features)

## Requirements

* Craft CMS v4.0.0+
* PHP 8.0.2+

## Installation

Add the following to your composer.json requirements. Be sure to adjust the version number to match the version you wish to install.

```
"masugadesign/labreports": "2.0.1",
```

## Configuration

To customize your Lab Reports configuration, create a `labreports.php` file in your Craft `config` folder. To start, you may copy the contents of the`config.php` file included in the plugin's `src` folder.

Lab Reports has the following config options:

### debug

Set debug to `true` to enable some advanced plugin logging. This can assist in diagnosing issues with configuring/running reports.

### fileStorageFolder

Specify a full system path to a _writable_ folder without a trailing slash. Lab Reports will store the generated report files there. Do not include this item in the plugin config file if you do not wish to override the default location which is a "labreports" subfolder in the Craft storage folder.

### functions

This is the array of PHP formatting functions used by the advanced reports.

```
<?php

return [
	'debug' => true,

	'fileStorageFolder' => '/home/ubuntu/sites/example.com/reports',

	'functions' => [
		'entryDump' => function ($entry) {
			return [
				(int) $entry->id,
				$entry->title,
				$entry->dateCreated->format('F j, Y g:i a')
			];
		}
	]
];
```

## Debugging

Lab Reports writes most errors/exceptions to the `storage/logs/labreports.log` file. In some cases, some errors may bypass that log and Craft will write them to the `web.log` or `queue.log` files.

When the Lab Reports `debug` configuration option is enabled, the plugin logs additional information. Each debug log line is prefixed with `[DEBUG]`.

Errors/Exceptions that occur during the report build process are stored in the `Report` element's `statusMessage` property. The value is displayed on the `Report` detail page in the control panel. Any report build that results in an "error" status should have a `statusMessage` value which may be helpful in diagnosing issues with the report template or configuration.

## Basic Reports

The basic report is a template-based report geared towards smaller data exports (< 3000 rows). These reports load the entire query result into memory at once when generating the CSV export.

### Configure

To configure a basic report, click the "New Report" button from the main Lab Reports page in the Craft control panel. On the form, choose "Basic" as the report _Type_. Enter a _Title_, a _Description_ (optional) and a relative path to a _Template_ in your main Craft templates folder. Click the "Create" button.

As an example, we will create a basic report that exports some entry metadata. The report template might look something like the following:

```
{#
Report Title : Books Entries Export
Report Description : A very basic export of all the Books entries.
Report Template : _reports/booksBasic
#}

{# Initialize the rows array by placing an array of column headers/labels in it. #}
{% set rows = [[
	'ID',
	'Title',
	'Date Published',
	'Author',
	'Cover Image'
]] %}

{# Construct a query and execute it with .all(). #}
{% set entries = craft.entries.section('books')
	.with(['bookAuthor','coverImage'])
	.orderBy('title')
	.all() %}

{# Loop through the entries and add them to the report rows array. #}
{% for entry in entries %}
	{% set author = entry.bookAuthor[0] ?? null %}
	{% set coverImage = entry.coverImage[0] ?? null %}
	{% set rows = rows|merge([[
		entry.id,
		entry.title,
		entry.bookPublishDate|date('F j, Y g:i A'),
		(author ? author.title : ''),
		(coverImage ? coverImage.url : '')
	]]) %}
{% endfor %}

{# Tell Lab Reports to construct the CSV File and save a new Report element. #}
{% do report.build(rows) %}
```

The `report` variable is automatically defined in the template. It contains a Lab Reports `Report` element instance.

## Advanced Reports

The advanced report is a template-based report geared towards larger data exports (>= 3000 rows) with a lot of columns and/or relationships. These reports allow for a developer to define a Query (not executed) as well as a PHP formatting function that should be applied to all the query results behind-the-scenes when the plugin is constructing the report file.

### Formatting Functions

The PHP formatting functions should be defined in the `labreports.php` config file's `functions` array. Each function must accept one parameter. The value of the parameter is a Craft Element of whatever type your report is centered around. The name of the variable does not matter. The function must return a single array which represents one row in the report. Pay careful attention to the order of each item in the array because it will need to match the order of the column names that you define in the report template.

```
<?php

return [

	'functions' => [
		// The function name in this example is "bookDump"
		'bookDump' => function ($entry) {
			$author = $entry->getFieldValue('bookAuthor')->one();
			$coverImage = $entry->getFieldValue('coverImage')->one();
			return [
				(int) $entry->id,
				$entry->title,
				$entry->postDate->format('F j, Y g:i a'),
				($author ? $author->title : null),
				($coverImage ? $coverImage->getUrl() : null),
				$entry->getFieldValue('publisherName')
			];
		},
		// The function name in this example is "assetDump"
		'assetDump' => function ($asset) {
			return [
				(int) $asset->id,
				$asset->filename,
				(int) $asset->folderId,
				(int) $asset->volumeId,
				$asset->getFieldValue('customFieldHandle'),
				$asset->getFieldValue('someOtherCustomField')
			];
		}
	],

];
```

If you aren't accustomed to coding with PHP, here are some Twig-to-PHP examples that may help you:

**Plain Text / Rich Text / Number**

```
{# Twig #}
{% set value = entry.fieldHandle %}
// PHP
$value = $entry->getFieldValue('fieldHandle');
```

**Relationship Field (not eager-loaded)**

```
{# Twig #}
{% set relatedEntry = entry.entryFieldHandle.one %}
// PHP
$relatedEntry = $entry->getFieldValue('entryFieldHandle')->one();
```

**Relationship Field (eager-loaded)**

These examples use NULL coalescing operators in case the eager-loaded relationship field has no value.

```
{# Twig #}
{% set relatedEntry = entry.entryFieldHandle[0] ?? null %}
// PHP
$relatedEntry = $entry->getFieldValue('entryFieldHandle')[0] ?? null;
```

**Date/Time Field**

```
{# Twig #}
{% set postDate = entry.postDate|date('F j, Y') %}
{% set customDate = entry.customDateField|date('Y-m-d') %}
// PHP
$postDate = $entry->postDate->format('F j, Y');
$customDate = $entry->getFieldValue('customDateField')->format('Y-m-d');
```

**Dropdown/Radio Fields**

```
{# Twig #}
{% set fieldValue = entry.dropdownField %}
{% set fieldLabel = entry.dropdownField.label %}
// PHP
$fieldValue = $entry->getFieldValue('dropdownField')->value;
$fieldLabel = $entry->getFieldValue('dropdownField')->label;

```

**Checkbox Fields**

The "use" statement should be placed at the top of your labreports.php file.

```
{# Twig #}
{% set commaSepValues = entry.checkboxField|join(', ') %}
// PHP
use craft\helpers\ArrayHelper;

$commaSepValues = implode(', ', ArrayHelper::getColumn(ArrayHelper::toArray( (array) $entry->getFieldValue('checkboxField') ), 'value'));
```

### Configure

To configure an advanced report, click the "New Report" button from the main Lab Reports page in the Craft control panel. On the form, choose "Advanced" as the report _Type_. Enter a _Title_, a _Description_ (optional), a relative path to a _Template_ in your main Craft templates folder and a _Formatting Function Name_. Click the "Create" button.

As an example, we will create an advanced report that exports some entry metadata along with some relationship data. Keep in mind the formatting function is saved to the Configured Report element record and does not appear anywhere in the report template. The report template might look something like the following:

```
{# Create a single array of column headers/labels. #}
{% set columnNames = [
	'ID',
	'Title',
	'Date Published',
	'Author',
	'Cover Image',
	'Publisher Name'
] %}

{# Construct an EntryQuery object WITHOUT .limit() or .all(). Do not execute the query here! #}
{% set entriesQuery = craft.entries.section('books').orderBy('title') %}

{# Tell Lab Reports to construct the CSV File and save a new Report element. #}
{% do report.build(columnNames, entriesQuery) %}
```

Like basic reports, the `report` variable is automatically defined in the template and contains a Lab Reports `Report` element instance.

## Running Reports

Reports can be generated in multiple ways.

### From the Control Panel

In the _Configured Reports_ tab of the Lab Reports control panel area, each configured report has a "Run" link column. Click that button link to generate a new report. Reports can also be generated by clicking the "Run" button from a single configured report's control panel edit form.

### Console Command

Another way to generate a new report is to use a console command. Commands like the following example can be set to run periodically via a cron job. Each configured report is a Craft element and has a unique ID, just like any other element. Pass that ID to the command using the `reportId` option.

```
php craft labreports/reports/build --reportId=15
```

### Cron Job

Console commands like the example above can be run periodically via cron job. If you plan to schedule a report routine, it would be best to configure Craft to execute the queue via a cron job as well. To do this, add the following line to your `general.php` config file's array:

```
'runQueueAutomatically' => false,
```

That setting will disable Craft's default behavior of executing queue jobs when there is activity in the control panel. Then create the following cron job on the server. **Be sure to update the paths accordingly.**

```
* * * * * cd /path/to/projectroot && /path/to/php craft queue/run
```

Without this configuration in place, queued reports will not run unless someone logs into the control panel.

## Template Variables

The following template variables are available for use:

### `craft.labreports.configuredReports`

Query Lab Reports `ConfiguredReport` elements by the following criteria:

* reportTitle
* reportType
* reportDescription
* template
* formatFunction

```
{% set basicReports = craft.labreports.configuredReports
	.reportType('basic')
	.orderBy('reportTitle').all() %}

{% set advReports = craft.labreports.configuredReports
	.reportType('advanced')
	.orderBy('reportTitle').all() %}

```

### `craft.labreports.generatedReports`

Query Lab Reports `Report` elements by the following criteria:

* dateGenerated
* configuredReportId
* userId
* filename
* totalRows

```
{% set reports = craft.labreports.generatedReports
    .configuredReportId(6)
    .dateGenerated('>= ' ~ someDateVar)
    .orderBy('filename').all() %}

{% set reports = craft.labreports.generatedReports
    .userId(4)
    .orderBy('dateGenerated desc').all() %}

```

### `craft.labreports.formatFunctionNames`

This template variable returns an array of all the defined PHP formatting function names.

```
{% set functionNames = craft.labreports.formatFunctionNames %}

{#
It might look like this only with real function names:
[
    'entryFormatter',
    'assetFormatter',
    'anotherFunctionName',
    'yetAnotherFunctionName'
]
#}

```

### `craft.labreports.formatFunctionOptions`

This template variable returns an associative array of PHP formatting function HTML select options, including an empty option. This is used by the plugin in the control panel and may not be very useful outside of that context, but hey, it is available.

```
{% set options = craft.labreports.formatFunctionOptions %}

{#
It might look like this only with real function names:
{
    '' : 'Select Function...'
    'entryFormatter' : 'entryFormatter',
    'assetFormatter' : 'assetFormatter',
    'anotherFunctionName' : 'anotherFunctionName',
    'yetAnotherFunctionName' : 'yetAnotherFunctionName'
}
#}

```

## Element Properties & Methods

### `ConfiguredReport`

These public properties and methods are available on the `ConfiguredReport` elements.

#### Properties

The following properties and methods are publicly on `ConfiguredReport` element objects.

`reportTitle`

Note that it is not just "title". This element type does not make use of Craft's native titles.

`reportType`

The value will either be "basic" or "advanced".

`reportDescription`

The brief description of the report.

`template`

This value is populated with a relative path to the report template.

`formatFunction`

This value contains the name of the PHP format function used if the ConfiguredReport is an advanced report.

#### Methods

`getRunUrl()`

This method returns the _control panel_ "run" URL for the ConfiguredReport. Note that the returned URL will not work for users that do not have permission to access the plugin in the control panel.

`getCpEditUrl()`

This method returns the control panel edit form URL.

`getTotalRan()`

This method returns the total number of times reports have been generated from this configuration.

### `Report`

These public properties and methods are available on the `Report` (generated report) elements.

#### Properties

`configuredReportId`

The related `ConfiguredReport` element ID.

`filename`

The filename of the generated report file.

`totalRows`

The total number of rows that were written to the report file.

`dateGenerated`

The full UTC date/time that the report file was generated.

`reportStatus`

The status label of the generated report. The value may be `in_progress` or `finished`.

`statusMessage`

A textual status message that may be populated when errors occur.

`userId`

The ID of the user that generated the report.

#### Methods

`build($param1, $param2)`

This generates the report file. The method parameters vary based on the Report Type. For basic reports, provide a single array of column headers/labels nested within another array. For advanced reports, provide a single array (not nested in another array) of column headers/labels followed by the Query object.

```
{# Basic Reports (Example) #}
{% do report.build(rowsArray) %}

{# Advanced Reports (Example) #}
{% do report.build(columnLabels, entryQuery) %}

```

`addRows(array $rows)`

Provide an array or report row arrays to write them to the report file.

`addRow(array $row)`

Provide a single report row array to be written to the report file.

`filePath()`

This method returns the full system file path to the report file whether or not the file actually exists.

`fileExists()`

This method returns a boolean `true`/`false` denoting whether or not the report file exists.

`setUser(User $user)`

This method sets the report's author to a specified `User` element.

`getUser()`

This method returns the report's author `User` element or `null` if there isn't one.

`setConfiguredReport(ConfiguredReport $cr)`

This method sets the `ConfiguredReport` object on the `Report` element. This governs which configuration will be used when building the report file.

`getConfiguredReport()`

This method returns the report's related `ConfiguredReport` or `null` if there isn't one.

`setQueueJob(GenerateReport $job)`

This method sets the report's queue job so it can update the progress of the job while building the report.

`updateStatus($status)`

This method updates the report's status to a given string (`in_progress`|`finished`) then saves the `Report` element.

`getStatusLabel()`

This returns the status label of the report otherwise `null` if it doesn't have one. The possible values are `In Progress`|`Finished`

`getDownloadUrl()`

This method returns the _control panel_ download URL string for the report file. Note that this  URL will not work for users that do not have control panel access.

## Planned Features

- Graphing tools
- Other export formats (custom delimiter, XML)
- Dynamic report parameters

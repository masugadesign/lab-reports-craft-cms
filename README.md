# Lab Reports for Craft CMS

Custom content/data reporting for Craft CMS.

### Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Features](#features)
- [Planned Features](#planned-features)

### Requirements

* Craft CMS v3.6.0+
* PHP 7.2.5+

### Installation

Add the following to your composer.json requirements. Be sure to adjust the version number to match the version you wish to install.

```
"masugadesign/labreports": "1.0.0",
```

### Configuration

To customize your Lab Reports configuration, create a `labreports.php` file in your Craft `config` folder. To start, you may copy the contents of the`config.php` file included in the plugin's `src` folder.

Lab Reports has the following config options:

**debug**

Set debug to _true_ to enable some advanced plugin logging. This can assist in diagnosing issues with configuring/running reports.

**functions**

This is the array of PHP formatting functions used by the advanced reports.

### Debugging

Lab Reports writes errors/exceptions to the `storage/logs/labreports.log` file. In some cases, some errors may bypass that log and Craft will write them to the `web.log` or `queue.log` files.

### Features

#### Basic Reports

The basic report is a template-based report geared towards smaller data exports (< 3000 rows). These reports load the entire query result into memory at once when generating the CSV export.

**Configure**

To configure a basic report, click the "New Report" button from the main Lab Reports page in the Craft control panel. On the form, choose "Basic" as the report _Type_. Enter a _Title_, a _Description_ (optional) and a relative path to a _Template_ in your main Craft templates folder. Click the "Create" button.

As an example, we will create a basic report that exports some entry metadata. The report template might look something like the following:

```
{#
Report Title : Books Entries Export
Report Description : A very basic export of all the Books entries.
Report Template : _reports/booksBasic
#}

{# Initialize the rows data with the CSV column names. #}
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

**Run Reports**

Reports can be built in multiple ways. In the Configured Reports tab of the Lab Reports control panel area, each configured report has a "Run" link column. Click that link to generate a new report.

Another way to generate a new report is to use a console command. Commands like the following example can be set to run periodically via a cron job. Each configured report is a Craft element and has a unique ID, just like any other element. Pass that ID to the command using the `reportId` option.

```
php craft labreports/reports/build --reportId=50
```

#### Advanced Reports

The advanced report is a template-based report geared towards larger data exports (>= 3000 rows) with a lot of columns and/or relationships. These reports allow for a developer to define the base element query as well as a PHP formatting function that should be applied to all the query results behind-the-scenes when constructing the report file.

**Formatting Functions**

The PHP formatting functions should be defined in the `labreports.php` config file's `functions` array. Each function must accept one parameter. The value of the parameter is a Craft Element of whatever type your report is centered around. The name of the variable does not matter. Pay careful attention to the order of each item in the array because it will need to match the order of the column names that you define in the report template.

```
<?php

return [

	'functions' => [
		// The function name in this example is "bookDump"
		'bookDump' => function ($entry) {
			$author = entry.bookAuthor.one() %}
			$coverImage = entry.coverImage.one() %}
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
				$asset->getFieldValue('bookAuthor'),
				$asset->getFieldValue('someOtherCustomField')
			];
		}
	],

];
```

**Configure**

To configure an advanced report, click the "New Report" button from the main Lab Reports page in the Craft control panel. On the form, choose "Advanced" as the report _Type_. Enter a _Title_, a _Description_ (optional), a relative path to a _Template_ in your main Craft templates folder and a _Formatting Function Name_. Click the "Create" button.

As an example, we will create an advanced report that exports some entry metadata along with some relationship data. The report template might look something like the following:

```
{# Initialize the rows data with the CSV column names. #}
{% set columnNames = [[
	'ID',
	'Title',
	'Date Published',
	'Author',
	'Cover Image',
	'Publisher Name'
]] %}

{# Construct an EntryQuery object WITHOUT .limit() or .all(). Do not execute the query here! #}
{% set entriesQuery = craft.entries.section('books').orderBy('title') %}
{# Tell Lab Reports to construct the CSV File and save a new Report element. #}
{% do report.build(columnNames, entriesQuery) %}
```

### Planned Features

- Graphing tools.

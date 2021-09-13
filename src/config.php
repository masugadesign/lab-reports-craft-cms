<?php

return [

	/**
	 * The plugin's boolean debug toggle. Set this to `true` to enabled additional
	 * logging by the plugin that may help investigate issues.
	 */
	'debug' => false,

	/**
	 * System path to a folder where generated report files will be stored.
	 */
	'fileStorageFolder' => '',

	/**
	 * These functions are used to generate advanced reports that require more
	 * optimized queries and batched loading of elements/records.
	 */
	'functions' => [
		'exampleEntryFunction' => function ($entry) {
			return [
				(int) $entry->id,
				$entry->title,
				$entry->dateCreated->format('F j, Y g:i a')
			];
		}
	],

];

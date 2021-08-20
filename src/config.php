<?php

return [

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

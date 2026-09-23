<?php
// This file is generated. Do not modify it manually.
return array(
	'recording-studio-widget' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'recording-studio/recording-studio-widget',
		'version' => '0.2.0',
		'title' => 'WP Template Demo',
		'category' => 'widgets',
		'description' => 'Shows a page from your studio.',
		'example' => array(
			
		),
		'attributes' => array(
			'pageRecordingId' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'supports' => array(
			'html' => false
		),
		'textdomain' => 'recording-studio-widget',
		'editorScript' => 'file:./index.js',
		'viewScript' => 'file:./front.js',
		'render' => 'file:./render.php'
	)
);

<?php
// This file is generated. Do not modify it manually.
return array(
	'recording-studio-widget' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'recording-studio/recording-studio-widget',
		'version' => '0.2.0',
		'title' => 'WordPress Plugin Demo',
		'category' => 'widgets',
		'icon' => 'smiley',
		'description' => 'Embeds a Recording Studio page from the wp_plugin_demo host API. Server-rendered payload; SDK mounts on the front.',
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
		'style' => 'file:./style-index.css',
		'viewScript' => 'file:./front.js',
		'render' => 'file:./render.php'
	)
);

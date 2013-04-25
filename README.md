Multiple content blocks
=========

Allow for more content blocks in WordPress than just the one. 


Installation
--------------
1. Download the zip
2. Unpack and upload to the /wp-content/plugins/ folder
3. Activate the plugin


How to use
--------------
Place one of the template tags in a WordPress template. When that template is used, an extra editor will appear in the back-end.


Template tags
--------------
	the_block($name)
This will display the $name content block

	get_the_block($name)
This will get $name content block's content, for you to process

Additional options
--------------
	the_block($name,array(
		'type' => 'one-liner',
		'apply_filters' => false
	))
- Won't display a WYSIWYG editor, but a plain one line text field (input type="text").
- Won't apply filters
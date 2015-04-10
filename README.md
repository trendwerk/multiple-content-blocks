Multiple content blocks
=========

Allow for more content blocks in WordPress than just the one. 


Installation
--------------
If you're using Composer to manage WordPress, add this plugin to your project's dependencies. Run:
```sh
composer require trendwerk/multiple-content-blocks 3.2.1
```

Or manually add it to your `composer.json`:
```json
"require": {
	"trendwerk/multiple-content-blocks": "3.2.1"
},
```


How to use
--------------
Place one of the template tags in a WordPress template. When that template is used, an extra editor will appear in the back-end.


Template tags
--------------
```php
the_block( $name, $args )
```
This will display the $name content block

```php
get_the_block( $name, $args )
```
This will get $name content block's content, for you to process

```php
has_block( $name, $args )
```
Will check if a block exists and has content

Additional arguments
--------------
```php
the_block( $name, array(
	'label'         => __( 'Admin label', 'text-domain' ),
	'type'          => 'one-liner',
	'apply_filters' => false,
) );
```

### label
*(string)* Label for the admin area.

Default: *None*

### type
*(string)* The type of content block.

Default: *editor*

- **editor**: WordPress' WYSIWYG editor.
- **one-liner**: A plain one line text field.

### apply_filters
*(boolean)* Whether to apply `the_content` filters or not.

Default: *true*

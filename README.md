![MTT logo](https://raw.github.com/brasofilo/Private-Comments-in-CPT/master/logo.png)

# Multisite Site Category
WordPress plugin for adding a custom meta to New Sites.

## Description
The plugin adds a new field "category" to the Site Info screen. 
A sortable column is also added in the Sites list screen.
A Categories submenu is created under Sites, add or remove categories there.

Available hooks:
```
// Cache time, default 3600 (1hour)
add_filter( 'msc_transient_time', function(){ return 1; } );
// For debugging purposes
add_filter( 'msc_show_mature_column', '__return_true' );
```

Originally based on this [WordPress Question](http://wordpress.stackexchange.com/q/50235/12615). 
[Here's a copy](https://gist.github.com/brasofilo/6715423) of the first version of the plugin.

## Installation
### Requirements
* WordPress version 3.3 and later (not tested in previous versions)

### Installation
1. Unpack the download-package
1. Upload the file to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Network Plugins' menu in WordPress

## Screenshots
**Sites Manager showing the debug column *mature*.**

![Sites Manager](https://github.com/brasofilo/multisite-site-category/raw/master/img/screenshot-1.png)

**Site info**

![Site add back end](https://github.com/brasofilo/multisite-site-category/raw/master/img/screenshot-2.png)

**Manage categories**

![Site add front end](https://github.com/brasofilo/multisite-site-category/raw/master/img/screenshot-3.png)


## Other Notes
### Licence
Released under GPL, you can use it free of charge on your personal or commercial blog.
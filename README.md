# CDN Integration WordPress Plugin
This plugin lets you serve your entire WordPress site through a CDN. Currently the only provider supported is [KeyCDN](https://www.keycdn.com/?a=7826).

## Usage
Install this plugin to your `/wp-config/plugins/` directory, activeate it, visit the setting page located in the WordPress Admin menu under *Settings* --> *CDN Integration*

## How can I tell if it is working?
The easiest way to check if your page is being served from a CDN as opposed to your origin server is to check the response headers of your page. You'll want to look for a header set called `X-CACHE` set to `HIT`.

 - KeyCDN has an online tool: https://tools.keycdn.com/curl
 - Use the network tab of your browser's developer tools 
 - Use curl: curl -I http://example.com
 
## How can I flush the cache?
The plugin handles flushing URLs from the CDN when content changes. This is thanks to the various actions WordPress provides when content is changed. You can also manually flush the cache using an included Dashboard widget:

![screen shot 2017-01-23 at 12 55 22 am](https://cloud.githubusercontent.com/assets/867430/22193012/bab644ca-e106-11e6-838c-babb752080e4.jpg)

With the widget you can stay within your own site and flush:
 - Multiple URLs at once (one per line)
 - Multiple [Cache Tags](https://www.keycdn.com/support/purge-cdn-cache/) (one per line)
 - The entire cache by entering `all`
 
 You can also flush the cache manually by visiting your CDN provider.

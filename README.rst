DibiProxy
=========
This is collection of "proxy drivers" for dibi database layer, which you can obtain on http://www.dibiphp.com

Why
====
Everyone knows the drill. When you're going to present something somewhere, you have whole project running on your
local machine. This approach works for most of the cases, however we have been faced a problem. Let's outline:

 - The website we have presented is connected to our ERP system
 - Internet connection is not available in the meeting, our account manager has to present local installation
 - We did not feel comfortable putting whole ERP on our account managers laptop

So we decided to take another approach. Let's install the-least-needed database for the presentation. As the project
has been using dibi as DBAL, we have created proxy classes instead.

How
====
The proxy classes sit on top of the actual database drivers, caching all the queries and their results. When database
is not available, data are loaded from cache.

As some of the queries are changing data & are loading result ids, we implemented "override" concept, where the results
are not cached by the query, but by the override key instead. So, data changing queries (like inserts) have pre-defined
key under which they are saved & loaded from the cache later.

Installation
=============
Simply put into your composer.json::

 require {
   "foglcz/dibiproxy": "1.*"
 }

Due to dibi driver loading process, you need to include appropiate driver before you use it::

 require_once 'vendor/foglcz/dibiproxy/lib/drivers/your.selected.driver.php';

Then, just use the common driver with "Proxy" prefix and enable the proxying::

 $config['driver'] = 'proxymysqli';
 $config['proxy'] = 'path_to_proxy_file.dat';

If you have data-changing queries (inserts/update), you should then use different class for connecting to database::

 dibiProxy::connect($config);

Afterwards, feel free to use the dibi:: class as usual for all data manipulation.

Usage
=====
When using the "proxy" configuration parameter, you SHOULD supply the path to file, where proxy data will be saved.
If you just provide "true", but not string, the classes will automatically create datafile in the place of lib/ class.

The classes work transparently, hence the update to proxying classes has no other steps.
The proxying is used when no database connection is available. Our usage is, that we click-through the entire project
on local machine while the connection is available - then we destroy the config parameters so that the project does NOT
connect. This time, we verify the result - everything should work as default.

Data changing queries
=====================
If you have some update/insert/delete queries, which you want to include, before calling dibi::query() and/or $connection->*
functions, you need to call override::

 dibi::setProxyOverride('some.ident.of.the.query');

This will result in caching the query results under this static identifier. Hence, you can have database-changing queries
running on proxied project.

Nette framework loading
=======================
If you're using Nette Framework (that awesome thing can be obtained at http://www.nette.org ) with the dibi driver within
config.neon file, load the proxied classes this way::

  database:
      driver: ProxyMysqli

  services:
      database: DibiProxyConnection(%database%)

... which basically means exchanging the driver within config parameters & changing the connection class name
(= changing DibiConnection --> DibiProxyConnection).

Author and license
==================
Created with love by Pavel Ptacek (c) 2012.

(these classes has been created within 2 hours during night hackathon before the presentation. Pull requests welcome.)
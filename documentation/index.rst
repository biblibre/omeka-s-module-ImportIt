Import It documentation
=======================

`Import It`_ is a module for Omeka S. It allows administrators to import repeatedly
from external sources into Omeka S.

.. _Import It: https://github.com/biblibre/omeka-s-module-ImportIt

The idea is to define a source once in Omeka S, and then import from this
source regularly, which can be manual or automatic (using cronjobs for
instance).

This is useful for sources that are updated on a regular basis.


Requirements
------------

* Omeka S >= 4.1.0
* PHP >= 8.0

Depending on the sources used, there may be other requirements


Supported sources
-----------------

Import It currently supports only one source type (more will be added later).

Other modules can add support for other source types.

* :doc:`source-types/server-side-mets`


Logs
----

Import It does not use the default log mechanism, because imports often produce a
lot of log messages and that can slow imports down. It can also make the ``job``
table grow quickly.

Instead, each import have its own log file on the server. That way they can be
rotated with usual tools (eg. logrotate).

They can still be viewed (or downloaded) from the admin interface (until they
are rotated).

By default, log files are stored in ``OMEKA_PATH/logs/importit``. It can be
changed by adding the following code to ``config/local.config.php``:

.. code-block:: php

   'importit_logger' => [
       'dir' => '/var/log/importit', // Make sure that Omeka have write permissions on this directory
   ],


Start import from the command line
----------------------------------

To start an import from the command line (useful to run imports periodically),
execute the following command:

.. code-block:: sh

   $OMEKA_PATH/modules/ImportIt/script/import.php --user-id <user-id> --source-id <source-id>

``<user-id>`` is the ID of an Omeka S user. The import will be run as this user.

``<source-id>`` is the ID of the Import It source.


Table of contents
=================

.. toctree::
   :maxdepth: 2

   sources
   source-types

.. include:: /Includes.rst.txt

============
Installation
============

Installation via Composer
=========================

The Fairway NetX extension is installed via Composer.

.. code-block:: bash

   composer require fairway/netx-fal

Afterwards, the usual TYPO3 steps for activating or updating the extension
must be carried out.

* **Update the database schema:** The extension defines the cache tables
  ``cf_netx_fal``, ``cache_netx_fal``, and ``cache_netx_fal_tags``.
* **Clear the TYPO3 caches:** Required after installation or activation.
* **Configure the extension:** Create or configure a TYPO3 File Storage using
  the NetX driver.
* **Install additional dependencies:** Required dependencies are installed
  through Composer.
* **Set up Scheduler tasks:** The Basic version does not define any Scheduler
  tasks within the extension.

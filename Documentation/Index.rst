.. include:: /Includes.rst.txt

============
Fairway NetX
============

The **Fairway NetX Extension for TYPO3** connects the
**NetX Digital Asset Management (DAM) system** with TYPO3.

The extension makes assets that are centrally managed in NetX available
directly within TYPO3. Editors can use these assets when creating and
maintaining website content.

For example, images and videos stored in the central DAM can be selected
directly in TYPO3 without having to upload or manage them separately in the
local TYPO3 file system.

Further information
===================

* `NetX <https://www.netx.net/de/>`_
* **Fairway / eCentral:** Project-specific Fairway information

Premium version
===============

The Premium version extends the NetX FAL integration with write operations
for files and folders as well as extended metadata support.

File actions
------------

* Upload files to NetX
* Create empty files in NetX
* Rename files
* Replace existing files by creating a new NetX version
* Delete files from NetX
* Automatically clear the NetX cache after write operations

Folder actions
--------------

* Create folders in NetX
* Recursively create folders based on paths
* Rename folders
* Delete folders
* Prevent non-recursive deletion of non-empty folders
* Automatically clear the NetX cache after folder changes

Metadata
--------

* Register a TYPO3 metadata extractor for the NetX FAL driver
* Read file information directly through the NetX FAL storage
* Make extracted metadata available for ``sys_file_metadata``
* Enable metadata editing for NetX files while respecting user permissions,
  file extensions, and FileMount restrictions

Documentation
=============

* :doc:`Features <Features/Index>`
* :doc:`How to Use the Extension <HowToUse/Index>`
* :doc:`Installation <Installation/Index>`
* :doc:`Technical Configuration <TechnicalConfiguration/Index>`
* :doc:`Requirements <Requirements/Index>`

.. toctree::
   :hidden:
   :maxdepth: 2

   Features/Index
   HowToUse/Index
   Installation/Index
   TechnicalConfiguration/Index
   Requirements/Index

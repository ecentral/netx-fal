.. include:: /Includes.rst.txt

========================
How to Use the Extension
========================

TYPO3 Fileadmin and Storages
============================

TYPO3 manages files using the **File Abstraction Layer (FAL)**. This allows
different storages to be integrated into TYPO3's file management.

The NetX integration uses this concept to make assets from the DAM available
directly within TYPO3.

For more information about TYPO3 file management, see:

* `File storages — TYPO3 Explained main documentation <https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Fal/Administration/Storages.html>`_
* `The Filelist module — Getting Started 13.4 documentation <https://docs.typo3.org/m/typo3/tutorial-getting-started/13.4/en-us/Concepts/Backend/FileModule/Index.html>`_

Setting Up a NetX DAM Storage
=============================

Before editors can access NetX assets, a corresponding DAM storage must first
be configured in TYPO3.

Create a Storage
----------------

#. Log in to the TYPO3 backend as an administrator.
#. Open the module for managing File Storages, usually under
   :guilabel:`System > File Storages`.
#. Create a new File Storage.
#. Enter a descriptive name, for example ``NetX DAM``.
#. Select the ``Netx(FAL)`` storage driver provided by the extension.
#. Enter the required NetX connection details:

   * ``NetX Host``: URL of the NetX instance.
   * ``NetX API key``: API token used to access NetX.
   * ``NetX User``: NetX user or technical user.
   * ``NetX Password``: Password of the configured NetX user.

#. Optionally configure the language settings:

   * ``Locale``: Preferred language for localized NetX labels
     (``de`` or ``en``).
   * ``Default locale``: Fallback language for localized NetX labels
     (``de`` or ``en``).

#. Optionally enter a comma-separated list of NetX root node IDs in
   ``Root nodes (comma separated list of ids)``.
#. Optionally configure ``Cache lifetime in minutes (0 < lifetime < 30)``.
   The default value is ``29``.
#. Save the File Storage.
#. Clear the TYPO3 caches.
#. Under :guilabel:`File > Filelist`, check whether the new NetX storage is
   displayed and whether the configured root nodes, folders, and assets are
   loaded correctly.
#. If backend users should have access to the NetX storage, enable:

   * ``NetX Files: Read``
   * ``NetX Files: Copy``
   * ``NetX Folder: Read``
   * ``NetX Folder: Copy``

#. If access should be further restricted for specific backend users,
   configure the appropriate TYPO3 file mounts.

Required Configuration
----------------------

Depending on the NetX and Fairway configuration, the following information
may be required:

* NetX URL / endpoint
* API access
* User or technical access credentials
* API token
* Additional NetX-specific settings

.. list-table::
   :header-rows: 1
   :widths: 24 28 12 36

   * - Field
     - Backend Label
     - Required
     - Description
   * - ``netxHost``
     - NetX Host
     - Yes
     - Base URL of the NetX instance.
   * - ``netxApiKey``
     - NetX API key
     - Yes
     - API token used for authenticated NetX API requests and file downloads.
   * - ``netxUser``
     - NetX User
     - Yes
     - NetX user or technical user used to access NetX.
   * - ``netxPassword``
     - NetX Password
     - Yes
     - Password of the configured NetX user.
   * - ``locale``
     - Locale
     - No
     - Preferred language for localized labels. Supported values:
       ``de`` or ``en``.
   * - ``defaultLocale``
     - Default locale
     - No
     - Fallback language for localized labels. Supported values:
       ``de`` or ``en``.
   * - ``roots``
     - Root nodes (comma separated list of ids)
     - No
     - Comma-separated list of NetX root node IDs that should be available
       within the TYPO3 File Storage.
   * - ``cacheLifetimeInMinutes``
     - Cache lifetime in minutes (0 < lifetime < 30)
     - No
     - Cache lifetime in minutes. Default: ``29``.

Open the NetX Storage
---------------------

#. Log in to the TYPO3 backend.
#. Open **File** in the left-hand navigation.
#. Select **Filelist**.
#. Select the NetX storage in the directory tree.
#. Open the desired folder or section.

Finding and Selecting Assets
============================

#. Open the NetX storage.
#. Navigate to the desired location or search for the required asset.
#. Review the asset using its preview, file name, and available information.
#. Select the desired asset.

Using Images in Content Elements
================================

NetX images can be used wherever a TYPO3 content element provides an image or
media field.

Typical examples include:

* **Text & Images**
* **Text & Media**
* **Images Only**
* Project-specific content elements with image fields

Add an Image
------------

#. Go to :guilabel:`Web > Page` and open the desired page.
#. Edit an existing content element or create a new one.
#. Open the **Images** section.
#. Select **Add image** or **Add media file**.
#. Open the NetX storage in the TYPO3 file picker.
#. Navigate to the desired asset or search for it.
#. Select the required image.
#. Confirm your selection.
#. Save the content element.

Selecting Multiple Images
=========================

#. Open the file or asset picker.
#. Select the NetX storage.
#. Select the desired images.
#. Confirm your selection.
#. If necessary, adjust the order.
#. Save the content element.

Using Metadata
==============

Assets from NetX can contain additional metadata, for example:

* Title
* Description
* Alternative text
* Copyright or author information
* Additional metadata maintained in NetX

The metadata made available in TYPO3 depends on the extension configuration.

Alternative Text
----------------

Depending on the configuration, metadata imported from NetX can be
supplemented or overridden for the specific use of an asset within TYPO3.

Editing the Image Crop
======================

#. Open the content element containing the selected image.
#. Select the edit option for the image.
#. Open **Crop**.
#. Define the desired crop area.
#. Apply the changes.
#. Save the content element.

The original asset in NetX is not modified.

Using Videos from NetX
======================

#. Go to :guilabel:`Web > Page` and open the desired page.
#. Edit an existing content element or create a new one.
#. Open the **Media** section.
#. Select **Add media file**.
#. Open the NetX storage in the file picker.
#. Navigate to the desired video or search for it.
#. Select the video.
#. Confirm your selection.
#. Save the content element.

.. note::

   The supported video formats depend on the individual TYPO3 and Fairway
   configuration.

Replacing an Asset
==================

#. Open the relevant content element.
#. Go to the **Images** or **Media** section.
#. Remove the existing media reference.
#. Select **Add image** or **Add media file**.
#. Open the NetX storage.
#. Select the new asset.
#. Confirm your selection.
#. Save the content element.
#. Check the result on the website.

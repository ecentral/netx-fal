.. include:: /Includes.rst.txt

========
Features
========

The Fairway NetX Extension integrates **NetX as a Digital Asset Management
system (DAM)** into TYPO3's file management.

The main features include:

* Integration of **NetX with TYPO3**
* Use of NetX as a storage within TYPO3
* Access to assets managed centrally in NetX
* Use of NetX assets in TYPO3 content elements
* Support for images and other approved file types
* Selection of NetX assets through TYPO3's file picker
* Use of asset metadata within TYPO3
* Support for TYPO3 image features such as cropping

Basic Version
=============

The Basic Version provides the core integration between NetX and TYPO3's file
management.

Limitations of the Basic Version
--------------------------------

* No write operations are available for the NetX storage.
* Files cannot be uploaded to NetX from TYPO3.
* Empty files cannot be created in NetX.
* Files cannot be renamed in NetX.
* Files cannot be replaced and no new NetX versions can be created from TYPO3.
* Files cannot be deleted from NetX.
* Folders cannot be created in NetX.
* Folders cannot be renamed in NetX.
* Folders cannot be deleted from NetX.
* NetX metadata cannot be edited from TYPO3.
* No extended NetX backend permissions are provided for Write, Add, Rename,
  Replace, Delete, or Edit Metadata operations.
* The NetX FAL storage is therefore limited to reading, browsing, using, and
  copying assets for editors.

Premium Version
===============

The Premium Version provides additional features for integrating NetX with
TYPO3.

It adds write support for files and folders as well as extended metadata
handling.

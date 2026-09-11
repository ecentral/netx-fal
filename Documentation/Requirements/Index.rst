.. include:: /Includes.rst.txt

============
Requirements
============

The technical and organizational requirements must be met in order to use the
Fairway NetX extension.

TYPO3
=====

**Supported TYPO3 versions:**

* TYPO3 12
* TYPO3 13

PHP
===

**Supported PHP versions:**

* PHP 8.2
* PHP 8.3
* PHP 8.4

NetX
====

A suitable **NetX** account or access is required to establish the connection.

Depending on the configuration, the following may also be required:

* NetX account
* Access to the required assets
* API access
* Appropriate API permissions
* Credentials or tokens required for the connection

Technical requirements
======================

* **PHP:** ``^8.2``
* **TYPO3 Core:** ``^12 || ^13``
* **TYPO3 Extension Manager constraint:** ``12.0.0-13.99.99``
* **Extension key:** ``netx_fal``
* **Composer package:** ``fairway/netx-fal``
* **Required API package:** ``fairway/netx-fal-api``

PHP extensions required by the Basic extension:

* ``ext-fileinfo``
* ``ext-curl``
* ``ext-gd``

PHP extensions required by the API package:

* ``ext-curl``
* ``ext-json``
* ``ext-mbstring``

Composer libraries:

* ``guzzlehttp/psr7``
* ``guzzlehttp/guzzle`` via ``fairway/netx-fal-api``

Required NetX credentials:

* NetX Host
* NetX API key
* NetX User
* NetX Password

A configured TYPO3 FAL File Storage with the driver type
``FairwayNetXDriver`` is required.

Fairway NetX Extension
======================

A version of the Fairway NetX extension that is compatible with the installed
TYPO3 version must be installed and activated.

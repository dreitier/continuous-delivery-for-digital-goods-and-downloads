=== Continuous Delivery for Digital Goods and Downloads ===
Contributors: dreitier,schakko,dreitierci
Tags: download,software,continuous,delivery,package,automation,digital,goods
Requires at least: 5.6
Tested up to: 6.1
Stable tag: @VERSION@
License: The MIT License (MIT)
License URI: https://opensource.org/licenses/MIT
Donate link: https://dreitier.com
Requires PHP: 8.1

*Continuous Delivery for Digital Goods and Downloads* expands your WordPress download portal to a fully-fledged Continuous Delivery pipeline.

== Description ==
*Continuous Delivery for Digital Goods and Downloads* provides unified API endpoints to publish new releases of your software products. Those endpoints can be called from CI services like GitHub Actions or Jenkins.
Previously uploaded files to AWS S3 can be published in Easy Digital Downloads or Download Monitor and then be provided as protected downloads.

=== Features ===

* Same integration experience for Easy Digital Downloads and Download Monitor
* Unified API endpoints for publishing new release versions
* Provide download of files in S3-compatible object storages like AWS S3 or Minio
* Downloaded files are logged in EDD's and DLM's reports

=== Requirements ==

* WordPress since 6.0
* PHP >= 8.1
* Easy Digital Downloads or Download Monitor

== Frequently Asked Questions ==

Please read the [FAQ](https://dreitier.github.io/continuous-delivery-for-digital-goods-and-downloads-docs/faq) of our [official documentation](https://dreitier.github.io/continuous-delivery-for-digital-goods-and-downloads-docs/).

== Screenshots ==

1. Configuration settings
2. Workflow

== Installation ==

== Changelog ==

For detailed information you can visit the official [GitHub repository of Continuous Delivery for Digital Goods and Downloads](https://github.com/dreitier/continuous-delivery-for-digital-goods-and-downloads)

= 1.0.4 =
* FIXED: Default usage of HTTPS S3 redirect to make it compatible with DLM
* FIXED: Accept empty `meta` field
* FIXED: Fail, if `.release.version` is missing

= 1.0.3 =
* CHANGED: initial release

== Upgrade Notice ==

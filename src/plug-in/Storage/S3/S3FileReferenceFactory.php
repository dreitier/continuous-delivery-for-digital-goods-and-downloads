<?php

namespace Dreitier\WordPress\ContinuousDelivery\Storage\S3;

class S3FileReferenceFactory
{
	/**
	 * Create a new S3FileReference based upon the given URL.
	 *
	 * @param $url
	 * @return S3FileReference
	 * @throws UnsupportedS3Uri
	 */
	public function create($url): S3FileReference
	{
		$r = parse_url($url);

		if (empty($url) || empty($r)) {
			throw new UnsupportedS3Uri("Invalid URL given");
		}

		if (!$this->supportsScheme($r['scheme'])) {
			throw new UnsupportedS3Uri("Unsupported scheme '" . $r['scheme'] . "' for artifact URL reference");
		}

		if (!$this->validDomain($r['host'])) {
			throw new UnsupportedS3Uri("Host '" . $r['host'] . "' can not be used as artifact source");
		}

		if (!$bucketName = $this->bucketName($r['host'])) {
			throw new UnsupportedS3Uri("Can not resolve hostname to bucket name.");
		}

		$path = $r['path'];

		if (strlen($path) > 1 && $path[0] === '/') {
			$path = substr($path, 1, strlen($path) - 1);
		}

		return new S3FileReference($r['scheme'] . '://' . $r['host'] . '/' . $path, $bucketName, $path);
	}

	/**
	 * Supports https, http and s3 schema.
	 * @param $scheme
	 * @return bool
	 */
	public function supportsScheme($scheme): bool
	{
		return in_array(strtolower($scheme), ['https', 'http', 's3']);
	}

	/**
	 * Return true, if the host's FQDN contains the .s3 domain
	 * TODO Support custom domains for Minio
	 * @param $fqdn
	 * @return bool
	 */
	public function validDomain($fqdn): bool
	{
		return preg_match('/^(.+)(?:\.s3[-.].*)$/', $fqdn, $ret);
	}

	/**
	 * Extract the bucket's name based upon the given FQDN. It just takes the first subdomain.
	 * @param $fqdn
	 * @return string|null
	 */
	public function bucketName($fqdn): ?string
	{
		$firstDot = strpos($fqdn, '.');

		if ($firstDot !== FALSE) {
			return strtolower(substr($fqdn, 0, $firstDot));
		}

		return null;
	}
}
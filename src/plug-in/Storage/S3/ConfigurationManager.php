<?php

namespace Dreitier\WordPress\ContinuousDelivery\Storage\S3;

class ConfigurationManager
{
	public function __construct(public readonly S3FileReferenceFactory $s3FileReferenceFactory,
								public readonly ConfigurationResolver  $s3ConfigurationResolver,
	)
	{
	}

	/**
	 * Lookup bucket configuration for a given file reference and create an accessible file.
	 * @param S3FileReference $s3FileReference
	 * @return AccessibleS3File
	 * @throws \Exception
	 */
	private function createAccessibleFile(S3FileReference $s3FileReference): AccessibleS3File
	{
		$configuration = $this->s3ConfigurationResolver->__invoke($s3FileReference);

		if (!$configuration) {
			throw new \Exception("No credentials for bucket '" . $s3FileReference . "'");
		}

		return new AccessibleS3File($s3FileReference, $configuration);
	}

	/**
	 * Based upon a given URL, a new S3FileReference is created.
	 * @param $s3Url
	 * @return AccessibleS3File
	 * @throws UnsupportedS3Uri
	 */
	public function delegate($s3Url): AccessibleS3File
	{
		$s3FileReference = $this->s3FileReferenceFactory->create($s3Url);

		return $this->createAccessibleFile($s3FileReference);
	}

	/**
	 * Send redirect via <em>Location</em> header to the given S3 URL.
	 * @param $s3Url
	 * @param callable|null $beforeExit
	 * @return void
	 * @throws UnsupportedS3Uri
	 */
	public function sendRedirect($s3Url, ?callable $beforeExit = null)
	{
		$url = $this->delegate($s3Url)->createUrl();

		header("Location: $url");

		if ($beforeExit) {
			$beforeExit();
		}

		exit;
	}
}
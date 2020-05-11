<?php namespace Karali\TesseractOCR;

/*
|---------------------------------------------
| Command
|---------------------------------------------
|
| Generate system command to run the
| tesseract binary file with the given
| parameters.
|
*/

class Command
{
	public $executable = 'tesseract';
	public $useFileAsInput = true;
	public $useFileAsOutput = true;
	public $options = [];
	public $configFile;
	public $tempDir;
	public $threadLimit;
	public $image;
	public $imageSize;
	private $outputFile;

	public function __construct($image=null, $outputFile=null)
	{
		$this->image = $image;
		$this->outputFile = $outputFile;
	}

	// Return generated command.
	public function build() { return "$this"; }

	// Generate command string.
	public function __toString()
	{
		$cmd = [];
		// Set thread limit.
		if ($this->threadLimit) $cmd[] = "OMP_THREAD_LIMIT={$this->threadLimit}";

		// Escape executable.
		$cmd[] = self::escape($this->executable);

		// Check to use file as Input/Output.
		$cmd[] = $this->useFileAsInput ? self::escape($this->image) : "-";
		$cmd[] = $this->useFileAsOutput ? self::escape($this->getOutputFile(false)) : "-";

		// Set tesseract version.
		$version = $this->getTesseractVersion();

		// Add each option.
		foreach ($this->options as $option) {
			$cmd[] = is_callable($option) ? $option($version) : "$option";
		}

		// Set configFile.
		if ($this->configFile) $cmd[] = $this->configFile;

		return join(' ', $cmd);
	}

	// Get te file used for ocr.
	public function getOutputFile($withExt=true)
	{
		// Set directory for file.
		if (!$this->outputFile)
			$this->outputFile = $this->getTempDir()
				.DIRECTORY_SEPARATOR
				.basename(tempnam($this->getTempDir(), 'ocr'));

		
		// Check to use extension.
		if (!$withExt) return $this->outputFile;

		// Check for custom file extension.
		$hasCustomExt = ['hocr', 'tsv', 'pdf'];
		$ext = in_array($this->configFile, $hasCustomExt) ? $this->configFile : 'txt';

		return "{$this->outputFile}.{$ext}";
	}

	// Get system temporary files dir.
	public function getTempDir()
	{
		return $this->tempDir ?: sys_get_temp_dir();
	}

	// Get Tesseract version.
	public function getTesseractVersion()
	{
		exec(self::escape($this->executable).' --version 2>&1', $output);
		return explode(' ', $output[0])[1];
	}

	// Get lnaguages installed in Tesseract.
	public function getAvailableLanguages()
	{
		exec(self::escape($this->executable) . ' --list-langs 2>&1', $output);
		array_shift($output);
		sort($output);
		return $output;
	}

	// Set escape string by OS.
	public static function escape($str)
	{
		$charlist = strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' ? '$"`' : '$"\\`';
		return '"'.addcslashes($str, $charlist).'"';
	}
}

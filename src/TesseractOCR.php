<?php namespace Karali\TesseractOCR;

use Karali\TesseractOCR\Command;
use Karali\TesseractOCR\Option;
use Karali\TesseractOCR\FriendlyErrors;

/*
|---------------------------------------------
| Tesseract OCR
|---------------------------------------------
|
| Generate user readable errors while also
| throwing the corresponging exceptions.
|
*/

class TesseractOCR
{
	public $command;
	private $outputFile = null;

	public function __construct($image=null, $command=null)
	{
		$this->command = $command ?: new Command;
		$this->image("$image");
	}

	public function run()
	{
		try {
			if ($this->outputFile !== null) {
				// Check for output write permissions.
				FriendlyErrors::checkWritePermissions($this->outputFile);
				$this->command->useFileAsOutput = true;
			}

			// Check if tesseract is installed.
			FriendlyErrors::checkTesseractPresence($this->command->executable);

			if ($this->command->useFileAsInput) {
				// Check if image exists.
				FriendlyErrors::checkImagePath($this->command->image);
			}

			// Create a process for the command.
			$process = new Process("{$this->command}");

			// Check to use file as input.
			if (!$this->command->useFileAsInput) {
				$process->write($this->command->image, $this->command->imageSize);
				$process->closeStdin();
			}

			// Wait for process to execute.
			$output = $process->wait();

			// Check if command executed correctly.
			FriendlyErrors::checkCommandExecution($this->command, $output["out"], $output["err"]);
		}
		// Clean temp files on exception.
		catch (TesseractOcrException $e) {
			if ($this->command->useFileAsOutput) $this->cleanTempFiles();
			throw $e;
		}

		// Check if a file is used as output.
		if ($this->command->useFileAsOutput) {
			$text = file_get_contents($this->command->getOutputFile());

			if ($this->outputFile !== null) {
				rename($this->command->getOutputFile(), $this->outputFile);
			}

			$this->cleanTempFiles();
		}
		else
			$text = $output["out"];

		// Return escaped result.
		return trim($text, " \t\n\r\0\x0A\x0B\x0C");
	}

	// Set image data and size from stdin.
	public function imageData($image, $size)
	{
		FriendlyErrors::checkTesseractVersion("3.03-rc1", "Reading image data from stdin", $this->command);
		$this->command->useFileAsInput = false;
		$this->command->image = $image;
		$this->command->imageSize = $size;
		return $this;
	}

	// Use stdout without using temp files.
	public function withoutTempFiles()
	{
		FriendlyErrors::checkTesseractVersion("3.03-rc1", "Writing to stdout (without using temp files)", $this->command);
		$this->command->useFileAsOutput = false;
		return $this;
	}

	// Set image file for processing.
	public function image($image)
	{
		$this->command->image = $image;
		return $this;
	}

	// Set executable.
	public function executable($executable)
	{
		$this->command->executable = $executable;
		return $this;
	}

	// Set config file for tesseract.
	public function configFile($configFile)
	{
		$this->command->configFile = $configFile;
		return $this;
	}

	// Set command tempDir.
	public function tempDir($tempDir)
	{
		$this->command->tempDir = $tempDir;
		return $this;
	}

	// Set tesseract processing thread limit.
	public function threadLimit($limit)
	{
		$this->command->threadLimit = $limit;
		return $this;
	}

	// @deprecated
	public function format($fmt) { return $this->configFile($fmt); }

	// Set output file.
	public function setOutputFile($path) {
		$this->outputFile = $path;
		return $this;
	}

	// Set char whitelist tesseract.
	public function whitelist()
	{
		$concat = function ($arg) { return is_array($arg) ? join('', $arg) : $arg; };
		$whitelist = join('', array_map($concat, func_get_args()));
		$this->command->options[] = Option::config('tessedit_char_whitelist', $whitelist);
		return $this;
	}

	// Get tesseract version.
	public function version()
	{
		return $this->command->getTesseractVersion();
	}

	// Get installed languages for tesseract.
	public function availableLanguages()
	{
		return $this->command->getAvailableLanguages();
	}

	public function __call($method, $args)
	{
		if ($this->isConfigFile($method)) return $this->configFile($method);
		if ($this->isOption($method)) {
			$option = $this->getOptionClassName().'::'.$method;
			$this->command->options[] = call_user_func_array($option, $args);
			return $this;
		}
		$arg = empty($args) ? null : $args[0];
		$this->command->options[] = Option::config($method, $arg);
		return $this;
	}

	// Check if file is a valid configFile.
	private function isConfigFile($name)
	{
		return in_array($name, ['digits', 'hocr', 'pdf', 'quiet', 'tsv', 'txt']);
	}

	// Check if the option exists and is supported.
	private function isOption($name)
	{
		return in_array($name, get_class_methods($this->getOptionClassName()));
	}

	// Return option namespace.
	private function getOptionClassName()
	{
		return __NAMESPACE__.'\\Option';
	}

	// Delete uneeded output files.
	private function cleanTempFiles()
	{
		if (file_exists($this->command->getOutputFile(false))) {
			unlink($this->command->getOutputFile(false));
		}
		if (file_exists($this->command->getOutputFile(true))) {
			unlink($this->command->getOutputFile(true));
		}
	}
}

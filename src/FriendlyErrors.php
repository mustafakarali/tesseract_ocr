<?php namespace Karali\TesseractOCR;

abstract class TesseractOcrException extends \Exception {}

class ImageNotFoundException extends TesseractOcrException {}
class TesseractNotFoundException extends TesseractOcrException {}
class UnsuccessfulCommandException extends TesseractOcrException {}
class FeatureNotAvailableException extends TesseractOcrException {}
class NoWritePermissionsForOutputFile extends TesseractOcrException {}

/*
|---------------------------------------------
| Friendly Errors
|---------------------------------------------
|
| Generate user readable errors while also
| throwing the corresponging exceptions.
|
*/

class FriendlyErrors
{
	// Verify if the image exists in the path specified.
	public static function checkImagePath($image)
	{
		// Verify if the file exists.
		if (file_exists($image)) return;

		// Error message generation.
		$currentDir = __DIR__;
		$msg = array();
		$msg[] = "Error! The image \"$image\" was not found.";
		$msg[] = '';
		$msg[] = "The current __DIR__ is $currentDir";
		$msg = join(PHP_EOL, $msg);

		throw new ImageNotFoundException($msg);
	}

	// Verify the tesseract binary is installed.
	public static function checkTesseractPresence($executable)
	{
		if (file_exists($executable)) return;

		// Check if OS is Windows.
		$cmd = stripos(PHP_OS, 'win') === 0
			// On windows use NUL.
			? 'where.exe '.Command::escape($executable).' > NUL 2>&1'
			// On Mac & Linux use /dev/.
			: 'type '.Command::escape($executable).' > /dev/null 2>&1';
		system($cmd, $exitCode);

		if ($exitCode == 0) return;

		// Error message generation.
		$currentPath = getenv('PATH');
		$msg = array();
		$msg[] = "Error! The command \"$executable\" was not found.";
		$msg[] = '';
		$msg[] = 'Make sure you have Tesseract OCR installed on your system:';
		$msg[] = 'https://github.com/tesseract-ocr/tesseract';
		$msg[] = '';
		$msg[] = "The current \$PATH is $currentPath";
		$msg = join(PHP_EOL, $msg);

		throw new TesseractNotFoundException($msg);
	}

	// Verify if the command was executed correctly.
	public static function checkCommandExecution($command, $stdout, $stderr)
	{
		// Check for file output.
		if ($command->useFileAsOutput) {
		    $file = $command->getOutputFile();
		    if (file_exists($file) && filesize($file) > 0)  return;
		}

		// Check for stdout output.
		if (!$command->useFileAsOutput && $stdout) {
			return;
		}

		// Error message generation.
		$msg = array();
		$msg[] = 'Error! The command did not produce any output.';
		$msg[] = '';
		$msg[] = 'Generated command:';
		$msg[] = "$command";
		$msg[] = '';
		$msg[] = 'Returned message:';
		$arrayStderr = explode(PHP_EOL, $stderr);
		array_pop($arrayStderr);
		$msg = array_merge($msg, $arrayStderr);
		$msg = join(PHP_EOL, $msg);

		throw new UnsuccessfulCommandException($msg);
	}

	// Check if the command process was created.
	public static function checkProcessCreation($processHandle, $command)
	{
		if ($processHandle !== FALSE) return;

		// Error message generation.
		$msg = array();
		$msg[] = 'Error! The command could not be launched.';
		$msg[] = '';
		$msg[] = 'Generated command:';
		$msg[] = "$command";
		$msg = join(PHP_EOL, $msg);

		throw new UnsuccessfulCommandException($msg);
	}

	// Check if tesseract is updated.
	public static function checkTesseractVersion($expected, $action, $command)
	{
		$actual = $command->getTesseractVersion();

		// Check if the version is supported.
		if ($actual[0] === 'v')
			$actual = substr($actual, 1);

		if (version_compare($actual, $expected, ">=")) return;

		// Error message generation.
		$msg = array();
		$msg[] = "Error! $action is not available this tesseract version";
		$msg[] = "Required version is $expected, actual version is $actual";
		$msg[] = '';
		$msg[] = 'Generated command:';
		$msg[] = "$command";
		$msg = join(PHP_EOL, $msg);

		throw new FeatureNotAvailableException($msg);
	}

	// Check of there are writhe permissions on the path.
	public static function checkWritePermissions($path)
	{
		// Check write permissions on directory.
		if (!is_dir(dirname($path))) mkdir(dirname($path));
		$writableDirectory = is_writable(dirname($path));
		$writableFile = true;
		if (file_exists($path)) $writableFile = is_writable($path);
		if ($writableFile && $writableDirectory) return;

		// Error message generation.
		$msg = array();
		$msg[] = "Error! No permission to write to $path";
		$msg[] = "Make sure you have the right outputFile and permissions "
			."to write to the folder";
		$msg[] = '';
		$msg = join(PHP_EOL, $msg);

		throw new NoWritePermissionsForOutputFile($msg);
	}
}

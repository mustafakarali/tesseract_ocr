<?php namespace Karali\TesseractOCR;

/*
|---------------------------------------------
| Option
|---------------------------------------------
|
| Set parameters to be run in the 
| tesseract binary and check if they
| are avaliable.
|
*/

class Option
{
	/*
	| Specify page segmentation mode.
	| Page segmentation modes:
  	|	0    Orientation and script detection (OSD) only.
	|  1    Automatic page segmentation with OSD.
	|  2    Automatic page segmentation, but no OSD, or OCR. (not implemented)
	|  3    Fully automatic page segmentation, but no OSD. (Default)
	|  4    Assume a single column of text of variable sizes.
	|  5    Assume a single uniform block of vertically aligned text.
	|  6    Assume a single uniform block of text.
	|  7    Treat the image as a single text line.
	|  8    Treat the image as a single word.
	|  9    Treat the image as a single word in a circle.
	| 10    Treat the image as a single character.
	| 11    Sparse text. Find as much text as possible in no particular order.
	| 12    Sparse text with OSD.
	| 13    Raw line. Treat the image as a single text line,
	|       bypassing hacks that are Tesseract-specific.
	*/
	public static function psm($psm)
	{
		return function($version) use ($psm) {
			$version = preg_replace('/^v/', '', $version);
			return (version_compare($version, 4, '>=') ? '-' : '')."-psm $psm";
		};
	}

	/*
	| Specify OCR Engine mode.
	| OCR Engine modes:
	|  0    Legacy engine only.
	|  1    Neural nets LSTM engine only.
	|  2    Legacy + LSTM engines.
	|  3    Default, based on what is available.
	*/
	public static function oem($oem)
	{
		return function($version) use ($oem) {
			self::checkMinVersion('3.05', $version, 'oem');
			return "--oem $oem";
		};
	}

	// Specify the location of user words file.
	public static function userWords($path)
	{
		return function($version) use ($path) {
			self::checkMinVersion('3.04', $version, 'user-words');
			return '--user-words "'.addcslashes($path, '\\"').'"';
		};
	}
	
	// Specify the location of user patterns file.
	public static function userPatterns($path)
	{
		return function($version) use ($path) {
			self::checkMinVersion('3.04', $version, 'user-patterns');
			return '--user-patterns "'.addcslashes($path, '\\"').'"';
		};
	}

	// Specify the location of tessdata path.
	public static function tessdataDir($path)
	{
		return function() use ($path) {
			return '--tessdata-dir "'.addcslashes($path, '\\"').'"';
		};
	}

	// Specify language(s) used for OCR.
	public static function lang()
	{
		$languages = func_get_args();
		return function() use ($languages) {
			return '-l '.join('+', $languages);
		};
	}

	// Set configfile to use.
	public static function config($var, $value)
	{
		return function() use($var, $value) {
			$snakeCase = function($str) {
				return strtolower(preg_replace('/([A-Z])+/', '_$1', $str));
			};
			$pair = $snakeCase($var).'='.$value;
			return '-c "'.addcslashes($pair, '\\"').'"';
		};
	}

	// Check min version of tesseract
	public static function checkMinVersion($minVersion, $currVersion, $option)
	{
		$minVersion = preg_replace('/^v/', '', $minVersion);
		$currVersion = preg_replace('/^v/', '', $currVersion);
		if (!version_compare($currVersion, $minVersion, '<')) return;
		$msg = "$option option is only available on Tesseract $minVersion or later.";
		$msg.= PHP_EOL."Your version of Tesseract is $currVersion";
		throw new \Exception($msg);
	}
}

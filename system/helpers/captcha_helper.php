<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * NOTICE OF LICENSE
 * 
 * Licensed under the Open Software License version 3.0
 * 
 * This source file is subject to the Open Software License (OSL 3.0) that is
 * bundled with this package in the files license.txt / license.rst.  It is
 * also available through the world wide web at this URL:
 * http://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to obtain it
 * through the world wide web, please send an email to
 * licensing@ellislab.com so we can send you a copy immediately.
 *
 * @package		CodeIgniter
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2012, EllisLab, Inc. (http://ellislab.com/)
 * @license		http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * CodeIgniter CAPTCHA Helper
 *
 * @package		CodeIgniter
 * @subpackage	Helpers
 * @category	Helpers
 * @author		EllisLab Dev Team
 * @link		http://codeigniter.com/user_guide/helpers/xml_helper.html
 */

// ------------------------------------------------------------------------

/**
 * Create CAPTCHA
 *
 * @access	public
 * @param	array	array of data for the CAPTCHA
 * @param	string	path to create the image in
 * @param	string	URL to the CAPTCHA image folder
 * @param	string	server path to font
 * @param	array	array of colors
 * 		Es: array('text_color' => '#ffffff','border_color' => '#000000')
 * @return	array
 */
if ( ! function_exists('create_captcha'))
{
	function create_captcha($data = '', $img_path = '', $img_url = '', $font_path = '', $colors = array())
	{
		$defaults = array('word' => '', 'img_path' => '', 'img_url' => '', 'img_width' => '150', 'img_height' => '30', 'font_path' => '', 'expiration' => 7200);

		foreach ($defaults as $key => $val)
		{
			if ( ! is_array($data))
			{
				if ( ! isset($$key) OR $$key == '')
				{
					$$key = $val;
				}
			}
			else
			{
				$$key = ( ! isset($data[$key])) ? $val : $data[$key];
			}
		}

		if ($img_path == '' OR $img_url == '')
		{
			return FALSE;
		}

		if ( ! @is_dir($img_path))
		{
			return FALSE;
		}

		if ( ! is_writable($img_path))
		{
			return FALSE;
		}

		if ( ! extension_loaded('gd'))
		{
			return FALSE;
		}

		// -----------------------------------
		// Remove old images
		// -----------------------------------

		list($usec, $sec) = explode(" ", microtime());
		$now = ((float)$usec + (float)$sec);

		$current_dir = @opendir($img_path);

		while ($filename = @readdir($current_dir))
		{
			if ($filename != "." and $filename != ".." and $filename != "index.html")
			{
				$name = str_replace(".jpg", "", $filename);

				if (($name + $expiration) < $now)
				{
					@unlink($img_path.$filename);
				}
			}
		}

		@closedir($current_dir);

		// -----------------------------------
		// Do we have a "word" yet?
		// -----------------------------------

	   if ($word == '')
	   {
			$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

			$str = '';
			for ($i = 0; $i < 8; $i++)
			{
				$str .= substr($pool, mt_rand(0, strlen($pool) -1), 1);
			}

			$word = $str;
	   }

		// -----------------------------------
		// Determine angle and position
		// -----------------------------------

		$length	= strlen($word);
		$angle	= ($length >= 6) ? rand(-($length-6), ($length-6)) : 0;
		$x_axis	= rand(6, (360/$length)-16);
		$y_axis = ($angle >= 0 ) ? rand($img_height, $img_width) : rand(6, $img_height);

		// -----------------------------------
		// Create image
		// -----------------------------------

		// PHP.net recommends imagecreatetruecolor(), but it isn't always available
		if (function_exists('imagecreatetruecolor'))
		{
			$im = imagecreatetruecolor($img_width, $img_height);
		}
		else
		{
			$im = imagecreate($img_width, $img_height);
		}

		// -----------------------------------
		//  Assign colors
		// -----------------------------------

		$default_color = array(
			'bg_color' 		=> array(255,255,255),
			'border_color' 	=> array(153,102,102),
			'text_color' 	=> array(204,154,153),
			'grid_color' 	=> array(255,182,182),
			'shadow_color' 	=> array(255,240,240)
		);
		
		if (count($colors) > 0)
		{
			foreach ($colors as $key => $val)
			{
				if (isset($default_color[$key]))
				{
					if (is_string($val) && preg_match("/^#[0-9A-F]{3,6}$/i", $val))
					{
						$_hex = _hex2RGB($val);
						if ($_hex !== FALSE)
							$default_color[$key] = array_values($_hex);
					}
					elseif (is_array($val) && count($val) == 3)
					{
						$default_color[$key] = $val;
					}
				}
			}
		}
		
		$bg_color		= imagecolorallocate ($im, $default_color['bg_color'][0], $default_color['bg_color'][1], $default_color['bg_color'][2]);
		$border_color	= imagecolorallocate ($im, $default_color['border_color'][0], $default_color['border_color'][1], $default_color['border_color'][2]);
		$text_color		= imagecolorallocate ($im, $default_color['text_color'][0], $default_color['text_color'][1], $default_color['text_color'][2]);
		$grid_color		= imagecolorallocate ($im, $default_color['grid_color'][0], $default_color['grid_color'][1], $default_color['grid_color'][2]);
		$shadow_color	= imagecolorallocate ($im, $default_color['shadow_color'][0], $default_color['shadow_color'][1], $default_color['shadow_color'][2]);

		// -----------------------------------
		//  Create the rectangle
		// -----------------------------------

		ImageFilledRectangle($im, 0, 0, $img_width, $img_height, $bg_color);

		// -----------------------------------
		//  Create the spiral pattern
		// -----------------------------------

		$theta		= 1;
		$thetac		= 7;
		$radius		= 16;
		$circles	= 20;
		$points		= 32;

		for ($i = 0; $i < ($circles * $points) - 1; $i++)
		{
			$theta = $theta + $thetac;
			$rad = $radius * ($i / $points );
			$x = ($rad * cos($theta)) + $x_axis;
			$y = ($rad * sin($theta)) + $y_axis;
			$theta = $theta + $thetac;
			$rad1 = $radius * (($i + 1) / $points);
			$x1 = ($rad1 * cos($theta)) + $x_axis;
			$y1 = ($rad1 * sin($theta )) + $y_axis;
			imageline($im, $x, $y, $x1, $y1, $grid_color);
			$theta = $theta - $thetac;
		}

		// -----------------------------------
		//  Write the text
		// -----------------------------------

		$use_font = ($font_path != '' AND file_exists($font_path) AND function_exists('imagettftext')) ? TRUE : FALSE;

		if ($use_font == FALSE)
		{
			$font_size = 5;
			$x = rand(0, $img_width/($length/3));
			$y = 0;
		}
		else
		{
			$font_size	= 16;
			$x = rand(0, $img_width/($length/1.5));
			$y = $font_size+2;
		}

		for ($i = 0; $i < strlen($word); $i++)
		{
			if ($use_font == FALSE)
			{
				$y = rand(0 , $img_height/2);
				imagestring($im, $font_size, $x, $y, substr($word, $i, 1), $text_color);
				$x += ($font_size*2);
			}
			else
			{
				$y = rand($img_height/2, $img_height-3);
				imagettftext($im, $font_size, $angle, $x, $y, $text_color, $font_path, substr($word, $i, 1));
				$x += $font_size;
			}
		}


		// -----------------------------------
		//  Create the border
		// -----------------------------------

		imagerectangle($im, 0, 0, $img_width-1, $img_height-1, $border_color);

		// -----------------------------------
		//  Generate the image
		// -----------------------------------

		$img_name = $now.'.jpg';

		ImageJPEG($im, $img_path.$img_name);

		$img = "<img src=\"$img_url$img_name\" width=\"$img_width\" height=\"$img_height\" style=\"border:0;\" alt=\" \" />";

		ImageDestroy($im);

		return array('word' => $word, 'time' => $now, 'image' => $img, 'image_src' => $img_url.$img_name);
	}
	
}

if (! function_exists('_hex2RGB'))
{
	function _hex2RGB($hexStr) {
		
		// Gets a proper hex string
		$hexStr = preg_replace("/[^0-9A-Fa-f]/", '', $hexStr);
		$rgbArray = array();
		
    	if (strlen($hexStr) == 6)
    	{ 
    		//If a proper hex code, convert using bitwise operation. No overhead... faster
			$colorVal = hexdec($hexStr);
			$rgbArray['r']	= 0xFF & ($colorVal >> 0x10);
			$rgbArray['g']	= 0xFF & ($colorVal >> 0x8);
			$rgbArray['b']	= 0xFF & $colorVal;
    	} 
    	elseif (strlen($hexStr) == 3) 
    	{ 
    		//if shorthand notation, need some string manipulations
			$rgbArray['r']	= hexdec(str_repeat(substr($hexStr, 0, 1), 2));
			$rgbArray['g']	= hexdec(str_repeat(substr($hexStr, 1, 1), 2));
			$rgbArray['b']	= hexdec(str_repeat(substr($hexStr, 2, 1), 2));
    	}
    	else
    	{
    		//Invalid hex color code
			return FALSE; 
    	}
    	
		return $rgbArray;
	} 
}

// ------------------------------------------------------------------------

/* End of file captcha_helper.php */
/* Location: ./system/helpers/captcha_helper.php */

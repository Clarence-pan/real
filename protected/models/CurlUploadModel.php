<?php
/////////////////////////////////////////////////////////////////////////////
// 这个文件是 图片上传 项目的一部分
/////////////////////////////////////////////////////////////////////////////
/**
* FILE_NAME : CurlUploadModel.php
* CURL上传类
*
* @author DPX daipengxiang@tuniu.com
* @package
* @subpackage
* @version 2012-10-16
*/
class CurlUploadModel {

    /**
	 * @var  boolean  remove spaces in uploaded files
	 */
	public static $remove_spaces = TRUE;
	
	/**
	 * @var  string  default upload directory
	 */
	public static $default_url = '';
	
	/**
	 * @var string default sub system
	 */
	public static $sub_system = 'vnd';
	/**
	 * @var string place image to save
	 */
	public static $folder = 'static';
	/**
	 * @var string sync to the CDN [0|1|2] 0-only local filebroker 1-sync now 2-sync just a minute, depend to param $folder
	 */
	public static $sync_quick = 2;
	/**
	 * @var string sync place [0|1|2], 0-all 1-nanjing 2-beijing, user like 1,2 or 1
	 */
	public static $sync_place ='1,2';
	
	/**
	 * Save an uploaded file to GFS. If no filename is provided,
	 * the original filename will be used, with a unique prefix added.
	 *
	 * This method should be used after validating the $_FILES array:
	 *
	 *     if ($array->check())
	 	*     {
	 *         // Upload is valid, save it
	 *         Upload::save($array['file']);
	 *     }
	 *
	 * @param   array    uploaded file data
	 * @param   string   new filename
	 * @param   string   new url
	 * @param   integer  chmod mask
	 * @return  string   on success, full path to new file
	 * @return  FALSE    on failure
	 */
	public static function save(array $file, $filename = NULL, $url = NULL)
	{
        if(! self::$default_url){
            self::$default_url = Yii::app()->params['FILEBROKER_HOST'].'upload';
        }

		$fileData = array();
		if ( ! isset($file['tmp_name']) OR ! is_uploaded_file($file['tmp_name']))
		{
			// Ignore corrupted uploads
			return FALSE;
		} else {
			$fileData['file'] = '@'.$file['tmp_name'];
		}
	
		if ($filename === NULL)
		{
			// Use the default filename, with a timestamp pre-pended
			$filename = $file['name'];
		}

		if (self::$remove_spaces === TRUE)
		{
			// Remove spaces from the filename
			$filename = strtr ( base64_encode(preg_replace('/\s+/u', '_', $filename)), array ('+' => '-', '/' => '_' ) );
		}
	
		if ($url === NULL)
		{
			// Use the pre-configured upload directory
			$url = self::$default_url.'?name='.$filename.'&sub_system='.self::$sub_system.'&folder='.self::$folder.'&sync_quick='.self::$sync_quick.'&sync_place='.self::$sync_place;
		}
		// put the image to the real place
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POST, 1 );
		curl_setopt($curl, CURLOPT_POSTFIELDS, $fileData);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_USERAGENT,"Mozilla/4.0");
		$result = curl_exec($curl);
		$error = curl_error($curl);
		return $error ? $error : $result;
	}
	
	/**
	 * Tests if upload data is valid, even if no file was uploaded. If you
	 * _do_ require a file to be uploaded, add the [Upload::not_empty] rule
	 * before this rule.
	 *
	 *     $array->rule('file', 'Upload::valid')
	 *
	 * @param   array  $_FILES item
	 * @return  bool
	 */
	public static function valid($file)
	{
		return (isset($file['error'])
				AND isset($file['name'])
				AND isset($file['type'])
				AND isset($file['tmp_name'])
				AND isset($file['size']));
	}
	
	/**
	 * Tests if a successful upload has been made.
	 *
	 *     $array->rule('file', 'Upload::not_empty');
	 *
	 * @param   array    $_FILES item
	 * @return  bool
	 */
	public static function not_empty(array $file)
	{
		return (isset($file['error'])
				AND isset($file['tmp_name'])
				AND $file['error'] === UPLOAD_ERR_OK
				AND is_uploaded_file($file['tmp_name']));
	}
	
	/**
	 * Test if an uploaded file is an allowed file type, by extension.
	 *
	 *     $array->rule('file', 'Upload::type', array(':value', array('jpg', 'png', 'gif')));
	 *
	 * @param   array    $_FILES item
	 * @param   array    allowed file extensions
	 * @return  bool
	 */
	public static function type(array $file, array $allowed)
	{
		if ($file['error'] !== UPLOAD_ERR_OK)
			return TRUE;
	
		$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	
		return in_array($ext, $allowed);
	}
	
	/**
	 * Validation rule to test if an uploaded file is allowed by file size.
	 * File sizes are defined as: SB, where S is the size (1, 8.5, 300, etc.)
	 * and B is the byte unit (K, MiB, GB, etc.). All valid byte units are
	 * defined in Num::$byte_units
	 *
	 *     $array->rule('file', 'Upload::size', array(':value', '1M'))
	 *     $array->rule('file', 'Upload::size', array(':value', '2.5KiB'))
	 *
	 * @param   array    $_FILES item
	 * @param   string   maximum file size allowed
	 * @return  bool
	 */
	public static function size(array $file, $size)
	{
		if ($file['error'] === UPLOAD_ERR_INI_SIZE)
		{
			// Upload is larger than PHP allowed size (upload_max_filesize)
			return FALSE;
		}
	
		if ($file['error'] !== UPLOAD_ERR_OK)
		{
			// The upload failed, no size to check
			return TRUE;
		}
	
		// Convert the provided size to bytes for comparison
		$size = Num::bytes($size);
	
		// Test that the file is under or equal to the max size
		return ($file['size'] <= $size);
	}
}

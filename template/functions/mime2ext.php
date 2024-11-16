<?php
const MIMES = array(
	"png"=>array("image/png","image/x-png"),
	"bmp"=>array("image/bmp","image/x-bmp","image/x-bitmap","image/x-xbitmap","image/x-win-bitmap","image/x-windows-bmp","image/ms-bmp","image/x-ms-bmp","application/bmp","application/x-bmp","application/x-win-bitmap"),
	"gif"=>array("image/gif"),
	"jpeg"=>array("image/jpeg","image/pjpeg"),
	"jpg"=>array("image/jpg","image/pjpg"),
	"xspf"=>array("application/xspf+xml"),
	"vlc"=>array("application/videolan"),
	"wmv"=>array("video/x-ms-wmv","video/x-ms-asf"),
	"au"=>array("audio/x-au"),
	"ac3"=>array("audio/ac3"),
	"flac"=>array("audio/x-flac"),
	"ogg"=>array("audio/ogg","video/ogg","application/ogg"),
	"kmz"=>array("application/vnd.google-earth.kmz"),
	"kml"=>array("application/vnd.google-earth.kml+xml"),
	"rtx"=>array("text/richtext"),
	"rtf"=>array("text/rtf"),
	"jar"=>array("application/java-archive","application/x-java-application","application/x-jar"),
	"zip"=>array("application/x-zip","application/zip","application/x-zip-compressed","application/s-compressed","multipart/x-zip"),
	"7zip"=>array("application/x-compressed"),
	"xml"=>array("application/xml","text/xml"),
	"svg"=>array("image/svg+xml"),
	"3g2"=>array("video/3gpp2"),
	"3gp"=>array("video/3gp","video/3gpp"),
	"mp4"=>array("video/mp4"),
	"m4a"=>array("audio/x-m4a"),
	"f4v"=>array("video/x-f4v"),
	"flv"=>array("video/x-flv"),
	"webm"=>array("video/webm"),
	"aac"=>array("audio/x-acc"),
	"m4u"=>array("application/vnd.mpegurl"),
	"pdf"=>array("application/pdf","application/octet-stream"),
	"pptx"=>array("application/vnd.openxmlformats-officedocument.presentationml.presentation"),
	"ppt"=>array("application/powerpoint","application/vnd.ms-powerpoint","application/vnd.ms-office","application/msword"),
	"docx"=>array("application/vnd.openxmlformats-officedocument.wordprocessingml.document"),
	"xlsx"=>array("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet","application/vnd.ms-excel"),
	"xl"=>array("application/excel"),
	"xls"=>array("application/msexcel","application/x-msexcel","application/x-ms-excel","application/x-excel","application/x-dos_ms_excel","application/xls","application/x-xls"),
	"xsl"=>array("text/xsl"),
	"mpeg"=>array("video/mpeg"),
	"mov"=>array("video/quicktime"),
	"avi"=>array("video/x-msvideo","video/msvideo","video/avi","application/x-troff-msvideo"),
	"movie"=>array("video/x-sgi-movie"),
	"log"=>array("text/x-log"),
	"txt"=>array("text/plain"),
	"css"=>array("text/css"),
	"html"=>array("text/html"),
	"wav"=>array("audio/x-wav","audio/wave","audio/wav"),
	"xhtml"=>array("application/xhtml+xml"),
	"tar"=>array("application/x-tar"),
	"tgz"=>array("application/x-gzip-compressed"),
	"psd"=>array("application/x-photoshop","image/vnd.adobe.photoshop"),
	"exe"=>array("application/x-msdownload"),
	"js"=>array("application/x-javascript"),
	"mp3"=>array("audio/mpeg","audio/mpg","audio/mpeg3","audio/mp3"),
	"rar"=>array("application/x-rar","application/rar","application/x-rar-compressed"),
	"gzip"=>array("application/x-gzip"),
	"hqx"=>array("application/mac-binhex40","application/mac-binhex","application/x-binhex40","application/x-mac-binhex40"),
	"cpt"=>array("application/mac-compactpro"),
	"bin"=>array("application/macbinary","application/mac-binary","application/x-binary","application/x-macbinary"),
	"oda"=>array("application/oda"),
	"ai"=>array("application/postscript"),
	"smil"=>array("application/smil"),
	"mif"=>array("application/vnd.mif"),
	"wbxml"=>array("application/wbxml"),
	"wmlc"=>array("application/wmlc"),
	"dcr"=>array("application/x-director"),
	"dvi"=>array("application/x-dvi"),
	"gtar"=>array("application/x-gtar"),
	"php"=>array("application/x-httpd-php","application/php","application/x-php","text/php","text/x-php","application/x-httpd-php-source"),
	"swf"=>array("application/x-shockwave-flash"),
	"sit"=>array("application/x-stuffit"),
	"z"=>array("application/x-compress"),
	"mid"=>array("audio/midi"),
	"aif"=>array("audio/x-aiff","audio/aiff"),
	"ram"=>array("audio/x-pn-realaudio"),
	"rpm"=>array("audio/x-pn-realaudio-plugin"),
	"ra"=>array("audio/x-realaudio"),
	"rv"=>array("video/vnd.rn-realvideo"),
	"jp2"=>array("image/jp2","video/mj2","image/jpx","image/jpm"),
	"tiff"=>array("image/tiff"),
	"eml"=>array("message/rfc822"),
	"pem"=>array("application/x-x509-user-cert","application/x-pem-file"),
	"p10"=>array("application/x-pkcs10","application/pkcs10"),
	"p12"=>array("application/x-pkcs12"),
	"p7a"=>array("application/x-pkcs7-signature"),
	"p7c"=>array("application/pkcs7-mime","application/x-pkcs7-mime"),
	"p7r"=>array("application/x-pkcs7-certreqresp"),
	"p7s"=>array("application/pkcs7-signature"),
	"crt"=>array("application/x-x509-ca-cert","application/pkix-cert"),
	"crl"=>array("application/pkix-crl","application/pkcs-crl"),
	"pgp"=>array("application/pgp"),
	"gpg"=>array("application/gpg-keys"),
	"rsa"=>array("application/x-pkcs7"),
	"ics"=>array("text/calendar"),
	"zsh"=>array("text/x-scriptzsh"),
	"cdr"=>array("application/cdr","application/coreldraw","application/x-cdr","application/x-coreldraw","image/cdr","image/x-cdr","zz-application/zz-winassoc-cdr"),
	"wma"=>array("audio/x-ms-wma"),
	"vcf"=>array("text/x-vcard"),
	"srt"=>array("text/srt"),
	"vtt"=>array("text/vtt"),
	"ico"=>array("image/x-icon","image/x-ico","image/vnd.microsoft.icon"),
	"csv"=>array("text/x-comma-separated-values","text/comma-separated-values","application/vnd.msexcel"),
	"json"=>array("application/json","text/json")
);
function mime2ext($mime){
    foreach (MIMES as $key => $value) {
        if(array_search($mime,$value) !== false) {
            return $key;
        }
    }
    return false;
}

function ext2mime($ext)
{
	global $mimes;
	foreach(MIMES as $key=>$value)
	{
		if($key===$ext)
		{
			return $value[0];
		}
	}
	return false;
}
?>
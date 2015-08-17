<?php
/**
 * Teon\Dmarc\Parser
 *
 * Copyright (C) 2015 Teon d.o.o.
 */



/*
 * Namespace definition
 */
namespace Teon\Dmarc\Parser;



/*
 * Class definition
 */
class   AggregateReportParser
{



    /*
     * Parse specified file into report object
     *
     * @param    string          Path to aggregate report file (either .zip, .gz or .xml)
     * @param    bool            Disable XML validation, enabled by default
     * @return   Entity\Report   Parsed report
     */
    public static function parseFile ($filePath, $xmlValidationEnabled=true)
    {
        // Detect media type
        $finfo         = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
        $fileMediaType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        // Act upon detected media type
        switch ($fileMediaType) {
            case 'application/xml':
                $fileContent = file_get_contents($filePath);
                break;
            case 'application/x-gzip':
                $fileContent = self::gunzip($filePath);
                break;
            case 'application/zip':
                $fileContent = self::unzip($filePath);
                break;
            default:
                throw new Exception("Unsupported file media type: $fileMediaType");
        }

        // Validate first
        if ($xmlValidationEnabled) {
            if (!self::validateXmlContent($fileContent)) {
                throw new Exception("XML validation against rua.xsd failed");
            }
        }

        // Parse it
        return self::parseXmlContent($fileContent);
    }



    /*
     * Parse specified file into report object
     *
     * This method does not do validation, which is expected to be done in parseFile() method
     *
     * @param    string   XML content to parse
     * @return   Object of objects and arrays
     */
    public static function parseXmlContent ($xmlContent)
    {
        $xml    = new \SimpleXMLElement($xmlContent);
        $report = \json_decode(json_encode($xml));
        unset($xml);

        // Adjust to array unconditionally
        if (is_array($report->record)) {
            $report->records = $report->record;
        } else {
            $report->records = array($report->record);
        }
        unset($report->record);

        // Fix empty objects - convert them to empty strings
        foreach ($report->records as $recordId => $record) {
            if (isset($record->auth_results->dkim->domain)) {
                if (is_object($record->auth_results->dkim->domain)) {
                    $record->auth_results->dkim->domain = '';
                }
            }
        }

        return $report;
    }



    /*
     * Validate XML content against DMARC rua.xsd
     *
     * @param    string          Content to validate
     * @return   bool
     */
    public static function validateXmlContent ($xmlContent)
    {
        // Load XML content
        $doc = new \DOMDocument();
        $doc->loadXML($xmlContent);

        // Validation #1
        try {
            $xsdFilePath = __DIR__ .'/../xsd/rua.xsd.adapted';
            return $doc->schemaValidate($xsdFilePath, LIBXML_SCHEMA_CREATE);
        } catch (\Exception $e) {
        }

        // Validation #2 - Hotmail bug - validate with alternative syntax
        $xsdFilePath = __DIR__ .'/../xsd/rua.xsd.adapted-hotmail-bug';
        return $doc->schemaValidate($xsdFilePath, LIBXML_SCHEMA_CREATE);
    }



    /**
     * Code below this comment has been copied from this location:
     * https://github.com/solarissmoke/php-dmarc
     * https://packagist.org/packages/solaris/php-dmarc
     *
     * Chages:
     * - switch functions from private to public and static
     * - had the original code been made like that, I would not have bothered to copy and adapt it at all :)
     */



    /*
     * Unzip a zipped DMARC report and return the contents.
     * Assumes (for now) that there is only one file to extract
     */
    public static function unzip( $zipfile ) {
        $zip = zip_open( $zipfile );
        if( !is_resource( $zip ) )
            return false;
        $data = false;
        $zip_entry = zip_read( $zip );
        if (!$zip_entry) {
            return false;
        }
        if( zip_entry_open( $zip, $zip_entry, 'r' ) ) {
            $data = zip_entry_read( $zip_entry, zip_entry_filesize( $zip_entry ) );
            zip_entry_close( $zip_entry );
        }
        zip_close( $zip );
        return $data;
    }
    /*
     * Unzip a gzipped DMARC report and return the contents.
     */
    public static function gunzip( $zipfile ) {
        $gzdata = file_get_contents($zipfile);
        if (!$gzdata) {
            return false;
        }
        if (function_exists('gzdecode')) {
            $data = gzdecode($gzdata);
        } else {
            $data = self::gzdecode($gzdata);
        }
        if (!$data) {
            return false;
        }
        return $data;
    }
    /*
     * http://php.net/gzdecode#82930
     */
    public static function gzdecode($data, &$filename='', &$error='', $maxlength=null) {
        $len = strlen($data);
        if ($len < 18 || strcmp(substr($data,0,2),"\x1f\x8b")) {
            $error = "Not in GZIP format.";
            return null;  // Not GZIP format (See RFC 1952)
        }
        $method = ord(substr($data,2,1));  // Compression method
        $flags  = ord(substr($data,3,1));  // Flags
        if ($flags & 31 != $flags) {
            $error = "Reserved bits not allowed.";
            return null;
        }
        // NOTE: $mtime may be negative (PHP integer limitations)
        $mtime = unpack("V", substr($data,4,4));
        $mtime = $mtime[1];
        $xfl   = substr($data,8,1);
        $os    = substr($data,8,1);
        $headerlen = 10;
        $extralen  = 0;
        $extra     = "";
        if ($flags & 4) {
            // 2-byte length prefixed EXTRA data in header
            if ($len - $headerlen - 2 < 8) {
                return false;  // invalid
            }
            $extralen = unpack("v",substr($data,8,2));
            $extralen = $extralen[1];
            if ($len - $headerlen - 2 - $extralen < 8) {
                return false;  // invalid
            }
            $extra = substr($data,10,$extralen);
            $headerlen += 2 + $extralen;
        }
        $filenamelen = 0;
        $filename = "";
        if ($flags & 8) {
            // C-style string
            if ($len - $headerlen - 1 < 8) {
                return false; // invalid
            }
            $filenamelen = strpos(substr($data,$headerlen),chr(0));
            if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8) {
                return false; // invalid
            }
            $filename = substr($data,$headerlen,$filenamelen);
            $headerlen += $filenamelen + 1;
        }
        $commentlen = 0;
        $comment = "";
        if ($flags & 16) {
            // C-style string COMMENT data in header
            if ($len - $headerlen - 1 < 8) {
                return false;    // invalid
            }
            $commentlen = strpos(substr($data,$headerlen),chr(0));
            if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8) {
                return false;    // Invalid header format
            }
            $comment = substr($data,$headerlen,$commentlen);
            $headerlen += $commentlen + 1;
        }
        $headercrc = "";
        if ($flags & 2) {
            // 2-bytes (lowest order) of CRC32 on header present
            if ($len - $headerlen - 2 < 8) {
                return false;    // invalid
            }
            $calccrc = crc32(substr($data,0,$headerlen)) & 0xffff;
            $headercrc = unpack("v", substr($data,$headerlen,2));
            $headercrc = $headercrc[1];
            if ($headercrc != $calccrc) {
                $error = "Header checksum failed.";
                return false;    // Bad header CRC
            }
            $headerlen += 2;
        }
        // GZIP FOOTER
        $datacrc = unpack("V",substr($data,-8,4));
        $datacrc = sprintf('%u',$datacrc[1] & 0xFFFFFFFF);
        $isize = unpack("V",substr($data,-4));
        $isize = $isize[1];
        // decompression:
        $bodylen = $len-$headerlen-8;
        if ($bodylen < 1) {
            // IMPLEMENTATION BUG!
            return null;
        }
        $body = substr($data,$headerlen,$bodylen);
        $data = "";
        if ($bodylen > 0) {
            switch ($method) {
            case 8:
                // Currently the only supported compression method:
                $data = gzinflate($body,$maxlength);
                break;
            default:
                $error = "Unknown compression method.";
                return false;
            }
        }  // zero-byte body content is allowed
        // Verifiy CRC32
        $crc   = sprintf("%u",crc32($data));
        $crcOK = $crc == $datacrc;
        $lenOK = $isize == strlen($data);
        if (!$lenOK || !$crcOK) {
            $error = ( $lenOK ? '' : 'Length check FAILED. ') . ( $crcOK ? '' : 'Checksum FAILED.');
            return false;
        }
        return $data;
    }
}

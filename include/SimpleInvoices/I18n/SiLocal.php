<?php
namespace SimpleInvoices\I18n;

/**
 * Wrapper class for some INTL methods
 */
class SiLocal 
{
    /**
     * Wrapper function for INTL NumberFormatter
     * 
     * @param unknown $number
     * @param int $precision
     * @param string $locale
     * @return unknown
     */
    public static function number($number, $fractionDigits = null, $locale = null)
    {
        global $config;
        
        $locale         = !empty($locale)         ? $locale               : $config->local->locale;
        $fractionDigits = !empty($fractionDigits) ? (int) $fractionDigits : (int) $config->local->precision; 
        
        $formatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $fractionDigits);
        return $formatter->format($number);
    }
    
    /**
     * Remove trailing and leading zeros - just to return cleaner number in invoice creation from ajax product change
     * 
     * @param unknown $num
     */
    public function number_clean($num)
    {
        //remove zeros from end of number ie. 140.00000 becomes 140.
        $clean = rtrim($num, '0');
        //remove zeros from front of number ie. 0.33 becomes .33
        $clean = ltrim($clean, '0');
        //remove decimal point if an integer ie. 140. becomes 140
        $clean = rtrim($clean, '.');
        
        return $clean;
    }
    
    /**
     * 
     * @param unknown $number
     */
    public static function number_trim($number)
    {
        global $config;        
        
        $formatted_number = SiLocal::number($number);
        
        //get the precision and add 1 - for the decimal place and reverse the sign
        $position = ($config->local->precision + 1 ) * -1;
        
        if(substr($formatted_number,$position,'1') == ".") {
            $formatted_number = rtrim(trim($formatted_number, '0'), '.');
        }
        
        if(substr($formatted_number,$position,'1') == ",") {
            $formatted_number = rtrim(trim($formatted_number, '0'), ','); /* Added to deal with "," */
        }
        
        return $formatted_number;
    }
	
    /**
     * Wrapper function for zend_date
     * @param \DateTime|string $date
     * @param string $length
     * @param string $locale
     */
    public static function date($date, $length = null, $locale = null)
    {
        global $config;
        
        $locale = !empty($locale) ? $locale : $config->local->locale;
        
        if (!$date instanceof \DateTime) {
            if (is_string($date)) {
                if (preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2})/', $date, $match)) {
                    $date = $match[1];
                }
            
                $dateTime = \DateTime::createFromFormat('Y-m-d', $date);
                if (!$dateTime) {
                    // TODO: Maybe throw an exception
                    return $date;
                }
                
                $date = $dateTime;
            } else {
                // TODO: Other methods or throw an exception
            }
        }
        
        // IntlDateFormatter::NONE - exclude this element from display
        // IntlDateFormatter::SHORT - shortest format (22/07/2007)
        // IntlDateFormatter::MEDIUM - abbreviated format (Jul 22, 2007)
        // IntlDateFormatter::LONG - unabbreviated format (July 22, 2007)
        // IntlDateFormatter::FULL - full date information
        switch ($length) {
            case "full":
                $formatter = new \IntlDateFormatter(
                    $locale,
                    \IntlDateFormatter::FULL,     // Date type
                    \IntlDateFormatter::NONE      // Time type
                );
                break;
            case "long":
                $formatter = new \IntlDateFormatter(
                    $locale,
                    \IntlDateFormatter::LONG,     // Date type
                    \IntlDateFormatter::NONE      // Time type
                );
                break;
            case "medium":
                $formatter = new \IntlDateFormatter(
                    $locale,
                    \IntlDateFormatter::MEDIUM,   // Date type
                    \IntlDateFormatter::NONE      // Time type
                );
                break;
            case "short":
                $formatter = new \IntlDateFormatter(
                    $locale,
                    \IntlDateFormatter::SHORT,    // Date type
                    \IntlDateFormatter::NONE      // Time type
                );
                break;
            case "month":
                $formatter = new \IntlDateFormatter(
                    $locale,
                    \IntlDateFormatter::FULL,     // Date type
                    \IntlDateFormatter::NONE      // Time type
                );
                $formatter->setPattern('MMMM');
                break;
            case "month_short":
                $formatter = new \IntlDateFormatter(
                    $locale,
                    \IntlDateFormatter::FULL,     // Date type
                    \IntlDateFormatter::NONE      // Time type
                );
                $formatter->setPattern('MMM');
                break;
            default:
                $formatter = new \IntlDateFormatter(
                    $locale,
                    \IntlDateFormatter::SHORT,    // Date type
                    \IntlDateFormatter::NONE      // Time type
                );
        }
        
        return $formatter->format($date);
	}
    
	/**
	 * Wrapper for php number_format
	 * 
	 * Purpose: to format numbers for data entry fields - ie invoice edit/ajax where data is in 6 decimial places but only neex x places in edit view
	 * 
	 * @param unknown $number
	 */
    public static function number_formatted($number)
    {
        global $config;
        
        $number_formatted = number_format($number, $config->local->precision, '.', '');
        return $number_formatted;
    }
}

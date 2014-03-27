<?php
namespace sis2ics;

/**
 * parser, which identifies the remaining games by sis-handball.de
 *      -> exports it to .ics
 *
 * @category  PHP
 * @package   sis2ics
 * @author Philipp Bergt
 * @copyright 2014 Philipp Bergt
 */
class SISParser {
    
    private $url;
    private $title;
    
    /**
     * contructor
     * @param string $url e.g.http://sis-handball.de/web/Mannschaft/?view=Mannschaft&Liga=001514000000000000000000000000000001003
     * @throws NoValidUrlException
     */
    public function __construct($url, $title) {
        // checks if it's a valid url
        if(filter_var($url, FILTER_VALIDATE_URL) !==  $url)
            throw new \UnexpectedValueException("Please specify a valid url!");
        
        // checks if title is given
        if((trim($title)) == "")
            throw new \UnexpectedValueException("Please specify a title!");
        
        // sets url and title
        $this->url = $url;
        $this->title = $title;
    }
    
    /**
     * analyzes the source code and returns the ics file
     */
    public function execute() {
        // gets the source code by specified url
        $html = $this->getHTML();
        
        // parses the code
        $resultArray = $this->extractContent($html);
        
        // content to ics format
        $format = $this->exportToICal($resultArray);
        
        // writes it to the output file
        file_put_contents ("output.ics", $format);
        
        // we'll be outputting a ics
        header('Content-type: application/ics');

        // it will be called downloaded.ics
        header('Content-Disposition: attachment; filename="downloaded.ics"');

        // the ics source is in output.ics
        readfile('output.ics');
    }
    
    /**
     * parse the code by regex
     * @param string $html 
     * @return array results
     */
    private function extractContent ($html) {
        //parse the code by regex
        $pattern = "#<tr.*><td.*><a.*>(.*)</a></td><td.*>.*</td><td.*><a.*>(.*)"
                . "</a></td><td.*>(.*)</td><td.*><a.*>(.*)</a></td><td.*><a.*>"
                . "(.*)</a></td><td>(.*)</td></tr>#i";
        preg_match_all($pattern, $html, $matches);
        
        // check if matches was found
        $matchesLength = count($matches);
       
        // return null, if there's no match
        if($matchesLength == 0)
            return NULL;
        
        $results = count($matches[0]);
        $outputArray;
        
        // transforms the array of contents
        for($i = 1; $i < $matchesLength; ++$i) 
            for($j = 0; $j < $results; ++$j) 
                $outputArray[$j][$i-1] = $matches[$i][$j];

        return $outputArray;
    }
    
    /**
     * creates ical format, see: http://tools.ietf.org/html/rfc5546
     * @param array $elementArray array of results
     * @return string
     */
    private function exportToICal($elementArray) {
        $output = "BEGIN:VCALENDAR\r\n";
        $output.= "VERSION:2.0\r\n";
        $output.= "PRODID:".$this->url."\r\n";
        
        foreach($elementArray as $elements) {
            $output.= "BEGIN:VEVENT\r\n";
            $output.= "UID:".$elements[0]."@".$this->url."\r\n";
            $output.= "DTSTART:".$this->toDateTime($elements[1], $elements[2])
                    ."\r\n";
            $output.= "SUMMARY:".$elements[3]." - ".$elements[4]." ("
                    .$this->title.")\r\n";
            $output.= "DESCRIPTION:".$this->title."\\n".$elements[3]." - "
                    .$elements[4]."\\n".$elements[1]."\\n".$elements[2]."\\n"
                    .$elements[5]."\r\n";
            $output.= "CATEGORIES:Sport, Handball\r\n";
            $output.= "TRANSP:OPAC\r\n";
            $output.= "STATUS: CONFIRMED\r\n";
            $output.= "URL:<a itemprop=\"url\" href=\"".$this->url."\">"
                    . "zu sis-handball.de</a>\r\n";
            $output.= "CLASS:PUBLIC\r\n";
            $output.= "END:VEVENT\r\n";
        }
    
        $output.= "END:VCALENDAR";
        
        return $output;
    }
    
    
    /**
     * gets the source code by specified url
     * @return string html
     */
    private function getHTML() {
       return file_get_contents($this->url);
    }
    
    /**
     * creates Date-Time format
     * @param string $date
     * @param string $time
     * @return string
     */
    private function toDateTime($date, $time) {
        
        //removes the colon
        $time = trim($time);
        $time = str_replace(":", "", $time);
        
        //parses the given date
        $date = trim($date);
        $dateArray = date_parse_from_format("d.m.y", $date);
        
        // we need two digits
        $month = ($dateArray["month"] <= 9) ? "0".$dateArray["month"] : 
            $dateArray["month"];
        
        // same as above
        $day = ($dateArray["day"] <= 9) ? "0".$dateArray["day"] : 
            $dateArray["day"];
        
        // creates the Date-Time format
        // YYYYMMDDTHHIISS e.g. 20040314T150000 for 14.03.2004 15:00
        $dateTime = $dateArray["year"].$month.$day."T"
                .$time."00";
        
        // return is
        return $dateTime;
    }
    
}

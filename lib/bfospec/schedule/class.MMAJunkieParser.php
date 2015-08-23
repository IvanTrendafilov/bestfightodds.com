<?php

require_once('lib/bfocore/parser/utils/class.ParseTools.php');

class MMAJunkieParser
{
    public static function fetchSchedule()
    {
        $sURL = 'http://api.mmajunkie.com/rumors/rss';
        //$sURL = 'http://localhost:8080/rss.txt';
        $aCurlOpts = array(CURLOPT_USERAGENT => 'MWFeedParser');
        $sContents = ParseTools::retrievePageFromURL($sURL, $aCurlOpts);

        $oXML = simplexml_load_string($sContents);
        if ($oXML == false)
        {
            //TODO: Error handling
        }

        $aEvents = array();

        if (sizeof($oXML->channel->item) < 5)
        {
            //List too small. suspicious..
            //TODO: error handling
        }

        foreach ($oXML->channel->item as $item)
        {
            $aNewEvent = array();

            $aNewEvent['title'] = self::renameEvents((string) $item->title);

            $sEventRegexp = '/<p>Date: ([^<]+)<\/p>/';
            $aMatches = ParseTools::matchBlock($item->description, $sEventRegexp);
            $aNewEvent['date'] = strtotime($aMatches[0][1]);

            $sFightRegexp = '/<li>[^<]*<a[^>]*>([^<]*)<\/a[^>]*>[^<]*<a[^>]*>([^<]*)<\/a[^>]*>[^<]*<\/li>/';
            $aMatches = ParseTools::matchBlock($item->description, $sFightRegexp);
            $aNewEvent['matchups'] = array();
            foreach ($aMatches as $aMatch)
            {
                //Ignore entries containing TBA
                if (strpos($aMatch[1], 'TBA') === false && strpos($aMatch[2], 'TBA') === false)
                {
                    $aNewEvent['matchups'][] = array(ParseTools::formatName($aMatch[1]), ParseTools::formatName($aMatch[2]));
                }
            }
            //Only add events that have at least one matchup, also that are not in the past
            if (count($aNewEvent['matchups']) > 0 && $aNewEvent['date'] > time())
            {
                $aEvents[] = $aNewEvent;
            }
        }
        return $aEvents;
    }

    private static function renameEvents($a_sEventName)
    {
        $a_sEventName = trim($a_sEventName);
        //Starts with "The Ultimate Fighter"
        if (0 === strpos(strtolower($a_sEventName), 'the ultimate fighter')) 
        {
            $a_sEventName = 'UFC: ' . $a_sEventName;
        }
        return $a_sEventName;

    }
}

?>
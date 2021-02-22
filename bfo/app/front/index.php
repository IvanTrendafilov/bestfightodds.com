<?php

/**
 * Index - Main access point for front end
 *
 * The following constants can be set to overwrite contents in the HTML page:
 *   PAGE_OVERRIDE_TITLE - Page title. Will be in the format "BestFightOdds.com - <overridden_title>"
 *   PAGE_OVERRIDE_DESCRIPTION - Meta description. Will be appended to the beginning of the standard description
 *   PAGE_OVERRIDE_KEYWORDS - Meta keywords. Will be appended to the beginning of the keywords
 *
 */
require_once('lib/bfocore/general/class.FighterHandler.php');
require_once('lib/bfocore/general/class.EventHandler.php');
require_once('lib/bfocore/utils/class.LinkTools.php');

//Enable override of cookies through params
if (isset($_GET['display']))
{
    if ($_GET['display'] == 'moneyline')
    {
        setcookie('bfo_odds_type', 1, time() + (60 * 60 * 24 * 365), '/');
    }
    else if ($_GET['display'] == 'decimal')
    {
        setcookie('bfo_odds_type', 2, time() + (60 * 60 * 24 * 365), '/');
    }
    else if ($_GET['display'] == 'return')
    {
        setcookie('bfo_odds_type', 3, time() + (60 * 60 * 24 * 365), '/');
        setcookie('bfo_risk_amount', 100, time() + (60 * 60 * 24 * 365), '/');
    }
}
if (isset($_GET['risk']))
{
    if (is_numeric($_GET['risk']) && $_GET['risk'] > 0)
    {
        if ($_GET['risk'] >= 10000)
        {
            setcookie('bfo_risk_amount', 9999, time() + (60 * 60 * 24 * 365), '/');
        }
        else
        {
            setcookie('bfo_risk_amount', $_GET['risk'], time() + (60 * 60 * 24 * 365), '/');
        }
    }
}

if (isset($_GET['desktop']) && $_GET['desktop'] == 'on')
{
    setcookie("bfo_reqdesktop", "true");
    header('Location: /');
    exit;
}
else if (isset($_GET['desktop']) && $_GET['desktop'] == 'off')
{
    setcookie("bfo_reqdesktop", "", time()-3600);
    header('Location: /');
    exit;
}


//Disable caching
header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
header('Expires: Mon, 12 Jul 1996 04:11:00 GMT'); //Any date passed.
header('Pragma: no-cache');

$oEvent = null;

//Override titles, descriptions and keywords
if (isset($_GET['p']))
{
    define('CURRENT_PAGE', $_GET['p']);
    switch ($_GET['p'])
    {
        case 'alerts': define('PAGE_OVERRIDE_TITLE', 'Alerts');
            break;
        case 'links': define('PAGE_OVERRIDE_TITLE', 'On your website/blog');
            break;
        case 'fighter':
            $oFighter = FighterHandler::getFighterByID($_GET['fighterID']);
            if ($oFighter != null)
            {
                define('PAGE_OVERRIDE_TITLE', $oFighter->getNameAsString() . '\'s MMA Odds History');
                define('PAGE_OVERRIDE_DESCRIPTION', $oFighter->getNameAsString() . ' betting odds history.');
                define('PAGE_OVERRIDE_KEYWORDS', $oFighter->getNameAsString());
            }
            break;
        case 'event':
            $oEvent = EventHandler::getEvent($_GET['eventID']);
            if ($oEvent != null)
            {
                define('PAGE_OVERRIDE_TITLE', $oEvent->getName() . ' Odds & Betting Lines');
                define('PAGE_OVERRIDE_DESCRIPTION', $oEvent->getName() . ' odds & betting lines.');
                define('PAGE_OVERRIDE_KEYWORDS', $oEvent->getName());
            }
            break;
        case 'matchup':
            $oMatchup = EventHandler::getFightByID($_GET['matchupID']);
            if ($oMatchup != null)
            {
                define('PAGE_OVERRIDE_TITLE', $oMatchup->getTeamAsString(1) . ' vs. ' . $oMatchup->getTeamAsString(2) . ' Odds & Betting Lines');
                define('PAGE_OVERRIDE_DESCRIPTION', $oMatchup->getTeamAsString(1) . ' vs. ' . $oMatchup->getTeamAsString(2) . ' odds & betting lines.');
                define('PAGE_OVERRIDE_KEYWORDS', $oMatchup->getTeamAsString(1) . ', ' . $oMatchup->getTeamAsString(2));
            }
            break;
        default:
    }
}
else
{
    define('CURRENT_PAGE', '');
}

//Anti scraping check and SEO redirect for old event page URLs
if (isset($_GET['p']) && $_GET['p'] == 'event' && $oEvent != null)
{
    //Check if incoming URL matches the slug URL for this event. If partially, we accept this and redirect with 301 to the real URL.
    //If not partially, then we assume its a bot scraping so we redirect to the main page

    $request_url = rtrim($_SERVER['REQUEST_URI'], "/");

    if ('/events/' . $oEvent->getEventAsLinkString() != $request_url)
    {
        //URL does not match, check partial match
        $iMarkPos = strpos($oEvent->getName(), ':') != null ? strpos($oEvent->getName(), ':') : strlen($oEvent->getName()); //Find position of ':'
        $sMatchEvent = strtolower(LinkTools::slugString(substr($oEvent->getName(), 0, $iMarkPos)));

        if ($sMatchEvent == strtolower(substr($request_url, 8, strlen($sMatchEvent))))
        {
            //Slug matches partially, redirect with 301 to real URL
            error_log('Incorrect slug URL, correcting with 301: ' . $_SERVER['REQUEST_URI'] . ' - New: /events/' . $oEvent->getEventAsLinkString()); //TODO: Can probably be removed later on when stable
            header('Location: https://' . $_SERVER['SERVER_NAME'] . '/events/' . $oEvent->getEventAsLinkString(), true, 301);
            exit;
        }
        else
        {
            //Slug does not match partially, redirect to main page with a 302
            header('Location: https://' . $_SERVER['SERVER_NAME'] . '/', true, 302);
            exit;
        }
    }

}
else if (isset($_GET['p']) && $_GET['p'] == 'event' && $oEvent == null)
{
    //No event found, redirect (302 Temporary) to main page
    error_log('Invalid event requested at ' . $_SERVER['REQUEST_URI']);
    header('Location: https://' . $_SERVER['SERVER_NAME'] . '/', true, 302);
    exit;
}




include_once('app/front/pages/inc.Top.php');

if (isset($_GET['p']) && (
        $_GET['p'] == 'alerts' ||
        $_GET['p'] == 'archive' ||
        $_GET['p'] == 'links' ||
        $_GET['p'] == 'fighter' ||
        $_GET['p'] == 'terms' ||
        $_GET['p'] == 'event' ||
        $_GET['p'] == 'event2' ||
        $_GET['p'] == 'stopscrape' || 
        $_GET['p'] == 'odds' ||
        $_GET['p'] == 'matchup' ||
        $_GET['p'] == 'prefightreport' ||
        $_GET['p'] == 'bookiesettings'
        ))
{
    require_once('app/front/pages/inc.FrontLogic.php');
    include_once('app/front/pages/page.' . $_GET['p'] . '.php');
}
else
{
    //Include scraper stop page if non allowed user agent is found
    if (isset($_SERVER['HTTP_USER_AGENT']) && ($_SERVER['HTTP_USER_AGENT'] == 'Apache-HttpClient/UNAVAILABLE (java 1.4)' ||  $_SERVER['HTTP_USER_AGENT'] == 'libwww-perl/6.02'))
    {
        require_once('app/front/pages/inc.FrontLogic.php');
        include_once('app/front/pages/page.stopscrape.php');
    }
    else
    {
        require_once('app/front/pages/inc.FrontLogic.php');
        include_once('app/front/pages/page.odds.php');
    }
}

include_once('app/front/pages/inc.Bottom.php');
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"> 
    <head>
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
        <meta name="description" content="<?php echo (defined('PAGE_OVERRIDE_DESCRIPTION') ? PAGE_OVERRIDE_DESCRIPTION . ' ' : ''); ?>Mixed Martial Arts odds comparison service. Find and compare the latest MMA lines from the top online betting sites." />
        <meta name="keywords" content="<?php echo (defined('PAGE_OVERRIDE_KEYWORDS') ? PAGE_OVERRIDE_KEYWORDS . ', ' : ''); ?>mma odds, mma betting, mma lines, ufc odds, ufc, mma, odds, betting" />
        <meta property="og:image" content="https://www.bestfightodds.com/img/iconv2.jpg" />
        <link href='http://fonts.googleapis.com/css?family=Roboto:500,700,400' rel='stylesheet' type='text/css'>
        <script type="text/javascript">
        if( screen.width < 468 ) {
            document.write( '<meta name="viewport" content="width=device-width, maximum-scale=1.0, minimum-scale=1.0, initial-scale=1.0" />' );
        }
        </script>
        <link rel="stylesheet" type="text/css" href="/css/stylesheets.php" />
        <link rel="shortcut icon" href="https://www.bestfightodds.com/favicon.ico" />
        <link rel="apple-touch-icon" sizes="144x144" href="/apple-touch-icon-144x144.png" />
        <link rel="apple-touch-icon" sizes="114x114" href="/apple-touch-icon-114x114.png" />
        <link rel="apple-touch-icon" sizes="72x72" href="/apple-touch-icon-72x72.png" />
        <link rel="apple-touch-icon" href="/apple-touch-icon-iphone.png" />
        <title><?php echo (defined('PAGE_OVERRIDE_TITLE') ? PAGE_OVERRIDE_TITLE . ' | Best Fight Odds': 'UFC &amp; MMA Odds &amp; Betting Lines | Best Fight Odds'); ?></title>
    </head>
    <!--[if lte IE 7]>
    <link rel="stylesheet" type="text/css" href="/css/bfo-ie7.css" />
    <![endif]--> 
    <!--[if IE 8]>
    <link rel="stylesheet" type="text/css" href="/css/bfo-ie8.css" />
    <![endif]--> 
    <!--[if IE 9]>
    <link rel="stylesheet" type="text/css" href="/css/bfo-ie9.css" />
    <![endif]--> 
    <body>
        <script type="text/javascript" async src="/js/javascripts.php"></script>
        <div id="chart-window" class="popup-window"><div class="popup-header" id="chart-header"><div></div><a href="#" class="cd-popup-close">&#10006;</a></div><div id="chart-area"></div></div>
        <div id="parlay-window" class="popup-window"><div class="popup-header" id="parlay-header">Parlay</div><div id="parlay-area">Click on a line to add it to your parlay</div></div>
        <div id="alert-window" class="popup-window"><div class="popup-header" id="alert-header"><div></div><a href="#" class="cd-popup-close">&#10006;</a></div><div id="alert-area">
            <form id="alert-form">Alert me at e-mail <input type="text" name="alert-mail" id="alert-mail"><br />when the odds reaches <input type="text" name="alert-odds" id="alert-odds"> or better<br/>at <select name="alert-bookie">
                <option value="-1">any bookie</option>
                <option value="1">5Dimes</option>
                <option value="13">BetDSI</option>
                <option value="3">BookMaker</option>
                <option value="5">Bovada</option>
                <option value="2">SportBet</option>
                <option value="4">Sportsbook</option>
                <option value="7">BetUS</option>
                <option value="9">Pinnacle</option>
                <option value="8">SportsInteraction</option>
                <option value="10">SBG</option>
                <option value="11">TheGreek</option>
                <option value="12">BetOnline</option>
              </select><br /><div id="alert-button-container"><input type="hidden" name="tn"><input type="hidden" name="m">
                <div class="alert-loader"></div>
                <div class="alert-result">&nbsp;</div>
              <input type="submit" value="Add alert" id="alert-submit"></div></form></div>
        </div>
        <div class="header">
                <div class="header-top">
                    <a href="/"><img src="/img/logo.png" class="logo" alt="BestFightOdds.com" /></a>
                    <div id="header-search-box">
                        <form method="get" action="/search"><input type="text" id="search-box1" class="search-box" name="query" placeholder="MMA Event / Fighter"/> <input type="submit" class="search-button" id="search-button" value="&#128269;" /></form>
                    </div>
                </div>
                <div class="header-menu-wrapper">
                        <div class="header-menu">
                        <a href="/"><div class="header-menu-item <?php echo CURRENT_PAGE == '' ? ' header-menu-selected ' : ''; ?>" style="margin-left: 10px">Latest odds</div></a>
                        <a href="/archive"><div class="header-menu-item <?php echo (CURRENT_PAGE == 'archive' || CURRENT_PAGE == 'fighter' || CURRENT_PAGE == 'event' || CURRENT_PAGE == 'search_results') ? ' header-menu-selected ' : ''; ?>">Archive</div></a>
                        <a href="/alerts"><div class="header-menu-item <?php echo CURRENT_PAGE == 'alerts' ? ' header-menu-selected ' : ''; ?>">Alerts</div></a>
                        <a href="/links"><div class="header-menu-item <?php echo CURRENT_PAGE == 'links' ? ' header-menu-selected ' : ''; ?> item-non-mobile">Widget/Feed</div></a>
                        <a href="http://twitter.com/bestfightodds" target="_blank"><div class="header-menu-item"><img src="/img/twitter.png" id="twitter-icon" alt="Twitter icon" /></div></a>
                        <div id="header-menu-right">
                           <div id="parlay-mode-container" 
                                 <?php
                                 //Only display parlay-mode container if we are displaying an event or are on the front page
                                 if (CURRENT_PAGE != '' && CURRENT_PAGE != 'event')
                                 {
                                     echo ' style="visibility: hidden" ';
                                 }
                                 ?>
                                 >
                                <a href="#" id="parlay-mode-box"><div class="bfo-check-box">✔</div> Parlay</a>
                            </div>
                            <div class="menu-divider"
                                <?php
                                 //Only display autorefresh-mode container if we are displaying an event or are on the front page
                                 if (CURRENT_PAGE != '')
                                 {
                                     echo ' style="display: none" ';
                                 }
                                 ?>
                            ></div>                 
                            <div id="auto-refresh-container"
                                <?php
                                 //Only display autorefresh-mode container if we are displaying an event or are on the front page
                                 if (CURRENT_PAGE != '')
                                 {
                                     echo ' style="display: none" ';
                                 }
                                 ?>
                                >
                                <ul class="dropdown">
                                    <li><a href="#"  onclick="toggleRefresh()" style="padding-left: 20px;"><img src="/img/refresh.png" class="refresh-ind" id="autoRefresh" alt="Toggle auto-refresh"/>&#9660;</a>
                                            <ul class="sub_menu">
                                                 <li><a href="#" id="afSelectorOn" class="list-checked"><span style="display: inline-block">&#10004;</span>Auto-refresh: On</a></li>
                                                 <li><a href="#" id="afSelectorOff"><span>&#10004;</span>Auto-refresh: Off</a></li>
                                            </ul>
                                    </li>
                                </ul>
                            </div>
                            <div class="menu-divider"
                                <?php
                                 //Only display autorefresh-mode container if we are displaying an event or are on the front page
                                 if (CURRENT_PAGE != '')
                                 {
                                     echo ' style="display: none" ';
                                 }
                                 ?>
                            ></div>
                            <div id="format-container">
                                <ul class="dropdown">
                                    <li><a href="#" id="format-toggle-text"><span class="item-non-mobile">Format: </span><span>Moneyline &#9660;</span></a>
                                        <ul class="sub_menu">
                                             <li><a href="#" id="formatSelector1" class="list-checked"><span style="display: inline-block">&#10004;</span>Moneyline</a></li>
                                             <li><a href="#" id="formatSelector2"><span>&#10004;</span>Decimal</a></li>
                                             <li><a href="#" id="formatSelector3" style="display: inline"><span>&#10004;</span>Return on </a>$<input type="text" name="amountBox" id="format-amount-box1" maxlength="4" value="100" style="display: inline"/></li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
                <div id="content">    

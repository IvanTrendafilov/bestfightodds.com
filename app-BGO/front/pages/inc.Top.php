<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"> 
    <head>
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
        <meta name="description" content="<?php echo (defined('PAGE_OVERRIDE_DESCRIPTION') ? PAGE_OVERRIDE_DESCRIPTION . ' ' : ''); ?>Mixed Martial Arts odds comparison service. Find and compare the latest MMA lines from the top online betting sites." />
        <meta name="keywords" content="<?php echo (defined('PAGE_OVERRIDE_KEYWORDS') ? PAGE_OVERRIDE_KEYWORDS . ', ' : ''); ?>mma odds, mma betting, mma lines, ufc odds, ufc, mma, odds, betting" />
        <link rel="stylesheet" type="text/css" href="/css/stylesheets.php" />
        <link rel="shortcut icon" href="http://www.bestfightodds.com/favicon.ico" />
        <link rel="apple-touch-icon" sizes="144x144" href="apple-touch-icon-144x144.png" />
        <link rel="apple-touch-icon" sizes="114x114" href="apple-touch-icon-114x114.png" />
        <link rel="apple-touch-icon" sizes="72x72" href="apple-touch-icon-72x72.png" />
        <link rel="apple-touch-icon" href="apple-touch-icon-iphone.png" />
        <script type="text/javascript" src="https://www.google.com/jsapi"></script>
        <script language="JavaScript" type="text/javascript" src="/js/javascripts.php"></script>
        <title><?php echo (defined('PAGE_OVERRIDE_TITLE') ? PAGE_OVERRIDE_TITLE . ' | BestFightOdds': 'UFC &amp; MMA Odds &amp; Betting Lines | BestFightOdds'); ?></title>
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
        <div id="page" >
            <div id="main">
                <div id="header-top">
                    <a href="/"><img src="/img/logo.gif" id="logo" alt="BestFightOdds.com"/></a>
                    <div id="header-search-box">
                        <form method="get" action="/search"><input type="text" id="search-box1" class="search-box" name="query" onmousedown="useSearchBox()" /> <input type="submit" class="search-button" id="search-button" value="Search" /></form>
                    </div>
                </div>
                <div id="header-menu">
                    <div class="header-menu-item" <?php echo CURRENT_PAGE == '' ? ' id="header-menu-selected" ' : ''; ?>><a href="/">Latest odds</a></div>
                    <div class="header-menu-item" <?php echo (CURRENT_PAGE == 'archive' || CURRENT_PAGE == 'fighter' || CURRENT_PAGE == 'event' || CURRENT_PAGE == 'search_results') ? ' id="header-menu-selected" ' : ''; ?>><a href="/archive">Archive</a></div>
                    <div class="header-menu-item" <?php echo CURRENT_PAGE == 'alerts' ? ' id="header-menu-selected" ' : ''; ?>><a href="/alerts">Alerts</a></div>
                    <div class="header-menu-item" <?php echo CURRENT_PAGE == 'links' ? ' id="header-menu-selected" ' : ''; ?>><a href="/links">On your website/blog</a></div>
                    <div class="header-menu-item"><a href="http://twitter.com/bestfightodds" target="_blank"><img src="/img/twitter.gif" id="twitter-icon" alt="Twitter icon" />Twitter</a></div>


                    <div class="format-amount-container" id="format-amount-container1">$<input type="text" name="amountBox" class="format-amount-box" id="format-amount-box1" maxlength="4" value="100" /><input type="submit" class="format-amount-button" id="typeAmountButton" value="Set" onclick="oddsToAmount()" /></div>
                    <div class="format-container">
                        <span>Display</span>
                        <select id="format-select1" onchange="setOddsType()">
                            <option value="1">Moneyline</option>
                            <option value="2">Decimal</option>
                            <option value="3">Return on..</option>
                        </select>
                    </div>
                    <div class="parlay-mode-container" 

                         <?php
                         //Only display parlay-mode container if we are displaying an event or are on the front page
                         if (CURRENT_PAGE != '' && CURRENT_PAGE != 'event')
                         {
                             echo ' style="visibility: hidden" ';
                         }
                         ?>
                         >
                        <input type="checkbox" onclick="toggleParlayMode()" id="parlay-mode-box" disabled="disabled" />Parlay
                    </div>

                </div>
                <div id="header-bottom">
                </div>
                <script type="text/javascript">initializeFront()</script>
                <div id="content">
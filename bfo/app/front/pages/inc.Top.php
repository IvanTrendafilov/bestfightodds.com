<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"> 
    <head>
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
        <meta name="description" content="<?php echo (defined('PAGE_OVERRIDE_DESCRIPTION') ? PAGE_OVERRIDE_DESCRIPTION . ' ' : ''); ?>UFC/MMA odds comparison service. Compare the latest UFC/MMA fight odds and betting lines from the top online sportsbooks" />
        <meta name="keywords" content="<?php echo (defined('PAGE_OVERRIDE_KEYWORDS') ? PAGE_OVERRIDE_KEYWORDS . ', ' : ''); ?>mma odds, mma betting, mma lines, ufc odds, ufc, mma, odds, betting" />
        <meta property="og:image" content="https://www.bestfightodds.com/img/iconv2.jpg" />
        <link href='https://fonts.googleapis.com/css?family=Roboto:500,700,400' rel='stylesheet' type='text/css'>
        <script type="text/javascript">
        <?php
        //Enable viewport if desktop has not explicitly been requested
        if (!(isset($_COOKIE['bfo_reqdesktop']) && $_COOKIE['bfo_reqdesktop'] == 'true'))
        {
        ?>
        if( screen.width < 767 ) {
            document.write( '<meta name="viewport" content="width=device-width, maximum-scale=1.0, minimum-scale=1.0, initial-scale=1.0" />' );
        }
        <?php
        }
        ?>
        </script>
        <link rel="stylesheet" type="text/css" href="/css/bfo.min.css" />
        <link rel="shortcut icon" href="https://www.bestfightodds.com/favicon.ico" />
        <link rel="apple-touch-icon" sizes="144x144" href="/apple-touch-icon-144x144.png" />
        <link rel="apple-touch-icon" sizes="114x114" href="/apple-touch-icon-114x114.png" />
        <link rel="apple-touch-icon" sizes="72x72" href="/apple-touch-icon-72x72.png" />
        <link rel="apple-touch-icon" href="/apple-touch-icon-iphone.png" />
        <title><?php echo (defined('PAGE_OVERRIDE_TITLE') ? PAGE_OVERRIDE_TITLE . ' | Best Fight Odds': 'UFC &amp; MMA Odds &amp; Betting Lines | Best Fight Odds'); ?></title>
    </head>
    <body>
        <script type="text/javascript" async src="/js/bfo.min.js?v=0.1.1"></script>
        <div class="header">
                <div class="header-top">
                    <a href="/"><div class="logo"></div></a>
                    <div id="header-search-box">
                        <form method="get" action="/search"><input type="text" id="search-box1" class="search-box" name="query" placeholder="MMA Event / Fighter"/> <input type="submit" class="search-button" id="search-button" value="&#128269;" /></form>
                    </div>
                </div>
                <div class="header-menu-wrapper">
                        <div class="header-menu">
                        <a href="/"><div class="header-menu-item <?php echo CURRENT_PAGE == '' ? ' header-menu-selected ' : ''; ?>" style="margin-left: 10px">Latest<span class="item-non-mob-mini"> odds</span></div></a>
                        <a href="/archive"><div class="header-menu-item <?php echo (CURRENT_PAGE == 'archive' || CURRENT_PAGE == 'fighter' || CURRENT_PAGE == 'event' || CURRENT_PAGE == 'search_results') ? ' header-menu-selected ' : ''; ?>">Archive</div></a>
                        <a href="/alerts"><div class="header-menu-item <?php echo CURRENT_PAGE == 'alerts' ? ' header-menu-selected ' : ''; ?>">Alerts</div></a>
                        <a href="/links"><div class="header-menu-item <?php echo CURRENT_PAGE == 'links' ? ' header-menu-selected ' : ''; ?> item-non-mobile">Widget</div></a>
                        <a href="https://www.proboxingodds.com" target="_blank"><div class="header-menu-item  item-non-mobile">Boxing</div></a>
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
                                <a href="#" id="parlay-mode-box"><div class="bfo-check-box">✓</div> Parlay</a>
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
                            <div id="mob-divider" class="menu-divider"
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
                                             <li><a href="#" id="formatSelector1" class="list-checked"><span style="display: inline-block">&#10003;</span>Moneyline</a></li>
                                             <li><a href="#" id="formatSelector2"><span>&#10003;</span>Decimal</a></li>
                                             <li><a href="#" id="formatSelector4"><span>&#10003;</span>Fractional</a></li>
                                             <li><a href="#" id="formatSelector3" style="display: inline"><span>&#10003;</span>Return on </a>$<input type="text" name="amountBox" id="format-amount-box1" maxlength="4" value="100" style="display: inline"/></li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
                <div id="content">    
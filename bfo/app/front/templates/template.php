<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
    <meta name="description" content="<?= isset($meta_desc) ? $meta_desc . ' ' : '' ?>UFC/MMA odds comparison service. Compare the latest UFC/MMA fight odds and betting lines from the top online sportsbooks">
    <meta name="keywords" content="<?= isset($meta_keywords) ? $meta_keywords . ' ' : '' ?>mma odds, mma betting, mma lines, ufc odds, ufc, mma, odds, betting">
    <meta property="og:image" content="https://www.bestfightodds.com/img/iconv2.jpg">
    <?php if (!(isset($_COOKIE['bfo_reqdesktop']) && $_COOKIE['bfo_reqdesktop'] == 'true')) : //Enable viewport if desktop has not explicitly been requested 
    ?>
        <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <?php endif ?>
    <?php if (isset($no_indexing) && $no_indexing) : //Disable robots if actively specified 
    ?>
        <meta name="robots" content="noindex">
    <?php endif ?>
    <link rel="preconnect" href="https://www.googletagmanager.com">
    <link rel="preconnect" href="https://www.google-analytics.com">
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
    </noscript>
    <link rel="stylesheet" type="text/css" href="/css/bfo.min.css?v=0.3.12">
    <link rel="shortcut icon" href="https://www.bestfightodds.com/favicon.ico">
    <link rel="apple-touch-icon" sizes="144x144" href="/apple-touch-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/apple-touch-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/apple-touch-icon-72x72.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon-iphone.png">
    <!-- Google Tag Manager -->
    <script>
        (function(w, d, s, l, i) {
            w[l] = w[l] || [];
            w[l].push({
                'gtm.start':

                    new Date().getTime(),
                event: 'gtm.js'
            });
            var f = d.getElementsByTagName(s)[0],

                j = d.createElement(s),
                dl = l != 'dataLayer' ? '&l=' + l : '';
            j.async = true;
            j.src =

                'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
            f.parentNode.insertBefore(j, f);

        })(window, document, 'script', 'dataLayer', 'GTM-5X6Z3Z9');
    </script>
    <!-- End Google Tag Manager -->
    <?php $title = isset($title) && strpos($title, 'MMA Odds History') === false ? implode(' Odds:', explode(':', $title, 2)) : $title; ?>
    <?php if (str_starts_with($title, 'UFC 268 Odds')) { ?>
    <title>UFC 268 Odds: Usman vs. Covington 2 Odds & Betting Lines</title>
    <?php } else { ?>
    <title><?= isset($title) ? $title . ' | Best Fight Odds' : 'UFC &amp; MMA Odds &amp; Betting Lines | Best Fight Odds' ?></title>
    <?php } ?>
</head>

<body>
    <script async src="/js/bfo.min.js?v=0.4.9"></script>
    <div class="flex-header">
        <div class="flex-header-wrap">
            <a href="/"><img src="/img/logo_3.png" class="logo" width="290" height="54" alt="Best Fight Odds logo"></a>
            <div id="header-search-box">
                <form method="get" action="/search"><input type="text" id="search-box1" class="search-box" name="query" placeholder="MMA Event / Fighter"> <input type="submit" class="search-button" id="search-button" value="&#128269;"></form>
            </div>
        </div>
    </div>
    <div class="flex-nav">
        <div class="flex-nav-wrap">
            <div class="flex-header-menu">
                <a href="/">
                    <div class="header-menu-item <?= !isset($current_page) || $current_page == '' ? ' header-menu-selected ' : '' ?>" style="margin-left: 10px">Latest<span class="item-non-mob-mini"> odds</span></div>
                </a>
                <a href="/archive">
                    <div class="header-menu-item <?= isset($current_page) && ($current_page == 'archive' || $current_page == 'event') ? ' header-menu-selected ' : '' ?>">Archive</div>
                </a>
                <a href="/alerts">
                    <div class="header-menu-item <?= isset($current_page) && $current_page == 'alerts' ? ' header-menu-selected ' : '' ?>">Alerts</div>
                </a>
                <a href="/links">
                    <div class="header-menu-item <?= isset($current_page) && $current_page == 'widget' ? ' header-menu-selected ' : '' ?> item-non-mobile">Widget</div>
                </a>
                <a href="https://www.proboxingodds.com" target="_blank" rel="noopener">
                    <div class="header-menu-item  item-non-mobile">Boxing</div>
                </a>
                <a href="http://twitter.com/bestfightodds" target="_blank" rel="noopener">
                    <div class="header-menu-item">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 248 204" style="enable-background:new 0 0 248 204;" xml:space="preserve" id="twitter-icon">
                            <style type="text/css">
                                .st0 {
                                    fill: #1D9BF0;
                                }
                            </style>
                            <g id="Logo_1_">
                                <path id="white_background" class="st0" d="M221.95,51.29c0.15,2.17,0.15,4.34,0.15,6.53c0,66.73-50.8,143.69-143.69,143.69v-0.04
                                            C50.97,201.51,24.1,193.65,1,178.83c3.99,0.48,8,0.72,12.02,0.73c22.74,0.02,44.83-7.61,62.72-21.66
                                            c-21.61-0.41-40.56-14.5-47.18-35.07c7.57,1.46,15.37,1.16,22.8-0.87C27.8,117.2,10.85,96.5,10.85,72.46c0-0.22,0-0.43,0-0.64
                                            c7.02,3.91,14.88,6.08,22.92,6.32C11.58,63.31,4.74,33.79,18.14,10.71c25.64,31.55,63.47,50.73,104.08,52.76
                                            c-4.07-17.54,1.49-35.92,14.61-48.25c20.34-19.12,52.33-18.14,71.45,2.19c11.31-2.23,22.15-6.38,32.07-12.26
                                            c-3.77,11.69-11.66,21.62-22.2,27.93c10.01-1.18,19.79-3.86,29-7.95C240.37,35.29,231.83,44.14,221.95,51.29z" />
                            </g>
                        </svg>
                    </div>
                </a>
            </div>
            <div id="flex-header-menu-right">
                <div id="parlay-mode-container" <?php if (isset($current_page) && $current_page != '' && $current_page != 'event') : ?> style="visibility: hidden" <?php endif ?>>
                    <a href="#" id="parlay-mode-box">
                        <div class="bfo-check-box">???</div> Parlay
                    </a>
                </div>
                <div class="menu-divider" <?php if (!isset($current_page) || $current_page != '') : ?> style="display: none" <?php endif ?>></div>
                <div id="auto-refresh-container" <?php if (true || !isset($current_page) || $current_page != '') : //Auto-refresh current disabled 
                                                    ?> style="display: none" <?php endif ?>>
                    <ul class="dropdown">
                        <li><a href="#" onclick="toggleRefresh()" style="padding-left: 20px;"><img src="/img/refresh.png" class="refresh-ind" id="autoRefresh" alt="Toggle auto-refresh">&#9660;</a>
                            <ul class="sub_menu">
                                <li><a href="#" id="afSelectorOn" class="list-checked"><span style="display: inline-block">&#10004;</span>Auto-refresh: On</a></li>
                                <li><a href="#" id="afSelectorOff"><span>&#10004;</span>Auto-refresh: Off</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
                <div id="mob-divider" class="menu-divider" <?php if (true || !isset($current_page) || $current_page != '') : //Auto-refresh current disabled 
                                                            ?> style="display: none" <?php endif ?>></div>
                <div id="format-container">
                    <ul class="dropdown">
                        <li><a href="#" id="format-toggle-text"><span>Settings &#9660;</span></a>
                            <ul class="sub_menu">
                                <?php /*<li><a href="#" id="normalModeSelector" class="list-checked"><span style="display: inline-block">&#10003;</span>Normal</a></li>
                                            <li style="border-bottom: 1px solid #393b42"><a href="#" id="darkModeSelector"><span>&#10003;</span>Dark mode</a></li> */ ?>
                                <li><a href="#" id="formatSelector1" class="list-checked"><span style="display: inline-block">&#10003;</span>Moneyline</a></li>
                                <li><a href="#" id="formatSelector2"><span>&#10003;</span>Decimal</a></li>
                                <li><a href="#" id="formatSelector4"><span>&#10003;</span>Fractional</a></li>
                                <li style="border-bottom: 1px solid #4a4c53"><a href="#" id="formatSelector3" style="display: inline"><span>&#10003;</span>Return on </a>$<input type="text" name="amountBox" id="format-amount-box1" maxlength="4" value="100" style="display: inline"></li>
                                <li><a href="#" id="bookieHideSelector">Customize view</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="flex-section">
        <div class="flex-content-wrap">
            <?= $this->section('content') ?>
        </div>
    </div>
    <div class="legend-container">
        <img src="/img/loading.gif" class="hidden-image" alt="Loading indicator" width="0" height="0">
        <img src="/img/expu.png" class="hidden-image" alt="Expand symbol" width="0" height="0">
    </div>

    <div id="bottom-container">
        <a href="/">Home</a><span class="menu-seperator">|</span>
        <?php if ((isset($_COOKIE['bfo_reqdesktop']) && $_COOKIE['bfo_reqdesktop'] == 'true')) : //Display switch to mobile if on forced desktop
        ?>
            <a href="#" onclick="setDesktop(false);">Mobile</a><span class="menu-seperator">|</span>
        <?php else : ?>
            <a href="#" onclick="setDesktop(true);">Desktop&nbsp;site</a><span class="menu-seperator">|</span>
        <?php endif ?>
        <a href="https://www.proboxingodds.com" target="_blank" rel="noopener">Boxing&nbsp;Odds</a><span class="menu-seperator">|</span><a href="/terms">Terms&nbsp;of&nbsp;service</a><span class="menu-seperator">|</span><a href="#">18+</a><span class="menu-seperator">|</span><a href="/responsiblegaming">Responsible&nbsp;Gaming</a><span class="menu-seperator">|</span><a href="https://www.begambleaware.org/">BeGambleAware</a><span class="menu-seperator">|</span><a href="mailto:contact@bestfightodds.com">Contact</a><span class="menu-seperator">|</span><a href="mailto:contact@bestfightodds.com"> &copy; <?= date('Y') ?></a>
    </div>

    <div id="chart-window" class="popup-window">
        <div class="popup-header" id="chart-header">
            <div></div><a href="#" class="cd-popup-close">&#10005;</a>
        </div>
        <div id="chart-area"></div><a href="#" target="_blank" rel="noopener">
            <div id="chart-link" class="highlighted-button">Bet this line at bookie</div>
        </a>
        <div id="chart-disc" style="display: none; color: #333">*Currently this Sportsbook does not accept players that reside in the US. 18+ Gamble Responsibly</div>
    </div>
    <div id="parlay-window" class="popup-window">
        <div class="popup-header" id="parlay-header">Parlay</div>
        <div id="parlay-area">Click on a line to add it to your parlay</div>
    </div>
    <div id="bookie-settings-window" class="popup-window">
        <div class="popup-header">Bookie display settings <a href="#" class="cd-popup-close">&#10005;</a></div>
        <div id="bookie-settings-area">Drag to change order, show/hide using checkbox<ul id="bookie-order-items"></ul><input type="button" class="button" value="Reset to default settings" id="bookieResetDefault"></div>
    </div>

    <?php if (isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] != '127.0.0.1') : //Disable Google Analytics if running locally 
    ?>
        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5X6Z3Z9" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
    <?php endif ?>
</body>

</html>

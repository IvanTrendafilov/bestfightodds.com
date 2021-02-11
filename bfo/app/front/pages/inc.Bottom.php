</div>
<div class="legend-container">
    <img src="/img/loading.gif" class="hidden-image" alt="Loading indicator" />
    <img src="/img/expu.png" class="hidden-image" alt="Loading indicator" />
</div>

<div id="bottom-container">
    <a href="/">Home</a><span class="menu-seperator">|</span>
         <?php   
         //Display switch to mobile if on forced desktop
            if ((isset($_COOKIE['bfo_reqdesktop']) && $_COOKIE['bfo_reqdesktop'] == 'true'))
            {
          ?>
            <a href="/?desktop=off">Mobile</a><span class="menu-seperator">|</span>
            <?php
            }
            else
            {
              ?>
            <a href="/?desktop=on">Desktop site</a><span class="menu-seperator">|</span>
              <?php
            }
          ?>
    <a href="https://www.proboxingodds.com" target="_blank" rel="noopener">Boxing Odds</a><span class="menu-seperator">|</span><a href="/terms">Terms of service</a><span class="menu-seperator">|</span><a href="#">18+</a><span class="menu-seperator">|</span><a href="https://www.begambleaware.org/">BeGambleAware</a><span class="menu-seperator">|</span><a href="mailto:info1@bestfightodds.com">Contact</a><span class="menu-seperator">|</span><a href="mailto:info1@bestfightodds.com">&copy; <?=date('Y')?></a>
</div>

<div id="chart-window" class="popup-window"><div class="popup-header" id="chart-header"><div></div><a href="#" class="cd-popup-close">&#10005;</a></div><div id="chart-area"></div><a href="#" target="_blank" rel="noopener"><div id="chart-link" class="button">Bet this line at bookie</div></a><div id="chart-disc" style="display: none; color: #333">*Currently this Sportsbook does not accept players that reside in the US. 18+ Gamble Responsibly</div></div>
<div id="parlay-window" class="popup-window"><div class="popup-header" id="parlay-header">Parlay</div><div id="parlay-area">Click on a line to add it to your parlay</div></div>
<div id="alert-window" class="popup-window"><div class="popup-header" id="alert-header"><div></div><a href="#" class="cd-popup-close">&#10005;</a></div><div id="alert-area">
    <form id="alert-form">Alert me at e-mail <input type="text" name="alert-mail" id="alert-mail"><br />when the odds reaches <input type="text" name="alert-odds" id="alert-odds"> or better<br/>at <select name="alert-bookie">
        <option value="-1">any bookie</option>
        <option value="1">5Dimes</option>
        <option value="20">BetWay</option>
        <option value="3">BookMaker</option>
        <option value="12">BetOnline</option>
        <option value="5">Bovada</option>
        <option value="19">Bet365</option>
        <option value="2">SportBet</option>
        <option value="17">William Hill</option>
        <option value="8">SportsInteraction</option>
        <option value="9">Pinnacle</option>
        <option value="4">Sportsbook</option>
        <option value="18">Intertops</option>
        <option value="13">BetDSI</option>
      </select><br /><div id="alert-button-container"><input type="hidden" name="tn"><input type="hidden" name="m">
        <div class="alert-loader"></div>
        <div class="alert-result">&nbsp;</div>
      <input type="submit" value="Add alert" id="alert-submit"></div></form></div>
</div>
<div id="bookie-settings-window" class="popup-window"><div class="popup-header">Bookie display settings <a href="#" class="cd-popup-close">&#10005;</a></div><div id="bookie-settings-area"><ul id="bookie-order-items"></ul></div></div>

<?php
//Disable Google Analytics if running locally
if ($_SERVER['SERVER_ADDR'] != '127.0.0.1')
{
    ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-2457531-1"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'UA-2457531-1');
</script>

<?php
}
?>


</body>
</html>
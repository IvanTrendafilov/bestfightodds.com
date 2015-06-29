<div class="legend-container">
    <img src="/img/error.png" class="hidden-image" alt="Error indicator" />
    <img src="/img/success.png" class="hidden-image" alt="Success indicator" />
    <img src="/img/ajax-loader.gif" class="hidden-image" alt="Loading indicator" />
    <img src="/img/ajax-loader2.gif" class="hidden-image" alt="Loading indicator" />
    <img src="/img/small-button-back.png" class="hidden-image" alt="Small Button Back" />
</div>

</div>

<div id="bottom-divider"></div>    
</div>

</div>
<img src="/img/bottom.png" alt="Bottom shadow" /> 
<div id="bottom-container">
    <a href="/">Home</a><span class="menu-seperator">|</span><a href="/terms">Terms of service</a><span class="menu-seperator">|</span><a href="mailto:info1@bestfightodds.com">Contact us</a><span class="menu-seperator">|</span><a href="mailto:info1@bestfightodds.com">&copy; 2013</a>
</div>
<?php
//Disable if running locally
if ($_SERVER['SERVER_ADDR'] != '127.0.0.1')
{
    ?>
    <script type="text/javascript">
        var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
        document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
    </script>
    <script type="text/javascript">
        try {
            var pageTracker = _gat._getTracker("UA-2457531-1");
            pageTracker._trackPageview();
        } catch(err) {}</script>
    <?php
}
?>
</body>
</html>
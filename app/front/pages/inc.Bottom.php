</div>
<div class="legend-container">
    <img src="/img/error.png" class="hidden-image" alt="Error indicator" />
    <img src="/img/success.png" class="hidden-image" alt="Success indicator" />
    <img src="/img/ajax-loader.gif" class="hidden-image" alt="Loading indicator" />
</div>

<div id="bottom-container">
    <a href="/">Home</a><span class="menu-seperator">|</span><a href="/terms">Terms of service</a><span class="menu-seperator">|</span><a href="mailto:info1@bestfightodds.com">Contact us</a><span class="menu-seperator">|</span><a href="mailto:info1@bestfightodds.com">&copy; 2015</a>
</div>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<?php
//Disable if running locally
if ($_SERVER['SERVER_ADDR'] != '127.0.0.1')
{
    ?>
<script>
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
								   m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
														      })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-2457531-1', 'bestfightodds.com');
  ga('send', 'pageview');

</script>
    <?php
}
?>

</body>
</html>
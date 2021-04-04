<?php

require_once('config/inc.config.php');

if (!isset($_GET['log']))
{
	$rLogDir = opendir(PARSE_LOGDIR);
	
	echo 'Logs: <br /><br />';
	
  $aFiles = array();
	while ($sFile = readdir($rLogDir))
	{
	
		if ($sFile != "." && $sFile != "..")
		{
      $aFiles[] = $sFile;
		}
	}
	
	sort($aFiles);
	
	foreach ($aFiles as $sFile)
	{
    echo '<a href="?p=viewLatestLog&log=' . $sFile . '">' . $sFile . '</a><br />';
  }
	
}
else
{
	echo '<div><pre>';

    if ($_GET['log'] == 'latest')
    {
        $rDir = opendir(PARSE_LOGDIR);
        $aFiles = array();
        while (false !== ($sFile = readdir($rDir)))
        {
            if ($sFile != 'archived')
            {
                $aFiles[] = $sFile;
            }
        }
        sort($aFiles);
        include_once(PARSE_LOGDIR . '/' . $aFiles[sizeof($aFiles) - 1]);
        
    }
    else
    {
        include_once(PARSE_LOGDIR . '/' . $_GET['log']);
    }
		
	echo '</div></pre>';
}


?>
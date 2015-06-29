<?php

// This file contains logic used on the front page to display dynamic content that cant be pre-generated by the generator

function getTimeDifference($a_sStart, $a_sEnd)
{
    if ($a_sStart == '')
    {
        return 'n/a';
    }

    if ($a_sStart !== -1 && $a_sEnd !== -1)
    {
        if ($a_sEnd >= $a_sStart)
        {
            $sRetString = '';

            $diff = $a_sEnd - $a_sStart;
            if ($days = intval(floor($diff / 86400)))
            {
                $diff = $diff % 86400;
            }
            if ($hours = intval(floor($diff / 3600)))
            {
                $diff = $diff % 3600;
            }
            if ($minutes = intval(floor($diff / 60)))
            {
                $diff = $diff % 60;
            }
            if ($days == 0 && $hours == 0 && $minutes == 0)
            {
                $minutes = 1;
            }

            if ($days > 0)
            {
                if ($days == 1)
                {
                    $sRetString .= '1 day';
                    if ($hours > 0)
                    {
                        $sRetString .= ' ' . $hours . ' hr';
                    }
                    else
                    {
                        if ($minutes > 0)
                        {
                            $sRetString .= ' ' . $minutes . ' min';
                        }
                    }
                }
                else
                {
                    $sRetString .= $days . ' days';
                }
            }
            else
            {
                if ($hours > 0)
                {
                    $sRetString .= $hours . ' hr';
                    if ($minutes > 0)
                    {
                        $sRetString .= ' ' . $minutes . ' min';
                    }
                }
                else
                {
                    if ($minutes == 1)
                    {
                        $sRetString .= '< ';
                    }
                    $sRetString .= $minutes . ' min';
                }
            }

            $sRetString .= ' ago';

            return $sRetString;
        }
    }
    return '';
}



?>
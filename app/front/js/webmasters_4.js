function fightSelected()
{
    fightID = $("#webFight")[0].options[$("#webFight")[0].selectedIndex].value;
    ftitle = $("#webFight")[0].options[$("#webFight")[0].selectedIndex].text.trim();
    if (fightID != 0)
    {
        imageLink = "";
        type = "";
        if ($('[name="webLineType"]:checked').val() == 'opening')
        {
            type += '_o';
        }
        if ($('[name="webLineFormat"]:checked').val() == '2')
        {
            type += '_d';
        }

        if (fightID > 0)
        {
            imageLink = 'fights/' + fightID + type + '.png';
        }
        else if (fightID < 0)
        {
            imageLink = 'events/' + Math.abs(fightID) + type + '.png';
        }
        $('[name="webTestImage"]')[0].src = "/img/ajax-loader.gif";
        $("#webHTML").val('<!-- Begin BestFightOdds code -->\n<a href="https://www.bestfightodds.com" target="_blank"><img src="https://www.bestfightodds.com/' + imageLink + '" alt="' + ftitle + ' odds - BestFightOdds" style="width: 216px; border: 0;" /></a>\n<!-- End BestFightOdds code -->');
        $("#webForum").val('[url=https://www.bestfightodds.com][img]https://www.bestfightodds.com/' + imageLink + '[/img][/url]');
        $('[name="webTestImage"]')[0].src = '' + imageLink;
        $("#webImageLink").val('https://www.bestfightodds.com/' + imageLink);
        $("#webFields").css({ 'display': ''});
    }
    else
    {
        $("#webFields").css({ 'display': 'none'});
    }
}
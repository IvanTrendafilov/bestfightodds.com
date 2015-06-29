function fightSelected()
{
    fightID = $("#webFight")[0].options[$("#webFight")[0].selectedIndex].value;
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
        $("#webHTML").val('<!-- Begin BestFightOdds code -->\n<a href="http://www.bestfightodds.com" target="_blank"><img src="http://www.bestfightodds.com/' + imageLink + '" alt="BestFightOdds.com" style="width: 216px; border: 0;" /></a>\n<!-- End BestFightOdds code -->');
        $("#webForum").val('[url=http://www.bestfightodds.com][img]http://www.bestfightodds.com/' + imageLink + '[/img][/url]');
        $('[name="webTestImage"]')[0].src = '' + imageLink;
        $("#webImageLink").val('http://www.bestfightodds.com/' + imageLink);
        $("#webFields").css({ 'display': ''});
    }
    else
    {
        $("#webFields").css({ 'display': 'none'});
    }
}
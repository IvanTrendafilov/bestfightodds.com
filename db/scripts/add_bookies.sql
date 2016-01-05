/* Updated 2012-03-13 */

INSERT INTO bets.bookies(id, name, url, refurl, active, position, date_added) VALUES

(1, '5Dimes', 'http://www.5dimes.com', 'http://affiliates.5dimes.com/tracking/Affiliate.asp?AffID=5D1796725&mediaTypeID=220&AffUrlID=31', 1, 1),

(2, 'SportBet', 'http://www.sportbet.com', 'http://affiliates.sportbet.com/tracking/Affiliate.asp?AffID=5D1796725&mediaTypeID=220&AffUrlID=31', 1, 4),

(3, 'BookMaker', 'http://www.bookmaker.eu', 'http://www.bookmaker.eu/?cmpid=13797_393', 1, 2),

(4, 'Sportsbook', 'http://www.sportsbook.ag', 'http://affiliates.commissionaccount.com/processing/clickthrgh.asp?btag=a_4684b_388', 1, 5),

(5, 'Bovada', 'http://www.bovada.lv', '/redir.php?b=5', 1, 3), 

(7, 'BetUS', 'http://www.betus.com.pa', 'http://www.betus.com.pa', 1, 6),

(8, 'SportsInteraction', 'http://www.sportsinteraction.com', 'http://affiliate.sportsinteraction.com/processing/clickthrgh.asp?btag=a_3145b_470&aid=mma', 1, 8),

(9, 'Pinnacle', 'http://www.pinnaclesports.com', 'http://affiliates.pinnaclesports.com/processing/clickthrgh.asp?btag=a_1274b_818', 1, 7),

(10, 'SBG Global', 'http://www.sbgglobal.com', 'http://www.sbgglobal.eu/wc/clicks.php?aff=10433_52_61_10433', 1, 9),

(11, 'TheGreek', 'http://www.thegreek.com', 'http://www.thegreek.com/dw/sportsbook.asp?ap=A165125', 1, 11),

(12, 'BetOnline', 'http://www.betonline.com', 'http://partners.commission.bz/processing/clickthrgh.asp?btag=a_1811b_2', 1, 12),

(13, 'BetDSI', 'http://www.betdsi.com', 'http://www.betdsi.eu/mma-betting?cmpid=18239_4778', 1, 2, '2012-08-19 00:00:00');


INSERT INTO bets.bookies_changenums(bookie_id, changenum) VALUES
(1, '-1'),
(2, '-1'),
(9, '-1');



INSERT INTO bets.bookies_parsers(bookie_id, parse_url, name, cn_inuse, mockfile, cn_urlsuffix) VALUES
(1, 'http://lines.5dimes.com/linesfeed/getlinefeeds.aspx?uid=bestfightodds5841&Type=ReducedReplace', '5Dimes', true, '5dimes.xml', '&changenum='),
(13, 'http://lines.betdsi.com', 'BetDSI', false, 'betdsi.xml', ''),
(12, 'http://livelines.betonline.com/sys/LineXML/LiveLineObjXml.asp?sport=Martial%20Arts', 'BetOnline', false, 'betonline.xml', ''),
(12, 'http://www.betonline.com/sports/Line/RetrieveLineData?param.PrdNo=-1&param.Type=Cntst&param.RequestType=Normal&param.CntstParam.Lv1=MMA%20Props', 'BetOnlineProps', false, 'betonlineprops.xml', ''),
(7, 'http://www.betus.com.pa/sportsbook/xmlfeed.aspx', 'BetUS', false, 'betus.xml', ''),
(7, 'http://www.betus.com.pa/sportsbook/futuresxmlfeed.aspx', 'BetUSFutures', false, 'betusfutures.xml', ''),
(3, 'http://lines.bookmaker.eu', 'BookMaker', false, 'bookmaker.xml', ''),
(5, 'http://sportsfeeds.bovada.lv/basic/UFC.xml', 'Bovada', false, 'bovada.xml', ''),
(5, 'http://sportsfeeds.bovada.lv/basic/MMA.xml', 'BovadaProps', false, 'bovadaprops.xml', ''),
(9, 'http://api.pinnaclesports.com/v1/feed?sportid=22&clientid=fightodds&apikey=7F6864D2-CCA3-45DA-A700-B06A0DCBC317', 'Pinnacle', true, 'pinnacle.xml', '&last='),
(10, 'http://feeds.sbgsportsbook.com/linefeed/SbgXmlFeedId?league=112_511', 'SBG', false, 'sbg.xml', ''),
(2, 'http://lines.sportbet.com/linesfeed/getlinefeeds.aspx?UID=bestfightodds5841', 'SportBet', true, 'sportbet.xml', '&changenum='),
(4, 'https://www.gamingsystem.ag/sbk/sportsbook4/www.sportsbook.ag/getodds.xgi?categoryId=92', 'Sportsbook', false, 'sportsbook.xml', ''),
(4, 'https://www.gamingsystem.ag/sbk/sportsbook4/www.sportsbook.ag/getodds.xgi?categoryId=122', 'SportsbookMMA', false, 'sportsbookmma.xml', ''),
(8, 'http://www.sportsinteraction.com/info/data/feeds/consume/?consumerName=bfodds&pwd=bfodds3145&feedID=5&formatID=4', 'SportsInteraction', false, 'sportsint.xml', ''),
(11, 'http://www.thegreek.com/sports/Boxing/boxselections.asp', 'TheGreek', false, 'thegreek.asp', '');

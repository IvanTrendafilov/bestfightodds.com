INSERT INTO bets.bookies (`id`, `name`, `url`, `refurl`, `active`, `position`, `date_added`) VALUES
	(1, '5Dimes', 'http://www.5dimes.com', 'http://affiliates.5dimes.com/tracking/Affiliate.asp?AffID=5D1796725&mediaTypeID=220&AffUrlID=31', 1, 1, '2000-01-01 00:00:00'),
	(2, 'SportBet', 'http://www.sportbet.com', 'http://affiliates.sportbet.com/tracking/Affiliate.asp?AffID=5D1796725&mediaTypeID=220&AffUrlID=31', 1, 5, '2000-01-01 00:00:00'),
	(3, 'BookMaker', 'http://www.bookmaker.eu', 'http://www.bookmaker.eu/?cmpid=13797_393', 1, 3, '2000-01-01 00:00:00'),
	(4, 'Sportsbook', 'http://www.sportsbook.ag', 'http://www.sportsbook.ag/_aMho0pW659_UOsjNOfgKeWNd7ZgqdRLk/1/', 1, 6, '2000-01-01 00:00:00'),
	(5, 'Bovada', 'http://www.bovada.lv', 'http://record.bettingpartners.com/_3_I4QQ0O0x7R1HsxxA1_FGNd7ZgqdRLk/1/', 1, 4, '2000-01-01 00:00:00'),
	(7, 'BetUS', 'http://www.betus.com', 'http://www.betus.com.pa', 1, 13, '2000-01-01 00:00:00'),
	(8, 'SportsInteraction', 'http://www.sportsinteraction.com', 'http://affiliate.sportsinteraction.com/processing/clickthrgh.asp?btag=a_3145b_470&aid=mma', 1, 9, '2000-01-01 00:00:00'),
	(9, 'Pinnacle', 'http://www.pinnaclesports.com', 'https://wlpinnacle.adsrv.eacdn.com/C.ashx?btag=a_1274b_10678c_&affid=5414&siteid=1274&adid=10678&c=', 1, 8, '2000-01-01 00:00:00'),
	(10, 'SBG Global', 'http://www.sbgglobal.com', 'http://www.sbgglobal.eu/wc/clicks.php?aff=10433_52_61_10433', 1, 10, '2000-01-01 00:00:00'),
	(11, 'TheGreek', 'http://www.thegreek.com', 'http://www.thegreek.com/dw/sportsbook.asp?ap=A165125', 1, 11, '2000-01-01 00:00:00'),
	(12, 'BetOnline', 'http://www.betonline.com', 'http://partners.commission.bz/processing/clickthrgh.asp?btag=a_1811b_2', 1, 12, '2000-01-01 00:00:00'),
	(13, 'BetDSI', 'http://www.betdsi.com', 'http://www.betdsi.eu/mma-betting?cmpid=18239_4778', 1, 2, '2012-08-19 00:00:00'),
	(17, 'William Hill', 'http://www.williamhill.eu', 'http://ads2.williamhill.com/redirect.aspx?pid=191293277&bid=1487413667&lpid=1487413047', 1, 7, '2016-04-22 00:00:00'),
	(18, 'Intertops', 'http://www.intertops.eu', 'http://affiliate.intertops.com/processing/clickthrgh.asp?btag=a_10831b_1099', 1, 13, '2017-07-14 00:00:00');

INSERT INTO bets.bookies_changenums(bookie_id, changenum) VALUES
(1, '-1'),
(2, '-1'),
(9, '-1'),
(18, '9999999');

INSERT INTO bets.bookies_parsers (`id`, `bookie_id`, `parse_url`, `name`, `cn_inuse`, `mockfile`, `cn_urlsuffix`, `cn_initial`) VALUES 
	(1, 1, 'http://lines.5dimes.com/linesfeed/getlinefeeds.aspx?uid=bestfightodds5841&Type=ReducedReplace', '5Dimes', 1, '5dimes.xml', '&changenum=', '-1'),
	(2, 13, 'http://lines.betdsi.eu/', 'BetDSI', 0, 'betdsi.xml', '', '-1'),
	(3, 12, 'http://livelines.betonline.com/sys/LineXML/LiveLineObjXml.asp?sport=Martial%20Arts', 'BetOnline', 0, 'betonline.xml', '', '-1'),
	(4, 12, 'http://www.betonline.com/sports/Line/RetrieveLineData?param.PrdNo=-1&param.Type=Cntst&param.RequestType=Normal&param.CntstParam.Lv1=MMA%20Props', 'BetOnlineProps', 0, 'betonlineprops.xml', '', '-1'),
	(5, 7, 'http://www.betus.com.pa/sportsbook/xmlfeed.aspx', 'BetUS', 0, 'betus.xml', '', '-1'),
	(6, 7, 'http://www.betus.com.pa/sportsbook/futuresxmlfeed.aspx', 'BetUSFutures', 0, 'betusfutures.xml', '', '-1'),
	(7, 3, 'http://lines.bookmaker.eu', 'BookMaker', 0, 'bookmaker.xml', '', '-1'),
	(8, 5, 'http://sportsfeeds.bovada.lv/basic/UFC.xml', 'Bovada', 0, 'bovada.xml', '', '-1'),
	(9, 5, 'http://sportsfeeds.bovada.lv/basic/MMA.xml', 'BovadaProps', 0, 'bovadaprops.xml', '', '-1'),
	(10, 9, 'https://api.pinnaclesports.com/v1/fixtures?sportId=22&IsLive=0', 'Pinnacle', 0, 'pinnacle.xml', '&since=', '-1'),
	(11, 10, 'http://feeds.sbgsportsbook.com/linefeed/SbgXmlFeedId?league=112_511', 'SBG', 0, 'sbg.xml', '', '-1'),
	(12, 2, 'http://lines.sportbet.com/linesfeed/getlinefeeds.aspx?UID=bestfightodds5841', 'SportBet', 1, 'sportbet.xml', '&changenum=', '-1'),
	(13, 4, 'https://www.gamingsystem.ag/sbk/sportsbook4/www.sportsbook.ag/getodds.xgi?categoryId=92', 'Sportsbook', 0, 'sportsbook.xml', '', '-1'),
	(14, 8, 'https://www.sportsinteraction.com/info/data/feeds/consume/?consumerName=bfodds&pwd=bfodds3145&feedID=30&formatID=4', 'SportsInteraction', 0, 'sportsint.xml', '', '-1'),
	(15, 11, 'http://www.thegreek.com/sportsbook/sport/martial%20arts', 'TheGreek', 0, 'thegreek.asp', '', '-1'),
	(16, 4, 'https://www.gamingsystem.ag/sbk/sportsbook4/www.sportsbook.ag/getodds.xgi?categoryId=122', 'SportsbookMMA', 0, 'sportsbook.xml', '', '-1'),
	(17, 17, 'http://whdn.williamhill.com/pricefeed/openbet_cdn?action=template&template=getHierarchyByMarketType&classId=402&filterBIR=N', 'WilliamHill', 0, 'williamhill.xml', '', '-1'),
	(18, 18, 'http://xmlfeed.intertops.com/xmloddsfeed/v2/xml/?apikey=860879d0-f4b6-e511-a090-003048dd52d5&sportId=6&includeCent=true', 'Intertops', 1, 'intertops.xml', '&delta=', '525600');


	
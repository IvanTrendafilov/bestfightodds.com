INSERT INTO bets_boxing.bookies (`id`, `name`, `url`, `refurl`, `active`, `position`) VALUES
	(1, '5Dimes', 'http://www.5dimes.com', 'http://affiliates.5dimes.com/tracking/Affiliate.asp?AffID=5D1796725&mediaTypeID=220&AffUrlID=31', 1, 1),
	(17, 'William Hill', 'http://www.williamhill.eu', 'http://ads2.williamhill.com/redirect.aspx?pid=189883705&bid=1487413667&lpid=1487413047', 1, 3),
	(2, 'SportBet', 'http://www.sportbet.com', 'http://affiliates.sportbet.com/tracking/Affiliate.asp?AffID=5D1796725&mediaTypeID=220&AffUrlID=31', 1, 9),
	(3, 'BookMaker', 'http://www.bookmaker.eu', 'http://www.bookmaker.eu/?cmpid=13797_393', 1, 5),
	(4, 'Sportsbook', 'http://www.sportsbook.ag', 'http://affiliates.commissionaccount.com/processing/clickthrgh.asp?btag=a_4684b_388', 1, 40),
	(5, 'Bodog', 'http://www.bovada.lv', 'http://record.bettingpartners.com/_3_I4QQ0O0x6cp_Bvs7i_umNd7ZgqdRLk/1/', 1, 4),
	(8, 'SportsInteraction', 'http://www.sportsinteraction.com', 'http://affiliate.sportsinteraction.com/processing/clickthrgh.asp?btag=a_3145b_470&aid=mma', 1, 13),
	(9, 'Pinnacle', 'http://www.pinnaclesports.com', 'https://wlpinnacle.adsrv.eacdn.com/C.ashx?btag=a_1274b_10678c_&affid=5414&siteid=1274&adid=10678&c=', 1, 12),
	(18, 'Ladbrokes', NULL, 'http://online.ladbrokes.com/promoRedirect?key=ej0xMzkzNDczMyZsPS0xJnA9NjgzMjc0', 1, 10),
	(11, 'TheGreek', 'http://www.thegreek.com', 'http://www.thegreek.com/dw/sportsbook.asp?ap=A165125', 1, 25),
	(12, 'BetOnline', 'http://www.betonline.com', 'http://partners.commission.bz/processing/clickthrgh.asp?btag=a_1811b_2', 1, 30),
	(13, 'BetDSI', 'http://www.betdsi.com', 'http://www.betdsi.eu/mma-betting?cmpid=18239_4778', 1, 6),
	(14, 'Bet365', 'http://www.bet365.com', 'http://www.bet365.com/home/?affiliate=365_380605', 1, 2),
	(16, 'Intertops', 'http://www.intertops.eu', 'http://affiliate.intertops.com/processing/clickthrgh.asp?btag=a_10831b_1099', 1, 8);

INSERT INTO bets_boxing.bookies_changenums(bookie_id, changenum) VALUES
(1, '-1'),
(2, '-1'),
(9, '-1'),
(16, '-1');

INSERT INTO bets_boxing.bookies_parsers (`id`, `bookie_id`, `parse_url`, `name`, `cn_inuse`, `mockfile`, `cn_urlsuffix`, `cn_initial`) VALUES
	(1, 1, 'http://lines.5dimes.com/linesfeed/getlinefeeds.aspx?uid=bestfightodds5841&Type=ReducedReplace', '5Dimes', 1, '5dimes.xml', '&changenum=', '-1'),
	(2, 13, 'https://www.bestfightodds.com/externalfeeds/betdsi-latest.xml', 'BetDSI', 0, 'betdsi.xml', '', '-1'),
	(3, 12, 'http://livelines.betonline.com/sys/LineXML/LiveLineObjXml.asp?sport=Boxing', 'BetOnline', 0, 'betonline.xml', '', '-1'),
	(4, 12, 'http://www.betonline.com/sports/Line/RetrieveLineData?param.PrdNo=-1&param.Type=Cntst&param.RequestType=Normal&param.CntstParam.Lv1=Boxing%20Props', 'BetOnlineProps', 0, 'betonlineprops.xml', '', '-1'),
	(15, 17, 'http://whdn.williamhill.com/pricefeed/openbet_cdn?action=template&template=getHierarchyByMarketType&classId=10&filterBIR=N', 'WilliamHill', 0, 'williamhill.xml', '', '-1'),
	(7, 3, 'https://www.bestfightodds.com/externalfeeds/bookmaker-latest.xml', 'BookMaker', 0, 'bookmaker.xml', '', '-1'),
	(8, 5, 'http://sportsfeeds.bovada.lv/basic/BOX.xml', 'Bovada', 0, 'bovada.xml', '', '-1'),
	(9, 9, 'https://api.pinnaclesports.com/v1/fixtures?sportId=6&IsLive=0', 'Pinnacle', 0, 'pinnacle.xml', '&since=', '-1'),
	(10, 2, 'http://lines.sportbet.com/linesfeed/getlinefeeds.aspx?UID=bestfightodds5841', 'SportBet', 1, 'sportbet.xml', '&changenum=', '-1'),
	(11, 4, 'https://www.gamingsystem.ag/sbk/sportsbook4/www.sportsbook.ag/getodds.xgi?categoryId=104', 'Sportsbook', 0, 'sportsbook.xml', '', '-1'),
	(12, 11, 'http://www.thegreek.com/sportsbook/sport/boxing', 'TheGreek', 0, 'thegreek.xml', '', '-1'),
	(13, 16, 'http://xmlfeed.intertops.com/xmloddsfeed/v2/xml/?apikey=860879d0-f4b6-e511-a090-003048dd52d5&sportId=6&catId=1629&includeCent=true', 'Intertops', 1, 'intertops.xml', '&delta=', '525600'),
	(14, 8, 'https://www.sportsinteraction.com/info/data/feeds/consume/?consumerName=bfodds&pwd=bfodds3145&feedID=5&formatID=4', 'SportsInteraction', 0, 'sportsint.xml', '', '-1'),
	(16, 14, 'http://oddsfeed.bet365.com/Boxing_v2.asp', 'Bet365', 0, 'bet365.xml', '', '-1'),
	(17, 18, 'https://www.proboxingodds.com/externalfeeds/ladbrokes-every4hour.json', 'Ladbrokes', 0, 'ladbrokes.xml', '', '-1');

<?php

class ResultParserTools
{
	private $logger;

	public function __construct($logger) 
	{
		$this->logger = $logger;
	}

	public function getPageFromWikipedia($title)
	{
			//Grab content page for found search result
			$wiki_content_result = $this->fetchPage("https://en.wikipedia.org/w/api.php?format=json&action=query&prop=revisions&rvprop=content&titles=" . urlencode($title));
			$wiki_content_json = json_decode($wiki_content_result, true);
			$this->logger->info('Found and fetched URL through search: ' . $wiki_content_url);
			return current($wiki_content_json['query']['pages'])['revisions'][0]['*']; 
	}

	public function getHTMLPageFromWikipedia($title)
	{
			//Grab content page for found search result
			$wiki_content_result = $this->fetchPage("https://en.wikipedia.org/w/api.php?redirects=true&action=parse&format=json&prop=text&page=" . urlencode($title));
			$wiki_content_json = json_decode($wiki_content_result, true);
			$this->logger->info('Found and fetched URL through search: https://en.wikipedia.org/w/api.php?redirects=true&action=parse&format=json&prop=text&page=' . urlencode($title));
			return $wiki_content_json['parse']['text']['*']; 
	}

	public function searchWikipediaForTitle($title)
	{
		$this->logger->info('Search URL: ' . $title);
		$wiki_search_result = $this->fetchPage("https://en.wikipedia.org/w/api.php?action=query&list=search&utf8=&format=json&srsearch=" . urlencode($title));
		$wiki_search_json = json_decode($wiki_search_result);

		if (!isset($wiki_search_json->query->search[0]->title))
		{
			$this->logger->warning('No search results found');	
			return false;
		}

		//If the first results are lists of some sort (starts with "List ") then skip them
		$i = 0;
		$searchtitle = $wiki_search_json->query->search[0]->title;
		while (substr($wiki_search_json->query->search[$i]->title, 0, 5) == 'List ' && $i <= count($wiki_search_json->query->search))
		{
			$i++;
			$searchtitle = $wiki_search_json->query->search[$i]->title;

		}
		return $searchtitle;
	}

	public function fetchPage($url)
	{
		$curl_opts = array(CURLOPT_USERAGENT => 'BestFightOdds/1.0 (https://bestfightodds.com/; info1@bestfightodds.com) BestFightOdds/1.0',
			CURLOPT_HTTPHEADER => ["accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8"]);



		$content = ParseTools::retrievePageFromURL($url, $curl_opts);
		return $content;
	}


	public function getGenericWinningMethods($method)
	{
		$method = trim(strtolower($method));

		//Check for keywords in the method that determines the aggregated method
		if (strpos($method, 'submission') !== false) {
		    return 'submission';
		}
		else if (strpos($method, 'draw') !== false) {
		    return 'draw';
		}
		else if (strpos($method, 'decis') !== false) {
		    if (strpos($method, 'split') !== false) {
		    	return "split dec";
			}
			else if (strpos($method, 'majority') !== false) {
		    	return "majority dec";
			}
			else if (strpos($method, 'unan') !== false) {
		    	return "unanimous dec";
			}
		}
		else if (strpos($method, 'dq') !== false || strpos($method, 'disq') !== false) {
		    return 'nc';
		}		
		else if (strpos($method, 'tko') !== false || strpos($method, 'ko') !== false) {
		    return 'tko/ko';
		}
		else if (strpos($method, 'nc') !== false || strpos($method, 'no contest') !== false) {
		    return 'nc';
		}
		else if (strpos($method, 'stoppage') !== false) {
		    return 'stoppage';
		}
		return 'other: ' . $method;

	}

}

?>
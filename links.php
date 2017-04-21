<?php
/*
  * PHP class for checking broken links of given website url
  * Author: Jan Fitz
  * Date: 2017-04-20
  * Version: v1.0
*/

ini_set('default_socket_timeout', 15);

class Links {

	/**
	* Process terminal arguments
	*
	* @param array $argv		terminal arguments
	*
	* @return int 				exit status	
	*	
	*/
	public function processArgs($argv) {
	  	switch($argv[1]) {

	   		case "-project" :
	       		$badLinks = $this->getLinks($argv[2]);

         		if(!empty($badLinks)) {
         			echo sizeof($badLinks) . " links broken\n";
         			print_r($badLinks);
         		}
         		else {
         			echo "No broken links found\n";
         		}
         	break;

        	default:
	          	echo "Something went wrong with args processing\n";
    	}

    	return;
   	}

	/**
	* Get all links on current page
	*
	* @param string $project		project name (url format)
	* @param array $links 			default empty array of found links
	* @param array $nadLinks		default empty array of badLinks
	*
	* @return array $badLinks		
	* 
	*/
	public function getLinks($project, $links = array(), $badLinks = array()) {

		$links = $this->normalizeLinks(
					$this->getTags(
						file_get_contents($project),
						$links
					), 
					$project
				);

		$parsedUrl = parse_url($project);
		$root = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];

		foreach ($links as $link) {
			/*
			* Link contains host name => internal link
			* Check validation and add to list
			*/	
			if (strpos($link, $root) !== false) {
				error_reporting(0);
					$content = file_get_contents($link);
				error_reporting(-1);
				if($content) {
					$links = $this->getTags(
								$content, 
								$links
							);
				}					
				$links = array_unique($links);
			}	
			/* 
			* Link does not contain host name => external link
			* Check validation and die
			*/	
			else {
				// Simulate array of links (function checkLinks accept an array)
				$oneLink[0] = $link; 

				$badLinks = array_merge(
					$badLinks, 
					$this->checkLinks($oneLink, $badLinks, $link)
				);
			}					
		}	

		//$links = array_unique($this->normalizeLinks($links, $project));	
		//echo sizeof($links) . " links found \n";	

		$badLinks = $this->checkLinks(
						array_unique(
							$this->normalizeLinks(
								$links, 
								$project 
							)
						), 
						$badLinks, 
						$project
					);	

	    return array_unique($badLinks, SORT_REGULAR);
	}

	/**
	* Normalize intern links to executable format
	*
	* @param array $links 			default empty array of found links
	* @param string $project		project name (url format)
	*
	* @return array $link 			normalized links		
	*
	*/
	public function normalizeLinks($links, $project) {

		foreach($links as &$link) {

		    $parsedUrl = parse_url($project);
			$root = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];

			if (strncmp($link, '#', 1) === 0) {
				$link =  "/";
			}
		    if (strncmp($link, '/', 1) === 0) {
		    	$link =  $root . $link;
		    }
		    
		    /*
		    * Add root host name to link if it is an internal link
		    * Does not contain http or https but starts with slash
		    */
		    if (substr($link, 0, 7 ) !== "http://") {
		    	if(substr($link, 0, 8 ) !== "https://") {
					if (substr($link, 0, 1 ) !== "/") {
				    	$link =  $root . "/" . $link;
					}
					else {
			    		$link =  $root . $link;
					}
				}
		    }    	
		}
	    return $links;
	}

	/**
	* Check http response of all recognized links
	*
	* @param array $links 			array of found links
	* @param array $badLinks 		array of found broken links
	* @param string $page 			current page
	*
	* @return array $badLinks 		found broken links	
	*
	*/
	public function checkLinks($links, $badLinks, $page) {

		foreach($links as $link) {

			error_reporting(0);
				$headers = get_headers($link, 1);
			error_reporting(-1);

			if ($headers) {				
		    	$statusCode = substr($headers[0], 9, 3 );
		    	/*
		    	* Accept only 2xx and 3xx response codes
		    	*/
			    if(substr($statusCode, 0, 1) !== "2") {
			    	if(substr($statusCode, 0, 1) !== "3") {
			    		array_push($badLinks, array(
			    			'page'=>$page,
			    			'link'=>$link,
			    			'statusCode'=>$statusCode
			    		)); 
			    	}
			    }	    	
			}
			/*
			* Script can not read header of given url
			*/
			else {
				array_push($badLinks, array(
			    	'page'=>$page,
			    	'link'=>$link,
			    	'statusCode'=>'Could not resolve host'
			    )); 
			}
		}	 
		return $badLinks;
	}

	/**
	*  Find all tags containing links
	*
	* @param string $content		html content of current page
	* @param array $links			default empty array of links
	*
	* @return array $links 	 		found links	
	*
	*/
	public function getTags($content, $links) {

		static $newLinks = array();

		/*
		* Get content of all href attributes
		*/
		preg_match_all('/href=".*"/U', $content, $links);
		foreach ($links as $linkArray) {
			foreach ($linkArray as $link) {
				$link = strrev(
							substr(
								strrev(
									substr(
										$link, 
										6
									)
								), 
								1
							)
						);
				array_push($newLinks, $link); 
			}
		}

		/*
		* Get content of all src attributes
		*/
		preg_match_all('/src=".*"/U', $content, $links);
		foreach ($links as $linkArray) {
			foreach ($linkArray as $link) {
				$link = strrev(
							substr(
								strrev(
									substr(
										$link, 
										5
									)
								), 
								1
							)
						);
				array_push($newLinks, $link); 
			}
		}

		array_merge($links, $newLinks);

	    return $newLinks;
	}
}

$obj = new Links;
$obj->processArgs($argv);
?>

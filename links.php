<?php
/*
  * PHP class for checking broken links on given website url
  * Author: Jan Fitz
  * Date: 2017-04-20
  * Version: v1.0
*/

ini_set('default_socket_timeout', 15);
include_once('simple_html_dom.php');

class Links {
  /*
  * Process terminal arguments
  */
   public function processArgs($argv) {
    	switch($argv[1]) {

       		case "-project" :
         		$badLinks = $this->getLinks($argv[2]);

         		if(!empty($badLinks)) {
         			print_r($badLinks);
         		}
         		else {
         			echo "No broken links found\n";
         		}
         	break;

        	default:
          	echo "Something went wrong with args processing\n";
    	}
   	}

	/*
	* Get all links on current page
	*/
	public function getLinks($project, $links = array(), $badLinks = array()) {

		$content = file_get_html($project);		

		$links = $this->getTags($content, $links);
		
		$links = $this->normalizeLinks($links, $project);

		// Get root of current project
		$parsedUrl = parse_url($project);
		$root = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];

		foreach ($links as $link) {
			/*
			* Link contains host name => internal link
			* Check validation and add to list
			*/	
			if (strpos($link, $root) !== false) {
				error_reporting(0);
				$content = file_get_html($link);
				error_reporting(-1);
				if($content) {
					$links = $this->getTags($content, $links);
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
		$links = array_unique($this->normalizeLinks($links, $project));

		echo sizeof($links) . " links found \n";	

		$badLinks = $this->checkLinks($links, $badLinks, $project);

	    return array_unique($badLinks, SORT_REGULAR);
	}

	/*
	*  Find all tags containing links
	*/
	public function getTags($content, $links) {

		// Find all 'a' tags (href atributte)
		foreach($content->find('a') as $element) {
			if($element->href) {	    	
				array_push($links, $element->href); 
			}
		}

		// Find all 'a' tags (href atributte)
		foreach($content->find('area') as $element) {
			if($element->href) {	    	
				array_push($links, $element->href); 
			}
		}

		// Find all 'a' tags (href atributte)
		foreach($content->find('base') as $element) {
			if($element->href) {	    	
				array_push($links, $element->href); 
			}
		}

		// Find all 'a' tags (href atributte)
		foreach($content->find('link') as $element) {
			if($element->href) {	    	
				array_push($links, $element->href); 
			}
		}
		
		// Find all 'img' tags (href atributte)
		foreach($content->find('img') as $element) {
		    if($element->scr) {
		    	array_push($links, $element->src); 
		    }
		}

		// Find all 'img' tags (href atributte)
		foreach($content->find('audio') as $element) {
		    if($element->scr) {
		    	array_push($links, $element->src); 
		    }
		}

		// Find all 'img' tags (href atributte)
		foreach($content->find('embed') as $element) {
		    if($element->scr) {
		    	array_push($links, $element->src); 
		    }
		}

		// Find all 'img' tags (href atributte)
		foreach($content->find('iframe') as $element) {
		    if($element->scr) {
		    	array_push($links, $element->src); 
		    }
		}

		// Find all 'img' tags (href atributte)
		foreach($content->find('script') as $element) {
		    if($element->scr) {
		    	array_push($links, $element->src); 
		    }
		}

		// Find all 'img' tags (href atributte)
		foreach($content->find('input') as $element) {
		    if($element->scr) {
		    	array_push($links, $element->src); 
		    }
		}

		// Find all 'img' tags (href atributte)
		foreach($content->find('source') as $element) {
		    if($element->scr) {
		    	array_push($links, $element->src); 
		    }
		}		

		// Find all 'img' tags (href atributte)
		foreach($content->find('track') as $element) {
		    if($element->scr) {
		    	array_push($links, $element->src); 
		    }
		}

		// Find all 'img' tags (href atributte)
		foreach($content->find('video') as $element) {
		    if($element->scr) {
		    	array_push($links, $element->src); 
		    }
		}

	    return $links;
	}

	/*
	* Normalize intern links to executable format
	*/
	public function normalizeLinks($links, $project) {

		// Recursively find all links on all pages
		foreach($links as &$link) {

			// Get root of current project
		    $parsedUrl = parse_url($project);
			$root = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];

			if (strncmp($link, '#', 1) === 0) {
				$link =  "/";
			}
		    if (strncmp($link, '/', 1) === 0) {
				// Concatenate new path
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

	/*
	* Check http response of all recognized links
	*/
	public function checkLinks($links, $badLinks, $page) {

		foreach($links as $link) {

			error_reporting(0);
			$headers = get_headers($link, 1);
			error_reporting(-1);

			if ($headers) {				
		    	$statusCode = substr($headers[0], 9, 3 );
		    	/*
		    	* Accept only request 2xx and 3xx codes
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
}

$obj = new Links; // New instance of json object

$obj->processArgs($argv); // Process terminal arguments
?>

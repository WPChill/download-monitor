<?php

/**
* Link validator v0.3
*	by: me@daantje.nl
*
*	first written: 30 october 2003
*	last update: Sun Mar 23 01:18:04 CEST 2008
*
*	Documentation:
*		This class will check if a link gives an error.
*       Mind your firewall! You'll need outgoing tcp port 80 to be open!!
*
*	Changed:
*		version 0.3:
*			(Sun Mar 23 01:18:04 CEST 2008)
*			The class follows redirects when status is between 300 and 399...
*			Default this option is set TRUE!! This can be disabled as shown
*			in example 4 in this file, with the follow_redirects().
*			Thanx to Felix Nagel for the feature request.
*			Also tested and works on PHP4 and PHP5.
*			Fixed PHP warnings.
*		version 0.2:
*			(Mon Mar 28 15:45:54 CEST 2005)
*			Now the class tries the HTTP/1.0 protocol first and
*			than when a 400 status is given, tries the HTTP/1.1. This will
*			fix hanging on some old servers. Thanx to __DireWolf for the
*			bug report.
*
*	This program is free software; you can redistribute it and/or modify
*	it under the terms of the GNU General Public License as published by
*	the Free Software Foundation; either version 2 of the License, or
*	(at your option) any later version.
*
*	This program is distributed in the hope that it will be useful,
*	but WITHOUT ANY WARRANTY; without even the implied warranty of
*	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*	GNU General Public License for more details.
*
*	You should have received a copy of the GNU General Public License
*	along with this program; if not, write to the Free Software
*	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*	(http://www.gnu.org/licenses/gpl.txt)
*/

/*
	//EXAMPLE CODE
	//check one URL
	$linkValidator = new linkValidator('http://www.daantje.nl/');

	echo $linkValidator->message();
	echo "<hr>";
	echo $linkValidator->status();
	echo "<br>".($linkValidator->status() ? "worked" : "failed");



	//EXAMPLE CODE 2
	//check multiple URL's
	$linkValidator = new linkValidator();

	$checkThese = array(
		"http://www.rokenmoetmogen.nl/",
		"http://www.daantje.nl/",
		"http://www.daantje.nl/",
		"http://www.daantje.nl/blah.html",
		"http://www.google.nl/"
	);

	foreach($checkThese as $url){
		$linkValidator->linkValidator($url);
		echo "$url<br>";
		echo $linkValidator->message();
		echo ($linkValidator->status() ? " - worked" : " - failed") ."<br>";
		echo "<hr>";
		flush();
	}



	//EXAMPLE CODE 3
	//do it all manualy
	$linkValidator = new linkValidator();

	//disable redirects... Show when it's a status 30x
	$linkValidator->follow_redirects(FALSE);

	$array = $linkValidator->disectURL('http://www.daantje.nl/index.php');
	$linkValidator->open($array['host'],$array['port'],$array['get'],'http://www.daantje.nl/','http://www.whole.world/fake/referer.html');
	echo $linkValidator->message();
	echo ($linkValidator->status() ? " - worked" : " - failed") ."<br>";



	//EXAMPLE CODE 4
	//check if it's allowd from this file as referer...
	$linkValidator = new linkValidator('http://www.daantje.nl/index.php',$_SERVER['REQUEST_URI']);

	echo $linkValidator->message();
	echo "<hr>";
	echo $linkValidator->status();
	echo "<br>".($linkValidator->status() ? "worked" : "failed");
*/


class linkValidator {

	//first try 1.0 header
	var $httpHeader_10 = "GET %s HTTP/1.0\nUser-Agent: %s\nHost: %s\nAccept: */*\nConnection: Keep-Alive\n\r\n";
	//HTTP 1.1 used to fake the referer so we can check the link
	var $httpHeader_11 = "GET %s HTTP/1.1\nAccept: */*\nHost: %s\nUser-Agent: %s\nReferer: %s\n\r\n";

	//fake user agent (browser)
	var $userAgent = "Mozilla/6.0 [en] (Linux)";

	//default port to connect.
	var $port = 80;
	
	//follow redirects
	var $follow_redirects = true;


	/**
	* BOOL $status = linkValidator::linkValidator( [STRING $url] [, STRING $referer] [, BOOL $follow_redirects])
	*	Does it all at ones... When referer is empty, it will be calculated from given url....
	*	When all arguments are empty, the constructor will do nothing.
	*/
	function linkValidator($url = '',$referer = TRUE,$follow_redirects = TRUE){
		if($url){
			$this->follow_redirects = $follow_redirects;

			//prepare URL
			$arr = $this->disectURL($url);

			//get status
			$this->open($arr['host'],$arr['port'],$arr['get'],$referer,$follow_redirects);

			return $this->status();
		}
	}


	/**
	* linkValidator::follow_redirects(BOOL $follow_redirects)
	*	By default this option is set TRUE. You can use this method to disable it.
	*/
	function follow_redirects($v){
		$this->follow_redirects = $v;
	}


	/**
	* ASSOC ARRAY $array = linkValidator::disectURL( STRING $url )
	*	Will disect an URL and will return it in an associative array.
	*	Available keys will be 'host', 'port' and 'get'
	*/
	function disectURL($url){
		//strip junk
		if(strtolower(substr($url,0,7)) == 'http://')
			$url = substr($url,7);

		//disect the url...
		$p = explode('/',$url);		
		if (strstr($p[0], ':')) list($arr['host'],$arr['port']) = explode(':',$p[0]);
		else list($arr['host'],$arr['port']) = array($p[0], '');
		unset($p[0]);
		$arr['get'] = '/'.implode('/',$p);
		return $arr;
	}


	/**
	* STRING $string = linkValidator::open(STRING $host, INT $port, STRING $get, STRING $referer [, STRING $protocol])
	*	Open a connection, check the $get URI and return the status...
	*	Returns 'Connection refused.' on no status found, or host down.
	*/
	function open($host,$port,$get,$referer,$protocol='1.0'){
		//get default port?
		if(!$port)
			$port = $this->port;

		//kill strange stuff
		trim($get);
		trim($host);
		trim($port);
		trim($referer);

		//calculate fake referer?
		if(!$referer)
			$referer = "http://$host$get";

		//try to open socket
		if($host){		
			//$fp = @fsockopen($host,$port,&$errnr,&$err,10);
			$fp = @fsockopen($host,$port,$errnr,$err,10);
			if($fp && !$errnr){
				//get header
				$header = $protocol == '1.0' ?sprintf($this->httpHeader_10,$get,$this->userAgent,$host) : sprintf($this->httpHeader_11,$get,$host,$this->userAgent,$referer);

				//push header
				fputs($fp,$header);

				//check output
				unset($i); //some lame servers with no EOF protection.
				$i = 0;
				while(!feof($fp) && $i<50){
					$output = fgets($fp,1024);
					if(!$output)
						return $this->lastStatus = "No output from server.";

					$status = (INT) substr($output,9,3);

					//if status >= 300 check location.
					if($status >= 300 && $status < 400 && $this->follow_redirects){
						while($output != "\n" && substr($output,0,10) != 'Location: ')
							$output = fgets($fp,1024);
						$l = trim(substr($output,10));
						if(substr($l,0,1) != '/')
							$l = substr($get,0,strrpos($get,'/'))."/$l";
						return $this->open($host,$port,$l,$referer,$protocol);
					}

					//if bad request on a 1.0 protocol, lets try 1.1
					if($protocol == '1.0' && substr($output,9,3) == 400)
						return $this->open($host,$port,$get,$referer,'1.1');

					//we have a normal status
					if(strstr($output,"HTTP/1."))
						return $this->lastStatus = trim($output);
					$i++;
				}
				fclose($fp);
			}
		}
		return $this->lastStatus = "Connection refused.";
	}


	/**
	* BOOL $works = linkValidator::status()
	*	Will return 'true' when the last link gave a status below 400.
	*	Else it will return 'false'.
	*/
	function status(){
		if(substr($this->lastStatus,9,3) >= 400 || $this->lastStatus == "Connection refused." || $this->lastStatus == "No output from server.")
			return FALSE;
		else
			return TRUE;
	}


	/**
	* STRING $string = linkValidator::message()
	*	returns the complete status message of the last link.
	*/
	function message(){
		return $this->lastStatus;
	}
}
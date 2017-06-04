<?php
/* Copyright (c) 2011 Zuora, Inc.
*
* Permission is hereby granted, free of charge, to any person obtaining a copy of
* this software and associated documentation files (the "Software"), to use copy,
* modify, merge, publish the Software and to distribute, and sublicense copies of
* the Software, provided no fee is charged for the Software. In addition the
* rights specified above are conditioned upon the following:
*
* The above copyright notice and this permission notice shall be included in all
* copies or substantial portions of the Software.
*
* Zuora, Inc. or any other trademarks of Zuora, Inc. may not be used to endorse
* or promote products derived from this Software without specific prior written
* permission from Zuora, Inc.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL
* ZUORA, INC. BE LIABLE FOR ANY DIRECT, INDIRECT OR CONSEQUENTIAL DAMAGES
* (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
* ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
* SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

//Globals
// $maxZObjectCount = 50;
// $defaultApiNamespace = "ns1";
// $defaultApiNamespaceURL = "http://api.zuora.com/";
// $defaultObjectNamespace = "ns2";
// $defaultObjectNamespaceURL = "http://object.api.zuora.com/";
// $SEPARATOR = ",";
// $TEXT_QUALIFIER = "\"";
// $MAX_QUERY_RETURN_COUNT = 100;
// $MAX_QUERY_WHERE_CLAUSE_COUNT = 500;

################################################################################
# High level call that takes care of logging in and making the query API call.
function queryAPI($wsdl, $username, $password, $body, $debug) {
    return ZuoraAPIHelper::queryAPI($wsdl, $username, $password, $body, $debug);
}

################################################################################
# High level call that takes care of logging in and making the API call.
function callAPI($wsdl, $username, $password, $payload, $debug) {
    return ZuoraAPIHelper::callAPI($wsdl, $username, $password, $payload, $debug);
}

################################################################################
function callAPIWithClient($client, $header, $soapRequest, $debug) {
    return ZuoraAPIHelper::callAPIWithClient($client, $header, $soapRequest, $debug);
}

################################################################################
function login($client, $username, $password, $debug) {
    return ZuoraAPIHelper::login($client, $username, $password, $debug);
}

################################################################################
# code pulled from http://www.imarc.net/communique/view/148/xml_pretty_printer_in_php5
function xml_pretty_printer($xml, $html_output=FALSE) {
    return ZuoraAPIHelper::xml_pretty_printer($xml, $html_output);
}

################################################################################
function xmlspecialchars($text) {
    return ZuoraAPIHelper::xmlspecialchars($text);
}

################################################################################
function getMethod($xml) {
    return ZuoraAPIHelper::getMethod($xml);
}

################################################################################
function getZObjectCount($xml, $method) {
    return ZuoraAPIHelper::getZObjectCount($xml, $method);
}

################################################################################
function printUsage() {
    return ZuoraAPIHelper::printUsage();
}

################################################################################
function createClient($wsdl, $debug) {
    return ZuoraAPIHelper::createClient($wsdl, $debug);
}

################################################################################
function createRequest($sessionKey, $payload) {
    return ZuoraAPIHelper::createRequest($sessionKey, $payload);
}

################################################################################
function createRequestWithNS($sessionKey, $payload, $apiNamespace, $objectNamespace) {
    return ZuoraAPIHelper::createRequestWithNS($sessionKey, $payload, $apiNamespace, $objectNamespace);
}

################################################################################
function getSoapAddress($wsdl) {
    return ZuoraAPIHelper::getSoapAddress($wsdl);
}

################################################################################
function getXMLElementFromWSDL($wsdl) {
    return ZuoraAPIHelper::getXMLElementFromWSDL($wsdl);
}

################################################################################
function getFileContents($wsdl) {
    return ZuoraAPIHelper::getFileContents($wsdl);
}

################################################################################
function bulkOperation($client, $header, $method, $payload, $itemCount, $debug, $htmlOutput=FALSE) {
    return ZuoraAPIHelper::bulkOperation($client, $header, $method, $payload, $itemCount, $debug, $htmlOutput);
}

################################################################################
function getOperationListFromWSDL($wsdl, $debug) {
    return ZuoraAPIHelper::getOperationListFromWSDL($wsdl, $debug);
}

################################################################################
function getObjectListFromWSDL($wsdl, $debug) {
    return ZuoraAPIHelper::getObjectListFromWSDL($wsdl, $debug);
}

################################################################################
function getAPIObjectListFromWSDL($wsdl, $namespace, $debug) {
    return ZuoraAPIHelper::getAPIObjectListFromWSDL($wsdl, $namespace, $debug);
}

################################################################################
function printTemplate($wsdl, $call, $object, $debug, $offset) {
    return ZuoraAPIHelper::printTemplate($wsdl, $call, $object, $debug, $offset);
}

################################################################################
function printTemplateWithNS($wsdl, $call, $object, $debug, $offset, $apiNamespace, $objectNamespace) {
    return ZuoraAPIHelper::printTemplateWithNS($wsdl, $call, $object, $debug, $offset, $apiNamespace, $objectNamespace);
}

################################################################################
class ZuoraAPIHelper {
	

	public static $maxZObjectCount = 50;
	public static $defaultApiNamespace = "ns1";
	public static $defaultApiNamespaceURL = "http://api.zuora.com/";
	public static $defaultObjectNamespace = "ns2";
	public static $defaultObjectNamespaceURL = "http://object.api.zuora.com/";
	public static $SEPARATOR = ",";
	public static $TEXT_QUALIFIER = "\"";
	public static $MAX_QUERY_RETURN_COUNT = 100;
	public static $MAX_QUERY_WHERE_CLAUSE_COUNT = 500;
	
    // Call Globals
    public static $lastLoginTime = 0;
    public static $loginToken = "";
    public static $client = 0;
    public static $header = 0;
    public static $batchSize = 0;

    ################################################################################
    public static function getNodeValue($xml, $xpath, $prefix, $namespace) {
    	 $xml_obj = new SimpleXMLElement($xml);
    	 $xml_obj->registerXPathNamespace($prefix,$namespace);
    	 $node = $xml_obj->xpath($xpath);
    	 return $node;
    }

    ################################################################################
    public static function getCreateResponseFieldValues($xml, $field) {
        // global $defaultApiNamespace;
        // global $defaultApiNamespaceURL;
        return ZuoraAPIHelper::getNodeValue($xml, "//*/*/*/*/" . ZuoraAPIHelper::$defaultApiNamespace . ":" . $field, ZuoraAPIHelper::$defaultApiNamespace, ZuoraAPIHelper::$defaultApiNamespaceURL);
    }

    ################################################################################
    public static function getQueryResponseFieldValues($xml, $field) {
        // global $defaultObjectNamespace;
        // global $defaultObjectNamespaceURL;
    	return ZuoraAPIHelper::getNodeValue($xml, "//*/*/*/*/*/" . ZuoraAPIHelper::$defaultObjectNamespace . ":" . $field, ZuoraAPIHelper::$defaultObjectNamespace, ZuoraAPIHelper::$defaultObjectNamespaceURL);
    }

    ################################################################################
    public static function getQueryResponseRecords($xml,$labels) {
        // global $defaultApiNamespace;
        // global $defaultApiNamespaceURL;
	// global $MAX_QUERY_RETURN_COUNT;
        $xml_obj = new SimpleXMLElement($xml);
        $xml_obj->registerXPathNamespace(ZuoraAPIHelper::$defaultApiNamespace,ZuoraAPIHelper::$defaultApiNamespaceURL);
        $resultRecords = $xml_obj->xpath("//" . ZuoraAPIHelper::$defaultApiNamespace . ":records");
        $sizeNode = $xml_obj->xpath("//" . ZuoraAPIHelper::$defaultApiNamespace . ":size");

        if ((int)$sizeNode[0] > count($resultRecords)) {
           print "\nWARNING: " . count($resultRecords) . " records returned, however the query return size is " . (int)$sizeNode[0] . ". Please use QueryMore to retrieve the additional records.\n";
        }

        return ZuoraAPIHelper::getQueryResultsFromRecords($resultRecords,$labels);
    }

    ################################################################################
    public static function getQueryResultsFromRecords($records,$labels) {
        // global $defaultObjectNamespace;
        // global $defaultObjectNamespaceURL;
        $retList = array();

        for ($i = 0; $i < count($records);$i++) {
            $found = false;
            $subList = array();
            for ($j = 0; $j < count($labels); $j++) {
            	$records[$i]->registerXPathNamespace(ZuoraAPIHelper::$defaultObjectNamespace,ZuoraAPIHelper::$defaultObjectNamespaceURL);
            	$value = $records[$i]->xpath(ZuoraAPIHelper::$defaultObjectNamespace . ":" . $labels[$j]);
        	if (count($value) > 0) {
        	   $subList[$labels[$j]] = $value[0];
                   $found = true;
        	} else {
        	   $subList[$labels[$j]] = "";
        	}
            }
            if ($found) {
                $retList[] = $subList;
            }
        }
        return $retList;
    }

    ################################################################################
    # High level call that takes care of logging in and making the query API call.
    public static function queryAPI($wsdl, $username, $password, $body, $debug) {
        return ZuoraAPIHelper::queryAPIWithSession($body, $debug, $wsdl, $username, $password);
    }

    ################################################################################
    # High level call that takes care of logging in and making the query API call.
    public static function queryAPIWithSession($body, $debug, $wsdl="", $username="", $password="") {
        $payload = "<ns1:query><ns1:queryString>" . $body . "</ns1:queryString></ns1:query>";
        if (strlen($wsdl) > 0) {
            ZuoraAPIHelper::checkLogin($wsdl, $username, $password, $debug);
        }
        return ZuoraAPIHelper::callAPIWithSession($payload, $debug);
    }

    ################################################################################
    # Call to prepare a query string for discrete queries.
    public static function prepDiscreteQuery($body, $fieldStr, $fieldValues) {
        // global $MAX_QUERY_WHERE_CLAUSE_COUNT;
        if (stripos($body, "where") === false) {
            $body .= " where";
        } else {
            $body .= " and";
        }
        // Remove duplicates so that multiple records are not returned across discreet queries in the set.
        $fieldValues = array_unique($fieldValues);
        $querySet = array();
        for ($i = 0; $i < count($fieldValues); $i += ZuoraAPIHelper::$MAX_QUERY_WHERE_CLAUSE_COUNT) {
            $maxValue = min(count($fieldValues), $i + ZuoraAPIHelper::$MAX_QUERY_WHERE_CLAUSE_COUNT);
            $tmpQuery = $body;
            $initial = true;
            for ($j = $i; $j < $maxValue; $j++) {
                $value = $fieldValues[$j];
                if (strlen($value) <= 0) {
                    continue;
                }
                if (!$initial) {
                    $tmpQuery .= " or";
                }
                $tmpQuery .= " " . $fieldStr . "='" . $value . "'";
                $initial = false;
            }
            $querySet[] = $tmpQuery;
        }
        return $querySet;
    }

    ################################################################################
    # High level call that takes care of logging in and making the query & queryMore API calls.
    public static function queryMoreAPI($wsdl, $username, $password, $body, $debug) {
	return ZuoraAPIHelper::queryMoreAPIWithSession($body, $debug, $wsdl, $username, $password);
    }

    ################################################################################
    # High level call that takes care of logging in and making the query & queryMore API calls.
    public static function queryMoreAPIWithSession($body, $debug, $wsdl="", $username="", $password="") {
        // global $defaultApiNamespace;
        // global $defaultApiNamespaceURL;
	$resultRecords = array();

        $payload = "<ns1:query><ns1:queryString>" . $body . "</ns1:queryString></ns1:query>";
        if (strlen($wsdl) > 0) {
            ZuoraAPIHelper::checkLogin($wsdl, $username, $password, $debug);
        }
        $xml = ZuoraAPIHelper::callAPIWithSession($payload, $debug);
	$queryLocator = ZuoraAPIHelper::getQueryLocator($xml);

        $xml_obj = new SimpleXMLElement($xml);
        $xml_obj->registerXPathNamespace(ZuoraAPIHelper::$defaultApiNamespace,ZuoraAPIHelper::$defaultApiNamespaceURL);
        $records = $xml_obj->xpath("//" . ZuoraAPIHelper::$defaultApiNamespace . ":records");
	foreach ($records as $record) {
            $resultRecords[] = $record;
        }

	while ($queryLocator) {
	    $payload = "<ns1:queryMore><ns1:queryLocator>" . $queryLocator . "</ns1:queryLocator></ns1:queryMore>";
            if (strlen($wsdl) > 0) {
                ZuoraAPIHelper::checkLogin($wsdl, $username, $password, $debug);
            }
            $xml = ZuoraAPIHelper::callAPIWithSession($payload, $debug);
	    $queryLocator = ZuoraAPIHelper::getQueryLocator($xml);

            $xml_obj = new SimpleXMLElement($xml);
            $xml_obj->registerXPathNamespace(ZuoraAPIHelper::$defaultApiNamespace,ZuoraAPIHelper::$defaultApiNamespaceURL);
            $records = $xml_obj->xpath("//" . ZuoraAPIHelper::$defaultApiNamespace . ":records");
	    foreach ($records as $record) {
                $resultRecords[] = $record;
            }
	}
	return $resultRecords;
    }

    ################################################################################
    # High level call that takes care of logging in and making the API call.
    public static function callAPI($wsdl, $username, $password, $payload, $debug) {

     try {
        ZuoraAPIHelper::checkLogin($wsdl, $username, $password, $debug);

        return ZuoraAPIHelper::callAPIWithSession($soapRequest, $debug);
     } catch (Exception $e) {
        //var_dump($e);
        throw new Exception($e->getMessage());
     }
    }

    ################################################################################
    # High level call that takes care of making the API call.
    public static function callAPIWithSession($payload, $debug) {

     try {
        $soapRequest = ZuoraAPIHelper::createRequest(ZuoraAPIHelper::$header->data["session"], $payload);

        if ($debug) {
           print "\n\nRequest:\n" . ZuoraAPIHelper::xml_pretty_printer($soapRequest);
        }

        return callAPIWithClient(ZuoraAPIHelper::$client, ZuoraAPIHelper::$header, $soapRequest, $debug);
     } catch (Exception $e) {
        //var_dump($e);
        throw new Exception($e->getMessage());
     }
    }

    ################################################################################
    public static function callAPIWithClient($client, $header, $soapRequest, $debug) {
       // global $maxZObjectCount;
       try {
          $client->myRequest = $soapRequest;   
          $soapMethod = ZuoraAPIHelper::getMethod($soapRequest);
       
          // check that we're not trying to create more than the maximum count of objects.
          $createCount = ZuoraAPIHelper::getZObjectCount($soapRequest, $soapMethod);
          if ($soapMethod == "create" && $createCount > ZuoraAPIHelper::$maxZObjectCount) {
             die("\n\nERROR: zObjects maximum count of " . ZuoraAPIHelper::$maxZObjectCount . " exceeded. Actual count found: " . $createCount . ".");
          }
       
          $timeBefore = microtime(true);
          $result = $client->__soapCall($soapMethod, array(), null, $header);   
          $timeAfter = microtime(true);

//echo "Request: " . $soapRequest . " Duration: " . ($timeAfter - $timeBefore)/60 . " minutes.\n";
       
          if ($debug) {
             print "\nResult:\n" . ZuoraAPIHelper::xml_pretty_printer($client->myResponse);
             print "\nResponse Time: " . ($timeAfter - $timeBefore);
          }
       
          return $client->myResponse;
       } catch (Exception $e) {
          throw $e;
       }
    }

    ################################################################################
    public static function checkLogin($wsdl, $username, $password, $debug){

     	$location = ZuoraAPIHelper::getSoapAddress($wsdl, $debug);
     	$tempLoginToken = $location . $username;
     	try {
     	    if (((microtime(true) - ZuoraAPIHelper::$lastLoginTime) > 600)
     	        || (ZuoraAPIHelper::$loginToken != $tempLoginToken)) {
     	        ZuoraAPIHelper::$loginToken = $tempLoginToken;
     	        if ($debug) {
     	      	    print "NOTE: Logging in.\n";
     	        }
     	        ZuoraAPIHelper::$client = ZuoraAPIHelper::createClient($wsdl, $debug);
     	        ZuoraAPIHelper::$header = ZuoraAPIHelper::login(ZuoraAPIHelper::$client, $username, $password, $debug);
     	        ZuoraAPIHelper::$lastLoginTime = microtime(true);
     	    }
     	} catch (Exception $e) {
     	    //var_dump($e);
     	    return $e->getMessage();
     	}
     	return "";
    }

    ################################################################################
    public static function loginWithSession($wsdl, $session, $debug){
       // global $defaultApiNamespaceURL;

       ZuoraAPIHelper::$client = ZuoraAPIHelper::createClient($wsdl, $debug);

       # set the authentication
       ZuoraAPIHelper::$header = ZuoraAPIHelper::getHeader($session);
       return true;
    }

    ################################################################################
    public static function getSession(){
       if (ZuoraAPIHelper::$header) {
           return ZuoraAPIHelper::$header->data["session"];
       } else {
           return "";
       }
    }

    ################################################################################
    public static function login($client, $username, $password, $debug){

       # do the login
       $login = array(
       		"username"=>$username,
       		"password"=>$password
       );

       $client->myDebug = 0;
       $result = $client->login($login);
       $client->myDebug = $debug;
       //if ($debug) var_dump($result);

       $session = $result->result->Session;
       $url = $result->result->ServerUrl;
       if ($debug) {
          print "\nSession: " . $session;
          print "\nServerUrl: " . $url;
          print "\n";
       }

       # set the authentication
       return $session;
       // return ZuoraAPIHelper::getHeader($session);
    }

    ################################################################################
   /*  public static function getHeader($sessionId){
       global $defaultApiNamespaceURL;

       $sessionVal = array('session'=>$sessionId);
       $header = new SoapHeader($defaultApiNamespaceURL,
    				'SessionHeader',
    				$sessionVal);
       return $header;
    }
 */
    ################################################################################
    # code pulled from http://www.imarc.net/communique/view/148/xml_pretty_printer_in_php5
    public static function xml_pretty_printer($xml, $html_output=FALSE)
    {
    $xml_obj = new SimpleXMLElement($xml);
    $xml_lines = explode("\n", str_replace("><",">\n<",$xml_obj->asXML()));
    $indent_level = 0;

    $new_xml_lines = array();
    foreach ($xml_lines as $xml_line) {
    	if (preg_match('#^(<[a-z0-9_:-]+((\s+[a-z0-9_:-]+="[^"]+")*)?>.*<\s*/\s*[^>]+>)|(<[a-z0-9_:-]+((\s+[a-z0-9_:-]+="[^"]+")*)?\s*/\s*>)#i', ltrim($xml_line))) {
    	   $new_line = str_pad('', $indent_level*4) . ltrim($xml_line);
    	   $new_xml_lines[] = $new_line;
    #	   $new_xml_lines[] = "Didn't increment 1.";
    	} elseif (preg_match('#^<[a-z0-9_:-]+((\s+[a-z0-9_:-]+="[^"]+")*)?>#i', ltrim($xml_line))) {

    	   $new_line = str_pad('', $indent_level*4) . ltrim($xml_line);
    	   $indent_level++;
    	   $new_xml_lines[] = $new_line;
    #	   $new_xml_lines[] = "Increment.";
    	} elseif (preg_match('#<\s*/\s*[^>/]+>#i', $xml_line)) {
    	   $indent_level--;
    	   if (trim($new_xml_lines[sizeof($new_xml_lines)-1]) == trim(str_replace("/", "", $xml_line))) {
    	      $new_xml_lines[sizeof($new_xml_lines)-1] .= $xml_line;
    #	      $new_xml_lines[] = "Decrement 1.";
    	   } else {
    	      $new_line = str_pad('', $indent_level*4) . $xml_line;
    	      $new_xml_lines[] = $new_line;
    #	      $new_xml_lines[] = "Decrement 2.";
    	   }
    	} else {
    	   $new_line = str_pad('', $indent_level*4) . $xml_line;
    	   $new_xml_lines[] = $new_line;
    #	   $new_xml_lines[] = "Didn't increment 2.";
    	}
    }

    $xml = join("\n", $new_xml_lines);
    return ($html_output) ? '<pre>' . xmlspecialchars($xml) . '</pre>' : $xml;
    }

    ################################################################################
    public static function xmlspecialchars($text) {
    	 return str_replace('&#039;', '&apos;', htmlspecialchars(str_replace('&','&amp;',$text), ENT_QUOTES, 'UTF-8', false));
    }

    ################################################################################
    public static function getMethod($xml) {
    	 $xml_obj = new SimpleXMLElement($xml);
    	 $xml_obj->registerXPathNamespace("SOAP-ENV","http://schemas.xmlsoap.org/soap/envelope/");
    	 $node = $xml_obj->xpath("//SOAP-ENV:Envelope/SOAP-ENV:Body/*");
    	 return $node[0]->getName();
    }

    ################################################################################
    public static function getQueryLocator($xml) {
    	 // global $defaultApiNamespace;
         // global $defaultApiNamespaceURL;
    	 $xml_obj = new SimpleXMLElement($xml);
         $xml_obj->registerXPathNamespace(ZuoraAPIHelper::$defaultApiNamespace,ZuoraAPIHelper::$defaultApiNamespaceURL);
         $node = $xml_obj->xpath("//" . ZuoraAPIHelper::$defaultApiNamespace . ":queryLocator");
    	 return $node[0];
    }

    ################################################################################
    public static function getZObjectCount($xml, $method) {
    	 // global $defaultApiNamespaceURL;
    	 $xml_obj = new SimpleXMLElement($xml);
    	 $xml_obj->registerXPathNamespace("SOAP-ENV","http://schemas.xmlsoap.org/soap/envelope/");
    	 $xml_obj->registerXPathNamespace("ns1",ZuoraAPIHelper::$defaultApiNamespaceURL);
    	 $nodes = $xml_obj->xpath("//SOAP-ENV:Envelope/SOAP-ENV:Body/ns1:" . $method . "/ns1:zObjects");
    	 // Can use the position() function to pull appropriate nodes.
    	 // e.g. //SOAP-ENV:Envelope/SOAP-ENV:Body/ns1:create/ns1:zObjects[position()<51]
    	 return count($nodes);
    }
    ################################################################################
    public static function printUsage() {
       // global $defaultApiNamespaceURL;
       // global $defaultObjectNamespaceURL;
       $usage = "USAGE: call.php api <username> <password> [call filename] [wsdl filename]\n";
       $usage = $usage . " call filename default: call.xml\n";
       $usage = $usage . " wsdl filename default: zuora.wsdl\n";
       $usage = $usage . "\nNOTE: call XML should like like this:\n";
       $usage = $usage . "<ns1:query><ns1:queryString>select id from Account</ns1:queryString></ns1:query>\n";
       $usage = $usage . "The following namespaces are to be assumed:\n";
       $usage = $usage . " ns1: " . ZuoraAPIHelper::$defaultApiNamespaceURL . "\n";
       $usage = $usage . " ns2: " . ZuoraAPIHelper::$defaultObjectNamespaceURL . "\n";
       $usage = $usage . " \n";
       $usage = $usage . "USAGE: call.php query <username> <password> <ZOQL> [wsdl filename]/\n";
       $usage = $usage . " executes the ZOQL query.\n";
       $usage = $usage . " wsdl filename default: zuora.wsdl\n";
       $usage = $usage . " \n";
       $usage = $usage . "USAGE: call.php pp <xml filename>/\n";
       $usage = $usage . " pretty prints passed in XML file contents.\n";
       $usage = $usage . " \n";
       $usage = $usage . "USAGE: call.php template <operation> <object name> [wsdl filename]/\n";
       $usage = $usage . " operation should be: create/query/update/delete.\n";
       $usage = $usage . " pretty prints XML of the selected object.\n";
       die($usage);
    }

    ################################################################################
    public static function createClient($wsdl, $debug) {
       $client = new MySoapClient($wsdl, array('exceptions' => 0));
       $client->setLocation(getSoapAddress($wsdl));
       $client->myDebug = $debug;
       return $client;
    }

    ################################################################################
    public static function createRequest($sessionKey, $payload) {
       // global $defaultApiNamespace;
       // global $defaultObjectNamespace;
       return ZuoraAPIHelper::createRequestWithNS($sessionKey, $payload, ZuoraAPIHelper::$defaultApiNamespace, ZuoraAPIHelper::$defaultObjectNamespace);
    }

    ################################################################################
    public static function createRequestWithNS($sessionKey, $payload, $apiNamespace, $objectNamespace) {
       // global $defaultApiNamespaceURL;
       // global $defaultObjectNamespaceURL;
       return ZuoraAPIHelper::createRequestAndHeadersWithNS($sessionKey, ZuoraAPIHelper::$batchSize, array(), $payload, $apiNamespace, $objectNamespace);
    }

    ################################################################################
    public static function createRequestAndHeadersWithNS($sessionKey, $batchSize, $callOptions, $payload, $apiNamespace, $objectNamespace) {
       // global $defaultApiNamespaceURL;
       // global $defaultObjectNamespaceURL;

       $sessionHeader = "";
       if (count(array_keys($callOptions)) > 0) {
       	   $sessionHeader .= "<" . $apiNamespace . ":CallOptions>";
       	   foreach ($callOptions as $paramKey => $paramValue) {
       	       $sessionHeader .= "<" . $apiNamespace . ":" . $paramKey . ">" . $paramValue . "</" . $apiNamespace . ":" . $paramKey . ">";
       	   }
       	   $sessionHeader .= "</" . $apiNamespace . ":CallOptions>";
       }

       $headerParams = array("session"=>$sessionKey);
       $sessionHeader .= "<" . $apiNamespace . ":SessionHeader>";
       foreach ($headerParams as $paramKey => $paramValue) {
           $sessionHeader .= "<" . $apiNamespace . ":" . $paramKey . ">" . $paramValue . "</" . $apiNamespace . ":" . $paramKey . ">";
       }
       $sessionHeader .= "</" . $apiNamespace . ":SessionHeader>";

       if ($batchSize > 0) {
       	   $headerOptions = array("batchSize"=>$batchSize);
       	   $sessionHeader .= "<" . $apiNamespace . ":QueryOptions>";
       	   foreach ($headerOptions as $paramKey => $paramValue) {
       	       $sessionHeader .= "<" . $apiNamespace . ":" . $paramKey . ">" . $paramValue . "</" . $apiNamespace . ":" . $paramKey . ">";
       	   }
       	   $sessionHeader .= "</" . $apiNamespace . ":QueryOptions>";
       }

       return "<?xml version=\"1.0\" encoding=\"UTF-8\"?><SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:" . $objectNamespace . "=\"" . ZuoraAPIHelper::$defaultObjectNamespaceURL . "\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:" . $apiNamespace . "=\"" . ZuoraAPIHelper::$defaultApiNamespaceURL . "\"><SOAP-ENV:Header>" . $sessionHeader ."</SOAP-ENV:Header><SOAP-ENV:Body>" . $payload . "</SOAP-ENV:Body></SOAP-ENV:Envelope>";
    }

    ################################################################################
    public static function getSoapAddress($wsdl) {
       $xml_obj = getXMLElementFromWSDL($wsdl);
       $node = $xml_obj->xpath("//default:definitions/default:service/default:port/soap:address");
       return (string) $node[0]->attributes()->location;
    }

    ################################################################################
    public static function getAPIVersion($wsdl) {
       $address = ZuoraAPIHelper::getSoapAddress($wsdl);
       #Expecting: https://apisandbox.zuora.com/apps/services/a/8.0
       return substr(strrchr($address, "/"), 1);
    }

    ################################################################################
    public static function getXMLElementFromWSDL($wsdl) {
       $xml = ZuoraAPIHelper::getFileContents($wsdl);

       $xml_obj = new SimpleXMLElement($xml);
       $xml_obj->registerXPathNamespace("default","http://schemas.xmlsoap.org/wsdl/");
       $xml_obj->registerXPathNamespace("soap","http://schemas.xmlsoap.org/wsdl/soap/");

       return $xml_obj;
    }

    ################################################################################
    public static function getFileContents($wsdl) {
       $contents = "";
       try {
          if(!file_exists($wsdl)) {
             throw new Exception("File '" . $wsdl . "' not found.");
          }
          $file = fopen($wsdl,"r");
	  if (filesize($wsdl) > 0) {
              $contents = fread($file,filesize($wsdl));
          }
          fclose($file);
       } catch (Exception $e) {
          throw $e;
       }
       return $contents;
    }

    ################################################################################
    public static function bulkOperation($client, $header, $method, $payload, $itemCount, $debug, $htmlOutput=FALSE, $zObjectCap=-1) {

       // global $maxZObjectCount;
       // global $defaultApiNamespaceURL;

       $result = array("errorList" => array(), "batchList" => array(), "successCount" => 0, "errorCount" => 0);

       // Only create/update calls are supported.
       if ($method != "create" && $method != "update" && $method != "delete") {
       	  return $result;
       }
       $nodeName = "zObjects";
       if ($method == "delete") {
       	  $nodeName = "ids";
       }

       // Allow control over how many objects are submitted in one call.
       if ($zObjectCap < 0) {
           $zObjectCap = ZuoraAPIHelper::$maxZObjectCount;
       }

       $soapRequest = createRequest($header->data["session"], $payload);
       $xml_obj = new SimpleXMLElement($soapRequest);
       $xml_obj->registerXPathNamespace("SOAP-ENV","http://schemas.xmlsoap.org/soap/envelope/");
       $xml_obj->registerXPathNamespace("ns1",ZuoraAPIHelper::$defaultApiNamespaceURL);
       $type = $xml_obj->xpath("//SOAP-ENV:Envelope/SOAP-ENV:Body/ns1:" . $method . "/ns1:type");

       // Iterate through the list of items, $zObjectCap at a time
       for ($counter = 0; $counter < $itemCount; $counter += $zObjectCap) {

          // Identify upper bound for this batch.
          $lowerBound = $counter + 1;
          $upperBound = $counter + $zObjectCap;
          if ($upperBound > $itemCount) {
             $upperBound = $itemCount;
          }

          // Create request for this batch of records.
          $batchNodes = $xml_obj->xpath("//SOAP-ENV:Envelope/SOAP-ENV:Body/ns1:" . $method . "/ns1:" . $nodeName . "[position()>=" . $lowerBound . " and position()<" . ($upperBound + 1) . "]");

          $batchPayload = "\n<ns1:" . $method . ">\n";
	  if ($method == "delete") {
             $batchPayload = $batchPayload . " " . $type[0]->asXML() . "\n";
	  }
          for ($i = 0; $i < count($batchNodes); $i++) {
             $batchPayload = $batchPayload . " " . $batchNodes[$i]->asXML() . "\n";
          }
          $batchPayload = $batchPayload . "</ns1:" . $method . ">\n";

          print "Batch " . ceil($upperBound / $zObjectCap) . ": submitting ZObjects " . $lowerBound . "-" . $upperBound . " (" . count($batchNodes) . ")...";
          $soapRequest = createRequest($header->data["session"], $batchPayload);

          // Execute the API call.
          $timeBefore = microtime(true);
          $batchResponse = ZuoraAPIHelper::callAPIWithClient($client, $header, $soapRequest, $debug);
          $timeAfter = microtime(true);
		  $result['response'] = $batchResponse; // Added by Myddleware to get the Ids
          print " " . ($timeAfter - $timeBefore) . " secs - Done.";
	  if ($htmlOutput) {
	     print "<br/>";
	  }
	  print "\n";

          // Parse through the response.
          $xml_obj2 = new SimpleXMLElement($batchResponse);
          $xml_obj2->registerXPathNamespace("SOAP-ENV","http://schemas.xmlsoap.org/soap/envelope/");
          $xml_obj2->registerXPathNamespace("ns1",ZuoraAPIHelper::$defaultApiNamespaceURL);
          $soapFaultNode = $xml_obj2->xpath("//SOAP-ENV:Envelope/SOAP-ENV:Body/SOAP-ENV:Fault");
          $resultNodes = $xml_obj2->xpath("//SOAP-ENV:Envelope/SOAP-ENV:Body/ns1:" . $method . "Response/ns1:result/ns1:Success | //SOAP-ENV:Envelope/SOAP-ENV:Body/ns1:" . $method . "Response/ns1:result/ns1:success");

          $successCount = 0;
          $errorCount = 0;
          if (count($soapFaultNode) > 0) {
              $errorCount = count($batchNodes);
              for ($i = 0; $i < count($batchNodes); $i++) {
                 $faultCode = $xml_obj2->xpath("//SOAP-ENV:Envelope/SOAP-ENV:Body/SOAP-ENV:Fault/faultstring");
                 $faultMessage = $xml_obj2->xpath("//SOAP-ENV:Envelope/SOAP-ENV:Body/SOAP-ENV:Fault/detail/Exception");			 
                 array_push($result["errorList"], array("index" => ($lowerBound + $i), "code" => (string)$faultCode[0], "message" => (!empty($faultMessage[0]) ? (string)$faultMessage[0] : '')));	
              }
              array_push($result["batchList"], array("start" => $lowerBound, "end" => $upperBound, "size" => count($batchNodes), "successCount" => $successCount, "errorCount" => $errorCount));
          } else {
              for ($i = 0; $i < count($resultNodes); $i++) {
                  $resultNode = $resultNodes[$i];
    	          if (strcasecmp($resultNode,"false") == 0) {
    	             $errorCodeNodes = $xml_obj2->xpath("//SOAP-ENV:Envelope/SOAP-ENV:Body/ns1:" . $method . "Response/ns1:result[position()=" . ($i+1) . "]/ns1:errors/ns1:Code | //SOAP-ENV:Envelope/SOAP-ENV:Body/ns1:" . $method . "Response/ns1:result[position()=" . ($i+1) . "]/ns1:Errors/ns1:Code");
    	             $errorMsgNodes = $xml_obj2->xpath("//SOAP-ENV:Envelope/SOAP-ENV:Body/ns1:" . $method . "Response/ns1:result[position()=" . ($i+1) . "]/ns1:errors/ns1:Message | //SOAP-ENV:Envelope/SOAP-ENV:Body/ns1:" . $method . "Response/ns1:result[position()=" . ($i+1) . "]/ns1:Errors/ns1:Message");
    	             $errorCount++;
    	             array_push($result["errorList"], array("index" => ($lowerBound + $i), "code" => (string)$errorCodeNodes[0], "message" => (string)$errorMsgNodes[0]));
    	          } else {
    	             $successCount++;
    	          }    
              }
              array_push($result["batchList"], array("start" => $lowerBound, "end" => $upperBound, "size" => count($resultNodes), "successCount" => $successCount, "errorCount" => $errorCount));
          }
          $result["successCount"] += $successCount;
          $result["errorCount"] += $errorCount;
       }

       return $result;
    }

    ################################################################################
    public static function getOperationListFromWSDL($wsdl, $debug) {
       $xml_obj = ZuoraAPIHelper::getXMLElementFromWSDL($wsdl);
       $node = $xml_obj->xpath("//default:definitions/default:portType/default:operation");
       $names = array();
       for ($i = 0; $i < count($node); $i++) {
           $names[] = (string) $node[$i]->attributes()->name;
       }
       return $names;
    }

    ################################################################################
    public static function getObjectListFromWSDL($wsdl, $debug) {
       // global $defaultObjectNamespaceURL;
       return ZuoraAPIHelper::getAPIObjectListFromWSDL($wsdl, /* ZuoraAPIHelper::$defaultObjectNamespaceURL, */ $debug);
    }

    ################################################################################
    public static function getAPIObjectListFromWSDL($wsdl, /* $namespace, */ $debug) {
       $xml_obj = ZuoraAPIHelper::getXMLElementFromWSDL($wsdl);   
       $xml_obj->registerXPathNamespace("xs","http://www.w3.org/2001/XMLSchema");
       $node = $xml_obj->xpath("//default:definitions/default:types/xs:schema[@targetNamespace='" . ZuoraAPIHelper::$defaultObjectNamespaceURL . "']/xs:complexType");
       $names = array();
       for ($i = 0; $i < count($node); $i++) {
           $names[] = (string) $node[$i]->attributes()->name;
       }	   
       return $names;
    }

    ################################################################################
    public static function printTemplate($wsdl, $call, $object, $debug, $offset) {
       // global $defaultApiNamespace;
       // global $defaultObjectNamespace;
       return ZuoraAPIHelper::printTemplateWithNS( $wsdl, $call, $object, $debug, $offset, ZuoraAPIHelper::$defaultApiNamespace, ZuoraAPIHelper::$defaultObjectNamespace);
    }

    ################################################################################
    public static function printTemplateWithNS($wsdl, $call, $object, $debug, $offset, $apiNamespace, $objectNamespace) {
    /*
       // Handle "delete".
       if ($call == "delete") {
          $payload = str_repeat(" ", $offset) . "<" . $apiNamespace . ":delete>\n";
          $payload .= str_repeat(" ", $offset) . " <" . $apiNamespace . ":type>" . $object . "</" . $apiNamespace . ":type>\n";
          $payload .= str_repeat(" ", $offset) . " <" . $apiNamespace . ":ids></" . $apiNamespace . ":ids>\n";
          $payload .= str_repeat(" ", $offset) . "</" . $apiNamespace . ":delete>\n";
          return $payload;
       }

       // Handle "create"/"update"/"query".
       $fieldNames = ZuoraAPIHelper::getFieldList($wsdl, $object);

       if ($call == "query") {
          $payload = str_repeat(" ", $offset) . "<" . $apiNamespace . ":query>\n";
          $payload .= str_repeat(" ", $offset) . " <" . $apiNamespace . ":queryString>select ";
          $payload .= "Id";
       } else {
          $payload = str_repeat(" ", $offset) . "<" . $apiNamespace . ":" . $call . ">\n";
          $payload .= str_repeat(" ", $offset) . " <" . $apiNamespace . ":zObjects xsi:type=\"" . $objectNamespace . ":" . $object . "\">\n";
          if ($call == "update") {
             // Assume zObject base.
             $payload .= str_repeat(" ", $offset) . "   <" . $objectNamespace . ":" . "Id" . "></" . $objectNamespace . ":" . "Id" . ">\n";
          }
       }
       for ($i = 0; $i < count($fieldNames); $i++) {
          if ($call == "query") {
          	 $payload .= "," . $fieldNames[$i];
          } else {
             $payload .= str_repeat(" ", $offset) . "   <" . $objectNamespace . ":" . $fieldNames[$i] . "></" . $objectNamespace . ":" . $fieldNames[$i] . ">\n";
          }
       }
       if ($call == "query") {
          $payload .= " from " . $object . "</" . $apiNamespace . ":queryString>\n";
          $payload .= str_repeat(" ", $offset) . "</" . $apiNamespace . ":query>\n";
       } else {
          $payload .= str_repeat(" ", $offset) . " </" . $apiNamespace . ":zObjects>\n";
          $payload .= str_repeat(" ", $offset) . "</" . $apiNamespace . ":" . $call . ">\n";
       }

       return $payload;
       / /*/
       $fieldNames = ZuoraAPIHelper::getFieldList($wsdl, $object);
       return ZuoraAPIHelper::printXMLWithNS($call, $object, $fieldNames, array(), $debug, $offset, $apiNamespace, $objectNamespace, true);
    }

    ################################################################################
    public static function printXMLWithNS($call, $object, $fieldNames, $values, $debug, $offset, $apiNamespace, $objectNamespace, $emptyValuesOk) {
       $ID_FIELD = "Id";
       $index = 0;

       if ($values == null || count($values) <= 0) {
          $values[] = array();
       }

       // Handle "delete".
       if ($call == "delete") {
          $payload = str_repeat(" ", $offset) . "<" . $apiNamespace . ":delete>\n";
          $payload .= str_repeat(" ", $offset) . " <" . $apiNamespace . ":type>" . $object . "</" . $apiNamespace . ":type>\n";
       	  foreach ($values as $data) {
	      $payload .= str_repeat(" ", $offset) . " <" . $apiNamespace . ":ids>" . $data[$ID_FIELD]. "</" . $apiNamespace . ":ids>\n";
	  }
          $payload .= str_repeat(" ", $offset) . "</" . $apiNamespace . ":delete>\n";
          return $payload;
       }

       // Handle "query".
       if ($call == "query") {
          $payload = str_repeat(" ", $offset) . "<" . $apiNamespace . ":query>\n";
          $payload .= str_repeat(" ", $offset) . " <" . $apiNamespace . ":queryString>select ";
          $payload .= $ID_FIELD;
	  for ($i = 0; $i < count($fieldNames); $i++) {
              $payload .= "," . $fieldNames[$i];
	  }
          $payload .= " from " . $object . "</" . $apiNamespace . ":queryString>\n";
          $payload .= str_repeat(" ", $offset) . "</" . $apiNamespace . ":query>\n";
	  return $payload;
       }

       // Handle "create"/"update".
       $payload = str_repeat(" ", $offset) . "<" . $apiNamespace . ":" . $call . ">\n";
       if ($call == "update" && !in_array($ID_FIELD, $fieldNames)) {
           // Assume zObject base.
           array_unshift($fieldNames, $ID_FIELD);
       }
       foreach ($values as $data) {
           $payload .= str_repeat(" ", $offset) . " <" . $apiNamespace . ":zObjects xsi:type=\"" . $objectNamespace . ":" . $object . "\">\n";
           for ($i = 0; $i < count($fieldNames); $i++) {
               $field = $fieldNames[$i];
	       if (!$emptyValuesOk && ($data[$field] == null || strlen($data[$field]) <= 0)) {
	           continue;
	       } else {
                   $payload .= str_repeat(" ", $offset) . "   <" . $objectNamespace . ":" . $field . ">" . $data[$field] . "</" . $objectNamespace . ":" . $field . ">\n";
               }
           }
           $payload .= str_repeat(" ", $offset) . " </" . $apiNamespace . ":zObjects>\n";
	   $index++;
	   /*
	   if ($index >= 100) {
	      break;
	   }
	   */
       }
       $payload .= str_repeat(" ", $offset) . "</" . $apiNamespace . ":" . $call . ">\n";

       return $payload;
    }

    ################################################################################
    public static function getElementFromXML($xml) {
        // global $defaultApiNamespace;
        // global $defaultObjectNamespace;
        // global $defaultApiNamespaceURL;
        // global $defaultObjectNamespaceURL;

        $xml_obj = new SimpleXMLElement($xml);
        $xml_obj->registerXPathNamespace(ZuoraAPIHelper::$defaultApiNamespace,ZuoraAPIHelper::$defaultApiNamespaceURL);
        $xml_obj->registerXPathNamespace($defaultObjectNamespace,ZuoraAPIHelper::$defaultObjectNamespaceURL);
	return $xml_obj;
    }

    ################################################################################
    public static function getCSVHeaders($xml_obj) {
        // global $defaultApiNamespace;
	$SPECIAL_FIELDS = array("Id");

        $labels = $xml_obj->xpath("//" . ZuoraAPIHelper::$defaultApiNamespace . ":records/*");

        #Determine headers.
	$found = false;
	$uniqueHeaders = array();
	foreach ($labels as $node) {
	    $value = $node[0]->getName();
	    if (strlen($value) > 0 && !in_array($value, $uniqueHeaders)) {
	        if (in_array($value, $SPECIAL_FIELDS)) {
		    $found = true;
		} else {
	            $uniqueHeaders[] = $value;
		}
	    }
	}
	sort($uniqueHeaders);
	if ($found) {
	    foreach ($SPECIAL_FIELDS as $field) {
	        array_unshift($uniqueHeaders, $field);
            }
	}
	return $uniqueHeaders;
    }

    ################################################################################
    public static function getFieldsFromQuery($query) {
	// global $SEPARATOR;
	$SPECIAL_FIELDS = array("Id");
        $START_TAG = "select ";
        $END_TAG = " from ";

        $query = trim($query);

        $temp = substr($query, strlen($START_TAG), stripos($query, $END_TAG) - strlen($START_TAG));

        $labels = explode($SEPARATOR, $temp);

        #Determine headers.
	$found = false;
	$uniqueHeaders = array();
	foreach ($labels as $value) {
            $value = trim($value);
	    if (strlen($value) > 0 && !in_array($value, $uniqueHeaders)) {
	        if (in_array($value, $SPECIAL_FIELDS)) {
		    $found = true;
		} else {
	            $uniqueHeaders[] = $value;
		}
	    }
	}
	sort($uniqueHeaders);
	if ($found) {
	    foreach ($SPECIAL_FIELDS as $field) {
	        array_unshift($uniqueHeaders, $field);
            }
	}
	return $uniqueHeaders;
    }

    ################################################################################
    public static function getCSVData($xml_obj, $uniqueHeaders, $print, $headers) {
        // global $defaultApiNamespace;
        // global $defaultObjectNamespace;
	// global $SEPARATOR;
	// global $TEXT_QUALIFIER;

        $resultRecords = $xml_obj->xpath("//" . ZuoraAPIHelper::$defaultApiNamespace . ":records");
        return ZuoraAPIHelper::getCSVDataFromRecords($resultRecords, $uniqueHeaders, $print, $headers);
/*
	#Fill in the data.
	$temp = "";
	$output = "";
	$outputArray = array();
	if ($headers) {
	    $temp = implode($SEPARATOR, $uniqueHeaders);
	    $output .= $temp . "\n";
            $outputArray[] = $temp;
	}
	if ($print) {
	    print $output;
	    $output = "";
	}
	for ($i = 0; $i < count($resultRecords); $i++) {
            $temp = "";
	    $index = 0;
	    foreach ($uniqueHeaders as $header) {
	    	if ($index > 0) {
		    $temp .= $SEPARATOR;
		}
	        $attr = $xml_obj->xpath("//" . ZuoraAPIHelper::$defaultApiNamespace . ":records[position()=" . ($i+1) . "]/" . $defaultObjectNamespace . ":" . $header);
		$value = $attr[0];
		if ($value) {
		   // Previously checked if the SEPARATOR character was in the data, but now just escaping by default.
		   $temp .= $TEXT_QUALIFIER . $value . $TEXT_QUALIFIER;
		}
		$index++;
	    }
	    $output .= $temp . "\n";
            $outputArray[] = $temp;
	    if ($print) {
	        print $output;
		$output = "";
	    }
	}
	return $outputArray;
*/
    }

    ################################################################################
    public static function getCSVDataFromRecords($resultRecords, $uniqueHeaders, $print, $headers) {
	// global $SEPARATOR;
	// global $TEXT_QUALIFIER;

	#Fill in the data.
	$output = "";
	$outputArray = array();
	if ($headers) {
	    $output = implode($SEPARATOR, $uniqueHeaders) . "\n";
            $outputArray[] = $output;
	}
	if ($print) {
	    print $output;
	    $output = "";
	}

        $records = ZuoraAPIHelper::getQueryResultsFromRecords($resultRecords,$uniqueHeaders);

        foreach ($records as $record) {
            $output = ZuoraAPIHelper::$TEXT_QUALIFIER . implode($TEXT_QUALIFIER . ZuoraAPIHelper::$SEPARATOR . ZuoraAPIHelper::$TEXT_QUALIFIER, $record) . ZuoraAPIHelper::$TEXT_QUALIFIER . "\n";
            $outputArray[] = $output;
	    if ($print) {
	        print $output;
		$output = "";
	    }
	}
	return $outputArray;
    }

    ################################################################################
    public static function convertXMLtoCSV($xml, $print, $headers) {

    	$xml_obj = ZuoraAPIHelper::getElementFromXML($xml);
        $uniqueHeaders = ZuoraAPIHelper::getCSVHeaders($xml_obj);
	return ZuoraAPIHelper::getCSVData($xml_obj, $uniqueHeaders, $print, $headers);
/* Old code.
        global $defaultApiNamespace;
        global $defaultObjectNamespace;
        global $defaultApiNamespaceURL;
        global $defaultObjectNamespaceURL;
	global $SEPARATOR;
	global $TEXT_QUALIFIER;
	$SPECIAL_FIELDS = array("Id");

        $xml_obj = new SimpleXMLElement($xml);
        $xml_obj->registerXPathNamespace($defaultApiNamespace,$defaultApiNamespaceURL);
        $xml_obj->registerXPathNamespace($defaultObjectNamespace,$defaultObjectNamespaceURL);
        $labels = $xml_obj->xpath("//" . $defaultApiNamespace . ":records/*");
        $resultRecords = $xml_obj->xpath("//" . $defaultApiNamespace . ":records");
        $retList = array();

        #Determine headers.
	$found = false;
	$uniqueHeaders = array();
	foreach ($labels as $node) {
	    $value = $node[0]->getName();
	    if (strlen($value) > 0 && !in_array($value, $uniqueHeaders)) {
	        if (in_array($value, $SPECIAL_FIELDS)) {
		    $found = true;
		} else {
	            $uniqueHeaders[] = $value;
		}
	    }
	}
	sort($uniqueHeaders);
	if ($found) {
	    foreach ($SPECIAL_FIELDS as $field) {
	        array_unshift($uniqueHeaders, $field);
            }
	}

	#Fill in the data.
	$output = implode($SEPARATOR, $uniqueHeaders) . "\n";
	if ($print) {
	    print $output;
	    $output = "";
	}
	for ($i = 0; $i < count($resultRecords); $i++) {
	    $index = 0;
	    foreach ($uniqueHeaders as $header) {
	    	if ($index > 0) {
		    $output .= $SEPARATOR;
		}
	        $attr = $xml_obj->xpath("//" . $defaultApiNamespace . ":records[position()=" . ($i+1) . "]/" . $defaultObjectNamespace . ":" . $header);
		$value = $attr[0];
		if ($value) {
		   // Previously checked if the SEPARATOR character was in the data, but now just escaping by default.
		   $output .= $TEXT_QUALIFIER . $value . $TEXT_QUALIFIER;
		}
		$index++;
	    }
	    $output .= "\n";
	    if ($print) {
	        print $output;
		$output = "";
	    }
	}
	return $output;
*/
    }

    ################################################################################
    public static function getFieldList($wsdl, $object) {
       // global $defaultObjectNamespaceURL;

       $list = array();
       $xml_obj = ZuoraAPIHelper::getXMLElementFromWSDL($wsdl);
       $xml_obj->registerXPathNamespace("xs","http://www.w3.org/2001/XMLSchema");
       $node = $xml_obj->xpath("//default:definitions/default:types/xs:schema[@targetNamespace='" . ZuoraAPIHelper::$defaultObjectNamespaceURL . "']/xs:complexType[@name='" . $object . "']");

       if (count($node) > 0) {
       	   $node = $xml_obj->xpath("//default:definitions/default:types/xs:schema[@targetNamespace='" . ZuoraAPIHelper::$defaultObjectNamespaceURL . "']/xs:complexType[@name='" . $object . "']/xs:complexContent/xs:extension/xs:sequence/xs:element");

       	   for ($i = 0; $i < count($node); $i++) {
       	      $list[] = (string) $node[$i]->attributes()->name;
       	   }
       }
       return $list;
    }

    ################################################################################
    # Helper call that finds the indexes of key in two arrays. Used for deciding if
    # the join will be innner or outer join.
    public static function getTableJoinKeyIndexes($table1Data, $table2Data, $key = "") {
        $index1 = -1;
        $counter1 = 0;
        $index2 = -1;
        foreach($table1Data[0] as $header1) {
            $counter2 = 0;
	    if (strlen($key) > 0) {
		if (strcasecmp($key,$header1) != 0) {
//	    	    print "Key Found: " . $key . " != " . $header1 . "<br/>\n";
                    $counter1++;
		    continue;
                }
//	    	print "Key Found: " . $key . " == " . $header1 . "<br/>\n";
            }
            foreach ($table2Data[0] as $header2) {
//	    	print "Header compare: " . $header1 . " == " . $header2 . "<br/>\n";
            	if (strcasecmp($header1,$header2) == 0) {
            	    $key = $header1;
            	    $index1 = $counter1;
            	    $index2 = $counter2;
//	    	    print "Found Key: " . $key . ", index1: " . $index1 . ", index2: " . $index2 . "<br/>\n";
            	    break;
            	 }
            	 $counter2++;
            }
            if ($index1 >= 0) {
                break;
            }
            $counter1++;
        }
        $found = false;
        if ($index1 >= 0 && $index2 >= 0) {
            $found = true;
        }
        return array($found, $index1, $index2);
    }   

    ################################################################################
    # Helper call that finds the indexes of keys in two arrays. Used for deciding if
    # the join will be innner or outer join.
    public static function getTableJoinKeyIndex($tableData, $key = "") {
	if (strlen($key) <= 0) {
            return -1;
        }
        $counter = 0;
        foreach($tableData[0] as $header) {
	    if (strcasecmp($key,$header) == 0) {
                return $counter;
            }
            $counter++;
        }
        return -1;
    }

    ################################################################################
    # Helper call that joins two arrays, each with headers that contain the link column.
    # Returns a 2-dimensional array with the result.
    public static function joinTables($table1Data, $table2Data, $key = "") {
        $indexes = ZuoraAPIHelper::getTableJoinKeyIndexes($table1Data, $table2Data, $key);
	return ZuoraAPIHelper::joinTablesWithIndexes($table1Data, $table2Data, $indexes[1], $indexes[2]);
/*
        $index1 = -1;
        $counter1 = 0;
        $index2 = -1;
        foreach($table1Data[0] as $header1) {
            $counter2 = 0;
	    if (strlen($key) > 0) {
		if (strcasecmp($key,$header1) != 0) {
//	    	    print "Key Found: " . $key . " != " . $header1 . "<br/>\n";
                    $counter1++;
		    continue;
                }
//	    	print "Key Found: " . $key . " == " . $header1 . "<br/>\n";
            }
            foreach ($table2Data[0] as $header2) {
//	    	print "Header compare: " . $header1 . " == " . $header2 . "<br/>\n";
            	if (strcasecmp($header1,$header2) == 0) {
            	    $key = $header1;
            	    $index1 = $counter1;
            	    $index2 = $counter2;
//	    	    print "Found Key: " . $key . ", index1: " . $index1 . ", index2: " . $index2 . "<br/>\n";
            	    break;
            	 }
            	 $counter2++;
            }
            if ($index1 >= 0) {
                break;
            }
            $counter1++;
        }
        $line = array();
        // Create the header.
        foreach($table1Data[0] as $header1) {
            $line[] = $header1;
        }
        $counter = 0;
        foreach ($table2Data[0] as $header2) {
            $line[] = $header2;
            $counter++;
        }
        $output[] = $line;

        for ($i = 1; $i < count($table1Data); $i++) {
            // Start the line
            $line = array();
            foreach($table1Data[$i] as $data1) {
                $line[] = $data1;
            }

            $key1 = "";
            if ($index1 >= 0) {
                $key1 = $table1Data[$i][$index1];
            }
            for ($j = 1; $j < count($table2Data); $j++) {
                $key2 = "";
                if ($index2 >= 0) {
                    $key2 = $table2Data[$j][$index2];
                }
                $completeLine = $line;
//		print "Data key compare: " . $key1 . " == " . $key2 . "<br/>\n";
                if (strcasecmp($key1,$key2) == 0) {
//		    print "Data key found: " . $key1 . "<br/>\n";
                    $counter = 0;
                    foreach ($table2Data[$j] as $data2) {
                        $completeLine[] = $data2;
                        $counter++;
                    }
                    $output[] = $completeLine;
                }
            }
        }
        return $output;
*/
    }   


    ################################################################################
    # Helper call that joins two arrays, each with headers that contain the link column.
    # Returns a 2-dimensional array with the result.
    public static function joinTablesWithIndexes($table1Data, $table2Data, $index1 = -1, $index2 = -1) {
        $line = array();
        // Create the header.
        foreach($table1Data[0] as $header1) {
            $line[] = $header1;
        }
        $counter = 0;
        foreach ($table2Data[0] as $header2) {
            $line[] = $header2;
            $counter++;
        }
        $output[] = $line;

        for ($i = 1; $i < count($table1Data); $i++) {
            // Start the line
            $line = array();
            foreach($table1Data[$i] as $data1) {
                $line[] = $data1;
            }

            $key1 = "";
            if ($index1 >= 0) {
                $key1 = $table1Data[$i][$index1];
            }
            for ($j = 1; $j < count($table2Data); $j++) {
                $key2 = "";
                if ($index2 >= 0) {
                    $key2 = $table2Data[$j][$index2];
                }
                $completeLine = $line;
//		print "Data key compare: " . $key1 . " == " . $key2 . "<br/>\n";
                if (strcasecmp($key1,$key2) == 0) {
//		    print "Data key found: " . $key1 . "<br/>\n";
                    $counter = 0;
                    foreach ($table2Data[$j] as $data2) {
                        $completeLine[] = $data2;
                        $counter++;
                    }
                    $output[] = $completeLine;
                }
            }
        }
        return $output;
    }   
    ################################################################################
    public static function execInBackground($cmd) {
        if (substr(php_uname(), 0, 7) == "Windows") {
            // Preferred way to run a background process on Windows
            // Taken from: http://www.somacon.com/p395.php
    	    $WshShell = new COM("WScript.Shell") or die("<p>Unable to start shell.</p>\n");
            $command = "cmd /C " . $cmd;
    	    $oExec = $WshShell->Run($command, 0, false);
        } else {
            exec($cmd . " > /dev/null &");
        }
    }
}
 
################################################################################
class MySoapClient extends SoapClient {
    public $myRequest = "";
    public $myResponse = "";
    public $myDebug = 0;
    public $myLocation = "";

    public function __construct($wsdl, $options = array()) {
        parent::__construct($wsdl, $options);
    }

    public function setLocation($url) {
    	$this->myLocation = $url;
	parent::__setLocation($url);	
    }

   public function __doRequest($request, $location, $action, $version, $one_way = null ) {
    	if ($this->myDebug) {
    	    print "\nRequest: " . $request;
	    print "\nmyRequest: " . $this->myRequest;
        }

        if ($this->myRequest != "") {
	    if ($this->myDebug) print "\n DOING SOMETHING SPECIAL.\n";
	    $this->myResponse = parent::__doRequest($this->myRequest, $location, $action, $version, $one_way);
	} else {
	    if ($this->myDebug) print "\n DOING NOTHING SPECIAL.\n";
	    $this->myResponse = parent::__doRequest($request, $location, $action, $version, $one_way);
        }
	return $this->myResponse;
    } 
}

################################################################################
/** Quoted from http://php.net/manual/en/function.substr.php, submitted by egingell at sisna dot com on 19-Oct-2006 10:19
 * string substrpos(string $str, mixed $start [[, mixed $end], boolean $ignore_case])
 *
 * If $start is a string, substrpos will return the string from the position of the first occuring $start to $end
 *
 * If $end is a string, substrpos will return the string from $start to the position of the first occuring $end
 *
 * If the first character in (string) $start or (string) $end is '-', the last occuring string will be used.
 *
 * If $ignore_case is true, substrpos will not care about the case.
 * If $ignore_case is false (or anything that is not (boolean) true, the function will be case sensitive.
 *        Both of the above: only applies if either $start or $end are strings.
 *
 * echo substrpos('This is a string with 0123456789 numbers in it.', 5, '5');
 *        // Prints 'is a string with 01234';
 *
 * echo substrpos('This is a string with 0123456789 numbers in it.', '5', 5);
 *        // Prints '56789'
 *
 * echo substrpos('This is a string with 0123456789 numbers in it and two strings.', -60, '-string')
 *        // Prints 's is a string with 0123456789 numbers in it and two '
 *
 * echo substrpos('This is a string with 0123456789 numbers in it and two strings.', -60, '-STRING', true)
 *        // Prints 's is a string with 0123456789 numbers in it and two '
 *
 * echo substrpos('This is a string with 0123456789 numbers in it and two strings.', -60, '-STRING', false)
 *        // Prints 's is a string with 0123456789 numbers in it and two strings.'
 *
 * Warnings:
 *        Since $start and $end both take either a string or an integer:
 *            If the character or string you are searching $str for is a number, pass it as a quoted string.
 *        If $end is (integer) 0, an empty string will be returned.
 *        Since this function takes negative strings ('-search_string'):
 *            If the string your using in $start or $end is a '-' or begins with a '-' escape it with a '\'.
 *            This only applies to the *first* character of $start or $end.
 */

// Define stripos() if not defined (PHP < 5).
if (!is_callable("stripos")) {
    function stripos($str, $needle, $offset = 0) {
        return strpos(strtolower($str), strtolower($needle), $offset);
    }
}

function substrpos($str, $start, $end = false, $ignore_case = false) {
    // Use variable functions
    if ($ignore_case === true) {
        $strpos = 'stripos'; // stripos() is included above in case it's not defined (PHP < 5).
    } else {
        $strpos = 'strpos';
    }

    // If end is false, set it to the length of $str
    if ($end === false) {
        $end = strlen($str);
    }

    // If $start is a string do what's needed to make it an integer position for substr().
    if (is_string($start)) {
        // If $start begins with '-' start processing until there's no more matches and use the last one found.
        if ($start{0} == '-') {
            // Strip off the '-'
            $start = substr($start, 1);
            $found = false;
            $pos = 0;
            while(($curr_pos = $strpos($str, $start, $pos)) !== false) {
                $found = true;
                $pos = $curr_pos + 1;
            }
            if ($found === false) {
                $pos = false;
            } else {
                $pos -= 1;
            }
        } else {
            // If $start begins with '\-', strip off the '\'.
            if ($start{0} . $start{1} == '\-') {
                $start = substr($start, 1);
            }
            $pos = $strpos($str, $start);
        }
        $start = $pos !== false ? $pos : 0;
    }

    // Chop the string from $start to strlen($str).
    $str = substr($str, $start);

    // If $end is a string, do exactly what was done to $start, above.
    if (is_string($end)) {
        if ($end{0} == '-') {
            $end = substr($end, 1);
            $found = false;
            $pos = 0;
            while(($curr_pos = strpos($str, $end, $pos)) !== false) {
                $found = true;
                $pos = $curr_pos + 1;
            }
            if ($found === false) {
                $pos = false;
            } else {
                $pos -= 1;
            }
        } else {
            if ($end{0} . $end{1} == '\-') {
                $end = substr($end, 1);
            }
            $pos = $strpos($str, $end);
        }
        $end = $pos !== false ? $pos : strlen($str);
    }

    // Since $str has already been chopped at $start, we can pass 0 as the new $start for substr()
    return substr($str, 0, $end);
}

?>

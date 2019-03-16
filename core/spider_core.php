<?php
/*********************************************************************************
 * Aims to handle all robotic operation, Each robots talk to this file.
 * All the function has been Tested, they are working fine till June 5, 2015
 ********************************************************************************/
require 'url_task.php';
require 'OpenGraph.php';

/**
 * @uses: Function to sanitize the urls into acceptable format E.g. 
 * http://www.aakash.ac.in/answers-solutions/re-aipmt/?utm_source=aipmt-2015&utm_medium=cpc&utm_campaign=buynow==> http://www.aakash.ac.in/answers-solutions/re-aipmt/abc.pdf
 * @param string $url
 * @param array $a_data
 * @return array $abc
 * This function is not in direct use, It is using by getFiles_src() function.
 */
function getSanitizedFiles_URL( $url, $a_data ) {
    $sendUrl=preg_replace('/^www\./', '',preg_replace( "#^[^:/.]*[:/]+#i", "",urldecode($url )));
    //$sendUrl="http://".trim(preg_replace( "#^[^:/.www.]*[:/www]+#i", "", urldecode( $url ) ),'.' );
    $data='';
    foreach( $a_data as $entity ) {
      $file_entity=url_to_absolute($url,$entity);
      $entity=preg_replace('/^www\./', '',preg_replace( "#^[^:/.]*[:/]+#i", "",urldecode($file_entity )));
      //$entity="http://".trim(preg_replace( "#^[^:/.www.]*[:/www]+#i", "", urldecode( $file_entity ) ),'.' );
      $files_result=totalUrl($sendUrl,$entity);
      if(!(isset($files_result['unique']))){
          $data['files'][]=$file_entity;}
    } 
    return $data;
}


/**
 * $uses: Search for direct files links of following format like .pdf,.mp3,.PDF,.MP3
 * @param string $url
 * @return array $data
 * Date Written: July 29, 2015 
 */
function getFiles_src( $url ) {
  require_once 'simple_html_dom.php';  
  $url=preg_replace('/^www\./', '',preg_replace( "#^[^:/.]*[:/]+#i", "",urldecode($url )));
  //$url="http://www.".trim(preg_replace( "#^[^:/.www.]*[:/www]+#i", "", urldecode( $url ) ),'.' );
  $html = file_get_html( $url );
 
  if(!$html){
    return "URL_NOT_FOUND";
  } 
  foreach( $html->find('a') as $element ) {    
    $data[] = $element->href;
  }    
  $a_data = preg_grep("/\.(cpp|h|txt|text|svg|rtf|doc|docx|ppt|pptx|xls|xlsx|pdf|swf|deb|tar|gzip|csv|mp3|mp4)$/i", $data );// RegEx to find out links ending with following exstensions
  
  if(sizeof( $a_data )!=0) {
    $data = getSanitizedFiles_URL( $url, $a_data );
  } else {
    $data = "NO_FILE_LINK_FOUND";
  }   
  return $data;
}

function getSite_Name( $url ) {  
  return str_replace('www.', '', parse_url( $url , PHP_URL_HOST));
}

/**
 * @uses Aims to collect page data like Meta Keywords, Meta description, Title, Body etc.
 * @param type $url
 * @return Array
 */
function getAssembled_Page_Component( $url ) {
    $data['meta_info'] = getMeta_Info( $url );
    $data['title'] = getPage_Title( $url );
    $data['body'] = getPage_Body( $url );
    return $data;  
}
/**
 * Function: Function to get the Keywords, description and author of the page
 * @params: string $url
 * @return: Array(Associative) 
 * @uses getMeta_Info($url = 'http://www.cnn.com'); echo "<pre>";  echo "</pre>";
 */ 
function getMeta_Info( $url ) {
    $headers = @get_headers($url);
    if (is_array($headers)){
      if(strpos($headers[0], '404 Not Found'))  //Check for http error here....should add checks for other errors too...
          return false;
      else{ 
          $tags = get_meta_tags(trim($url));
      }
    }
    $tagsArr=array();
    if(isset($tags['keywords'])){
        $tagsArr['keywords']=$tags['keywords'];
    }else{
        $tagsArr['keywords']='';
    }if(isset($tags['description'])){
        $tagsArr['description']=$tags['description'];
    }else{
        $tagsArr['description']='';
    }if(isset($tags['content'])){
        $tagsArr['content']=$tags['content'];
    }else{
        $tagsArr['content']='';
    }
    return $tagsArr;    
}
/**
 * Function: Function to get the title of HTML page
 * @params: string $url
 * @return: string $title
 * @uses getPage_Title($url = 'http://www.cnn.com'); echo "<pre>";  echo "</pre>";
 */   
function getPage_Title( $url ) {
    require_once 'simple_html_dom.php';
    $html = file_get_html( $url ); 
    $title = $html->find('title',0);
    return $title->plaintext;  
}
/**
 * Function: Aim to find out the HTML Page body content
 * @params: string $url
 * @return: string $s_content
 * @uses getPage_Body($url = 'http://www.cnn.com'); echo "<pre>";  echo "</pre>";
 */  
function getPage_Body( $url ) {
    require_once 'simple_html_dom.php';
    $headers = @get_headers($url);
    if (is_array($headers)){
        if(strpos($headers[0], '404 Not Found'))  //Check for http error here....should add checks for other errors too...
            return false;
        else{
            $html = file_get_html( $url ); 
            $body = $html->find('body',0);
            return $body->plaintext;  
        }
    }
}
/*** get og:image ***/
function getImageOg($url) {
    libxml_use_internal_errors(true);
    $c = file_get_contents($url);
    $d = new DomDocument();
    $d->loadHTML($c);
    $xp = new domxpath($d);
    foreach ($xp->query("//meta[@property='og:image']") as $el) {
        $path=url_to_absolute($url,$el->getAttribute("content"));
    }  
     if(isset($path))
        return $path;
    else 
        return 0;
}
/**
 * Function: Function to get the HTML page content as plain Text
 * @params: string $url
 * @return: string $s_content
 * @uses getPage_Content_From_Html($url = 'http://www.cnn.com'); echo "<pre>";  echo "</pre>";
 */   
function getPage_Content_From_Html( $url ) {
    ini_set('max_execution_time', 0);
    require_once 'simple_html_dom.php';  
    $s_content  = file_get_html( $url )->plaintext;   
    return $s_content;
}
/**
 * Function: Aims to recover empty indexes from array by filtering out empty values
 * @param Array $a_str  E.g. array('cnn.com/scv=?df','bbc.com/asdc/233cfgf?d=sdfsd'); 
 * @return Array  E.g. array('http://cnn.com','http://bbc.com'); 
 */
 function getUnique_Domains( $a_str ) {
    $a_uniqueDomains =  array_values( array_filter(parseInput(  implode(" ",$a_str)  )) );   
    foreach ($a_uniqueDomains as &$value) {
        $value = 'http://'.$value;          
    }
    foreach( $a_uniqueDomains as &$domain ) {
        $domain =  'http://'.parse_url( $domain, PHP_URL_HOST );
    }  
    return $a_uniqueDomains;
 }
/**
 * Function: Function to get the set of Anhor tag href present in HTML document
 * @params: string $url
 * @return: array
 * @uses getAnhors_Tag($url = 'http://www.cnn.com'); echo "<pre>";  echo "</pre>";
 */
function getAnhors_Tag($url) {
   // echo $url;
    ini_set('max_execution_time', 0);
    require_once 'simple_html_dom.php';
    $domain_url = getHost_Name($url);
    $arr=explode("//",$domain_url );
    $domainname=$arr[1];
    $domain_url_org=explode(".",$domainname);
    $main_domain=$domain_url_org[0];
    $html = file_get_html($url);
    $a_anhors = array(); 
    if($html){
        foreach($html->find('a') as $e) {
            
            if(substr($e->href,0,1) === "#" ||substr($e->href,0,6) === "mailto:" ||  $e->href === "javascript:void(0);" ||$e->href === "" ||$e->href === " " ||  $e->href === "javascript:void(0)"){     
            }           
            else if( substr( $e->href , 0,1 ) === "/") {
                
                $e->href=url_to_absolute($url,$e->href);
//                $e->href= parse_url($url, PHP_URL_SCHEME )."://".substr( $e->href, 1 ); 
               $d_name_arr=preg_replace('/^www\./', '',preg_replace( "#^[^:/.]*[:/]+#i", "",urldecode($e->href )));
                $d_Domain=explode("/",$d_name_arr);
                $d_name=explode(".",$d_name_arr);
                $name_domain=$d_name[0];
                if($name_domain!=$main_domain){
                    $uniqe_url =explode("/",$d_name_arr);
                    $a_anhors['unique'][]=$uniqe_url[0];
                }else 
                     $a_anhors['site'][] = $e->href;
                //print_r( $a_anhors['site']);die();
            }
             else if( substr( $e->href , 0,2 ) === "//" ) {
                $e->href= parse_url($url, PHP_URL_SCHEME )."://".substr( $e->href, 2 ); 
                $d_name_arr=preg_replace('/^www\./', '',preg_replace( "#^[^:/.]*[:/]+#i", "",urldecode($e->href )));
                //$d_name_arr=trim(preg_replace( "#^[^:/.www.]*[:/www]+#i", "", urldecode( $e->href ) ),'.' );
                $d_Domain=explode("/",$d_name_arr);
                $d_name=explode(".",$d_name_arr);
                $name_domain=$d_name[0];
                if($name_domain!=$main_domain){
                    $uniqe_url =explode("/",$d_name_arr);
                    $a_anhors['unique'][]=$uniqe_url[0];
                }else 
                     $a_anhors['site'][] = $e->href;
            }
            else if( substr( $e->href , 0,3 )==="www"){
                //$d_name_arr=trim(preg_replace( "#^[^:/.www.]*[:/www]+#i", "", urldecode( $e->href ) ),'.' );
                $d_name_arr=preg_replace('/^www\./', '',preg_replace( "#^[^:/.]*[:/]+#i", "",urldecode($e->href )));
                $d_Domain=explode("/",$d_name_arr);
                $d_name=explode(".",$d_name_arr);
                $name_domain=$d_name[0];
                if($name_domain!=$main_domain){
                    $uniqe_url =explode("/",$d_name_arr);
                    $a_anhors['unique'][]=$uniqe_url[0] ;
                }else
                    $a_anhors['site'][] = "http://".$d_name_arr;
            }
            else{
                $e->href = url_to_absolute($url,$e->href);
                $d_name_arr=preg_replace('/^www\./', '',preg_replace( "#^[^:/.]*[:/]+#i", "",urldecode($e->href )));
                //$d_name_arr=trim(preg_replace( "#^[^:/.www.]*[:/www]+#i", "", urldecode( $e->href ) ),'.' );
                $d_Domain=explode("/",$d_name_arr);
                $d_name=explode(".",$d_name_arr);
                $name_domain=$d_name[0];
                //echo urldecode( $e->href );echo '----';echo $d_name_arr;
                //print_r($d_Domain);die();
                if($name_domain!=$main_domain){
                    $uniqe_url =explode("/",$d_name_arr);
                    $a_anhors['unique'][]=$uniqe_url[0] ;
                }else
                    $a_anhors['site'][] = $e->href;
            }
        }
    }
    //print_r($a_anhors);die();
    return  $a_anhors;
}
function getHost_Name ( $url ) {  
    return parse_url($url, PHP_URL_SCHEME )."://".parse_url($url,PHP_URL_HOST); 
}
/**
 * Function: Function to get the set of Image src present in document
 * @params: string $url
 * @return: array
 * @uses getImage_Src($url = 'http://www.cnn.com'); echo "<pre>";  echo "</pre>";
 */
function getImage_Src( $url ) {
    ini_set('max_execution_time', 0);
    require_once 'simple_html_dom.php';                                                                       
    $a_images = array();
    $meta=getMeta_Info($url);
    $headers = @get_headers($url);
    if (is_array($headers)){
        if(strpos($headers[0], '404 Not Found'))
            return false;
        else{
            $html = file_get_html($url);
            if($html){
                $i=0;
                foreach($html->find('img') as $element) {
                    $src=totalUrl($url,$element->src);
                    if(isset($src['unique'])){
                        $a_images['unique'][]= $src['unique'];
                    }
                    else{
                        $headers = @get_headers($src);
                        if (is_array($headers)){
                            if(strpos($headers[0], '404 Not Found'))
                                return false;
                            else{
                                $a_images['images'][$i]['image_link'] = $src; 
                                if($element->alt){
                                    $a_images['images'][$i]['image_text'] = $element->alt ;
                                }
                                if($element->title){
                                    $a_images['images'][$i]['image_title'] = $element->alt ;
                                }
                            }
                        }
                        $i++;
                    }
                }
            }
        }
    }
    return  $a_images;  
}
function addNew_Video_Links( $a_links,$from_link,$xmlFile_Path) {
    $doc = new DOMDocument();
    $doc->load($xmlFile_Path);
    $doc->formatOutput = true;
    $containerTag = $doc->getElementsByTagName( "container" )->item(0);
    if($a_links){
        foreach( $a_links as $link ) {
            $xml = simplexml_load_file($xmlFile_Path) or die("Error: Cannot create object"); 
            $input = trim($link['video_src'], '/');
//            $input=trim(preg_replace( "#^[^:/.www.]*[:/www]+#i", "", urldecode( $input) ),'.' ); 
            //$input=preg_replace('/^www\./', '',preg_replace( "#^[^:/.]*[:/]+#i", "",$input));
           $input= preg_replace('/^www\./', '',preg_replace( "#^[^:/.]*[:/]+#i", "",urldecode($input )));
            $nodes = $xml->xpath("//*[video_link='".urlencode("http://".$input)."']");
            if ($nodes) { 
            }
            else{
                $hurl="http://".$input; $hsurl="https://".$input;
                $heurl="http://".$input."/"; $hseurl="https://".$input."/";
                $hedurl="http://".$input."//";$hsedurl="https://".$input."//";
                $hwurl="http://www.".$input; $hswurl="https://www.".$input;
                $hweurl="http://www.".$input."/"; $hsweurl="https://www.".$input."/";
                $hwedurl="http://www.".$input."//";$hswedurl="https://www.".$input."//";
                if($xml->xpath("//*[video_link='".urlencode($hurl)."']") ||$xml->xpath("//*[video_link='".urlencode($hsurl)."']") ||$xml->xpath("//*[video_link='".urlencode($heurl)."']") 
                    ||$xml->xpath("//*[video_link='".urlencode($hseurl)."']")||$xml->xpath("//*[video_link='".urlencode($hedurl)."']") ||$xml->xpath("//*[video_link='".urlencode($hsedurl)."']")
                    ||$xml->xpath("//*[video_link='".urlencode($hwurl)."']")||$xml->xpath("//*[video_link='".urlencode($hswurl)."']")
                    ||$xml->xpath("//*[video_link='".urlencode($hweurl)."']")||$xml->xpath("//*[video_link='".urlencode($hsweurl)."']")
                    ||$xml->xpath("//*[video_link='".urlencode($hwedurl)."']")||$xml->xpath("//*[video_link='".urlencode($hswedurl)."']")
                    ){
                  }
                else{  
                    $linkObj = $doc->createElement( "videos" );
                    $linkObj->setAttribute('from_link', urlencode($from_link));
                    $linkObj->appendChild($doc->createElement('video_link',urlencode("http://".$input)));
                    if($link['video_thumbnail']){
                    $linkObj->appendChild($doc->createElement('video_thumbnail', urlencode($link['video_thumbnail'])));
                    }
                    if($link['video_type']){
                        $linkObj->appendChild($doc->createElement('video_type', $link['video_type']));
                    }
                    $containerTag->appendChild( $linkObj );
                } 
            }
        $doc->saveXML();  
        $doc->save($xmlFile_Path);
        }
    }
}
function getVideo_Src( $url ) {
    ini_set('max_execution_time', 0);
    require_once 'simple_html_dom.php';                                                                       
    $headers = @get_headers($url);
    if (is_array($headers)){
        if(strpos($headers[0], '404 Not Found'))
            return false;
        else{
            $html = file_get_html($url);
            if($html){
                $videoInt=0;
                if($html->find('<video')){//Get Video tag DATA Starts
                    foreach($html->find('<video') as $element) {
                        if(isset($element->src)){
                            $result=totalUrl($url,$element->src);
                            if(isset($result['unique'])){
                                $videoAr['unique'][]=$result['unique'];
                            }
                            else
                            $videoAr['video'][$videoInt]['video_src']=$result;
                            if($element->poster){
                            $poster_result=totalUrl($url,$element->poster);
                            if(isset($poster_result['unique'])){
                                unset($videoAr['video'][$videoInt]['video_src']);
                                $videoAr['unique'][]=$poster_result['unique'];
                            }
                            else
                            $videoAr['video'][$videoInt]['video_thumbnail']=$poster_result;
                            }
                            if($element->type)
                                $videoAr['video'][$videoInt]['video_type']=$element->type; 
                            $videoInt++;
                        }else{
                            foreach($element->find('source') as $sr){
                                $result=totalUrl($url,$sr->src);
                                if(isset($result['unique'])){
                                    $videoAr['unique'][]=$result['unique'];
                                }
                                else
                                    $videoAr['video'][$videoInt]['video_src']=$result;
                                if($sr->poster){
                                    $poster_result=totalUrl($url,$sr->poster);
                                    if(isset($poster_result['unique'])){
                                        unset($videoAr['video'][$videoInt]['video_src']);
                                        $videoAr['unique'][]=$poster_result['unique'];
                                    }
                                    else
                                        $videoAr['video'][$videoInt]['video_thumbnail']=$poster_result;
                                }
                                if($sr->type)
                                    $videoAr['video'][$videoInt]['video_type']=$sr->type; 
                                $videoInt++;
                           }
                        }
                    }
                }//Get Video tag DATA Ends
                if($html->find('object')){//Get object tag DATA Starts 
                    foreach($html->find('object') as $element) {
                        if($element->type!='text/html'){//for type application/x-shockwave-flash
                            $result=totalUrl($url,$element->data);
                            if(isset($result['unique'])){
                                $videoAr['unique'][]=$result['unique'];
                            }
                            else
                                $videoAr['video'][$videoInt]['video_src']=$result;
                            if($element->type)
                                $videoAr['video'][$videoInt]['video_type']=$element->type;                            
                        }
                        $videoInt++;
                    }
                }//Get object tag DATA Ends
                $d = new DOMDocument();//Get Itemprop DATA Starts
                libxml_use_internal_errors(true);
                $d->loadHTML($html);
                $xpath = new DOMXPath($d);
                libxml_use_internal_errors(false);
                $itemprobs = $xpath->query('//*[@itemprop]');
                if($itemprobs->length=='0'){//og data get starts
                    $graph = OpenGraph::fetch($url);
                    if($graph){
                        $videoAr['video'][$videoInt]['video_src']=trim($url);
                        foreach ($graph as $key => $value) {
                            if($key=='thumbnailUrl' || $key=='thumbnail'){
                                $poster_result=totalUrl($url,$value);
                                if(isset($poster_result['unique'])){
                                    unset($videoAr['video'][$videoInt]['video_src']);
                                    $videoAr['unique'][]=$poster_result['unique'];
                                }
                                else
                                    $videoAr['video'][$videoInt]['video_thumbnail']=$poster_result;
                            } 
                        }    
                        $videoInt++;
                   }
                   // print_R($videoAr);die();
                }//Get og DATA Ends
                else{//Get itemprop DATA Starts
                    foreach ($itemprobs as $v=>$itemv) {
                        if($itemv->getAttribute('itemprop')=='thumbnailUrl'){
                            if($itemv->getAttribute('content')){
                                $dataText=$itemv->getAttribute('content');
                            }else if($itemv->getAttribute('href')){
                                $dataText=$itemv->getAttribute('href');
                            }else if($itemv->getAttribute('src')){
                                $dataText=$itemv->getAttribute('src');
                            }else{
                                $dataText=$itemv->nodeValue;
                            }
                            $videoAr['video'][$videoInt]['video_src']=$url;
                            $poster_result=totalUrl($url,$dataText);
                                if(isset($poster_result['unique'])){
                                    unset($videoAr['video'][$videoInt]['video_src']);
                                    $videoAr['unique'][]=$poster_result['unique'];
                                }
                                else
                                    $videoAr['video'][$videoInt]['video_thumbnail']=$poster_result;
                            $videoInt++;
                        }                  
                    } 
                }//Get itemprop DATA Ends
            }
        }
    }
    return $videoAr;
}
function totalUrl($url,$href){
    $arr=explode("//",$url );
    $domainname=$arr[1];
    $domain_url_org=explode(".",$domainname);
    $main_domain=$domain_url_org[0];
    if($href === "#" || $href=== "javascript:void(0);" ||$href === "" ||$href === " " ||  $href === "javascript:void(0)"){     
        return 0;
    }
    else{
        $href = url_to_absolute($url,$href );
        $arr1=explode("//",$href );
        $domainname1=$arr1[1];
        $domain_url_org1=explode(".",$domainname1);
        $d_name_arr=$domain_url_org1[0];	
       	$d_Domain=explode("/",$d_name_arr);
        $d_name=explode(".",$d_name_arr);
        $name_domain=$d_name[0];
        if($name_domain!=$main_domain){
            $uniqe_url =explode("/",$domainname1);
            $a_anhors['unique'][]=$uniqe_url[0] ;
            return  $a_anhors;
        }else
            return $href;
    }
}
function addNew_Image_Links( $a_links,$from_link,$xmlFile_Path) {
    $doc = new DOMDocument();
    $doc->load($xmlFile_Path);
    $doc->formatOutput = true;
    $containerTag = $doc->getElementsByTagName( "container" )->item(0);
    if($a_links){
        foreach( $a_links as $link ) {
            $xml = simplexml_load_file($xmlFile_Path) or die("Error: Cannot create object"); 
            $input = trim($link['image_link'], '/');
//            $input= trim(preg_replace( "#^[^:/.www.]*[:/www]+#i", "", urldecode( $input) ),'.' ); 
            //$input=preg_replace('/^www\./', '',preg_replace( "#^[^:/.]*[:/]+#i", "",$input));
            $input= preg_replace('/^www\./', '',preg_replace( "#^[^:/.]*[:/]+#i", "",urldecode($input )));
            $nodes = $xml->xpath("//*[image_link='".urlencode("http://".$input)."']");
            if ($nodes) { 
            }
            else{
                $hurl="http://".$input; $hsurl="https://".$input;
                $heurl="http://".$input."/"; $hseurl="https://".$input."/";
                $hedurl="http://".$input."//";$hsedurl="https://".$input."//";
                $hwurl="http://www.".$input; $hswurl="https://www.".$input;
                $hweurl="http://www.".$input."/"; $hsweurl="https://www.".$input."/";
                $hwedurl="http://www.".$input."//";$hswedurl="https://www.".$input."//";
                if($xml->xpath("//*[image_link='".urlencode($hurl)."']") ||$xml->xpath("//*[image_link='".urlencode($hsurl)."']") ||$xml->xpath("//*[image_link='".urlencode($heurl)."']") 
                    ||$xml->xpath("//*[image_link='".urlencode($hseurl)."']")||$xml->xpath("//*[image_link='".urlencode($hedurl)."']") ||$xml->xpath("//*[image_link='".urlencode($hsedurl)."']")
                    ||$xml->xpath("//*[image_link='".urlencode($hwurl)."']")||$xml->xpath("//*[image_link='".urlencode($hswurl)."']")
                    ||$xml->xpath("//*[image_link='".urlencode($hweurl)."']")||$xml->xpath("//*[image_link='".urlencode($hsweurl)."']")
                    ||$xml->xpath("//*[image_link='".urlencode($hwedurl)."']")||$xml->xpath("//*[image_link='".urlencode($hswedurl)."']")
                    ){
                }
                else{  
                    $linkObj = $doc->createElement( "images" );
                    $linkObj->setAttribute('from_link', urlencode($from_link));
                    $linkObj->appendChild($doc->createElement('image_link',urlencode("http://".$input)));
                    if($link['image_text']){
                        $linkObj->appendChild($doc->createElement('image_text', $link['image_text']));
                    }
                    if($link['image_title']){
                        $linkObj->appendChild($doc->createElement('image_title', $link['image_title']));
                    }
                    $containerTag->appendChild( $linkObj );
                } 
            }
            $doc->saveXML();  
            $doc->save($xmlFile_Path); 
        }
    }	
}
/**
 * @Function: Function to add New Level containing the sets of the links.
 * @param type $a_links
 * @param type $level_num
 * @return none 
 * USAGE: $links = array('http://www.google.com1', 'http://www.youtube.com');
 */
function addNew_Level_Links( $a_links, $level_num, $from_link,$xmlFile_Path ) {
    $doc = new DOMDocument();
    $doc->load($xmlFile_Path );
    $doc->formatOutput = true;
    $levelText="level{$level_num}";
    if($doc->getElementsByTagName($levelText)->length){
         $level = $doc->getElementsByTagName($levelText)->item(0);
    }
    else{
        $containerTag = $doc->getElementsByTagName( "container" )->item(0);
        $doc->appendChild( $containerTag );  
        $level = $doc->createElement( $levelText );
        $level->setAttribute('from_link', urlencode($from_link));
    }
    foreach( $a_links as $link ) {
       
        $xml = simplexml_load_file($xmlFile_Path) or die("Error: Cannot create object");   
        $input = trim($link, '/');
        //$input=trim(preg_replace( "#^[^:/.www.]*[:/www]+#i", "", urldecode( $input) ),'.' );
        $input= preg_replace('/^www\./', '',preg_replace( "#^[^:/.]*[:/]+#i", "",urldecode($input )));
        $nodes = $xml->xpath("//*[link='".urlencode("http://".$input)."']");
        if ($nodes) { 
        }
        else{
            $hurl="http://".$input; $hsurl="https://".$input;
            $heurl="http://".$input."/"; $hseurl="https://".$input."/";
            $hedurl="http://".$input."//";$hsedurl="https://".$input."//";
            $hwurl="http://www.".$input; $hswurl="https://www.".$input;
            $hweurl="http://www.".$input."/"; $hsweurl="https://www.".$input."/";
            $hwedurl="http://www.".$input."//";$hswedurl="https://www.".$input."//";
            if($xml->xpath("//*[link='".urlencode($hurl)."']") ||$xml->xpath("//*[link='".urlencode($hsurl)."']") ||$xml->xpath("//*[link='".urlencode($heurl)."']") 
              ||$xml->xpath("//*[link='".urlencode($hseurl)."']")||$xml->xpath("//*[link='".urlencode($hedurl)."']") ||$xml->xpath("//*[link='".urlencode($hsedurl)."']")
              ||$xml->xpath("//*[link='".urlencode($hwurl)."']")||$xml->xpath("//*[link='".urlencode($hswurl)."']")
              ||$xml->xpath("//*[link='".urlencode($hweurl)."']")||$xml->xpath("//*[link='".urlencode($hsweurl)."']")
              ||$xml->xpath("//*[link='".urlencode($hwedurl)."']")||$xml->xpath("//*[link='".urlencode($hswedurl)."']")
              ){
            }
            else{ 
                if($doc->getElementsByTagName( $levelText)->length){
                    $linkObj = $doc->createElement( "link",urlencode("http://".$input));
                    $level->appendChild( $linkObj );
//                    $level->appendChild( $linkObj );
                }else{
                    echo $input;
                    $linkObj = $doc->createElement( "link" );
                    $linkObj->appendChild( $doc->createTextNode(urlencode("http://".$input) )    );
                    $level->appendChild( $linkObj );
                    $containerTag->appendChild( $level );
                }
            }
        }
        $doc->saveXML();
        $doc->save($xmlFile_Path );
    }
}
 /****
  * Function to extract the words from document and filtered all special character from each word.
  * @uses: echo "<pre>"; print_r(  getFiltered_Word_From_Document( 'http://bbc.com') ); echo "</pre>";
  * @returns: Array
  */
 function getFiltered_Word_From_Document( $s_url ) {
    $s_content = getPage_Body( $s_url );
    $a_words = array_map('strtolower', array_filter( explode(" ", $s_content ) ) );
    foreach ($a_words as &$s_value) {        
        $s_value = preg_replace("/[^A-Za-z0-9\-\(\) ]/", "", $s_value);     
    }   
    return  array_unique( array_values($a_words) );
}
function parseInput( $string ) {
    $regex = '/https?\:\/\/[^\" ]+/i';
    preg_match_all($regex, $string, $matches);
    $urls = $matches[0];
    $data='';
    for($i=0; $i<sizeof($urls); $i++ )
    {
        preg_match("/^(http:\/\/)?([^\/]+)/i", $urls[$i], $matches);
        $host = $matches[2]; 
        preg_match("/[^\.\/]+\.[^\.\/]+$/", $host, $matches);
        $data .= $matches[0]." ";
    }        
    return uniqueUrls( $data );
}
//Function returns unique element from passed array.
function uniqueUrls( $data ){
    $dataarray = explode(" ",$data);
    return array_values(array_unique($dataarray));        
}
/**
 * Function: Aim to check if url attribute has already the given value
 * @param type $level_index
 * @param type $url
 * @return Boolean (1 Means it is exist, 0 means it does not exist)
 * @uses:  checkIf_url_exist_in_xml( $level_index='level1', $url='http://cnn.com1' );
 */
function checkIf_url_exist_in_xml( $level_index, $url, $xml_file ) {  
    
    $xml = simplexml_load_file( $xml_file ) or die("Error: Cannot create object");
    $string = '/container/'.$level_index.'[@from_link="%s"]';
    $nodes = $xml->xpath(sprintf($string, $url));
    if (!empty($nodes)) {
        return 1;
    } else {
        return 0;
    }
}
/**
 * Function: Function to check whether th html lang is en or not
 * @params: string $url
 * @return: boolean
 * @uses check_LangOfPage($url = 'http://www.cnn.com'); echo "<pre>";  echo "</pre>";
 */
function check_LangOfPage( $url ) {
    ini_set('max_execution_time', 0);
    require_once 'simple_html_dom.php';                                                                       
    
    $headers = @get_headers($url);
    if (is_array($headers)){
        if(strpos($headers[0], '404 Not Found'))
            return false;
        else{
            libxml_use_internal_errors(true);
            $c = file_get_contents($url);     
            if($c){
                $d  =   new DomDocument();
                $d->loadHTML($c);
                $xp =   new domxpath($d);
                foreach($xp->query('//html') as $val){
                    if($val->getAttribute('content')!=null && strtolower($val->getAttribute('content'))!='en' && strtolower($val->getAttribute('content'))!='english' && strtolower($val->getAttribute('content'))!='en-us'  && strtolower($val->getAttribute('content'))!='en-cockney'){
                       return false;
                    }
                    else if($val->getAttribute('lang')!=null && strtolower($val->getAttribute('lang'))!='en' && strtolower($val->getAttribute('lang'))!='english' && strtolower($val->getAttribute('lang'))!='en-us'  && strtolower($val->getAttribute('lang'))!='en-cockney'){
                       return false;
                    }
                    else{
                       return true;
                    }
                }
            }else{
                return false;
            }
        }
    } 
}
?>

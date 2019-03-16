<?php
/*********************************************************************************
 * Aims to handle all robotic operation, Each robots talk to this file.
 * All the function has been Tested, they are working fine till July 28, 2015
 ********************************************************************************/
require_once WEB_ROOT_PATH.'/fila/core/spider_core.php';
require_once WEB_ROOT_PATH.'/fila/core/db_crud.php'; 
/*** Return SpiderId ***/
function getRobotConfigFile_ID($currentFile_Name) {
  $str = explode('.', $currentFile_Name);
  $str = explode( '_', $str[0] );  
  return $str[1];  
}
/*** Return XML FILENAME ***/
function getXMLFileName( $robot_configFileName , $spiderID) { 
  $robot_data = file_get_contents (WEB_ROOT_PATH.'/fila/robots/'.$spiderID.'/'.$robot_configFileName);
  $robot_data = json_decode($robot_data, TRUE);
  return $robot_data['files']['web_xml'];  
}
/*** Return Host Name ***/
function getXmlWebHostName($robot_configFileName,$spiderID){
  $robot_data = file_get_contents (WEB_ROOT_PATH.'/fila/robots/'.$spiderID.'/'.$robot_configFileName);
  $robot_data = json_decode($robot_data, TRUE);
  return $robot_data['host']; 
}
/*** Return Log FILENAME ***/
function getLogFileName( $robot_configFileName ,$spiderID) {
  $robot_data = file_get_contents (WEB_ROOT_PATH.'/fila/robots/'.$spiderID.'/'.$robot_configFileName);
  $robot_data = json_decode($robot_data, TRUE);
  return $robot_data['files']['log_xml'];  
}
/*** Return Image FILENAME ***/
function getImageFileName( $robot_configFileName ,$spiderID) {
  $robot_data = file_get_contents (WEB_ROOT_PATH.'/fila/robots/'.$spiderID.'/'.$robot_configFileName);
  $robot_data = json_decode($robot_data, TRUE);
  return $robot_data['files']['img_xml'];  
}
/*** Return Video FILENAME ***/
function getVideoFileName( $robot_configFileName,$spiderID ) {
  $robot_data = file_get_contents (WEB_ROOT_PATH.'/fila/robots/'.$spiderID.'/'.$robot_configFileName);
  $robot_data = json_decode($robot_data, TRUE);
  return $robot_data['files']['vid_xml'];  
}
/*** Insert URL IN XML FILENAME ***/
function insertUrl($robotId,$domainId,$news,$xmlpath){
   // print_r($domainId);die();
   $xml = simplexml_load_file($xmlpath);
   foreach($xml as $level)
    {
        foreach($level->link as $link)
        {
            $link=urldecode($link);
            //echo $link;
//            echo ' ----- ';continue;
            $headers = @get_headers($link);//print_R($headers);continue;
            if (is_array($headers)){
                
                if(strpos($headers[0], '404 Not Found'))  //Check for http error here....should add checks for other errors too...
                  return false;
                else{
                    $ogImagePath=getImageOg($link);
                    
                    $link_body_dat  =   trim(getPage_Body($link));
                    $link_body_dat = str_replace(array('\'','\"','\&','\;'),"",$link_body_dat);
                    $page_BodySt  = strtolower($link_body_dat);  
                    $formedKeywords =   strtolower(preg_replace("/[^A-Za-z0-9 ,]/u",'', strip_tags($page_BodySt)));// Removes special chars.
                    if(trim($formedKeywords)!=''){//if keywords exists insert/update in keywords table
                        $body_keys  =   preg_replace("/[\ \n\,]+/u",',', $formedKeywords);//replace newline,space,comma with comma in string
                        $body_keys= implode(',',array_unique(explode(',', $body_keys)));
                    }
                    $separator = ",";
                    $ins_tabl_name="fi_documents"; 
                    $link_docDat    =   select('document_id,keywords_str,is_news,page_content',$ins_tabl_name,"url='$link'");
                    if(!empty($link_docDat)){//if docu already present
                        $doc_ID =   $link_docDat[0]['document_id'];
                        if($link_docDat[0]['is_news']=='Yes')
                            $feild_nameV="news_id_fk";
                        else
                            $feild_nameV="document_id_fk";
                        if(trim($formedKeywords)!=''){//if keywords exists insert/update in keywords table
                            if(strcmp($link_body_dat,$link_docDat[0]['page_content'])==0){//two page contents are equal do nothing                        
                            }else{
                                $remove='';
                                $append='';   
                                $changePAgeCNtR =   0;
                                if($link_docDat[0]['keywords_str']!=''){//if page content is present in db column
                                    $line = strtok($body_keys, $separator);//seperate word from string seperated by comma
                                    while ($line !== false) {//to append/insert
                                        if (preg_match("/\b".$line."\b/i", $link_docDat[0]['keywords_str'])) {//if keyword is in db document-keywords_str feild do nothing                                
                                        } else {//insert if not present/update if keyword exists,with doc id in keywords table
                                            $custom_Q="select rec_aid,".$feild_nameV." from fi_keywords where keyword='".$line."'";// and ".$feild_nameV." REGEXP '^".$doc_ID."$'
                                            $custom_QRes    =   custom($custom_Q);
                                            if($custom_QRes){//if keyword presents in db, check if doc id presents 
                                                if(preg_match("/\b".$doc_ID."\b/i", $custom_QRes[0][$feild_nameV])){// already doc id presents so do nothing
                                                }else{//doc id/news id not presents so append to old data in keywords tbl
                                                    $recid  =   $custom_QRes[0]['rec_aid'];
                                                    $updk_data=array();
                                                    if(trim($custom_QRes[0][$feild_nameV])!=''){//if already some ids present in feild append current doc id
                                                        $updk_data[$feild_nameV]=$custom_QRes[0][$feild_nameV].','.$doc_ID;
                                                    }else{//else insert current doc id to the feild
                                                        $updk_data[$feild_nameV]=$doc_ID;
                                                    }
                                                    update($updk_data,"fi_keywords","rec_aid='$recid'");//update keywords table
                                                }
                                            }
                                            else{//insert keyword and doc id/news id in keywords tbl
                                                $ins_KeydataT["keyword"]=$line;
                                                $ins_KeydataT[$feild_nameV]=$doc_ID;
                                                insert( $ins_KeydataT ,"fi_keywords");//insert keyword and doc id in keywords table
                                            }
                                            $changePAgeCNtR=1;
                                          $append=$line.$separator.$append;//appent to string to insert in keywords tbl
                                        }
                                        $line = strtok( $separator );//seperate word from string seperated by comma
                                    }   
                                }
                                else{//if page content is not present in db column
                                    $line1 = strtok($body_keys, $separator);//seperate word from string seperated by comma
                                    while ($line1 !== false) {//to append/insert
                                        $custom_QRes='';
                                        $custom_Q="select rec_aid,".$feild_nameV." from fi_keywords where keyword='".$line1."'";// and ".$feild_nameV." REGEXP '^".$doc_ID."$'
                                        $custom_QRes    =   custom($custom_Q);
                                        if($custom_QRes){//if keyword presents in db, check if doc id presents 
                                            if(preg_match("/\b".$doc_ID."\b/i", $custom_QRes[0][$feild_nameV])){// already doc id presents so do nothing
                                            }else{//doc id/news id not presents so append to old data in keywords tbl
                                                $recid  =   $custom_QRes[0]['rec_aid'];
                                                $updk_data=array();
                                                if(trim($custom_QRes[0][$feild_nameV])!=''){//if already some ids present in feild append current doc id
                                                    $updk_data[$feild_nameV]=$custom_QRes[0][$feild_nameV].','.$doc_ID;
                                                }else{//else insert current doc id to the feild
                                                    $updk_data[$feild_nameV]=$doc_ID;
                                                }
                                                update($updk_data,"fi_keywords","rec_aid='$recid'");//update keywords table
                                            }
                                        }else{//insert keyword and doc id/news id in keywords tbl
                                            $ins_KeydataT["keyword"]=$line1;
                                            $ins_KeydataT[$feild_nameV]=$doc_ID;
                                            insert( $ins_KeydataT,"fi_keywords");//insert keyword and doc id in keywords table
                                        }
                                        $changePAgeCNtR=1;
                                        $append =   $line1.$separator.$append;//appent to string to insert in keywords tbl
                                        $line1  =   strtok( $separator );//seperate word from string seperated by comma
                                    }
                                }
                                $ins_data=array();
                                $ins_data=array("page_content"=>$link_body_dat,"image_path"=>$ogImagePath);
                                if($changePAgeCNtR==1){//update Page Content in doc table.
                                    $ins_data["keywords_str"]=$body_keys;    
                                }
                                update($ins_data,"fi_documents","document_id='$doc_ID'");//update doc table
                            }
                        }
                        else{//no page content found
                        }
                    }
                    else{
                        
                        $keywords=getMeta_Info($link);
                        $title=getPage_Title($link);
                        $ins_data=array();
                        $ins_data=array("url"=>$link,
                            "Keywords"=>$keywords['keywords'],
                            "title_text"=>"$title",
                            "meta_description"=>$keywords['description'],
                            "keywords_str"=>$body_keys,
                            "page_content"=>$link_body_dat,
                            "robot_id"=>$robotId,
                            "domain_id_fk"=>$domainId,
                            "is_news"=>$news,
                            "image_path"=>$ogImagePath);
                        
                        $obj=insert( $ins_data ,$ins_tabl_name);//insert doc details in doc table
                        if($obj==1){//if inserted
                            //print_R($obj);die();
                            $document_idAr = select('document_id',$ins_tabl_name,"url='$link'");
                            $Docid=$document_idAr[0]['document_id'];
                            if(trim($formedKeywords)!=''){//if keywords exists insert/update in keywords table
                                $kword = strtok($body_keys, $separator);//seperate word from string seperated by comma
                                while ($kword !== false) {//to append/insert
                                    $fi_keysDataArr=array();
                                    $updKeywrds_data=array();
                                    $fi_keysDataArr    =   select('rec_aid,document_id_fk,news_id_fk',"fi_keywords","keyword='$kword'");
                                    if($fi_keysDataArr){//if keyword already exists append this keyword
                                        if($ins_data['is_news']=='Yes'){//if url is related to news
                                            if(strlen($fi_keysDataArr[0]['news_id_fk'])>0){//if data in news_id_fk column append
                                                if (preg_match('/\b'.$Docid.'\b/', $fi_keysDataArr[0]['news_id_fk'])) {//if doc id already placed in column
                                                }else
                                                    $updKeywrds_data['news_id_fk']  =   $fi_keysDataArr[0]['news_id_fk'].','.$Docid;//append doc id
                                            }else{//if not insert
                                                $updKeywrds_data['news_id_fk']  =   $Docid;
                                            }
                                        }else{//if not
                                            if(strlen($fi_keysDataArr[0]['document_id_fk'])>0){//if data in document_id_fk column append
                                                if (preg_match('/\b'.$Docid.'\b/', $fi_keysDataArr[0]['document_id_fk'])) {//if doc id already placed in column
                                                }else
                                                    $updKeywrds_data['document_id_fk']  =   $fi_keysDataArr[0]['document_id_fk'].','.$Docid;//append doc id
                                            }else{//if not insert
                                                $updKeywrds_data['document_id_fk']  =   $Docid;
                                            }
                                        }
                                        $p_Keyid    =   $fi_keysDataArr[0]['rec_aid'];
                                        if($updKeywrds_data)
                                            update($updKeywrds_data,"fi_keywords","rec_aid=$p_Keyid");//update keywords table
                                    }else{//if not exists this keyword
                                        $insKeywrds_data=array();
                                        $insKeywrds_data["keyword"]=$kword;
                                        if($ins_data['is_news']=='Yes')//if url is related to news
                                            $insKeywrds_data["news_id_fk"]=$Docid;
                                        else//if not
                                            $insKeywrds_data["document_id_fk"]=$Docid;
                                        $obj=insert( $insKeywrds_data ,"fi_keywords");//insert keyword in keywords table
                                    }
                                    $kword  =   strtok( $separator );//seperate word from string seperated by comma
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
/*** Insert New Unique Domains ***/
function insertNewUniqueDomains($linkAll,$website_name){
    foreach($linkAll as $link){
        if(check_LangOfPage("http://".$link)){//if page lang is en ok.
            $table_name="fi_unique_domains"; 
            $un_table_name='fi_in_out_links';
            if(!empty(select('domain',$table_name,"domain='$link'"))){//if current link already present in unique domains table
                if(!empty(select('rec_aid',$un_table_name,"f_domain='$website_name' and t_domain='$link'"))){//if link present in fi_in_out_links table
                    $a_data=array("domain_rank"=>'domain_rank'+1);
                    update($a_data,$table_name,"domain=$link"); //update domain_rank by one in unique domains table
                }else{
                    $ins_data=array('f_domain'=>$website_name,'t_domain'=>$link);
                    if(insert($ins_data,$un_table_name))  {//insert from,to domains in in_out links, later
                        $a_data=array("domain_rank"=>domain_rank+1);
                        update($a_data,$table_name,"domain=$link");//update domain_rank by one in unique domains table
                    }
                }
            }
            else{//if not
                $ins_data=array('f_domain'=>$website_name,'t_domain'=>$link);
                if(insert($ins_data,$un_table_name))  {//insert from,to domains in in_out links, later 
                    $a_data=array("domain"=>$link,"domain_rank"=>1);
                    $query_result=insert($a_data,$table_name);//insert domain,domain_rank in unique domains table
                }
            }
        }
    }
}
/*** Insert Files Data ***/
function insertFilesInfo($filesAll,$url,$website_name){
    foreach($filesAll as $link){
        $table_name="fi_files"; 
        if(!empty(select('rec_aid',$table_name,"link='$link'"))){//if current link already present in fi_files table do nothing
        }
        else{//if not insert in fi_files table
            $file_types =   substr($link, strrpos($link, '.') + 1);//for file extension
            $ins_data=array('site_name'=>$website_name,'document_url'=>$url,'link'=>$link,'file_types'=>$file_types);
            insert($ins_data,$table_name);//insert data in fi_files table                
        }        
    }
}
/**** Insert Video Information ***/
function insertVideoInfo($video_xml_fileName,$domainId){
    $xml_video_host = simplexml_load_file($video_xml_fileName);
    $video_xml_sitename= urldecode($xml_video_host->website);
    foreach(  $xml_video_host->videos as $url_video) {//for every video link
        
        $table_name="fi_videos";
        $xml_video_link= urldecode($url_video->video_link);
        $link_From  =   urldecode($url_video['from_link']);
        if($xml_video_link!=''){
            
            $fi_docDataArr=array();
            $fi_docDataArr    =   select('document_id,is_news',"fi_documents","url='$link_From'");//get document id of from link
            if($fi_docDataArr){//if url already exists in doc table
                $docId  =   $fi_docDataArr[0]['document_id'];
                if($fi_docDataArr[0]['is_news']=='Yes')
                    $feild_nameV="news_id_fk";
                else
                    $feild_nameV="document_id_fk"; 
                $updImagIds_data=array();$fi_keywdsDataArr=array();
                $video_tb_D =select('video_aid',$table_name,"url='$xml_video_link'");
                if(!empty($video_tb_D)){//if video is already in videos table
                    $img_ID =   $video_tb_D[0]['video_aid']; 
                    goto Videoupdate_keyword;//do updating
                }
                else{//if not in videos table
                    $keywords=getMeta_Info($link_From);
                    $title=getPage_Title($link_From);
            
                    $a_data=array();
                    $a_data=array("url"=>$xml_video_link,
                                 "domain_id_fk"=>$domainId,
                                "document_id_fk"=>$docId,
                                "title"=>$title,
                                "description"=>$keywords['description'],
                              "site_name"=>$video_xml_sitename,
                              "thumbnail_url"=> $url_video->video_thumbnail
                              );
                    $obj=insert($a_data,$table_name);//insert in videos table and
                    if($obj==1){
                        $rec_aidAr = select('video_aid',$table_name,"url='$xml_video_link'");
                        $img_ID =   $rec_aidAr[0]['video_aid']; 
                        goto Videoupdate_keyword;// do updating
                    }
                }
                Videoupdate_keyword:
                    $fi_keywdsDataArr    =   select('rec_aid,video_id_fk',"fi_keywords","$feild_nameV REGEXP '[[:<:]]".$docId."[[:>:]]'");
                    if($fi_keywdsDataArr){
                    foreach($fi_keywdsDataArr as $kwaV){
                        $updImagIds_data='';
                        if (preg_match('/\b'.$img_ID.'\b/', $kwaV['video_id_fk'])) {//if video id already placed in column do nothing
                        }else if(strlen($kwaV['video_id_fk'])>0){//if some video ids, other than current video id placed in column 
                            $updImagIds_data['video_id_fk']  =   $kwaV['video_id_fk'].','.$img_ID; // append video id to the video ids already present
                        }else{
                            $updImagIds_data['video_id_fk']  =   $img_ID; // insert current video id
                        }
                        if($updImagIds_data){
                            $chidnum=$kwaV['rec_aid'];
                            update($updImagIds_data,"fi_keywords","rec_aid='$chidnum'");//update keywords table with video ids
                        }
                    }    
                    }
            }
        }
    }
}
/**** Insert Image Info ****/
function insertImageInfo($image_xml_fileName,$domainId){
    $xml_images_host = simplexml_load_file($image_xml_fileName);
    $image_xml_sitename=urldecode($xml_images_host->website);
    foreach(  $xml_images_host->images as $url_image ) {//for every images
        $table_name="fi_images";
        $xml_image_link= urldecode($url_image->image_link);
        $link_From  =   urldecode($url_image['from_link']);
        if($xml_image_link!=''){// if image link not empty
            $fi_docDataArr='';
            $fi_docDataArr    =   select('document_id,is_news',"fi_documents","url='$link_From'");//get document id of from link
            if($fi_docDataArr){//if url already exists in doc table
                $docId  =   $fi_docDataArr[0]['document_id'];
                if($fi_docDataArr[0]['is_news']=='Yes')
                    $feild_nameV="news_id_fk";
                else
                    $feild_nameV="document_id_fk"; 
                $updImagIds_data=array();$fi_keywdsDataArr=array();
                $image_tb_D =select('rec_aid',$table_name,"url='$xml_image_link'");
                if(!empty($image_tb_D)){//if image is already in image table
                    $img_ID =   $image_tb_D[0]['rec_aid']; 
                    goto Imageupdate_keyword;//do updating
                }else{//if not in image table
                    $a_data=array();
                    $a_data=array("url"=>$xml_image_link,
                       "site_name"=>$image_xml_sitename,
                        "document_id_fk"=>$docId,
                        "domain_id_fk"=>$domainId,
                       "alt"=>$url_image->image_text,
                       "title"=>$url_image->image_title);
                    $obj=insert($a_data,$table_name);// insert in image table
                    if($obj==1){ //after inserting
                        $rec_aidAr = select('rec_aid',$table_name,"url='$xml_image_link'");
                        $img_ID =   $rec_aidAr[0]['rec_aid'];
                        goto Imageupdate_keyword;//do updating
                    }
                }   
                Imageupdate_keyword:
                    $fi_keywdsDataArr    =   select('rec_aid,image_id_fk',"fi_keywords","$feild_nameV REGEXP '[[:<:]]".$docId."[[:>:]]'");
                    if($fi_keywdsDataArr){
                        foreach($fi_keywdsDataArr as $kwaV){
                            if (preg_match('/\b'.$img_ID.'\b/', $kwaV['image_id_fk'])) {//if image id already placed in column do nothing
                            }else if(strlen($kwaV['image_id_fk'])>0){//if some image ids other than current image id already placed in column, append current image id
                                $updImagIds_data['image_id_fk']  =   $kwaV['image_id_fk'].','.$img_ID;
                            }else{//if feild is empty insert current image id
                                $updImagIds_data['image_id_fk']  =   $img_ID;
                            }
                            if($updImagIds_data){
                                $rechnum=$kwaV['rec_aid'];
                                update($updImagIds_data,"fi_keywords","rec_aid='$rechnum'");//update keywords table with image ids
                            }
                        }
                    }
            }
        }
    }
}

//to get robot id based on hostname
function getRobotId($spiderID){
    $table_name="fi_robots";
    $where="robot_name='$spiderID'";
    $fields="rec_aid";
    $query_result=select( $fields, $table_name, $where);
    return $query_result[0]['rec_aid']; 
}
 /*** Update robot Log ***/
function updateRobot_Log($robotId,$startTime,$endTime,$totalExecutionTime,$maxlevels) {  
    $a_data=array(
    'robot_id_fk'=>$robotId,
    'execution_starts_on'=>date("Y-m-d H:i:s",$startTime),
    'execution_stopped_on'=>date("Y-m-d H:i:s",$endTime),
    'total_execution_time'=>$totalExecutionTime,
    'max_levels'=>$maxlevels);
    $table_name="fi_robots_log";
    insert($a_data, $table_name);
}
//to get domain id based on url
function domainId($website_name){
    $website_name   =   preg_replace('/^www\./', '',preg_replace( "#^[^:/.]*[:/]+#i", "",$website_name));
    $table_name="fi_unique_domains"; 
    $query_result=select('domain_aid',$table_name,"domain='$website_name'");
    return $query_result[0]['domain_aid'];
}
/*** check url aready ***/
function checkifURL_already_present_in_preceeding_levels( $curLevel, $url, $xml_filePath  ) {
    $flag = 0 ;// IT means URL not processed 
    for( $i = 0; $i <= $curLevel; $i++ ) {
        $level_index = 'level'.$i;
        $b_exist = checkIf_url_exist_in_xml( $level_index, $url, $xml_filePath );
        if($b_exist===1) {
            $flag = 1; break;
        } 
    }
    return $flag;
}
/*** return is_news yes or no ***/
function getIsNews($domId) {
    $table_name="fi_unique_domains"; 
    $query_result=select('is_news',$table_name,"domain_aid=$domId");
    return $query_result[0]['is_news'];
}

<?php
require 'db_crud.php';
$robot_result=select("*","fi_users_query","");
foreach($robot_result as $res){
    $query=$res['query'];
    if($query!=""){
    $query_id=$res['query_id'];
    $a_userInput_chunks_holder =explode(" ",$query);
    $document_id_fk=getGenericResult( $a_userInput_chunks_holder ,"document_id_fk");
    $image_id_fk=getGenericResult( $a_userInput_chunks_holder ,"image_id_fk");
    $video_id_fk=getGenericResult( $a_userInput_chunks_holder ,"video_id_fk");
    $news_id_fk=getGenericResult( $a_userInput_chunks_holder ,"news_id_fk");
    if($image_id_fk=='')
        $image_id_fk=0;
    if($document_id_fk=='')
        $document_id_fk=0;
    if($video_id_fk=='')
        $document_id_fk=0;
    if($news_id_fk=='')
        $news_id_fk=0;
    if($document_id_fk!=$res['document_id_fk']||$image_id_fk!=$res['image_id_fk']||$news_id_fk!=$res['news_id_fk']||$video_id_fk!=$res['video_id_fk']){
        update(array('document_id_fk'=>$document_id_fk,'news_id_fk'=>$news_id_fk,'image_id_fk'=>$image_id_fk,'video_id_fk'=>$video_id_fk),"fi_users_query","query_id=$query_id");
    }
    }
}
function getGenericResult( $a_userInput_chunks_holder ,$column) {
        $s_Chunks = '';    
        foreach( $a_userInput_chunks_holder as $s_eachChunk ) {        
          $s_Chunks = $s_Chunks."'". $s_eachChunk . "'".',';           
        }
        $s_wordsEnclosed_by_quotes = rtrim( $s_Chunks,',' ); // This line is to remove trailing Comma from the sentence
        $query="SELECT distinct($column) FROM fi_keywords WHERE keyword in ( $s_wordsEnclosed_by_quotes )";
        $a_multipleTuples = custom($query);
        if($a_multipleTuples[0][$column]){
            $i=0;
            foreach( $a_multipleTuples as $a_tuples ) {
            $arr=explode(',',$a_tuples["$column"]);
            foreach($arr as $key){
              $res[$i]=$key;
              $i++;
            }}
            $acv=array_count_values($res); //  1=>2, 2=>3,3=>1
            arsort($acv); //save keys,           2=>3, 1=>2, 3=>1 
            foreach ($acv as $item=>$val) {
              if (!isset($merged[$val]))
                  $merged[$val] = array();
              $merged[$val][] =  $item;     }
            $a_documentsDataDump='';
            foreach ($merged as $item){
              $s_uniqueDocumentIds=implode(',',$item);
              if($column=="news_id_fk"||$column=="document_id_fk"){
                  $doc_result=custom("SELECT d.document_id FROM fi_documents as d,fi_unique_domains as u where u.domain_aid=d.domain_id_fk and d.document_id IN ($s_uniqueDocumentIds) order by u.domain_rank desc,d.search_weitage desc");
                  if($doc_result[0]['document_id']){
                    if($a_documentsDataDump!=''){
                        $a_documentsDataDump.= ",".implode(',',array_map(function($el){ return $el['document_id']; },$doc_result));
                    }else{
                        $a_documentsDataDump.= implode(',',array_map(function($el){ return $el['document_id']; },$doc_result));
                    }
                  }
              }
              else if($column=="video_id_fk"){
                  $doc_result=custom("SELECT d.video_aid FROM fi_videos as d,fi_unique_domains as u where u.domain_aid=d.domain_id_fk and d.video_aid IN ($s_uniqueDocumentIds) order by u.domain_rank desc,d.search_weitage desc");
                  if($doc_result[0]['video_aid']){
                    if($a_documentsDataDump!=''){
                        $a_documentsDataDump.= ",".implode(',',array_map(function($el){ return $el['video_aid']; },$doc_result));
                    }else{
                        $a_documentsDataDump.= implode(',',array_map(function($el){ return $el['video_aid']; },$doc_result));
                    }
                  }
              }
              else if($column=="image_id_fk"){
                  $doc_result=custom("SELECT d.rec_aid FROM fi_images as d,fi_unique_domains as u where u.domain_aid=d.domain_id_fk and d.rec_aid IN ($s_uniqueDocumentIds) order by u.domain_rank desc,d.search_weitage desc");
                  if($doc_result[0]['rec_aid']){
                    if($a_documentsDataDump!=''){
                        $a_documentsDataDump.= ",".implode(',',array_map(function($el){ return $el['rec_aid']; },$doc_result));
                    }else{
                        $a_documentsDataDump.= implode(',',array_map(function($el){ return $el['rec_aid']; },$doc_result));
                    }
                  }
              }
            }
            return $a_documentsDataDump;
        }
        else
            return '';
    }  
?>

<?php
define('WEB_ROOT_PATH','/var/www/html'); # /var/www/html
define('ROBOT_ROOT_PATH','/var/www/html/fila/robots/'); # /var/www/html
require_once WEB_ROOT_PATH.'/fila/core/generic_functions.php';
$spiderID              =   getRobotConfigFile_ID(basename( __FILE__ ));
$robot_name            =   "robot"."_".$spiderID;
$robot_path            =   "robots/".$spiderID."/".$robot_name.".php";
$robot_configFileName  =   'robot_config_'.$spiderID.'.json';
$xmlfileName           =   ROBOT_ROOT_PATH.$spiderID.'/'.getXMLFileName( $robot_configFileName, $spiderID );
$website_name          =   getXmlWebHostName($robot_configFileName,$spiderID);
$domainId              =   domainId($website_name);
if(check_LangOfPage($website_name)){//if lang is 'en' ok.

    $robotId            =   getRobotId($spiderID);
    $robot_logFileName  =   ROBOT_ROOT_PATH.$spiderID.'/'.getLogFileName( $robot_configFileName , $spiderID);
    $image_xml_fileName =   ROBOT_ROOT_PATH.$spiderID.'/'.getImageFileName( $robot_configFileName , $spiderID);
    $video_xml_fileName =   ROBOT_ROOT_PATH.$spiderID.'/'.getVideoFileName( $robot_configFileName , $spiderID);
    $obj = new spider( $xmlfileName,$robot_logFileName,$image_xml_fileName,$robot_name,$video_xml_fileName,$website_name);
    $startTime  =   microtime(true); // Start Time
    $maxLevel   =   $obj->run();//run spider
    echo "\n";print_r("Max Level Length $maxLevel");
    //insertRobotsInfo($robot_name,$spiderID,$robot_path,$xmlfilepath,$image_xml_fileName,$video_xml_fileName,$maxLevel);//insert robot info
    $endTime = microtime(true); // End Time
    $totalExecutionTime = $endTime - $startTime ;
    $fp = fopen($robot_logFileName , 'a+');
    fwrite($fp,"\n\n[".date("Y/m/d H:i:s")."]: Spider Id is $spiderID. Max Level Length $maxLevel.The time taken to complete this run is  $totalExecutionTime  seconds"." \n");
    fclose($fp);
    echo "\n\n";
    echo "\033[01;31m -------------------------------------------------------------------------------------  \033[0m"." \n";
    echo "The time taken to complete this run is \033[01;36m $totalExecutionTime \033[0m seconds"." \n";
    echo "\033[01;31m -------------------------------------------------------------------------------------  \033[0m"." \n";
    
    $news   =   getIsNews($domainId);//get is_news status of website
    insertUrl($robotId,$domainId,$news,$xmlfileName);//insert web urls in db
    insertImageInfo($image_xml_fileName,$domainId);//insert image urls in db
    insertVideoInfo($video_xml_fileName,$domainId);//insert video urls in db
    updateRobot_Log($robotId,$startTime,$endTime,$totalExecutionTime,$maxLevel);//update/insert robot log
}
class spider {
    private $robot_logFileName;
    private $xml_file_path;
    private $image_xml_fileName;
    private $robot_name;
    private $video_xml_fileName;
    private $website_name;
    function spider( $xml_fileName,$robot_logFileName,$image_xml_fileName,$robot_name,$video_xml_fileName,$website_name) {
        $this->image_xml_fileName = $image_xml_fileName;
        $this->video_xml_fileName=$video_xml_fileName;
        $this->robot_logFileName=$robot_logFileName; 
        $this->xml_file_path = $xml_fileName;
        $this->robot_name=$robot_name;
        $this->website_name=$website_name;
    } 
    function run() {
       // $fp = fopen($this->robot_logFileName , 'a+');
        set_time_limit(0); // Setting the Infinite Time limit
        ignore_user_abort(true);
        ini_set('max_execution_time', 0);
        error_reporting(0);
        $curLevel = $prevLevel='';
        do {
            $xml = simplexml_load_file($this->xml_file_path) or die("Error: Cannot create object"); // Creating the robot  
            $flag = 0; // if Flag = 1, it means next level is present to traverse
            if ( empty($curLevel) ) {     $curLevel = 0;      } 
            // Defining level context
            $level = 'level'.$curLevel; 
            // Getting Level Count of Current Level
            $levelCount_of_cur_level = sizeof($xml->$level);
            echo "\n "."\033[01;32m Currently Processing for Level {$curLevel} & Level Length is $levelCount_of_cur_level  \033[0m"." \n";
            if ( $levelCount_of_cur_level != 0 ) {  
                for( $i = 0; $i < $levelCount_of_cur_level; $i++ ) {    
                    $obj = $xml->$level;
                    foreach( $obj[$i]->link as $url ) {
                        $url=urldecode($url);
                        echo "For Loop: Processing for {$url}"." \n";     // Traversing inner links is working 
                        $status = checkifURL_already_present_in_preceeding_levels( $curLevel, $url, $this->xml_file_path );
                        if ( !$status ) {
                            if(check_LangOfPage($url)){//if lang is 'en' ok.
                                $flag = 1;
                                $a_typesData = getFiles_src($url); //get files data                                
                                if ( isset($a_typesData['files']) && sizeof( $a_typesData['files'] ) != 0  ) {  
                                    insertFilesInfo($a_typesData['files'],$url,$this->website_name);  //inset files data in fi_files table          
                                }                                
                                $a_images = getImage_Src($url);//get the set of Image src present in document
                                if ( sizeof( $a_images['images'] ) != 0  ) { //if images exists 
                                  addNew_Image_Links($a_images['images'],$url,$this->image_xml_fileName ); //insert image links in xml           
                                } 
                                if ( sizeof( $a_images['unique'] ) != 0  ) {  
                                 insertNewUniqueDomains($a_images['unique'],$this->website_name);
                                }
                                $a_videos =getVideo_Src($url);
                                if ( sizeof( $a_videos['video'] ) != 0  ) {  
                                 addNew_Video_Links( $a_videos['video'],$url,$this->video_xml_fileName );            
                                } 
                                if ( sizeof( $a_videos['unique'] ) != 0  ) {  
                                 insertNewUniqueDomains($a_videos['unique'],$this->website_name);
                                }
                                  $a_links = getAnhors_Tag($url); 
                                if ( sizeof( $a_links['site'] ) != 0  ) {  
                                  addNew_Level_Links($a_links['site'], $curLevel + 1, $url, $this->xml_file_path);            
                                }     
                                if ( sizeof( $a_links['unique'] ) != 0  ) {  
                                    insertNewUniqueDomains($a_links['unique'],$this->website_name);
                                }
                            }//End if lang               
                        } // End of If Loop status
                    } // End of Foreach Loop        
                } // End of Outer For Loop  
            } 
            else {  
                $prevLevel = $curLevel;        
            }  
            if ( $flag === 1) {
                $prevLevel = $curLevel;
                $curLevel = $curLevel + 1;
              } 
            else {
                $prevLevel = $curLevel;
            }
            if( !empty($prevLevel) && ( $curLevel === $prevLevel )  ) {
                echo "\n "."\033[01;32m EXECUTION STOPPED: Since, No More level exist after LEVEL $curLevel to process...BYE  \033[0m"." \n";
                break;     
            }  
            unset($xml);
        } while ( $curLevel > $prevLevel ); // End of Main WHILE Loop
        echo "\n "."\033[02;33m OUT OF WHILE LOOP  \033[0m"." \n";
       // fclose($fp);
        return $curLevel;
    }
} // End of Spider Class
?>


<?php
/** **********************************************************
 * USAGE: This File used to setup new Robot. This file not in direct use. 
 * It can be accessible via Cron Manager Class.
 * Date Written: July 6, 2015 @22:00 HRS
 * @author MD Danish <trade.danish@gmail.com>
 * @param type $xml_webName
 * @param type $host
 * @tutorial $obj_factory = new robotFactoryClass( $i_robot_id=7898, $host='http://stumbleupon.com' );    
 ***************************************************************/
class robotFactoryClass {
    private $i_newRobotID;
    private $s_host;
    function robotFactoryClass( $i_robot_id, $host ) {
        $this->i_newRobotID = $i_robot_id; 
        $this->s_host = $host;
        $this->setUpRobot();
    }
    /*
     * This function is entryPoint to Setup complete robot package -- Danish 
     */
    function setUpRobot( ) {
        $robotID = $this->i_newRobotID;
        $host = $this->s_host;
        $robot_name = 'robot_'.$robotID;
        $root_path = dirname(__DIR__).'/robots/'.$robotID;  
        mkdir( dirname(__DIR__).'/robots/'.$robotID );  
        $this->createXML( $xml_webName= $root_path.'/'.'xml_web_'.$robotID.'.xml', $host );
        $this->createImageXML( $xml_webName= $root_path.'/'.'xml_img_'.$robotID.'.xml', $host );
        $this->createVideoXML( $xml_webName= $root_path.'/'.'xml_vid_'.$robotID.'.xml', $host );
        copy( dirname(__DIR__).'/robots/default/robot.php',  dirname(__DIR__).'/robots/'.$robotID.'/'.$robot_name.'.php' );
        $this->createJSON_File( $robotID, $host, dirname(__DIR__).'/robots/'.$robotID.'/robot_config_'.$robotID.'.json' );
        $fp = fopen( dirname(__DIR__).'/robots/'.$robotID.'/robot_log_'.$robotID.'.log' , 'w');
        fclose($fp);
        shell_exec("sudo chmod -R 777 ".dirname(__DIR__).'/robots/'.$robotID);
        echo "New Robot created";
    }  
    /*
     * This function generate the predefined XML. -- Danish 
     */
    function createXML( $xml_webName, $host ){
        $xml = new DOMDocument('1.0');
        $xml->formatOutput=true;
        $container = $xml->createElement("container");
        $xml->appendChild($container);
        $level = $xml->createElement("level0");
        $level->setAttribute('from_link', 'sample');
        $link = $xml->createElement( 'link', urlencode("http://".$host) );
        $level->appendChild($link);
        $container->appendChild($level);
        $xml->saveXML();
        $xml->save( $xml_webName );    
    }  
    function createImageXML( $xml_webName, $host ){
        $xml = new DOMDocument('1.0');
        $xml->formatOutput=true;
        $container = $xml->createElement("container");
        $xml->appendChild($container);
        $link = $xml->createElement( 'website', urlencode("http://".$host) );
        $container->appendChild($link);
        $xml->saveXML();
        $xml->save( $xml_webName );    
    } 
    function createVideoXML( $xml_webName, $host ){
        $xml = new DOMDocument('1.0');
        $xml->formatOutput=true;
        $container = $xml->createElement("container");
        $xml->appendChild($container);
        $link = $xml->createElement( 'website', urlencode("http://".$host) );
        $container->appendChild($link);
        $xml->saveXML();
        $xml->save( $xml_webName );    
    } 
    /*
     * This function generate Configuration file of robot -- Danish 
    */
    function createJSON_File( $robot_id, $host, $jsonFile_Name_with_full_path ) {
        $date_created = date('Y-m-d H:i:s');
        $response = array(
            'robot_id'=>$robot_id,
            'host'=> "http://".$host ,
            'date_created'=> $date_created ,      
            'files'=>array(
                'web_xml'=>'xml_web_'.$robot_id.'.xml',
                              'log_xml'=>'robot_log_'.$robot_id.'.log',
                'img_xml'=>'xml_img_'.$robot_id.'.xml',
                'vid_xml'=>'xml_vid_'.$robot_id.'.xml',
            )      
         );
        $fp = fopen( $jsonFile_Name_with_full_path , 'w');
        fwrite($fp, json_encode($response));
        fclose($fp);
    }
}

<?php
/** **********************************************************
 * USAGE: This File used to setting up the robot in CRON tab
 * Date Written: July 14, 2015 @22:00 HRS
 * @author MD Danish <trade.danish@gmail.com>
 * @param type $xml_webName
 * @param type $host
 ***************************************************************/
require_once 'db_crud.php';
require_once 'robot_factory.php';
define("CRON_EXIST","THIS CRON IS ALREADY EXIST IN CRON TAB");
define("CRON_INSTALLED","THIS CRON IS SUCCESSFULLY INSTALLED");
new cronManagerClass; // Object creation
class cronManagerClass {
    function __construct() {
      $this->initiateProcess();
    }
    function prepareCommand( $robo_path, $cronLog_path ) {    
      $timeString = $this->getNext_Four_consecutive_hours();
      $timeString = '0 '.$timeString.' * * *';
      $command = "$timeString /usr/bin/php $robo_path >> $cronLog_path";  
      return $command;
    }  
    function initiateProcess() {
        $robot_result=select("domain_aid,domain","fi_unique_domains","flag=0");
        if($robot_result!=0){
            foreach($robot_result as $result){
                $domain_aid=$result['domain_aid'];
                $robot_name=$result['domain_aid']+10000;
                $domain=$result['domain'];
                $this->createRobot($robot_name, $domain);
                $path=dirname(__DIR__);

                insert(array('robot_name'=>$robot_name,'domain_id_fk'=>$domain_aid,'domain'=>$domain,'robot_path'=>"$path/robots/$robot_name/robot_$robot_name.php",'cron_log_path'=>"$path/robots/$robot_name/robot_log_$robot_name.log"),"fi_robots");
                update( array('flag'=>1) , 'fi_unique_domains', "domain_aid=$domain_aid");
            }    
        }
        $db_dump = select( "robot_name, robot_path, cron_log_path, domain" , "fi_robots" ,"is_alloted_to_cron = 0" );   
        if (empty($db_dump)==1) {
            $message = 'Run Successful: No Robot found';
        } else if(!empty($db_dump)){
            for( $i=0; $i < sizeof($db_dump); $i++ ) {      
                $command = $this->prepareCommand( $db_dump[$i]['robot_path'], $db_dump[$i]['cron_log_path'] );
                $this->append_cronjob("$command");      
                $robotName = $db_dump[$i]['robot_name'];
                update( array('is_alloted_to_cron'=>1) , 'fi_robots', "robot_name='$robotName'");  
                $robos[] = $db_dump[$i]['robot_name'];             
            }    
            $message = 'Run Successful: '. sizeof($robos).' Number of Robot scheduled. The following robots are '.implode(",",$robos);      
        }    
        $this->recordLog( $message );    
    }
    function createRobot( $robotID, $domain ) {
        new robotFactoryClass( $robotID, $domain );          
    }
    function recordLog( $message  ) {
        $dateTime = date('d-m-Y H:i:s', time());    
        file_put_contents('cron_manager.log', $dateTime.' == '.$message.PHP_EOL , FILE_APPEND);    
    }
    /***
     * Aim: Function returns 0 if file is already exist in crontab else 1
     * parameter string <command>
     * return Boolead <1/0>
     * Usage: cronjob_exists( '0 5 * * 1 /var/www/html/memcache.php' );
     */
    function cronjob_exists( $command ){    
        $cronjob_exists=0;
        exec('crontab -l', $crontab);
        if(isset($crontab) && is_array($crontab)){
            if(!empty( array_search($command, $crontab ) )) {
                $cronjob_exists=1;
            } 
        }
        return $cronjob_exists;
    }  
    /***
     * Aim: Function returns CRON_EXIST or CRON_INSTALLED depending upon if exist already in crontab or not
     * parameter string <command>
     * return String <Command>
     * Usage: append_cronjob( '* * * * * /usr/bin/php /home/danish/memcache3.php >> /home/danish/cron1.log' );
     */  
    function append_cronjob ( $command ) { 
        if(is_string($command)&&!empty($command)&& $this->cronjob_exists($command)== 0){       
            shell_exec("crontab -l | { cat; echo '".$command."'; } |crontab -");      
        } else {
            return CRON_EXIST;
        }
        return CRON_INSTALLED;
    }  
    function getNext_Four_consecutive_hours() {
        $system_time = date('H', time()+3600 ) ;
        $firstChunk = date('H',  $system_time  + 6*3600 ) ;
        $secondChunk = date( 'H',$firstChunk + 12*3600 );
        $thirdChunk = date( 'H',$secondChunk + 18 * 3600 );
        return $timeString = $system_time.','.$firstChunk.','.$secondChunk.','.$thirdChunk;    
    }  
}

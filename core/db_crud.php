<?php
/*********************************************************************************
 * MODIFIED GENERIC CLASS FOR DATABASE CRUD-C OPERATION
 * ------> This is updated class supports Improved MySQL connection i.e. MySqli
 * Author: MD Danish <trade.danish@gmail.com>
 * Date Written: June 5, 2015
 ********************************************************************************/
require_once('db_config.php');
new Database;
/**************************************************************
  Manual: Function to delete the record from database     
    $mytable = "iws_profiles";
    $where = "profile_id='4'";
    var_dump($this->delete( $mytable ,$where ) ); 
   Usage: $this->delete($mytable,$where); 
  return : 0 on failure or 1 on success
**************************************************************/    
function delete( $mytable, $where ) {
    global $connection;
    $query = "DELETE FROM $mytable WHERE 1";
    if( !empty( $where ) ) {
        $query = "DELETE FROM $mytable WHERE $where";
    }     
    $result = mysqli_query( $connection, $query );
    return $result;	
}    
/**************************************************************
  Manual: function to update the record into database
  $fields = array(	'first_name'=>'MD','last_name'=>'Danish');
  $mytable = "iws_profiles";
  $where = "profile_id='1' AND is_verified=1";
  var_dump($this->update( $fields , $mytable ,$where ) ); 
  Usage: $this->update($fields,$mytable,$where); 
  return : 0 on failure or 1 on success 
**************************************************************/
function update( $fields, $mytable, $where ) {	
    global $connection;
    $i=0;$FIELD='';
    $index = sizeof( $fields );
    foreach($fields as $key => $value ){
        $i++;
        $FIELD .=  $key." = "."'".$value."'"; 
        if ( $index != $i ){
            $FIELD .= ",";
        }	
    }
    $query = "UPDATE $mytable SET $FIELD WHERE $where";
    $result = mysqli_query( $connection, $query );
    if(!empty($result)) {
        return 1;
    } else {
        return 0;
    }
}
/***************************************************************
  Manual: function to retrieve the record from database
    $myfields = "first_name,last_name";
    $mytable = "iws_profiles";
    $where = "profile_id='1' AND is_verified=1";
    var_dump($this->select( $myfields , $mytable ,$where ) );
  Usage: $this->select($myfields,$mytable,$where);    
***************************************************************/
function select( $fields, $mytable, $where ){     
    global $connection;
    $query = "SELECT $fields FROM $mytable WHERE 1";
    if( !empty($where) ) {
        $query = "SELECT $fields FROM $mytable WHERE $where";
    }
    $result = mysqli_query($connection, $query);
    if (mysqli_num_rows($result) > 0) {
        // output data of each row
        while($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        return $data;
    } else {
        return 0;
    }
    mysqli_close($connection);   
}
/***************************************************************
  Manual: function to insert the record from database      
    $tbl_name = "iws_profiles";
    $a_data=array("username"=>"nalini","email"=>"nalini@gmail.com","mobile_no"=>"9642031819","zip_code"=>"500072");
    var_dump($this->insert( $myfields , $mytable ,$where ) );
  Usage: $this->insert($myfields,$mytable,$where); 
  return  : 0 on failure or 1 on success
***************************************************************/  
function insert( $a_data, $tbl_name )  { 
    global $connection;
    $fields = array_keys($a_data); 
    $sql = "INSERT INTO ".$tbl_name." (`".implode('`,`', $fields)."`) VALUES('".implode("','", $a_data)."')";	//echo $sql; die();
    $result=mysqli_query($connection, $sql);
    return $result;
}
function custom($query){
    global $connection;  
    $result=mysqli_query($connection,$query);
    if (mysqli_num_rows($result) > 0) {
        // output data of each row
        while($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        return $data;
    } else {
        return 0;
    }
}
?>

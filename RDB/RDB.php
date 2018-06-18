<?php

/*

RDB: Relational Database v1

The purpose of this class is to provide a simple and uniform interface for running simple or complex queries which are agnostic of database type.

For example, it should be simple and fast to get a list of tables regardless of whether the database is MySQL, MSSQL, Postgres, etc. 

Likewise there should be a simple format which describes the columns, etc.

*/

class RDB{
  
  private $Credentials = false;
  private $Type = false;
  private $Resource = false;
  
  function __construct(&$Legba, $ConfigPath){
    
    if(!(file_exists($ConfigPath))){
      //TODO maybe this should not be a fatal error?
      die('Invalid Database Configuration File.');
    }
    
    $this->Credentials = array(
      'Hostname' => $Legba->Config( $ConfigPath, 'Hostname' ),
      'Username' => $Legba->Config( $ConfigPath, 'Username' ),
      'Password' => $Legba->Config( $ConfigPath, 'Password' ),
      'Database' => $Legba->Config( $ConfigPath, 'Database' )
    );
    
    $this->Type = strtolower($Legba->Config( $ConfigPath, 'Type' ));
    
    switch($this->Type){
      
      case 'mysql':
        $this->Resource = mysqli_connect(
          $this->Credentials['Hostname'],
          $this->Credentials['Username'],
          $this->Credentials['Password'],
          $this->Credentials['Database']
        ) or die(mysqli_error());
        //TODO database charset should probably be editable, but in almost every modern case, this is the correct charset.
        $this->Resource']->set_charset('utf8mb4');
        break;
        
      default:
        die('Invalid Database Type: '.$this->Type);
      
    }
    
  }
  
  //Return the Type of this database
  public function Type(){
    return $this->Type;
  }
  
  //Return a list of all tables in this database
  public function ListTables(){
    switch($this->Type){
      case 'mysql':
        $Tables = $this->Query('show tables');
        return $Tables;
        
      default:
        die('Invalid Database Type: '.$this->Type);
    }
  }
  
  //Return a list of all columns in this database including data type and whether null, primary key, foreign key, index, etc.
  
  //Insert a row
  
  //Edit a row
  
  //Delete a row
  
  //Run a query
  public function Query($SQL){
    switch($this->Type){
      case 'mysql':
        $Result = mysqli_query($this->Resource, $SQL) or die(mysqli_error($this->Resource));
        if(is_bool($Result)){
          return $Result;
        }
        $Output=array();
        while($Row=mysqli_fetch_assoc($Result)){
          $Output[]=$Row;
        }
        return $Output;
        break;
        
      default:
        die('Invalid Database Type: '.$this->Type);
    }
  }
  
  

}

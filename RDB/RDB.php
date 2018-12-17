<?php

/*

RDB: Relational Database v1

The purpose of this class is to provide a simple and uniform interface for running simple or complex queries which are agnostic of database type.

For example, it should be simple and fast to get a list of tables regardless of whether the database is MySQL, MSSQL, Postgres, etc. 

Likewise there should be a simple format which describes the columns, etc.

*/

class RDB{
  
  Private $Legba = false;
  private $Credentials = false;
  private $Type = false;
  private $Resource = false;
  
  function __construct(&$L, $ConfigPath){
    
    $this->Legba = $L;
    
    if(!(file_exists($ConfigPath))){
      //TODO maybe this should not be a fatal error?
      die('Invalid Database Configuration File.');
    }
    
    $this->Credentials = array(
      'Hostname' => $this->Legba->Config( $ConfigPath, 'Hostname' ),
      'Username' => $this->Legba->Config( $ConfigPath, 'Username' ),
      'Password' => $this->Legba->Config( $ConfigPath, 'Password' ),
      'Database' => $this->Legba->Config( $ConfigPath, 'Database' )
    );
    
    $this->Type = strtolower($this->Legba->Config( $ConfigPath, 'Type' ));
    
    switch($this->Type){
      
      case 'mysql':
        $this->Resource = mysqli_connect(
          $this->Credentials['Hostname'],
          $this->Credentials['Username'],
          $this->Credentials['Password'],
          $this->Credentials['Database']
        ) or die(mysqli_error());
        //TODO database charset should probably be editable, but in almost every modern case, this is the correct charset.
        $this->Resource->set_charset('utf8mb4');
        break;
        
      default:
        die('Invalid Database Type: '.$this->Type);
      
    }
    
    //Load the route for description of this schema
    //TODO this should eventually be secured within the user session and reference the user's permissions.
    //TODO also the database should be renamable with some kind of alias instead of using just its name.
    $Event = 'Before Login - SSL';
    $Route = 'schema/'.$this->Credentials['Database'];
    $this->Legba->Hook($Event, $Route, array($this,'DescribeSchema') );
    foreach($this->ListTables() as $Table){
      $Route = 'schema/'.$this->Credentials['Database'].'/'.$Table;
      $this->Legba->Hook($Event, $Route, array($this,'DescribeThisTable') );
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
        $Results = $this->Query('show tables');
        $Tables = array();
        foreach($Results as $Row => $Array){
          foreach($Array as $Key => $Value){
            $Tables[]=$Value;
          }
        }
        return $Tables;
      default:
        die('Invalid Database Type: '.$this->Type);
    }
  }
  
  //Describe the columns in a table
  public function getTableDescription($Table){
    switch($this->Type){
      case 'mysql':
        
        if(!(in_array($Table,$this->ListTables()))){
          die('Describe Invalid Table: '.$Table);
        }
        
        $Results = $this->Query('DESCRIBE '.$Table);
        $Description = '';
        foreach($Results as $Row => $Array){
          foreach($Array as $Key => $Value){
            $Description.=$Value.PHP_EOL;
          }
        }
        $Description = trim($Description);
        return $Description;
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
  
  //Describe this schema
  public function DescribeSchema(){
    $Contents=' <div class="container">
      <div class="row">
        <div class="col-12">
          <h1>Database '.$this->Credentials['Database'].'</h1>
          <h2>Tables:</h2>
          <ul>
            ';
      $Tables = $this->ListTables();
      foreach($Tables as $Key => $Value){
        $Contents.='
              <li><a href="'.$Value.'">'.$Value.'</a></li>
        ';
      }
      $Contents.=' 
          </ul>
        </div>
      </div>
    </div>
    ';
    $this->Legba->SimplePage($Contents);
  }
  
  //Describe a table within this schema
  public function DescribeThisTable(){
    $Table = $this->Legba->Route(2);
    DescribeTable($Table);
  }
  public function DescribeTable($Table){
    if(!(in_array($Table,$this->ListTables()))){
      die('Describe Invalid Table: '.$Table);
    }
    $Contents=' <div class="container">
      <div class="row">
        <div class="col-12">
          <h1>Database '.$this->Credentials['Database'].'</h1>
          <h2>Table '.$Table.'</h2>
          '.$this->Legba->getTableDescription($Table).'
        </div>
      </div>
    </div>
    ';
    $this->Legba->SimplePage($Contents);
  }

}

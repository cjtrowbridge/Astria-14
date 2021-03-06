<?php

/*

RDB: Relational Database v1

The purpose of this class is to provide a simple and uniform interface for running simple or complex queries which are agnostic of database type.

For example, it should be simple and fast to get a list of tables regardless of whether the database is MySQL, MSSQL, Postgres, etc. 

Likewise there should be a simple format which describes the columns, etc.

*/

global $Schemas;
$Schemas = new Schemas();

class Schemas{
  private $Schemas = array();
  
  public function ListSchemas(){
    $Output = array();
    foreach($this->Schemas as $Name => $Resource){
      //TODO eventually the key should be an editable alias
      $Output[$Name]=$Name;
    }
    return $Output;
  }
  public function getSchema($Name){
    if(isset($this->Schemas[$Name])){
      return $this->Schemas[$Name];
    }else{
      return false;
    }
  }
  public function add($Name,$Resource){
    $this->Schemas[$Name]=$Resource;
  }
  
}

class RDB{
  
  Private $Legba = false;
  private $Credentials = false;
  private $Type = false;
  private $Resource = false;
  
  function __construct(&$L, $ConfigPath){
    
    $this->Legba = $L;
    
    if(!(file_exists($ConfigPath))){
      //TODO maybe this should not be a fatal error?
      die('Invalid Database Configuration File: '.$ConfigPath);
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
        ) or die(mysqli_error($this->Resource));
        //TODO database charset should probably be editable, but in almost every modern case, this is the correct charset.
        $this->Resource->set_charset('utf8mb4');
        break;
        
      default:
        die('Invalid Database Type: '.$this->Type);
      
    }
    
    //Load the route for description of this schema
    //TODO this should eventually reference the user's permissions.
    //TODO also the database should be renamable with some kind of alias instead of using just its name.
    $Event = 'Logged In - Show Content';
    $Route = 'schema/'.$this->Credentials['Database'];
    $this->Legba->Hook($Event, $Route, array($this,'DescribeSchema') );
    foreach($this->ListTables() as $Table){
      $Route = 'schema/'.$this->Credentials['Database'].'/'.$Table;
      $this->Legba->Hook($Event, $Route, array($this,'DescribeThisTable') );
    }
    
    //Add to listener
    global $Schemas;
    $Schemas->add($this->Credentials['Database'],$this);
    
  }
  
  //Return the Type of this database
  public function Type(){
    return $this->Type;
  }
  
  //Return a list of all tables in this database
  public function ListTables(){
    $Database = $this->Credentials['Database'];
    
    global $DescribeTableColumn;
    if(isset($DescribeTableColumn[$Database])){
      $Output = array();
      foreach($DescribeTableColumn[$Database] as $Key => $Value){
        $Output[]=$Key;
      }
      return $Output;
    }else{
      switch($this->Type){
        case 'mysql':
          $Results = $this->Query('show tables');
          $Tables = array();
          foreach($Results as $Row => $Array){
            foreach($Array as $Key => $Value){
              $Tables[]=$Value;
              $DescribeTableColumn[$Database][$Value]=array();
            }
          }
          return $Tables;
        default:
          die('Invalid Database Type: '.$this->Type);
      }
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
        $Description = $this->Legba->ArrTabler($Results);
        return $Description;
      default:
        die('Invalid Database Type: '.$this->Type);
    }
  }
  public function getTopRows($Table, $Count = 10){
    if(!(in_array($Table,$this->ListTables()))){
      die('Describe Invalid Table: '.$Table);
    }
    
    $Count = intval($Count);
    if($Count==0){$Count = 10;}
    
    switch($this->Type){
      case 'mysql':
        $SQL = "SELECT * FROM `".$Table."` ORDER BY 1 DESC LIMIT ".$Count;
        $this->Legba->Event($SQL);
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
  
  //Return a list of all columns in this database including data type and whether null, primary key, foreign key, index, etc.
  
  //Insert a row
  
  //Edit a row
  
  //Delete a row
  
  //Run a query
  public function Query($SQL){
    global $QUERIES_RUN;
    if(!(isset($QUERIES_RUN))){
      $QUERIES_RUN=0;
    }
    switch($this->Type){
      case 'mysql':
        if($this->Legba->MayI('Show All Queries as Events')){
          $this->Legba->Event($SQL);
        }
        $Result = mysqli_query($this->Resource, $SQL) or die(mysqli_error($this->Resource));
        $QUERIES_RUN++;
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
    $Database = $this->Credentials['Database'];
    $Contents=' <div class="container">
      <div class="row">
        <div class="col-12">
          <h1>Database '.$Database.'</h1>
          <h2 title="AKA Tables">Objects:</h2>
          <ul>
            ';
      $Tables = $this->ListTables();
      foreach($Tables as $Key => $Value){
        $Contents.='
              <li><a href="/schema/'.$Database.'/'.$Value.'/">'.$Value.'</a></li>
        ';
      }
      $Contents.=' 
          </ul>
        </div>
      </div>
    </div>
    ';
    $this->Legba->SimpleUserPage($Contents, $Database.' Database');
  }
  
  //Describe a table within this schema
  public function DescribeThisTable(){
    $Table = $this->Legba->Route(2);
    $this->DescribeTable($Table);
  }
  public function DescribeTable($Table){
    $this->ValidateTable($Table);
    //TODO add permission check here
    
    $Database = $this->Credentials['Database'];
    
    $Contents=' <div class="container">
      <div class="row">
        <div class="col-12">
          <h1><a href="/schema/'.$Database.'/">Database '.$Database.'</a></h1>
          <h2>Table '.$Table.'</h2>
          '.$this->getTableDescription($Table).'
        </div>
    ';
    
    if(isset($_GET['show'])){
      $Count = $_GET['show'];
    }else{
      $Count = 10;
    }
    
    
    //Default output is the top ten rows of the table.
    $Data  = $this->getTopRows($Table, $Count);
    
    //Rewrite all the cell contents to include links for keys, etc.
    $Data = $this->Legba->ArrTabler($Data, 'table tablesorter tablesorter-ice tablesorter-bootstrap', 'OutputTable', true, array($this, 'TableCellOutputHandler',$Table) );
    
    $Contents.='
        <div class="col-12">
          <h2>Top '.$Count.' Rows</h2>
          <div>
            <a href="javascript:void(0);" class="text-muted" onclick="$(\'#Search\').slideDown(\'fast\');">Search</a> - 
            <a href="/schema/'.$Database.'/'.$Table.'/?show=100" class="text-muted">Show More</a>
          </div
          '.$Data.'
        </div>
        
      </div>
    </div>
    
    <!--div class="card" id="ShowMore">
      <div class="card-body">
        <h5 class="card-title">Show How Many More?</h5>
        <div class="card-text">
          <form action="/schema/'.$Database.'/'.$Table.'/" method="get" class="form">
            <input type="text" name="showMore" class="form-control">
            <input type="submit" class="form-control btn btn-success">
            <a href="javascript:void(0);" class="btn btn-danger" onclick="$(\'#ShowMore\').slideUp(\'fast\');">
          </form>
        </div>
      </div>
    </div-->
    
    ';
    
    $this->Legba->SimpleUserPage($Contents, 'Astria://'.$Database.'/'.$Table.'/');
  }
  public function DescribeTableColumn($Table, $Column){
    $Database = $this->Credentials['Database'];
    //Cache this data so it is not running the query more than once per execution
    global $DescribeTableColumn;
    if(!(is_array($DescribeTableColumn))){
      //Create an array for this database and populate it with a list of tables. 
      //TODO This design may not be ideal for databases with large numbers of tables
      $this->ListTables();
    }
    
    if(
      (!(isset($DescribeTableColumn[$Database][$Table])))||
      count($DescribeTableColumn[$Database][$Table])==0
    ){
      $DescribeTableColumn[$Database][$Table] = array();
      
      $Data = $this->getTableColumnDescriptions($Table);
      foreach($Data as $C){
        $DescribeTableColumn[$Database][$Table][$C['COLUMN_NAME']]=$C;
      }
      
      $Data = $this->getTableColumnKeyDescriptions($Table);
      
      foreach($Data as $Row){
        //If this column is not yet listed in the cached table array, create it.
        if(!(isset($DescribeTableColumn[$Database][$Table][$Row['COLUMN_NAME']]))){
          $DescribeTableColumn[$Database][$Table][$Row['COLUMN_NAME']]=array();
        }
        
        //If this constraint type for this column is not yet listed in the cached table array, create it.
        if(!(isset($DescribeTableColumn[$Database][$Table][$Row['COLUMN_NAME']]))){
          $DescribeTableColumn[$Database][$Table][$Row['COLUMN_NAME']][$Row['CONSTRAINT_TYPE']]=array();
        }
        $DescribeTableColumn[$Database][$Table][$Row['COLUMN_NAME']][$Row['CONSTRAINT_TYPE']][]=$Row;
      }
    }
    
    if(!(isset($DescribeTableColumn[$Database][$Table][$Column]))){
      return false;
    }else{
      return $DescribeTableColumn[$Database][$Table][$Column];
    }
  }
  
  public function ValidateTable($Table){
    if(!(in_array($Table,$this->ListTables()))){
      die('Invalid Table: '.$Table);
    }
  }
  public function ValidateTableColumn($Table,$Column){
    if($this->DescribeTableColumn($Table,$Column)===false){
      die('Invalid Column: '.$Table.'/'.$Column);
    }
  }
  
  public function getTableColumnDescriptions($Table){
    $Database = $this->Credentials['Database'];
    $this->ValidateTable($Table);  
    $SQL = "
      SELECT TABLE_SCHEMA,TABLE_NAME,COLUMN_NAME,DATA_TYPE,IS_NULLABLE,COLUMN_DEFAULT 
      FROM information_schema.COLUMNS 
      WHERE 
        TABLE_SCHEMA = '".$Database."' AND
        TABLE_NAME = '".$Table."'
    ";
    $Data = $this->Query($SQL);
    return $Data;
  }
    
    
  public function getTableColumnKeyDescriptions($Table){
    
    $Database = $this->Credentials['Database'];
    $this->ValidateTable($Table);
    
    $SQL="
      SELECT COLUMN_NAME, CONSTRAINT_TYPE, REFERENCED_COLUMN_NAME, REFERENCED_TABLE_NAME
      FROM information_schema.KEY_COLUMN_USAGE 
      LEFT JOIN information_schema.TABLE_CONSTRAINTS ON
        information_schema.TABLE_CONSTRAINTS.TABLE_SCHEMA    = information_schema.KEY_COLUMN_USAGE.TABLE_SCHEMA AND
        information_schema.TABLE_CONSTRAINTS.TABLE_NAME      = information_schema.KEY_COLUMN_USAGE.TABLE_NAME AND
        information_schema.TABLE_CONSTRAINTS.CONSTRAINT_NAME = information_schema.KEY_COLUMN_USAGE.CONSTRAINT_NAME
      WHERE 
        information_schema.TABLE_CONSTRAINTS.TABLE_SCHEMA = '".$Database."' AND
        information_schema.TABLE_CONSTRAINTS.TABLE_NAME   = '".$Table."'
    ";
    
    $Data = $this->Query($SQL);
    return $Data;
  }
  
  public function TableCellOutputHandler($Key, $Value, $Row, $Table){
    $Database = $this->Credentials['Database'];
    $this->ValidateTable($Table);
    $this->ValidateTableColumn($Table, $Key);
    $Column = $this->DescribeTableColumn($Table, $Key);
    $ModifiedOutput = '';
    $Output= '<span title="Column: '.PHP_EOL.var_export($Column,true).'Row: '.PHP_EOL.var_export($Row,true).'">';
    if(isset($Column['PRIMARY KEY'])){
      //TODO add support for multi-column primary keys.
      $ModifiedOutput = '<a href="/schema/'.$Database.'/'.$Table.'/?'.$Key.'='.$Value.'">'.$Value.'</a>';
    }
    if(isset($Column['FOREIGN KEY'])){
      //TODO This should probably not be indexed.
      $ForeignTable   = $Column['FOREIGN KEY'][0]['REFERENCED_TABLE_NAME'];
      $ForeignColumn  = $Column['FOREIGN KEY'][0]['REFERENCED_COLUMN_NAME'];
      $ModifiedOutput = '<a href="/schema/'.$Database.'/'.$ForeignTable.'/?'.$ForeignColumn.'='.$Value.'">'.$Value.'</a>';
    }
    
    
    if($ModifiedOutput==''){
      $Output.= $Value;
    }else{
      $Output.= $ModifiedOutput;
    }
    $Output.= '</span>';
    return $Output;
  }
}

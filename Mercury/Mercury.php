<?php

/*
  
  Mercury 1.0
  
  General purpose web front-end for JSON APIs.
  
  This tool allows APIs to be fetched, interpreted, and displayed. Endpoints are mapped to routes which allows simple web navigation through the data the API provides.

*/

global $Mercuries;
$Mercuries = new Mercuries();
class Mercuries{
  private $A = array();
  public function ListMercuries(){
    $Output = array();
    foreach($this->A as $Name => $Resource){
      //TODO eventually the key should be an editable alias
      $Output[$Name]=$Name;
    }
    return $Output;
  }
  public function getMercury($Name){
    if(isset($this->A[$Name])){
      return $this->A[$Name];
    }else{
      return false;
    }
  }
  public function add($Name,$Resource){
    $this->A[$Name]=$Resource;
  }
  
}
class Mercury{
  
  Private $Legba = false;
  private $API = false;
  
  function __construct(&$L, $ConfigPath){
    
    $this->Legba = $L;
    
    if(!(file_exists($ConfigPath))){
      //TODO maybe this should not be a fatal error?
      die('Invalid Mercury Configuration File: '.$ConfigPath);
    }
    
    $this->API = array(
      'Name' => $this->Legba->Config( $ConfigPath, 'Name' ),
      'Root' => $this->Legba->Config( $ConfigPath, 'Root' ),
      'Type' => strtolower($this->Legba->Config( $ConfigPath, 'Type' ))
      
    );
    
    if($this->Legba->Config( $ConfigPath, 'Endpoints' )==false){
      $this->Legba->SaveConfig( $ConfigPath, 'Endpoints', array('Root' => $thi->API['Root']) );
    }else{
      $this->API['Endpoints'] = $this->Legba->Config( $ConfigPath, 'Endpoints' );
    }
    
    
    switch($this->API['Type']){
      
      case 'json':
        
        break;
        
      default:
        die('Invalid API Type: '.$this->API['Type']);
      
    }
    
    //Load the route for description of this schema
    //TODO this should eventually reference the user's permissions.
    //TODO also the api should be renamable with some kind of alias instead of using just its name.
    $Event = 'Logged In - Show Content';
    $Route = 'api/'.$this->API['Name'];
    $this->Legba->Hook($Event, $Route, array($this,'DescribeAPI') );
    /*
    foreach($this->List() as $Endpoint){
      $Route = 'api/'.$this->API['Name'].'/'.$Endpoint;
      $this->Legba->Hook($Event, $Route, array($this,'DescribeThisTable') );
    }
    */
    //Add to listener
    global $Mercuries;
    $Mercuries->add($this->API['Name'],$this);
    
  }
  
  //Return the Type of this API
  public function Type(){
    return $this->API['Type'];
  }
  
  public function ListEndpoints(){
    return $this->API['Endpoints'];
  }
  public function DescribeAPI(){
    $API = $this->API['Name'];
    $Contents=' <div class="container">
      <div class="row">
        <div class="col-12">
          <h1>API '.$API.'</h1>
          <h2>Endpoints:</h2>
          <ul>
            ';
      $Endpoints = $this->ListEndpoints();
      foreach($Endpoints as $Key => $Value){
        $Contents.='
              <li><a href="/api/'.$API.'/'.$Value.'/">'.$Value.'</a></li>
        ';
      }
      $Contents.=' 
          </ul>
        </div>
      </div>
    </div>
    ';
    $this->Legba->SimpleUserPage($Contents, $API.' API');
  }
}

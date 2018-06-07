<?php

class Legba{
  
  private $ThreadID = false;
  private $Debug    = false;
  private $Events   = false;
  
  //Native class primitives
  function __construct(){
    //Create a new id for this thread.
    $ThreadID = Legba::sha256(uniqid(true));
    
    //Set up initial debug array.
    $Debug = array(
      0=>array(
        'Description'=> 'Legba Constructor',
        'RAM'        => (memory_get_usage()/1000000),
        'Delta-RAM'  => 0,
        'Time'       => round(microtime(true),4),
        'Delta-Time' => 0
      )
    );
    
    //Create initial empty events list.
    $this->$Events = array(
      
    );
    
  }
  function __destruct(){
    
  }
  function __call($Name, $Arguments){
    //TODO Include graceful error handling
    die('Legba: Unknow function "'.$Name.'" called.');
  }
  
  
  //Static Functions
  public static function pd($Input){
    echo '<pre>';
    var_dumo($Input);
    echo '</pre>';
  }
  public static function sha256($Input){
    return hash('sha256', $Input);
  }
  public static function sha512($Input){
    return hash('sha512', $Input);
  }
  
  
  //Accessor/Mutator Functions
  public function Config($File, $Key, $Value = '9BDUo49XFN54CMaIfQOb4Cd2PB8BmZwZQ3ktFhXj1i9Tz8u7cTiKvRt526Tzo5QQ'){
    //Obfuscated default parameter is designed to allow passing of values like null, false, etc.
    
    if($Value == '9BDUo49XFN54CMaIfQOb4Cd2PB8BmZwZQ3ktFhXj1i9Tz8u7cTiKvRt526Tzo5QQ'){
      //Search for key's current value in specified file. Return it or null.
      
      
    }else{
      //Set the given key to the given value in the given file. Return true or false regarding ability to save.
      
      
    }
    
    return false;
  }
  public function Event($Name, $Callback = false){
    //Hook a callback onto an event name or trigger the callbacks associated with the event name.
    
    if($Callback == false){
      //Trigger the callbacks hooked to a particular event name
      if(isset($this->$Events[$Name])){
        foreach($this->$Events[$Name] as $Callback){
          /* Note that the callback is evaluated, and as such can be any php script, but must be syntactically complete. ie: "foo();" */
          try{
            eval($Callback);
          }catch(Exception $e){
            /* TODO permissions and config to permit this
            Event('Event Exception');
            echo '<p><b>EVENT THREW EXCEPTION</b></p>';
            pd($e);
            */
          }
        }
      }
    }else{
      //Hook a callback onto an event name
      if(is_string($Name)){
        /* If this event doesn't already exist, create it. */
        if(
          (!(isset($this->$Events[$Name])))
        ){
          $this->$Events[$Name]=array();
        }
        /* Add the callback to the array for this event */
        $this->$Events[$Name][]=$Callback;
      }else{
        fail('<h1>Event Description Must Be A String;</h1><pre>'.var_export($EventDescription,true).'</pre>');
      }
    }
    return false;
  }
  public function Load($Directory = false){
    //Load all plugins in the specified directory
    
    return false;
  }
  public function LoggedIn(){
    //Check whether a user is currently logged in and authenticated. Return user or false.
    
    return false;
  }
  public function RequireSSL(){
    //If the current path is not an SSL path, then redirect to an SSL version of the current path.
    if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on'){
      header("Status: 301 Moved Permanently");
      header(sprintf('Location: https://%s%s',$_SERVER['HTTP_HOST'],$_SERVER['REQUEST_URI']));
      exit();
    }
    return true;
  }
  
}
  

<?php

class Legba{
  
  private $ThreadID = false;
  private $Debug    = false;
  private $Events   = false;
  private $User     = false;
  
  //Native class primitives
  function __construct(){
    //Create a new id for this thread.
    $this->ThreadID = Legba::sha256(uniqid(true));
    
    //Set up initial debug array.
    $RAM  = round(memory_get_usage()/1000000,4);
    $Time = round(microtime(true),4);
    $this->Debug = array(
      0=>array(
        'Event Index' => 0,
        'Description' => 'Legba Constructor',
        'RAM'         => $RAM,
        'Delta-RAM'   => 0,
        'Time'        => $Time,
        'Delta-Time'  => 0
      )
    );
    
    //Create initial empty events list.
    $this->Events = array();
    
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
  //TODO make the default table arguments come from a config setting.
  public function ArrTabler($arr, $table_class = 'table tablesorter tablesorter-ice tablesorter-bootstrap', $table_id = null,$Sort = true,$OutputCallback = false){
    $return='';
    if($table_id==null){
      $table_id=md5(uniqid(true));
    }
    if(count($arr)>0){
      $return.="\n<div class=\"table-responsive\">\n";
      $return.= "\r\n".'	<table id="'.$table_id.'" class=" table'.$table_class.'">'."\n";
      $first=true;
      foreach($arr as $row){
        if($first){
          $return.= "		<thead>\n";
          $return.= "			<tr>\n";
          foreach($row as $key => $value){
            $return.= "				<th>".ucwords($key)."</th>\n";
          }
          $return.= "			</tr>\n";
          $return.= "		</thead>\n";
          $return.= "		<tbody>\n";
        }
        $first=false;
        $return.= "			<tr>\n";
        foreach($row as $key => $value){
          if($OutputCallback == false){
            $return.="<td>".$value."</td>";  
          }else{
            //TODO i dont think this will work like this but i dont need it to work at this point
            $return.="<td>".$OutputCallback($key, $value,$row)."</td>";
          }

        }
        $return.= "			</tr>\n";
      }
      $return.= "		</tbody>\n";
      $return.= "	</table>\n";
      $return.= "</div>\n";
      if($Sort){
        $return.= "<script>$('#".$table_id."').tablesorter({widgets: ['zebra', 'filter']});</script>\n";
      }else{
        $return.= "<script>$('#".$table_id."').tablesorter({widgets: ['zebra']});</script>\n";
      }
    }else{
      $return.="No Results Found.";
    }
    return $return;
  }
  public function Config($File, $Key){
    //Assume these config files contain valid associative arrays. Return the specified element in the first dimension of the array.
      
    //Load the file into a variable. 
    if(file_exists($File)){
      include($File);
    }else{
      return false;
    }
    
    //If the specified key exists, then return it, otherwise return false. This means non-present values will return as false.
    if(isset($ConfigFile[$Key])){
      return $ConfigFile[$Key];
    }else{
      return false;
    }
    
  }
  public function SaveConfig($File, $Key, $NewValue){
    //Set the given key to the given value in the given file. Return true or false regarding success of saving.
    
    //Load the file if it exists or create a blank array.
    if(file_exists($File)){
      include($File);
    }else{
      $ConfigFile = array();
    }
    
    //Update the existing array with the new data.
    $ConfigFile[$Key]=$NewValue;
    
    //Save the file.
    $ConfigFile = serialize($ConfigFile);
    $ConfigFile = "<?php $ConfigFile = unserialize('".$ConfigFile."');";
    return file_put_contents($File, $ConfigFile);
    
  }
  public function Event($Name, $Callback = false){
    
    //Fetch previous data for comparison.
    $Previous = $this->Debug[(count($this->Debug)-1)];
    
    //Calculate debug information.
    $RAM  = round(memory_get_usage()/1000000,4);
    $Time = round(microtime(true),4);
    
    //Add debug information to thread log.
    $EventDebug = array(
      'Event Index' => ($Previous['Event Index'] + 1),
      'Description' => $Name,
      'RAM'         => $RAM,
      'Delta-RAM'   => round($RAM  - $Previous['RAM'],4),
      'Time'        => $Time,
      'Delta-Time'  => round($Time - $Previous['Time'],4)
    );
    $this->Debug[]=$EventDebug;
    
    //Output verbose event information if permitted and requested.
    if(
      $this->MayI('Verbose')&&
      isset($_GET['verbose'])
    ){
      echo '<h4 title="'.var_export($EventDebug,true).'">Event: "'.$Name.'"</h4>';
    }
    
    //Hook a callback onto an event name or trigger the callbacks associated with the event name.
    if($Callback == false){
      //Trigger the callbacks hooked to a particular event name
      if(isset($this->Events[$Name])){
        foreach($this->Events[$Name] as $Callback){
          /* Note that the callback is evaluated, and as such can be any php script, but must be syntactically complete. ie: "foo();" */
          try{
            eval($Callback);
          }catch(Exception $e){
            if(
              $this->MayI('Verbose')&&
              isset($_GET['verbose'])
            ){
              Event('Event Exception');
              echo '<h4>EVENT "'.$Name.'" THREW EXCEPTION</h4>';
              pd($e);
            }
          }
        }
      }
    }else{
      //Hook a callback onto an event name
      if(is_string($Name)){
        /* If this event doesn't already exist, create it. */
        if(
          (!(isset($this->Events[$Name])))
        ){
          $this->Events[$Name]=array();
        }
        /* Add the callback to the array for this event */
        $this->Events[$Name][]=$Callback;
      }else{
        fail('<h1>Event Description Must Be A String;</h1><pre>'.var_export($EventDescription,true).'</pre>');
      }
    }
    
    if(strtolower($Name) == 'end'){
      //This is an important failure state so we will potentially dump extra debug information.
      if(
        $this->MayI('Verbose')&&
        isset($_GET['verbose'])
      ){
        $this->ShowDebugSummary();
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
  public function MayI($Name){
    //Check whether the user has a case-insensitive permission. Return true or false.
    $Name = strtolower($Name);
    //TODO
    switch($Name){
      case 'show runtime errors':
      case 'show everyone runtime errors':
      case 'verbose':
        return true;
      default: 
        return false;
    }
  }
  public function ShowDebugSummary(){
    echo '<hr><h4>Debug Summary</h4>';
    echo $this->ArrTabler($this->Debug);
  }
  public function ShowRuntimeErrors(){
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
  }
  private function User(){
    return $this->User;
  }
  
}
  

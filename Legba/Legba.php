<?php

/*

  Lebga 2.0
  
  Legba is an Astria module which manages users, groups, sessions, permissions, configuration, database connections, and events.

*/

class Legba{
  
  private $ThreadID = false;
  private $Debug    = false;
  private $Events   = false;
  private $User     = false;
  private $Route    = false;
  
  //Native class primitives
  function __construct(){
    //Create a new id for this thread.
    $this->ThreadID = Legba::sha256(uniqid(true));
    
    
    //Figure out the route and set a global variable in case htaccess is not available.
    if(!(isset($_GET['route']))){
      //If the route is not set via superglobal, then parse it from the REQUEST_URI
      $_GET['route'] = $_SERVER['REQUEST_URI'];
    }
    //Remove any extra slashes in the request
    $_GET['route'] = trim($_GET['route'], '/');
    //If there is a question mark, truncate the url we are parsing at that point.
    $Temp = explode("?", $_GET['route']);
    $_GET['route'] = $Temp[0];
    //Make sure there is exactly one trailing slash in the route
    if(!(substr($_GET['route'], -1)=='/')){$_GET['route'].='/';}
    $RequestSegments = explode('/', $_GET['route']);
    $Route = array();
    foreach($RequestSegments as $RequestSegment){
      if(!(trim($RequestSegment)=='')){
        $Route[]=$RequestSegment;
      }
    }
    $this->Route = $Route;
    //Clean up these variables
    unset($Route, $RequestSegments, $RequestSegment);
    
    
    //Set up initial debug array.
    $RAM  = round(memory_get_usage()/1000000,4);
    $Time = round(microtime(true),4);
    $this->Debug = array(
      0=>array(
        'Index' => 0,
        'Route'       => $_GET['route'],
        'Event'       => 'Legba Constructor',
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
  public static function BlowfishEncrypt($pure_string, $encryption_key=null){
    $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    $encrypted_string = base64_encode(mcrypt_encrypt(MCRYPT_BLOWFISH, $encryption_key, utf8_encode($pure_string), MCRYPT_MODE_ECB, $iv));
    return $encrypted_string;
  }
  public static function BlowfishDecrypt($encrypted_string, $Key = null){
    $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    $decrypted_string = mcrypt_decrypt(MCRYPT_BLOWFISH, $encryption_key, base64_decode($encrypted_string), MCRYPT_MODE_ECB, $iv);
    $decrypted_string = iconv(mb_detect_encoding($decrypted_string, mb_detect_order(), true), "UTF-8", $decrypted_string);
    return $decrypted_string;
  }  
  public static function pd($Input){
    echo '<pre>';
    var_dump($Input);
    echo '</pre>';
  }
  public static function sha256($Input){
    return hash('sha256', $Input);
  }
  public static function sha512($Input){
    return hash('sha512', $Input);
  }
  
  
  //Default Pages
  public static function DefaultPageLogin(){
    //Show login page from template
    die(file_get_contents('Legba/Pages/Login.html'));
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
  //TODO try using json instead of serialize and see if that is more managable. I have the feeling that serialize might not be the most elegant and robust long-term solution. 
  //TODO add functionality for blowfishing these.
  public function Config($File, $Key, $Default = false){
    //Assume these config files contain valid associative arrays. Return the specified element in the first dimension of the array.
    $this->Event('Loading Config File: "'.$File.'" and Key "'.$Key.'"');
    
    //Load the file into a variable. 
    if(file_exists($File)){
      include($File);
    }else{
      $this->Event('Failed Loading Config File: "'.$File.'" and Key "'.$Key.'" Because file did not exist. Created new config file with default value for this key.');
      $this->SaveConfig($File,$Key,$Default);
      return $Default;
    }
    
    //If the specified key exists, then return it, otherwise return false. This means non-present values will return as false.
    if(isset($ConfigFile[$Key])){
      $this->Event('Succeeded Loading Config File: "'.$File.'" and Key "'.$Key.'".');
      return $ConfigFile[$Key];
    }else{
      $this->Event('Failed Loading Config File: "'.$File.'" and Key "'.$Key.'" Because key not found; saving default value for this key.');
      $this->SaveConfig($File,$Key,$Default);
      return $Default;
    }
    
  }
  public function BackupConfig($File){
    if(!(file_exists($File))){
      return false;
    }
    $this->Event('Backing Up Config File: "'.$File.'"');
    
    $FilePrefix = rtrim($File,'php');
    
    $Index = 0;
    while(file_exists($FilePrefix.'Backup.'.$Index.'.php')){
      $Index++;
    }
    $Dest=$FilePrefix.'Backup.'.$Index.'.php';
    $Ret = copy($File, $Dest);
    if($Ret){
      $this->Event('Succeeded Backing Up Config File: "'.$File.'" to "'.$Dest.'"');
    }else{
      $this->Event('Failed Backing Up Config File: "'.$File.'" to "'.$Dest.'"');
    }
  }
  public function DeleteConfig($File, $Key){
    $this->BackupConfig($File);
    $this->Event('Deleting Key From Config File: "'.$File.'" and Key "'.$Key.'"');
    //Delete the given key from the given file. Return true or false regarding success of saving new version.
    
    //Load the file if it exists or create a blank array.
    if(file_exists($File)){
      include($File);
    }else{
      $ConfigFile = array();
    }
    
    //Delete the key from the array.
    unset($ConfigFile[$Key]);
    
    //Save the file.
    $ConfigFile = serialize($ConfigFile);
    $ConfigFile = '<?php $ConfigFile = unserialize(\''.$ConfigFile.'\');';
    $Ret = file_put_contents($File, $ConfigFile);
    if($Ret){
      $this->Event('Succeeded Deleting Key From Config File: "'.$File.'" and Key "'.$Key.'"');
    }else{
      $this->Event('Failed Deleting Key From Config File: "'.$File.'" and Key "'.$Key.'"');
    }
  }
  public function SaveConfig($File, $Key, $NewValue){
    $this->BackupConfig($File);
    $this->Event('Saving Config File: "'.$File.'" and Key "'.$Key.'"');
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
    $ConfigFile = '<?php $ConfigFile = unserialize(\''.$ConfigFile.'\');';
    $Ret = file_put_contents($File, $ConfigFile);
    if($Ret){
      $this->Event('Succeeded Saving Config File: "'.$File.'" and Key "'.$Key.'"');
    }else{
      $this->Event('Failed Saving Config File: "'.$File.'" and Key "'.$Key.'"');
    }
  }
  public function Event($Name, $Route = 'Any'){
    
    //Fetch previous data for comparison.
    $Previous = $this->Debug[(count($this->Debug)-1)];
    
    //Calculate debug information.
    $RAM  = round(memory_get_usage()/1000000,4);
    $Time = round(microtime(true),4);
    
    //Add debug information to thread log.
    $EventDebug = array(
      'Index'       => ($Previous['Index'] + 1),
      'Route'       => $_GET['route'],
      'Event'       => $Name,
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
      echo '<h4 title="'.str_replace('"',"'",var_export($EventDebug,true)).'">Event: "'.$Name.'"</h4>';
    }
    
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
    
    //Potentially show debug information if we are at the end of the request
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
  
  //Hook a function onto an event
  public function Hook($Event, $Route = 'Any', $Callback = false){
    
    //The Callback should be hooked onto the event only when the specified route matches the current route, or 'Any.'
    if(
      $Route !== $_GET['route'] &&
      $Route !== 'Any'
    ){
      return false;
    }
    
    //Hook a callback onto an event name
    if(is_string($Event)){
      /* If this event doesn't already exist, create it. */
      if(
        (!(isset($this->Events[$Event])))
      ){
        $this->Events[$Event]=array();
      }
      /* Add the callback to the array for this event */
      $this->Events[$Event][]=$Callback;
    }else{
      die('Event must be a string.');
    }
  }
  
  public function Load($Directory = false){
    //Load all plugins in the specified directory
    
    return false;
  }
  public function LoggedIn(){
    //Check whether a user is currently logged in and authenticated. Return user or false.
    
    //Get the list of default administrators from the Legba configuration file
    $this->Event('Fetching list of default administrators...');
    $DefaultAdministrators = $this->Config('Legba/Config.php','Default Administrators');
    if($DefaultAdministrators==false){
      $this->Event('No default administrators are currently configured.');
    }else{
      
    }
    
    
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
    echo '<hr><h4>Route: '.$_GET['route'].'</h4>';
    echo '<hr><h4>Debug Summary</h4>';
    echo $this->ArrTabler($this->Debug);
    echo '<hr><h4>Events</h4>';
    echo $this->pd($this->Events);
    
  }
  public function ShowRuntimeErrors(){
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
  }
  public function DefaultPages(){
    //Hook default pages to events.
    $this->Event('Hooking default pages onto routes as configured...');
    if($this->Config('Legba/Pages/Config.php','Use Legba Login Page',true) == true){
      $this->Hook('Not Logged In - Show Content', 'login/', 'Legba::PageLogin();');
    }
    
    $this->Event('Done hooking default pages onto routes as configured...');
  }
  public function User(){
    return $this->User;
  }
  
}
  

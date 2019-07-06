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
  private $Application = array();
  
  //Native class primitives
  function __construct(){
    
    session_start();
    
    //Create a new id for this thread.
    $this->ThreadID = Legba::sha256(uniqid(true));
    
    //Check whether a verbosity flag is set
    if(
      isset($_GET['verbose'])&&
      (
        $_GET['verbose']=='on' ||
        $_GET['verbose']=='off'
      )
    ){
      $_SESSION['Verbose'] = $_GET['verbose'];
      $this->Event('Session verbosity set to '.$_GET['verbose']);
    }else{
      if(
        isset($_SESSION['Verbose'])&&
        $_SESSION['Verbose']=='on'
      ){
        $_GET['verbose']='on';
      }
    }
    
    //If Astria is being executed on the command line, initialize missing superglobals.
    if(!(isset($_SERVER['REQUEST_URI']))){
      $_SERVER['REQUEST_URI']='';
    }
    if(!(isset($_SERVER['HTTP_HOST']))){
      $_SERVER['HTTP_HOST']='localhost';
    }
    
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
        'ΔRAM'   => 0,
        'Time'        => $Time,
        'ΔTime'  => 0
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
  
  

  
  //Accessor/Mutator Functions
  public function UpdateApplication($Array){
    foreach($Array as $Key=>$Value){
      $this->Application[$Key]=$Value;
    }
  }
  public function Application($Key, $Default = false){
    if(isset($this->Application[$Key])){
      return $this->Application[$Key];
    }else{
      return $Default;
    }
  }
  //TODO make the default table arguments come from a config setting.
  public function Route($Index){
    if(isset($this->Route[$Index])){
      return $this->Route[$Index];
    }else{
      return false;
    }
  }
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
            $return.= "<td>";
            
            if(is_array($OutputCallback)){
              
              $CallbackInstance = $OutputCallback[0];
              $CallbackMethod   = $OutputCallback[1];
              $Table            = $OutputCallback[2];
                
              //Call the callback with all these things included.
              $return.= call_user_func_array(
                //passed in info about the instance and method that will handle the cells' contents
                array(
                  $CallbackInstance, 
                  $CallbackMethod
                ), 
                //data about the cell and its row and columns to pass back to the instance method
                array(
                  $key,
                  $value, 
                  $row,
                  $Table
                )
              );
            }else{
              $return.= "Unknown Handler Passed.";
            }
            
            $return.= "</td>";
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
    
    //TODO do not save if nothing has changed. This wastes time doing slow disk operations.
    
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
    
    $Ret = false;
    try{
      $Ret = file_put_contents($File, $ConfigFile);
    }catch(Exception $e){
      echo 'Caught exception: ',  $e->getMessage(), "\n";
    }
    
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
      'ΔRAM'   => round($RAM  - $Previous['RAM'],4),
      'Time'        => $Time,
      'ΔTime'  => round($Time - $Previous['Time'],4)
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
      foreach($this->Events[$Name] as $Key => $Callback){
        /* Note that the callback is evaluated, and as such can be any php script, but must be syntactically complete. ie: "foo();" */
        try{
          //Possibly call a class reference 
          if(
            (is_array($Callback))
            //&& is_callable($Callback[0])
          ){
            //This will automatically call the class referenced by the first element and the method referenced by the second element.
            call_user_func($Callback);
            //Remove the callback reference so that PHP will not display its contents
            $this->Events[$Name][$Key]=array('Callback');
          }else{
            //Or else just evaluate the callback
            eval($Callback);
          }
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
    
    //Trim route to be consistent with the event method.
    $Route = trim($Route, '/');
    if(!(substr($Route, -1)=='/')){$Route.='/';}
    
    //The Callback should be hooked onto the event only when the specified route matches the current route, or 'Any.'
    if(
      $Route !== $_GET['route'] &&
      $Route !== 'Any/'
    ){
       $this->Event("Hook: Skipped Because Of Route Mismatch '".$Route."'... Curent Route Is: ".$_GET['route']);
      return false;
    }
    //TODO come up with some kind of description.
    $this->Event("Hooked:");
    
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
    if(
      (isset($_POST['inputEmail']))&&
      (isset($_POST['inputPassword']))&&
      (isset($_POST['inputPasswordConfirm']))
    ){
      $this->ProcessSignup();
    }
    if(
      (isset($_POST['inputEmail']))&&
      (isset($_POST['inputPassword']))
    ){
      $this->ProcessLogin();
    }
    
    //Check whether a user is currently logged in and authenticated. Return user or false.
    if(!(isset($_SESSION['User']['Email']))){
      return false;
    }
    
    return true;
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
    
    //If user is an admin, they have all permissions.
    if(
      isset($_SESSION['User'])&&
      $_SESSION['User']['Role']=='Administrator'
    ){
      return true;
    }
    
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
    $First = $this->Debug[0];
    //Calculate debug information.
    $RAM  = round(memory_get_usage()/1000000-$First['RAM'],4);
    $Runtime = round(microtime(true)-$First['Time'],4);
    echo '<hr><h4>Route: '.$_GET['route'].'</h4>';
    $this->pd($this->Route);
    echo '<h4>Runtime: '.$Runtime.' sec</h4>';
    echo '<h4>Memory: '.$RAM.' mb</h4>';
    echo '<h4>Session Verbosity: ';
    if(isset($_SESSION['Verbose'])){
      echo $_SESSION['Verbose'];
    }else{
      echo 'unset';
    }
    echo '</h4>';
    echo '<hr><h4>Debug Summary</h4>';
    echo $this->ArrTabler($this->Debug);
    if($this->MayI('Show Extended Debug Summary')){
      echo '<hr><h4>Events</h4>';
      $this->pd($this->Events);
      echo '<hr><h4>Session</h4>';
      $this->pd($_SESSION);
      echo '<hr>';
      echo '<h4>Schema</h4>';
      global $DescribeTableColumn;
      $this->pd($DescribeTableColumn);
    }
  }
  public function ShowRuntimeErrors(){
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
  }
  public function DefaultPages(){
    //Hook default pages to events.
    $this->Event('Hooking default pages onto routes as configured...');
    
    if($this->Config('Legba/Pages/Config.php','Use Legba Logout',true) == true){
      $this->Hook('Before Login', 'logout/', '$this->Logout();');
    }
    if($this->Config('Legba/Pages/Config.php','Use Legba Login Page',true) == true){
      $this->Hook('Not Logged In - Show Content', 'login/', '$this->DefaultPage_Login();');
    }
    if($this->Config('Legba/Pages/Config.php','Use Legba Signup Page',true) == true){
      $this->Hook('Not Logged In - Show Content', 'signup/', '$this->DefaultPage_Signup();');
    }
    if($this->Config('Legba/Pages/Config.php','Use Legba User Home Page',true) == true){
      $this->Hook('Logged In - Show Content', '/', '$this->DefaultPage_UserHome();');
    }
    if($this->Config('Legba/Pages/Config.php','Use Legba Public Home Page',true) == true){
      $this->Hook('Not Logged In - Show Content', '/', '$this->DefaultPage_PublicHome();');
    }
    if($this->Config('Legba/Pages/Config.php','Use Legba Logged In 404',true) == true){
      $this->Hook('Logged In - 404', 'Any', '$this->DefaultPage_404();');
    }
    if($this->Config('Legba/Pages/Config.php','Use Legba Not Logged In 404',true) == true){
      $this->Hook('Not Logged In - 404', 'Any', '$this->DefaultPage_404();');
    }
    
    $this->Event('Done hooking default pages onto routes as configured...');
  }
  public function User(){
    return $this->User;
  }
 
  
  //OAuth
  private function GetOAuthLoginBlob(){
    return '<!-- No OAuth Providers Are Currently Enabled. -->';
  }
  
  //Default Pages
  public function DefaultPage_404(){
    header("HTTP/1.0 404 Not Found");
    $Title = $this->Application('Default Page Title','Astria 14');
    $File = 'Legba/Pages/404.html';
    $this->Event('Showing Page From Template: '.$File);
    $Template = $this->GetPageFromTemplate($File);
    $Template = str_replace('<!--TITLE-->',    $Title, $Template);
    $Template = str_replace('<!--FOOTER-->',   $this->UserFooterContents().'<!--FOOTER-->', $Template);
    echo $Template;
    $this->Event('end');
    exit;
  }
  public function DefaultPage_PublicHome(){
    //Show public home page from template
    
    $File = 'Legba/Pages/PublicHome.html';
    $this->Event('Showing Page From Template: '.$File);
    $Template = $this->GetPageFromTemplate($File);
    
    //Get the title of the application from Legba
    $Title = $this->Application('Default Page Title','Astria 14');
    $Template = str_replace('<!--TITLE-->',$Title,$Template);
    
    //TODO this should probably be separate(?)
    $ApplicationName = $Title;
    $Template = str_replace('<!--Application Name-->',$ApplicationName,$Template);
    
    //Output the completed tempalate and exit
    echo $Template;
    $this->Event('end');
    exit;
    
  }
  public function DefaultPage_Signup(){
    
    $SignupPagePath = 'Legba/Pages/Signup.html';
    //Get the contents of the signup page from template
    $Page = $this->GetPageFromTemplate($SignupPagePath);
    //Insert OAuth blob
    $OAuthBlob = $this->GetOAuthLoginBlob(); 
    $Page = str_replace('<!-- Insert OAuth Options Here -->', $OAuthBlob, $Page);
    $this->Event('Showing Page From Template: '.$SignupPagePath);
    //Show the modified page including the OAuth blob
    echo $Page;
    $this->Event('end');
    exit;
  }
  public function DefaultPage_Login(){
    
    $LoginPagePath = 'Legba/Pages/Login.html';
    //Get the contents of the login page from template
    $Page = $this->GetPageFromTemplate($LoginPagePath);
    //Insert OAuth blob
    $OAuthBlob = $this->GetOAuthLoginBlob(); 
    $Page = str_replace('<!-- Insert OAuth Options Here -->', $OAuthBlob, $Page);
    $this->Event('Showing Page From Template: '.$LoginPagePath);
    //Show the modified page including the OAuth blob
    echo $Page;
    $this->Event('end');
    exit;
  }
  public function SimplePage($Contents, $Title = false){
    if($Title==false){
      $Title = $this->Application('Default Page Title','Astria 14');
    }
    //Show simple page from template
    $File = 'Legba/Pages/BlankPage.html';
    $this->Event('Showing Page From Template: '.$File);
    $Template = $this->GetPageFromTemplate($File);
    $Template = str_replace('<!--TITLE-->',$Title,$Template);
    $Template = str_replace('<!--CONTENTS-->',$Contents.'<!--CONTENTS-->',$Template);
    echo $Template;
    $this->Event('end');
    exit;
  }
  public function SimpleUserPage($Contents, $Title = false){
    if($Title==false){
      $Title = $this->Application('Default Page Title','Astria 14');
    }
    //TODO this should change with the current context
    $ApplicationName = $this->Application('Default Page Title','Astria 14');
    //Show simple user page from template
    $File = 'Legba/Pages/UserHome.html';
    $this->Event('Showing Page From Template: '.$File);
    $Template = $this->GetPageFromTemplate($File);
    $Template = str_replace('<!--Application Name-->', $ApplicationName,$Template);
    $Template = str_replace('<!--TITLE-->',    $Title, $Template);
    $Template = str_replace('<!--TOP NAV-->',  $this->UserTopNav().'<!--TOP NAV-->',        $Template);
    $Template = str_replace('<!--CONTENTS-->', $Contents.'<!--CONTENTS-->', $Template);
    $Template = str_replace('<!--FOOTER-->',   $this->UserFooterContents().'<!--FOOTER-->', $Template);
    echo $Template;
    $this->Event('end');
    exit;
  }
  public function DefaultPage_UserHome(){
    //Show user home page from template
    
    $File='Legba/Pages/UserHome.html';
    $this->Event('Showing Page From Template: '.$File);
    $Template = $this->GetPageFromTemplate($File);
    
    //Get the title of the application from Legba
    $Title = $this->Application('Default Page Title','Astria 14');
    
    //TODO this should change with the current context
    $ApplicationName = $this->Application('Default Page Title','Astria 14');
    $Template = str_replace('<!--Application Name-->', $ApplicationName,$Template);
    $Template = str_replace('<!--TITLE-->',    $Title, $Template);
    $Template = str_replace('<!--TOP NAV-->',  $this->UserTopNav().'<!--TOP NAV-->',        $Template);
    $Template = str_replace('<!--CONTENTS-->', $this->UserHomeContents().'<!--CONTENTS-->', $Template);
    $Template = str_replace('<!--FOOTER-->',   $this->UserFooterContents().'<!--FOOTER-->', $Template);
    
    echo $Template;
    $this->Event('end');
    exit;
    
  }
  
  public function UserTopNav(){
    $Output='';
    
    
        
    if(
      ($this->Route(0)==false)||
      ($this->Route(0)=='schema')
    ){
      global $Schemas;
      $S = $Schemas->ListSchemas();
      if(count($S)>0){
        $Output.='  <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="schemaDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              Database
            </a>
            <div class="dropdown-menu" aria-labelledby="schemaDropdown">
              ';
        $ShownObjects = 0;
        foreach($S as $Key => $Database){
          if($this->MayI('View Database Object '.$Database)){
            //TODO these database names should be displayed as a configurable alias
        $Output.='    <a class="dropdown-item" href="/schema/'.$Database.'">'.$Key.'</a>
    ';
            $ShownObjects++;
          }
        }
        if($ShownObjects==0){
          $Output.='    <a class="dropdown-item" href="#">You do not have permission to view any databases.</a>
    ';
        }
        $Output.='
            </div>
          </li>

        ';
        $Output.=$this->UserTopNavSchemaObjectsDropdown();
      }
    }
    
    
    
    
    if(
      ($this->Route(0)==false)||
      ($this->Route(0)=='api')
    ){
      global $Mercuries;
      $M = $Mercuries->ListMercuries();
      if(count($M)>0){
        $Output.='
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="apiDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            API
          </a>
          <div class="dropdown-menu" aria-labelledby="apiDropdown">
            ';
            foreach($M as $Key => $Value){
              $Output.='
              <a class="dropdown-item" href="/api/'.$Value.'">'.$Value.'</a>
              ';
            }
            $Output.='
          </div>
        </li>
        ';
      }
    }
    
    
    return $Output;
  }
  public function UserTopNavSchemaObjectsDropdown(){
    $Output='';
    
    //This should only run if we are at the schema/x path
    if(!(
      ($this->Route(0)  == 'schema') &&
      (!($this->Route(1) === false))
    )){
      return '<!--Select a schema to view list of objects-->'.PHP_EOL;
    }
    
    global $Schemas;
    $Database = $this->Route(1);
    $Schema = $Schemas->getSchema($Database);
    $Tables = $Schema->ListTables();
    
    $Output.='  <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="schemaDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          Objects
        </a>
        <div class="dropdown-menu" aria-labelledby="schemaDropdown">
          ';
    $ShownObjects = 0;
    foreach($Tables as $Key => $Value){
      //TODO these Values should have configurable aliases
      if($this->MayI('View Database Object '.$Database.'/'.$Value)){
      $Output.='    <a class="dropdown-item" href="/schema/'.$Database.'/'.$Value.'">'.$Value.'</a>
';
        $ShownObjects++;
      }
    }
    
    if($ShownObjects==0){
      $Output.='    <a class="dropdown-item" href="#">You do not have permission to view any objects in this database.</a>
';
    }
    
    $Output.='
        </div>
      </li>
    ';
    
    return $Output;
  }
  public function UserHomeContents(){
    $Output='';
    
    
    return $Output;
  }
  public function UserFooterContents(){
    $Output='';
    
    $Output.= $this->CurrentRam().' '.$this->CurrentRuntime().' '.$this->QueriesRun();
    
    return $Output;
  }
  function CurrentRuntime($Precision = 3){
    global $BEGIN;
    $Ret = microtime(true)-$BEGIN;
    $Val = round($Ret,$Precision);
    $Ret = $Val.' seconds.';
    
    //TODO make these threshholds configurable
    if($Val > .1){
      $Ret = '<span class="text-danger">'.$Ret.'</span>';
    }else{
      $Ret = '<span class="text-success">'.$Ret.'</span>';
    }
    
    return $Ret;
  }
  function CurrentRam($Precision = 3){
    $Ret = memory_get_usage() / 1000000;
    $Val = round($Ret,$Precision);
    $Ret = $Val.' megabytes.';
    
    return $Ret;
  }
  public function QueriesRun(){
    global $QUERIES_RUN;
    return $QUERIES_RUN.' queries.';
  }
  public function GetPageFromTemplate($File){
    return file_get_contents($File);
  }
  public function ShowPageFromTemplate($File){
    $this->Event('Showing Page From Template: '.$File);
    echo $this->GetPageFromTemplate($File);
    $this->Event('end');
    exit;
  }
  
  public function ProcessSignup(){
    //Verify required fields have been submitted.
    if(!(
      (isset($_POST['inputEmail']))&&
      (isset($_POST['inputPassword']))&&
      (isset($_POST['inputPasswordConfirm']))
    )){
      die('Missing Fields. Unable to Process Signup.');
    }
    
    //Make sure passwords match.
    if(!($_POST['inputPassword']==$_POST['inputPasswordConfirm'])){
      die('Passwords do not match.');
    }
    
    //Check whether any first-time-setup admins have been added
    $DefaultAdministrators = $this->Config('Legba/Config.php','Default Administrators');
    if($DefaultAdministrators==false){
      //No default administrators are currently configured so this user is the first user. Add them to the default administrators list in the Legba Config file.
      //TODO this should not work if a user database has been connected. This is only for initial setup purposes.
      $this->SaveConfig('Legba/Config.php','Default Administrators',array($_POST['inputEmail']=>$_POST['inputPassword']));
      $this->ValidateUser($Email,'Administrator');
      
    }else{
      //TODO do a database lookup for the user and verify their identity
      
    }
    
    
    
    header('Location: /');
    exit;
  }
  public function ProcessLogin(){
    if(!(
      (isset($_POST['inputEmail']))&&
      (isset($_POST['inputPassword']))
    )){
      die('Missing Fields. Unable to Process Login.');
    }else{
      //TODO this should eventually also include a database lookup
      $DefaultAdministrators = $this->Config('Legba/Config.php','Default Administrators');
      if($DefaultAdministrators[$_POST['inputEmail']]==$_POST['inputPassword']){
        $this->ValidateUser($_POST['inputEmail'],'Administrator');
      }else{
        //TODO database lookup
        die('Invalid User');
        //TODO log failed attempts and ban IPs
        header('/login?message='.url_encode('Invalid Login.'));
        exit;
      }
    }
    header('Location: /');
    exit;
  }
  public function ValidateUser($Email,$Role = false){
    //create a session for this user who has been validated through one of the login options.
    $_SESSION['User']=array(
      'Email' => $Email,
      'Role'  => $Role
    );
    
  }
  public function Logout(){
    $this->Event('Log Out');
    session_destroy();
    $_SESSION = [];
    header('Location: /');
    exit;
  }

}
  

<?php


//Legba is a modular part of Astria which manages users, groups, sessions, permissions, configuration, database connections, and events. These are most of the core Astria features.
include('Legba/Legba.php');
$Legba = new Legba();

//Include the relational database management class.
include('RDB/RDB.php');

//Check the main Legba configuration file to see whether SSL is required for all connections...
if($Legba->Config('Legba/Config.php','Allow Non-Secure Connections') != true){
  //This function requires the current connection to use SSL. If it is not, it will be redirected to a secure connection.
  $Legba->RequireSSL();
}

//If so configured, show everyone runtime errors. This is useful for development builds.
if($Legba->MayI('Show Everyone Runtime Errors')){
  $Legba->ShowRuntimeErrors();
}

//Extensions add core shared functionality. Example: file management. Thus extensions are loaded before plugins which add specific features.
$Legba->Load('plugins');

//This event should be used to handle secure pre-login webhooks or endpoints. Examples include obfuscated secure webhooks which should not be transmitted in the clear but do not feature user authentication.
$Legba->Event('Before Login - SSL');

//Check if the user is logged in, and process the login if they are trying to log in.
if($Legba->LoggedIn()){
  //The user is logged in and authenticated.
  
  //If so configured, show this user any runtime errors. This is useful for admins.
  if($Legba->MayI('Show Runtime Errors')){
    $Lebga->ShowRuntimeErrors();
  }
  
  //This event should be used for things that need to happen quickly and sometimes without showing a normal page. For example JSON endpoints or authenticated webhooks.
  $Legba->Event('Logged In');
  
  //This event should be used to prepare content for pages. For example, asking plugins what they want to put into the hamburger menu.
  $Legba->Event('Logged In - Prepare Content');
  
  //This event should be used for showing the proper current page.
  $Legba->Event('Logged In - Show Content');
  
  //This event is used for handling routes with no endpoint. A default page exists which can be overridden 
  $Legba->Event('Logged In - 404');
  
}else{
  //The user is not logged in or authenticated.
  
  //This event should be used for things that need to happen quickly, but only when a user is not logged in. Examples include webhooks or endpoints which are different when a user is not logged in. 
  $Legba->Event('Not Logged In');
  
  //This event should be used to prepare content for pages. For example, asking plugins what they want to put into the hamburger menu.
  $Legba->Event('Not Logged In - Prepare Content');
  
  //This event should be used for showing the proper current page.
  $Legba->Event('Not Logged In - Show Content');
  
  //This event is used for handling routes with no endpoint. A default page exists which can be overridden 
  $Legba->Event('Not Logged In - 404');
  
}

//This event should not happen. Something should catch each route. If we get here, it is important to log how we got here and alert developers to correct the issue.
$Legba->Event('End');

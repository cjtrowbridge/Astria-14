<?php

global $BEGIN;
$BEGIN = microtime(true);

//Mercury manages APIs
include('Mercury/Mercury.php');

//Legba is a modular part of Astria which manages users, groups, sessions, permissions, configuration, database connections, and events. These are most of the core Astria features.
include('Legba/Legba.php');
$Legba = new Legba();

//Include the relational database management class.
include('RDB/RDB.php');

//This is probably a temporary solution. If there is a Config.php file in the root of the application directory, then include it. 
//TODO This should be replaced by a first-time set up wizard at a later stage in the app's development journey.
if(file_exists('Config.php')){
  include('Config.php');
}

//Check the main Legba configuration file to see whether SSL is required for all connections...
if($Legba->Config('Legba/Config.php','Allow Non-Secure Connections') != true){
  //This function requires the current connection to use SSL. If it is not, it will be redirected to a secure connection.
  $Legba->RequireSSL();
}

//If so configured, show everyone runtime errors. This is useful for development builds.
if($Legba->MayI('Show Everyone Runtime Errors')){
  $Legba->ShowRuntimeErrors();
}

//This event should be used to handle secure pre-login webhooks or endpoints. Examples include obfuscated secure webhooks which should not be transmitted in the clear but do not feature user authentication.
$Legba->Event('Before Login - SSL');

//Load any default pages as configured
$Legba->DefaultPages();

$Legba->Event('Before Login');

//Check if the user is logged in, and process the login if they are trying to log in.
if($Legba->LoggedIn()){
  //The user is logged in and authenticated.
  
  //If so configured, show this user any runtime errors. This is useful for admins.
  if($Legba->MayI('Show Runtime Errors')){
    $Legba->ShowRuntimeErrors();
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

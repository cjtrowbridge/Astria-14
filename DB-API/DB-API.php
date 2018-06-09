<?php

/*
  
  DB-API needs a better name. 
  
  The purpose of this astria module is to facilitate procedural interfaces for JSON GET/POST and web GET/POST interfaces for databases.
  
  The idea is that these interfaces are configured using very simple permissions structures which are then translated into json or web pages containing forms, data, or visualisations.
  
  It needs to be relatively simple to use a single process to create different versions of the same route based on the permissions or a particular user or their group, versus creating an open public page with the same kind of data.
  
  Example use cases;
    -A survey page which anyone can fill out, where the same site also has secure admin pages for analytics.
    -A CRM where everything is secure, and every user has different access to the data.
    -A multi-company CRM where multiple organizations have access to different versions of the same data.
    -A purely JSON GET/POST API which works only with a native app.
  
*/

class DBAPI{
  
  function __construct(){
    
  }
  
}

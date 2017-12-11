# Astria-14
A simple and secure web application framework.

Astria understands databases, and generates procedural pages for interacting with data. These pages can be replaced with your own, or left as they are.

Astria manages your users. It authenticates them and gives you access to their data simply. It even manages user permissions for you at scale.

Speaking of scale, Astria can scale to handle very large workloads or serve just one or two users. Today, it is managing multi-million-dollar companies, and home media servers. 


## Goals with this version

-OAuth only for all logins  
-SchemaRouter is default view for all routes  
-Events include routes as an optional parameter  
-Schema definition stored in memory  

Data Goals;  
-All queries stored in memory with past results for 24 hours  
-Previous data is delivered with page  
-Fresh data in views is queried asynchronously from page  

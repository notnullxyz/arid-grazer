FORMAT: 1A

# Arid-Grazer API
This is the documentation, in **API Blueprint** for the Arid-Grazer API.
This API will enable clients to obtain a token, and use this token and a username assigned to them 
(called uniq's) to send and receive messages (called packages) between clients, or uniqs.

**Features**
- Create a client token, with a uniq.
- Verify a client token with a given OTP.
- Get client details.
- Create and send a package with any given payload.
- Retrieve a list of packages due.
- Retrieve a specific package by its internal id or hash.
- Request a new token, and go through the verify loop again.

**Rendering:** You can parse this API Blueprint with any parser you like.

The documentation herewith, is in skeleton mode, and still needs work.

# User
### /user/{uniq} [GET]
Get a uniq's data, by uniq

+ Request

+ Response 200 (application/json)
    + Body

                {
                	"email": "piet@pompies.domain",
                	"uniq": "lime-idell",
                	"created": "1492439404.7598",
                	"active": true
                }

### /user [POST]
Create a user
+ Request (application/json)

    + Body
    
                {
                	"email": "piet@pompies.domain"
                }
    
+ Response 200 OK (application/json)
    + Body

                {
                	"email": "piet@pompies.domain",
                	"uniq": "lime-idell",
                	"created": 1492439404.759788,
                	"active": true,
                	"token": "bafd888f32d886cf6ebd35176c409c1280c35253"
                }

# Token
### /token [POST]
Request a new token. New tokens are inactive and needs verification. This call with result in a new token
and an OTP to be used with verify.
 
 + Request (application/json)
 
     + Body
     
                 {
                 	"uniq": "purple-mail-clerk"
                 }
     
 + Response 202 Accepted
 
### /token/{otp} [GET]
 Verify a token, by supplying its OTP. If the OTP matches the token stored in the system, it will be activated.
 Any prior/existing tokens will be purged.
 
  + Response 204 NO CONTENT
 
# Package
### /package [POST]
Create and dispatch a new package to an existing uniq.

+ Request (application/json)

    + Body
    
            {
                "dest": "lime-idell",
                "label": "for my friend.",
                "content": "jsonstuff 1╜foobars - pew pew payload.... asjasjsadkj9803289r32ujkas",
                "expire" : "28 april 2017 21:00"	
            }
            
+ Response 200 OK (application/json)

    + Body
            
            {
            	"origin": "purple-mail-clerk",
            	"dest": "lime-idell",
            	"label": "for my friends Lime.",
            	"sent": 1492440572.427563,
            	"expire": 43200,
            	"content": "jsonstuff 1╜foobars - pew pew payload.... asjasjsadkj9803289r32ujkas",
            	"id": "892fca295fb1d58f892570688aad9ce6"
            }

### /package/{id} [GET]
Retrieve a package and its payload. The supplied id is the internal package hash.

+ Response 200 OK (application/json)

    + Body
    
            {
                "origin": "purple-mail-clerk",
                "dest": "lime-idell",
                "label": "for my friend.",
                "sent": "1492440572.4276",
                "expire": 43155,
                "content": "jsonstuff 1╜foobars - pew pew payload.... asjasjsadkj9803289r32ujkas"
            }

### Summary
This is a basic layout of available endpoints, to get things rolling. This document needs an overhaul.

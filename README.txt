Hi folks,

sorry that there's no manual. You have to wait a bit longer.

If you decide to try this extension, please report any mistake you find to me (info@bednarik.org). It's still an alpha testing!

This extension is a sort of a bridge to my thesis, which is about CoolURIs (SE friendly URIs). It's a kind of universal rewriting engine and it's placed in cooluri/cooluri directory.


Configuration:

- Copy EXT:cooluri/cooluri/CoolUriConf.xml_default file to typo3conf/CoolUriConf.xml

- use the same .htaccess as with the RealURI
- install extension
- if you have a multi-language site, set language identifier
- add to your template setup:

config.baseURL = http://www.example.com/
config.tx_cooluri_enable = 1
config.redirectOldLinksToNew = 1 # if you want to redirect index.php?id=X to a new URI

All configuration is placed in the CoolURIConf.xml. I hope it can be understood even without the manual.

What it can do and RealURL can't do (or I don't know it can do)?

- check whether an URI has changed every X days (or every time)
- redirect old links (those which have changed) to new ones
- redirect index.php?id=X... to the new (cool) URI


Requirements:

- PHP 5+ with SimpleXML enabled!
- MySQL 4.1+


